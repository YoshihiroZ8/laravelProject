import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const uploadButton = document.getElementById('upload-button');
    const fileNameDisplay = document.getElementById('file-name-display');
    const uploadProgress = document.getElementById('upload-progress');
    const progressBar = document.getElementById('progress-bar');
    const uploadMessage = document.getElementById('upload-message');
    const uploadHistoryBody = document.getElementById('upload-history-body');

    let selectedFile = null;
    let activePolls = new Map(); // To store active polling intervals {uploadId: intervalId}
    let pollingInterval = 5000; // Poll every 5 seconds

    // --- Helper Functions ---
    const formatBytes = (bytes, decimals = 2) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        try {
            const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            return new Date(dateString).toLocaleString(undefined, options);
        } catch (e) {
            return dateString; // Fallback
        }
    }

    const getStatusBadge = (status) => {
        let badgeClass = 'bg-gray-400 dark:bg-gray-600'; // Default/pending
        if (status === 'processing') badgeClass = 'bg-yellow-400 dark:bg-yellow-600';
        else if (status === 'completed') badgeClass = 'bg-green-500 dark:bg-green-600';
        else if (status === 'failed') badgeClass = 'bg-red-500 dark:bg-red-600';
        return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${badgeClass} text-white">${status}</span>`;
    }

    const resetUploadUI = () => {
        selectedFile = null;
        fileInput.value = ''; // Clear the file input
        fileNameDisplay.textContent = '';
        uploadButton.disabled = true;
        uploadProgress.classList.add('hidden');
        progressBar.style.width = '0%';
        uploadMessage.textContent = '';
        uploadMessage.className = 'mt-2 text-sm'; // Reset message style
    }

    // --- File Selection & Drag/Drop ---
    dropZone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', (e) => {
        handleFileSelect(e.target.files);
    });

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-blue-500', 'dark:border-blue-400', 'bg-gray-50', 'dark:bg-gray-700');
    });

    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-blue-500', 'dark:border-blue-400', 'bg-gray-50', 'dark:bg-gray-700');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-blue-500', 'dark:border-blue-400', 'bg-gray-50', 'dark:bg-gray-700');
        handleFileSelect(e.dataTransfer.files);
    });

    const handleFileSelect = (files) => {
        if (files.length > 0) {
            // Basic validation (check if CSV/TXT)
            const file = files[0];
            if (file.type === 'text/csv' || file.type === 'text/plain' || file.name.toLowerCase().endsWith('.csv') || file.name.toLowerCase().endsWith('.txt')) {
                selectedFile = file;
                fileNameDisplay.textContent = `${selectedFile.name} (${formatBytes(selectedFile.size)})`;
                uploadButton.disabled = false;
                uploadMessage.textContent = ''; // Clear previous messages
                uploadMessage.className = 'mt-2 text-sm';
            } else {
                selectedFile = null;
                fileNameDisplay.textContent = '';
                uploadButton.disabled = true;
                uploadMessage.textContent = 'Invalid file type. Please select a CSV or TXT file.';
                uploadMessage.className = 'mt-2 text-sm text-red-600 dark:text-red-400';
            }
        }
    }

    // --- Upload Logic ---
    uploadButton.addEventListener('click', () => {
        if (selectedFile) {
            uploadFile(selectedFile);
        }
    });

    const uploadFile = async (file) => {
        const formData = new FormData();
        formData.append('file', file);

        uploadButton.disabled = true;
        uploadProgress.classList.remove('hidden');
        progressBar.style.width = '0%';
        uploadMessage.textContent = 'Uploading...';
        uploadMessage.className = 'mt-2 text-sm text-blue-600 dark:text-blue-400';

        try {
            const response = await axios.post('/api/uploads', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
                onUploadProgress: (progressEvent) => {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    progressBar.style.width = `${percentCompleted}%`;
                }
            });

            uploadMessage.textContent = response.data.message;
            uploadMessage.className = 'mt-2 text-sm text-green-600 dark:text-green-400';
            resetUploadUI();
            fetchUploadHistory(); // Refresh history after successful upload start

        } catch (error) {
            console.error('Upload error:', error);
            let errorMessage = 'Upload failed. Please try again.';
            if (error.response && error.response.data && error.response.data.message) {
                errorMessage = error.response.data.message;
                // Handle validation errors specifically
                if (error.response.data.errors && error.response.data.errors.file) {
                    errorMessage += ` ${error.response.data.errors.file.join(', ')}`;
                }
            }
            uploadMessage.textContent = errorMessage;
            uploadMessage.className = 'mt-2 text-sm text-red-600 dark:text-red-400';
            uploadButton.disabled = false; // Re-enable button on failure
            uploadProgress.classList.add('hidden');
        }
    }

    // --- History Fetching & Display ---
    const fetchUploadHistory = async () => {
        try {
            const response = await axios.get('/api/uploads');
            displayUploadHistory(response.data.data); // Access data within pagination object
        } catch (error) {
            console.error('Error fetching upload history:', error);
            uploadHistoryBody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-red-500 dark:text-red-400">Failed to load history.</td></tr>';
        }
    }

    const displayUploadHistory = (uploads) => {
        if (!uploads || uploads.length === 0) {
            uploadHistoryBody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No uploads yet.</td></tr>';
            return;
        }

        let tableContent = '';
        uploads.forEach(upload => {
            const progressPercent = (upload.total_rows && upload.processed_rows) 
                                   ? Math.round((upload.processed_rows / upload.total_rows) * 100) 
                                   : (upload.status === 'completed' ? 100 : 0);
            const isProcessing = upload.status === 'pending' || upload.status === 'processing';

            tableContent += `
                <tr id="upload-row-${upload.id}" class="${isProcessing ? 'animate-pulse-fast' : ''}">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">${formatDate(upload.created_at)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${upload.original_filename}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm status-cell">${getStatusBadge(upload.status)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm progress-cell">
                        ${ isProcessing || upload.status === 'completed' ? 
                           `<div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: ${progressPercent}%"></div>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">${upload.processed_rows ?? 0} / ${upload.total_rows ?? '?'} rows (${progressPercent}%)</span>` 
                            : (upload.status === 'failed' ? `<span class="text-red-500 dark:text-red-400">${upload.error_message ? upload.error_message.substring(0, 50)+'...' : 'Failed'}</span>` : '-')
                        }
                    </td>
                </tr>
            `;

            // Start polling for this upload if it's pending/processing and not already being polled
            if (isProcessing && !activePolls.has(upload.id)) {
                startPolling(upload.id);
            }
            // Stop polling if it's completed or failed
            if (!isProcessing && activePolls.has(upload.id)) {
                stopPolling(upload.id);
            }
        });

        uploadHistoryBody.innerHTML = tableContent;

        // If no active polls remain after update, maybe stop a global interval if one exists
        // (Current implementation polls per-upload)
    }

    // --- Real-time Status Update (Polling per Upload) ---
    const startPolling = (uploadId) => {
        console.log(`Starting polling for upload ID: ${uploadId}`);
        const intervalId = setInterval(async () => {
            try {
                const response = await axios.get(`/api/uploads/${uploadId}`);
                const upload = response.data;
                updateTableRow(upload);

                // Stop polling if the status is final
                if (upload.status === 'completed' || upload.status === 'failed') {
                    stopPolling(uploadId);
                }
            } catch (error) {
                console.error(`Error polling status for upload ${uploadId}:`, error);
                // Optionally stop polling on error after a few retries
                // stopPolling(uploadId);
            }
        }, pollingInterval);
        activePolls.set(uploadId, intervalId);
    }

    const stopPolling = (uploadId) => {
        if (activePolls.has(uploadId)) {
            console.log(`Stopping polling for upload ID: ${uploadId}`);
            clearInterval(activePolls.get(uploadId));
            activePolls.delete(uploadId);
            // Remove animation class if it exists
            const row = document.getElementById(`upload-row-${uploadId}`);
            if(row) row.classList.remove('animate-pulse-fast');
        }
    }

    const updateTableRow = (upload) => {
        const row = document.getElementById(`upload-row-${upload.id}`);
        if (!row) return; // Row might not exist if history refreshed

        const statusCell = row.querySelector('.status-cell');
        const progressCell = row.querySelector('.progress-cell');

        const newStatusBadge = getStatusBadge(upload.status);
        if (statusCell.innerHTML !== newStatusBadge) {
             statusCell.innerHTML = newStatusBadge;
        }

        const progressPercent = (upload.total_rows && upload.processed_rows)
                               ? Math.round((upload.processed_rows / upload.total_rows) * 100)
                               : (upload.status === 'completed' ? 100 : 0);
        const isProcessing = upload.status === 'pending' || upload.status === 'processing';

        let newProgressHTML = '';
        if (isProcessing || upload.status === 'completed') {
            newProgressHTML = 
               `<div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2.5">
                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: ${progressPercent}%"></div>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">${upload.processed_rows ?? 0} / ${upload.total_rows ?? '?'} rows (${progressPercent}%)</span>`;
        } else if (upload.status === 'failed') {
             newProgressHTML = `<span class="text-red-500 dark:text-red-400">${upload.error_message ? upload.error_message.substring(0, 50)+'...' : 'Failed'}</span>`;
        } else {
            newProgressHTML = '-';
        }

        if (progressCell.innerHTML !== newProgressHTML) {
            progressCell.innerHTML = newProgressHTML;
        }

        // Add/remove animation based on status
        if (isProcessing) {
            row.classList.add('animate-pulse-fast');
        } else {
            row.classList.remove('animate-pulse-fast');
        }
    }

    // --- Initial Load ---
    fetchUploadHistory();

    // Add custom animation class if needed (optional)
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes pulse-fast {
            50% { opacity: .6; }
        }
        .animate-pulse-fast {
            animation: pulse-fast 1s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    `;
    document.head.appendChild(style);
});
