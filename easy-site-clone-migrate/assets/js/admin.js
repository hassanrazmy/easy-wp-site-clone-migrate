/**
 * Admin JavaScript for Easy Site Clone & Migrate
 */
(function($) {
    'use strict';

    var ESCM = {
        uploadedFilepath: null,

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Export buttons
            $('#escm-export-full').on('click', this.exportFull);
            $('#escm-export-db').on('click', this.exportDatabase);
            $('#escm-export-files').on('click', this.exportFiles);

            // Import file upload
            $('#escm-import-upload-form').on('submit', this.uploadFile);

            // Import buttons
            $('#escm-import-full').on('click', this.importFull);
            $('#escm-import-db').on('click', this.importDatabase);
            $('#escm-import-files').on('click', this.importFiles);
        },

        showProgress: function(section, message) {
            $('#' + section + '-progress').show();
            $('#' + section + '-progress .escm-progress-text').text(message);
            $('#' + section + '-progress .escm-progress-bar').css('width', '100%');
            $('#' + section + '-result').hide().removeClass('success error');
        },

        hideProgress: function(section) {
            $('#' + section + '-progress').hide();
        },

        showResult: function(section, type, message) {
            var $result = $('#' + section + '-result');
            $result.show().addClass(type).html(message);
        },

        exportFull: function() {
            if (!confirm(escm_ajax.strings.confirm_export)) {
                return;
            }

            ESCM.showProgress('escm-export', escm_ajax.strings.exporting);

            $.ajax({
                url: escm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'escm_export_site',
                    nonce: escm_ajax.export_nonce
                },
                success: function(response) {
                    ESCM.hideProgress('escm-export');
                    if (response.success) {
                        var message = '<p>' + response.data.message + '</p>';
                        message += '<p><strong>' + escm_ajax.strings.file_size + ': </strong>' + response.data.file_size + '</p>';
                        message += '<p><a href="' + response.data.download_url + '" class="button button-primary" download>' + 
                                   '<span class="dashicons dashicons-download"></span> Download Backup</a></p>';
                        ESCM.showResult('escm-export', 'success', message);
                    } else {
                        ESCM.showResult('escm-export', 'error', '<p>' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    ESCM.hideProgress('escm-export');
                    ESCM.showResult('escm-export', 'error', '<p>' + escm_ajax.strings.error + ': ' + escm_ajax.strings.exporting + '</p>');
                }
            });
        },

        exportDatabase: function() {
            if (!confirm(escm_ajax.strings.confirm_export)) {
                return;
            }

            ESCM.showProgress('escm-export', escm_ajax.strings.exporting);

            // Direct download for database export
            window.location.href = escm_ajax.ajax_url + '?action=escm_export_database&nonce=' + escm_ajax.export_nonce;
            
            setTimeout(function() {
                ESCM.hideProgress('escm-export');
                ESCM.showResult('escm-export', 'success', '<p>' + 'Database export started. Check your downloads folder.' + '</p>');
            }, 2000);
        },

        exportFiles: function() {
            if (!confirm(escm_ajax.strings.confirm_export)) {
                return;
            }

            ESCM.showProgress('escm-export', escm_ajax.strings.exporting);

            $.ajax({
                url: escm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'escm_export_files',
                    nonce: escm_ajax.export_nonce
                },
                success: function(response) {
                    ESCM.hideProgress('escm-export');
                    if (response.success) {
                        var message = '<p>' + response.data.message + '</p>';
                        message += '<p><strong>File Size: </strong>' + response.data.file_size + '</p>';
                        message += '<p><a href="' + response.data.download_url + '" class="button button-primary" download>' + 
                                   '<span class="dashicons dashicons-download"></span> Download Files</a></p>';
                        ESCM.showResult('escm-export', 'success', message);
                    } else {
                        ESCM.showResult('escm-export', 'error', '<p>' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    ESCM.hideProgress('escm-export');
                    ESCM.showResult('escm-export', 'error', '<p>' + escm_ajax.strings.error + ': ' + escm_ajax.strings.exporting + '</p>');
                }
            });
        },

        uploadFile: function(e) {
            e.preventDefault();

            var fileInput = $('#escm-import-file')[0];
            if (!fileInput.files.length) {
                alert('Please select a file to upload.');
                return;
            }

            var formData = new FormData();
            formData.append('action', 'escm_upload_file');
            formData.append('nonce', escm_ajax.import_nonce);
            formData.append('import_file', fileInput.files[0]);

            ESCM.showProgress('escm-import', escm_ajax.strings.uploading);

            $.ajax({
                url: escm_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    ESCM.hideProgress('escm-import');
                    if (response.success) {
                        ESCM.uploadedFilepath = response.data.filepath;
                        $('#escm-uploaded-filename').text(response.data.filename);
                        $('#escm-import-actions').show();
                        ESCM.showResult('escm-import', 'success', '<p>' + response.data.message + '</p>');
                    } else {
                        ESCM.showResult('escm-import', 'error', '<p>' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    ESCM.hideProgress('escm-import');
                    ESCM.showResult('escm-import', 'error', '<p>' + escm_ajax.strings.error + ': ' + escm_ajax.strings.uploading + '</p>');
                }
            });
        },

        importFull: function() {
            if (!confirm(escm_ajax.strings.confirm_import)) {
                return;
            }

            ESCM.showProgress('escm-import', escm_ajax.strings.importing);

            $.ajax({
                url: escm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'escm_import_site',
                    nonce: escm_ajax.import_nonce,
                    filepath: ESCM.uploadedFilepath
                },
                success: function(response) {
                    ESCM.hideProgress('escm-import');
                    if (response.success) {
                        ESCM.showResult('escm-import', 'success', '<p>' + response.data.message + '</p>');
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 3000);
                    } else {
                        ESCM.showResult('escm-import', 'error', '<p>' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    ESCM.hideProgress('escm-import');
                    ESCM.showResult('escm-import', 'error', '<p>' + escm_ajax.strings.error + ': ' + escm_ajax.strings.importing + '</p>');
                }
            });
        },

        importDatabase: function() {
            if (!confirm(escm_ajax.strings.confirm_import)) {
                return;
            }

            ESCM.showProgress('escm-import', escm_ajax.strings.importing);

            $.ajax({
                url: escm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'escm_import_database',
                    nonce: escm_ajax.import_nonce,
                    filepath: ESCM.uploadedFilepath
                },
                success: function(response) {
                    ESCM.hideProgress('escm-import');
                    if (response.success) {
                        ESCM.showResult('escm-import', 'success', '<p>' + response.data.message + '</p>');
                    } else {
                        ESCM.showResult('escm-import', 'error', '<p>' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    ESCM.hideProgress('escm-import');
                    ESCM.showResult('escm-import', 'error', '<p>' + escm_ajax.strings.error + ': ' + escm_ajax.strings.importing + '</p>');
                }
            });
        },

        importFiles: function() {
            if (!confirm(escm_ajax.strings.confirm_import)) {
                return;
            }

            ESCM.showProgress('escm-import', escm_ajax.strings.importing);

            $.ajax({
                url: escm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'escm_import_files',
                    nonce: escm_ajax.import_nonce,
                    filepath: ESCM.uploadedFilepath
                },
                success: function(response) {
                    ESCM.hideProgress('escm-import');
                    if (response.success) {
                        ESCM.showResult('escm-import', 'success', '<p>' + response.data.message + '</p>');
                    } else {
                        ESCM.showResult('escm-import', 'error', '<p>' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    ESCM.hideProgress('escm-import');
                    ESCM.showResult('escm-import', 'error', '<p>' + escm_ajax.strings.error + ': ' + escm_ajax.strings.importing + '</p>');
                }
            });
        }
    };

    $(document).ready(function() {
        ESCM.init();
    });

})(jQuery);
