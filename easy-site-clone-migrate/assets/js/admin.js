jQuery(document).ready(function($) {
    'use strict';
    
    // Tab switching
    $('.escm-tab').on('click', function() {
        var tabId = $(this).data('tab');
        
        $('.escm-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.escm-tab-content').hide();
        $('#' + tabId + '-tab').show();
    });
    
    // File drop zone handling
    var $fileDrop = $('#escm-file-drop');
    var $fileInput = $('#escm-import-file');
    var $importBtn = $('#escm-import-btn');
    
    $fileDrop.on('dragover dragenter', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });
    
    $fileDrop.on('dragleave dragend drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });
    
    $fileDrop.on('drop', function(e) {
        var files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            $fileInput[0].files = files;
            updateFileDisplay(files[0].name);
        }
    });
    
    $fileInput.on('change', function() {
        if (this.files && this.files[0]) {
            updateFileDisplay(this.files[0].name);
        }
    });
    
    function updateFileDisplay(filename) {
        $fileDrop.find('p').text('Selected: ' + filename);
        $importBtn.prop('disabled', false);
    }
    
    // Export functionality
    var exportInterval;
    
    $('#escm-export-btn').on('click', function() {
        var $btn = $(this);
        var $result = $('#export-result');
        var $progressContainer = $('#export-progress-container');
        var $progress = $('#export-progress');
        var $progressText = $('#export-progress-text');
        
        $btn.prop('disabled', true).text(escm_ajax.i18n.exporting);
        $result.hide().removeClass('success error');
        $progressContainer.show();
        $progress.css('width', '0%');
        $progressText.text('Starting export...');
        
        // Start progress polling
        exportInterval = setInterval(updateProgress, 1000);
        
        var formData = new FormData();
        formData.append('action', 'escm_export_site');
        formData.append('nonce', escm_ajax.nonce);
        
        $.ajax({
            url: escm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                clearInterval(exportInterval);
                $progress.css('width', '100%');
                
                if (response.success) {
                    $progressText.text('Export completed!');
                    $result.html(
                        '<strong>' + escm_ajax.i18n.success + '</strong><br>' +
                        'Backup file: <strong>' + response.data.filename + '</strong><br>' +
                        'Size: ' + response.data.size + '<br>' +
                        'Files: ' + response.data.manifest.total_files + '<br>' +
                        '<a href="' + response.data.download_url + '" class="button button-primary" style="margin-top:10px;">Download Backup</a>'
                    ).addClass('success').fadeIn();
                    
                    // Auto-download
                    window.location.href = response.data.download_url;
                } else {
                    $progressText.text('Export failed');
                    $result.html('<strong>' + escm_ajax.i18n.error + '</strong> ' + response.data.message)
                        .addClass('error').fadeIn();
                }
                
                $btn.prop('disabled', false).text('Start Export');
                updateLogViewer();
            },
            error: function(xhr, status, error) {
                clearInterval(exportInterval);
                $progressText.text('Export failed');
                $result.html('<strong>' + escm_ajax.i18n.error + '</strong> ' + error)
                    .addClass('error').fadeIn();
                $btn.prop('disabled', false).text('Start Export');
                updateLogViewer();
            }
        });
    });
    
    // Import functionality
    var importInterval;
    
    $('#escm-import-btn').on('click', function() {
        if (!confirm(escm_ajax.i18n.confirm_import)) {
            return;
        }
        
        var $btn = $(this);
        var $result = $('#import-result');
        var $progressContainer = $('#import-progress-container');
        var $progress = $('#import-progress');
        var $progressText = $('#import-progress-text');
        var fileInput = document.getElementById('escm-import-file');
        
        if (!fileInput.files || !fileInput.files[0]) {
            alert('Please select a file first');
            return;
        }
        
        $btn.prop('disabled', true).text(escm_ajax.i18n.importing);
        $result.hide().removeClass('success error');
        $progressContainer.show();
        $progress.css('width', '0%');
        $progressText.text('Starting import...');
        
        // Start progress polling
        importInterval = setInterval(updateProgress, 1000);
        
        var formData = new FormData();
        formData.append('action', 'escm_import_site');
        formData.append('nonce', escm_ajax.nonce);
        formData.append('escm_import_file', fileInput.files[0]);
        
        $.ajax({
            url: escm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 600000, // 10 minutes timeout
            success: function(response) {
                clearInterval(importInterval);
                $progress.css('width', '100%');
                
                if (response.success) {
                    $progressText.text('Import completed!');
                    $result.html(
                        '<strong>' + escm_ajax.i18n.success + '</strong><br>' +
                        response.data.message + '<br>' +
                        'Original site: ' + response.data.manifest.site_url + '<br>' +
                        'WordPress version: ' + response.data.manifest.wordpress_version
                    ).addClass('success').fadeIn();
                    
                    // Reload after 3 seconds
                    setTimeout(function() {
                        window.location.reload();
                    }, 3000);
                } else {
                    $progressText.text('Import failed');
                    $result.html('<strong>' + escm_ajax.i18n.error + '</strong> ' + response.data.message)
                        .addClass('error').fadeIn();
                }
                
                $btn.prop('disabled', false).text('Start Import');
                updateLogViewer();
            },
            error: function(xhr, status, error) {
                clearInterval(importInterval);
                $progressText.text('Import failed');
                
                var errorMsg = error;
                if (status === 'timeout') {
                    errorMsg = 'Operation timed out. This may still be processing. Check the logs.';
                }
                
                $result.html('<strong>' + escm_ajax.i18n.error + '</strong> ' + errorMsg)
                    .addClass('error').fadeIn();
                $btn.prop('disabled', false).text('Start Import');
                updateLogViewer();
            }
        });
    });
    
    // Progress polling
    function updateProgress() {
        $.ajax({
            url: escm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'escm_get_progress',
                nonce: escm_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.logs) {
                    var logs = response.data.logs;
                    var lines = logs.split('\n').filter(function(line) {
                        return line.trim() !== '';
                    });
                    
                    if (lines.length > 0) {
                        var lastLine = lines[lines.length - 1];
                        $('#export-progress-text, #import-progress-text').text(lastLine.replace(/^\[.*?\]\s*/, ''));
                    }
                }
            }
        });
    }
    
    // Update log viewer
    function updateLogViewer() {
        $.ajax({
            url: escm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'escm_get_progress',
                nonce: escm_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.logs) {
                    var logs = response.data.logs;
                    if (logs.trim() !== '') {
                        $('#escm-log-viewer').text(logs);
                    }
                }
            }
        });
    }
    
    // Clear logs
    $('#escm-clear-logs').on('click', function() {
        $('#escm-log-viewer').html('<p>No logs yet. Perform an export or import to see logs here.</p>');
    });
    
    // Auto-refresh logs when viewing logs tab
    $('button[data-tab="logs"]').on('click', function() {
        setTimeout(updateLogViewer, 500);
    });
});
