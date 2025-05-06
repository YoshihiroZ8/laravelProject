<?php

namespace App\Jobs;

use App\Models\Upload;
use App\Models\Product; // Make sure you have a Product model
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;
use Exception;
use Illuminate\Support\Facades\DB; // Import DB facade for transactions
use Illuminate\Support\LazyCollection; // For memory efficiency

class ProcessCsvUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Upload $upload;

    /**
     * Create a new job instance.
     *
     * @param Upload $upload
     */
    public function __construct(Upload $upload)
    {
        $this->upload = $upload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->upload->update(['status' => 'processing']);
        $filePath = Storage::disk('local')->path($this->upload->filepath);

        try {
            // --- Optimized File Handling: Stream the file --- 
            // Use createFromPath to read the file directly without loading all into memory
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0); // Assumes the first row is the header

            // --- Get Total Rows (Requires iterating once, might be slow for huge files) ---
            // Note: Counting rows accurately without loading the whole file requires iteration.
            // If an exact count beforehand isn't critical, you could remove this 
            // or update it progressively within the loop.
            $totalRows = iterator_count($csv->getRecords()); // More memory efficient count
            $this->upload->update(['total_rows' => $totalRows]);

            $processedCount = 0;
            $chunkSize = 100; // Process in chunks

            // --- Reset iterator after counting --- 
            // Re-create the reader or reset its internal pointer if needed after counting
            // For simplicity, we re-create it here. If performance is critical, 
            // explore stream filters or other ways to avoid a second pass.
            $csv = Reader::createFromPath($filePath, 'r'); 
            $csv->setHeaderOffset(0);

            // Use LazyCollection for memory efficiency with large files
            LazyCollection::make($csv->getRecords()) // $csv->getRecords() is already iterable
                ->chunk($chunkSize)
                ->each(function ($chunk) use (&$processedCount) {
                    $upsertData = [];
                    foreach ($chunk as $row) {
                        // Map CSV columns to database columns (ensure keys match CSV header exactly)
                        $upsertData[] = [
                            'unique_key'             => $row['UNIQUE_KEY'], 
                            'product_title'          => $row['PRODUCT_TITLE'] ?? null,
                            'product_description'    => $row['PRODUCT_DESCRIPTION'] ?? null,
                            'style'                  => $row['STYLE#'] ?? null, 
                            'sanmar_mainframe_color' => $row['SANMAR_MAINFRAME_COLOR'] ?? null,
                            'size'                   => $row['SIZE'] ?? null,
                            'color_name'             => $row['COLOR_NAME'] ?? null,
                            'piece_price'            => isset($row['PIECE_PRICE']) ? (float)str_replace(['$', ','], '', $row['PIECE_PRICE']) : null, 
                            'created_at'             => now(),
                            'updated_at'             => now(),
                        ];
                    }

                    if (!empty($upsertData)) {
                        DB::transaction(function () use ($upsertData) {
                            Product::upsert(
                                $upsertData,
                                ['unique_key'], 
                                [ 
                                    'product_title',
                                    'product_description',
                                    'style',
                                    'sanmar_mainframe_color',
                                    'size',
                                    'color_name',
                                    'piece_price',
                                    'updated_at'
                                ]
                            );
                        });
                    }

                    // Update progress more frequently within the loop
                    $processedCount += count($chunk);
                    // Consider updating progress less frequently (e.g., every few chunks) 
                    // if DB updates become a bottleneck
                    $this->upload->update(['processed_rows' => $processedCount]); 
                });

            // Final status update
            $this->upload->update(['status' => 'completed', 'processed_rows' => $processedCount]);

        } catch (\League\Csv\Exception $e) { // More specific CSV exception
            Log::error('CSV Parsing Error for upload ID ' . $this->upload->id . ': ' . $e->getMessage());
            $this->upload->update(['status' => 'failed', 'error_message' => 'CSV Parsing Error: ' . $e->getMessage()]);
            // throw $e; // Re-throw if you want the job to fail
        } catch (\Illuminate\Database\QueryException $e) { // Catch DB errors
             Log::error('Database Error during CSV Processing for upload ID ' . $this->upload->id . ': ' . $e->getMessage());
            $this->upload->update(['status' => 'failed', 'error_message' => 'Database Error: ' . substr($e->getMessage(), 0, 255)]);
            // throw $e;
        } catch (Exception $e) { // Generic fallback
            Log::error('Generic CSV Processing Error for upload ID ' . $this->upload->id . ': ' . $e->getMessage());
            $this->upload->update([
                'status' => 'failed',
                'error_message' => 'Generic Error: ' . substr($e->getMessage(), 0, 255), // Limit error message length
            ]);
            // throw $e;
        }
    }
}