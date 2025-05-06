<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>File Uploader</title> 

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
        <div id="app" class="container mx-auto p-8">

            <h1 class="text-2xl font-bold mb-6">File Upload</h1>

            <!-- Upload Section -->
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-8">
                <div 
                    id="drop-zone" 
                    class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center cursor-pointer hover:border-blue-500 dark:hover:border-blue-400 transition-colors"
                >
                    <p class="text-gray-500 dark:text-gray-400 mb-2">Select file / Drag and drop</p>
                    <input type="file" id="file-input" class="hidden" />
                    <!-- REMOVE BUTTON FROM HERE -->
                </div>
                <!-- ADD BUTTON AND FILENAME DISPLAY HERE -->
                <button 
                    id="upload-button" 
                    class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors disabled:opacity-50"
                    disabled
                >
                    Upload File
                </button>
                <p id="file-name-display" class="mt-2 text-sm text-gray-600 dark:text-gray-300"></p>

                <div id="upload-progress" class="mt-4 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 hidden">
                    <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
                 <p id="upload-message" class="mt-2 text-sm"></p> 
            </div>

            <!-- Upload History Table -->
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <h2 class="text-xl font-semibold p-4 border-b border-gray-200 dark:border-gray-700">Upload History</h2>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">File Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Progress</th>
                        </tr>
                    </thead>
                    <tbody id="upload-history-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- Rows will be added dynamically here -->
                        <tr>
                            <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">Loading history...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div> 
    </body>
</html>