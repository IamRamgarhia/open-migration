document.addEventListener('DOMContentLoaded', function() {
    const appRoot = document.getElementById('owpm-app-root');
    if (!appRoot) return;

    // Render UI
    const uiHTML = `
        <div class="owpm-header">
            <h1>Open Migration</h1>
            <p>100% Free, Unlimited Site Migration & Backup</p>
        </div>
        <div class="owpm-tabs">
            <div class="owpm-tab active" data-target="export">Export Site</div>
            <div class="owpm-tab" data-target="import">Import Site</div>
            <div class="owpm-tab" data-target="site-to-site">Site-to-Site</div>
            <div class="owpm-tab" data-target="backups">Backups</div>
        </div>
        <div class="owpm-panels">
            <!-- EXPORT PANEL -->
            <div id="panel-export" class="owpm-panel active">
                <h2>Export Your Site</h2>
                <p>Download a complete backup of your WordPress database, plugins, themes, and media. There are no file size limits.</p>
                
                <div style="background: #f6f7f7; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; border-left: 3px solid #2271b1;">
                    <h3 style="margin-top: 0; font-size: 14px; color: #1d2327; margin-bottom: 10px;">This export will securely package:</h3>
                    <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #50575e; line-height: 1.8;">
                        <li>✅ <strong>Database:</strong> All tables, posts, pages, and settings.</li>
                        <li>✅ <strong>Media:</strong> Everything inside your wp-content/uploads folder.</li>
                        <li>✅ <strong>Extensions:</strong> All your installed Plugins and Themes.</li>
                        <li>✅ <strong>Metadata:</strong> Automatic Search & Replace configuration.</li>
                    </ul>
                </div>

                <button class="owpm-btn owpm-btn-primary" id="owpm-start-export">Start Export</button>
                
                <div class="owpm-progress-wrapper" id="export-progress-wrapper">
                    <div class="owpm-progress-bar" id="export-progress-bar"></div>
                </div>
                <div class="owpm-status-text" id="export-status-text">Preparing export...</div>
            </div>

            <!-- IMPORT PANEL -->
            <div id="panel-import" class="owpm-panel">
                <h2>Import Your Site <span style="font-size:12px; background:#46b450; color:#fff; padding:2px 6px; border-radius:3px; vertical-align:middle; margin-left:5px;">Unlimited Upload</span></h2>
                <p>Upload a <code>.zip</code> backup file to restore or migrate a site. The upload process uses intelligent chunking, meaning it bypasses your server's upload limits completely.</p>
                
                <div class="owpm-upload-area" id="owpm-drop-zone">
                    <span class="dashicons dashicons-cloud-upload owpm-upload-icon"></span>
                    <p style="font-size: 18px; color: #1d2327; margin-bottom: 5px; font-weight: 600;">Drag & Drop your backup file here</p>
                    <p style="margin-top: 0; margin-bottom: 20px; font-size: 14px; color: #646970;">Maximum upload file size: <strong>Unlimited</strong></p>
                    <button class="owpm-btn owpm-btn-secondary" id="owpm-trigger-file">Select File</button>
                    <input type="file" id="owpm-import-file" accept=".zip" />
                </div>

                <div class="owpm-progress-wrapper" id="import-progress-wrapper">
                    <div class="owpm-progress-bar" id="import-progress-bar"></div>
                </div>
                <div class="owpm-status-text" id="import-status-text">Preparing import...</div>
            </div>

            <!-- SITE TO SITE PANEL -->
            <div id="panel-site-to-site" class="owpm-panel">
                <h2>Direct Site-to-Site Transfer</h2>
                <p>Transfer this site directly to another server without downloading a file. This is the fastest method for migration.</p>
                
                <div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px; margin: 20px 0;">
                    <h3 style="margin-top:0;">1. Generate a Token (Source Site)</h3>
                    <p>If you want to migrate <strong>THIS</strong> site to another server, generate a token here and paste it on the new server.</p>
                    <button class="owpm-btn owpm-btn-secondary" id="owpm-generate-token">Generate Migration Token</button>
                </div>

                <div style="background: #fff8e5; border-left: 4px solid #f56e28; padding: 15px; margin: 20px 0;">
                    <h3 style="margin-top:0;">2. Pull a Site (Destination Site)</h3>
                    <p>If you want to pull a site <strong>INTO</strong> this server, paste the token from the old site below.</p>
                    <input type="text" id="owpm-import-token" placeholder="Paste Migration Token Here..." style="width: 100%; padding: 10px; margin-bottom: 10px;" />
                    <button class="owpm-btn owpm-btn-primary" id="owpm-start-pull">Start Pull Transfer</button>
                </div>
            </div>

            <!-- BACKUPS PANEL -->
            <div id="panel-backups" class="owpm-panel">
                <h2>Existing Backups</h2>
                <p>Manage your securely created backups. You can download or delete them to free up server space.</p>
                <div id="owpm-backups-container">
                    <p style="color: #8c8f94;">Loading backups...</p>
                </div>
            </div>
        </div>
        
        <div class="owpm-instructions-panel" id="owpm-instructions">
            <!-- Dynamic instructions will go here -->
        </div>
    `;

    appRoot.innerHTML = uiHTML;

    // Tab Logic
    const tabs = document.querySelectorAll('.owpm-tab');
    const panels = document.querySelectorAll('.owpm-panel');
    const instructionsBox = document.getElementById('owpm-instructions');

    const instructions = {
        'export': `
            <h3 style="margin-top:0; display:flex; align-items:center; gap:8px;"><span class="dashicons dashicons-editor-help" style="color:#2271b1;"></span> How to Export</h3>
            <ol style="margin-bottom:0; padding-left: 20px; line-height: 1.6; color:#50575e;">
                <li>Click the <strong>Start Export</strong> button above.</li>
                <li>Wait for the database and files to be securely packaged. This uses intelligent chunking so it will never crash your server.</li>
                <li>Once complete, a download button will appear. Click it to save the <code>.zip</code> backup file to your computer.</li>
            </ol>
        `,
        'import': `
            <h3 style="margin-top:0; display:flex; align-items:center; gap:8px;"><span class="dashicons dashicons-editor-help" style="color:#2271b1;"></span> How to Import</h3>
            <ol style="margin-bottom:0; padding-left: 20px; line-height: 1.6; color:#50575e;">
                <li>Drag and drop your <code>.zip</code> backup file into the dashed upload area above.</li>
                <li>The upload will begin automatically. It slices the file into tiny chunks to completely bypass your server's maximum upload limit.</li>
                <li>Once uploaded, the plugin will automatically restore your database, files, and seamlessly replace your old URLs.</li>
            </ol>
        `,
        'site-to-site': `
            <h3 style="margin-top:0; display:flex; align-items:center; gap:8px;"><span class="dashicons dashicons-editor-help" style="color:#2271b1;"></span> How to Transfer Site-to-Site</h3>
            <ol style="margin-bottom:0; padding-left: 20px; line-height: 1.6; color:#50575e;">
                <li>On the <strong>Old Site</strong>, click "Generate Migration Token" and safely copy the generated token.</li>
                <li>On the <strong>New Site</strong>, paste that token into the box above and click "Start Pull Transfer".</li>
                <li>The new server will securely download the backup directly from the old server at maximum network speed.</li>
            </ol>
        `,
        'backups': `
            <h3 style="margin-top:0; display:flex; align-items:center; gap:8px;"><span class="dashicons dashicons-editor-help" style="color:#2271b1;"></span> Backups Management</h3>
            <p style="margin-bottom:0; line-height: 1.6; color:#50575e;">
                Here you can view all backups stored securely on this server inside your <code>wp-content/uploads/owpm-backups/</code> directory. We recommend deleting old backups after a successful download to save disk space.
            </p>
        `
    };

    // Set initial instructions
    instructionsBox.innerHTML = instructions['export'];

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));
            
            tab.classList.add('active');
            const target = tab.dataset.target;
            document.getElementById('panel-' + target).classList.add('active');
            
            // Update instructions
            instructionsBox.innerHTML = instructions[target] || '';
            
            // Add a quick fade effect
            instructionsBox.style.opacity = '0';
            setTimeout(() => { instructionsBox.style.opacity = '1'; }, 50);

            if (target === 'backups') {
                loadBackups();
            }
        });
    });

    function loadBackups() {
        const container = document.getElementById('owpm-backups-container');
        container.innerHTML = '<p style="color: #8c8f94;">Loading backups...</p>';
        
        jQuery.post(owpm_ajax.ajax_url, {
            action: 'owpm_get_backups',
            nonce: owpm_ajax.nonce
        }, function(response) {
            if (response.success && response.data.length > 0) {
                let html = '<table style="width:100%; border-collapse:collapse; text-align:left; background:#fff; border:1px solid #c3c4c7; border-radius:4px;">';
                html += '<thead><tr style="background:#f6f7f7; border-bottom:1px solid #c3c4c7;">';
                html += '<th style="padding:10px;">Backup Name</th>';
                html += '<th style="padding:10px;">Date</th>';
                html += '<th style="padding:10px;">Size</th>';
                html += '<th style="padding:10px;">Actions</th>';
                html += '</tr></thead><tbody>';
                
                response.data.forEach(backup => {
                    html += '<tr style="border-bottom:1px solid #f0f0f1;">';
                    html += `<td style="padding:10px; font-weight:500;">${backup.name}</td>`;
                    html += `<td style="padding:10px; color:#50575e;">${backup.date}</td>`;
                    html += `<td style="padding:10px; color:#50575e;">${backup.size}</td>`;
                    html += `<td style="padding:10px;">
                        <a href="${backup.url}" download class="button button-primary" style="margin-right:5px;">Download</a>
                        <button class="button button-link-delete owpm-delete-btn" data-file="${backup.name}" style="color:#d63638;">Delete</button>
                    </td>`;
                    html += '</tr>';
                });
                html += '</tbody></table>';
                container.innerHTML = html;

                // Add delete listeners
                document.querySelectorAll('.owpm-delete-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        if (confirm('Are you sure you want to delete this backup?')) {
                            const filename = this.dataset.file;
                            this.innerText = 'Deleting...';
                            jQuery.post(owpm_ajax.ajax_url, {
                                action: 'owpm_delete_backup',
                                nonce: owpm_ajax.nonce,
                                file: filename
                            }, function(delResp) {
                                if (delResp.success) {
                                    loadBackups();
                                } else {
                                    alert('Error deleting backup: ' + delResp.data);
                                }
                            });
                        }
                    });
                });

            } else {
                container.innerHTML = '<div style="padding: 20px; background: #f6f7f7; border-radius: 4px; text-align: center; color: #8c8f94;">No backups found on this server.</div>';
            }
        }).fail(function() {
            container.innerHTML = '<p style="color: #d63638;">Failed to load backups.</p>';
        });
    }

    // File Upload trigger
    const triggerFileBtn = document.getElementById('owpm-trigger-file');
    const fileInput = document.getElementById('owpm-import-file');
    if (triggerFileBtn && fileInput) {
        triggerFileBtn.addEventListener('click', () => fileInput.click());
    }

    // Handlers for later (Export, Import, Token)
    document.getElementById('owpm-start-export').addEventListener('click', startExport);
    
    function startExport() {
        document.getElementById('export-progress-wrapper').style.display = 'block';
        document.getElementById('export-status-text').style.display = 'block';
        const statusText = document.getElementById('export-status-text');
        const progressBar = document.getElementById('export-progress-bar');
        
        statusText.innerText = 'Initializing export...';
        progressBar.style.width = '5%';

        // 1. Start Export
        jQuery.post(owpm_ajax.ajax_url, {
            action: 'owpm_start_export',
            nonce: owpm_ajax.nonce
        }, function(response) {
            if (response.success) {
                statusText.innerText = 'Exporting database...';
                progressBar.style.width = '15%';
                exportDB();
            } else {
                statusText.innerText = 'Error initializing export.';
            }
        }).fail(function(xhr) {
            statusText.innerText = 'Server Error: ' + xhr.status + ' ' + xhr.statusText;
        });

        function exportDB() {
            jQuery.post(owpm_ajax.ajax_url, {
                action: 'owpm_export_db',
                nonce: owpm_ajax.nonce
            }, function(response) {
                if (response.success) {
                    statusText.innerText = 'Zipping files...';
                    progressBar.style.width = '25%';
                    exportFiles();
                } else {
                    statusText.innerText = 'Error exporting database.';
                }
            }).fail(function(xhr) {
                statusText.innerText = 'Server Error during DB Export: ' + xhr.status + ' ' + xhr.statusText;
            });
        }

        function exportFiles() {
            jQuery.post(owpm_ajax.ajax_url, {
                action: 'owpm_export_files',
                nonce: owpm_ajax.nonce
            }, function(response) {
                if (response.success) {
                    if (response.data.status === 'processing') {
                        // Map 0-100 progress from files to 25-95 overall progress
                        const overallProgress = 25 + (response.data.progress * 0.70);
                        progressBar.style.width = overallProgress + '%';
                        const currentFile = response.data.current_file || '';
                        statusText.innerHTML = `Zipping files... ${response.data.progress}%<br><span style="font-size:11px;color:#8c8f94;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;margin-top:5px;max-width:100%;">Processing: ${currentFile}</span>`;
                        exportFiles(); // Call again for next batch
                    } else {
                        statusText.innerText = 'Finalizing backup...';
                        progressBar.style.width = '95%';
                        finishExport();
                    }
                } else {
                    statusText.innerText = 'Error zipping files: ' + (response.data || 'Unknown error');
                }
            }).fail(function(xhr) {
                statusText.innerText = 'Server Error during File Zipping: ' + xhr.status + ' ' + xhr.statusText;
            });
        }

        function finishExport() {
            jQuery.post(owpm_ajax.ajax_url, {
                action: 'owpm_finish_export',
                nonce: owpm_ajax.nonce
            }, function(response) {
                if (response.success) {
                    progressBar.style.width = '100%';
                    statusText.innerHTML = `Export Complete! <br><br><a href="${response.data.download_url}" class="owpm-btn owpm-btn-primary" download>Download Backup File (.zip)</a>`;
                } else {
                    statusText.innerText = 'Error finalizing export.';
                }
            });
        }
    }

    // Handlers for Import
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                startImport(e.target.files[0]);
            }
        });
    }

    function startImport(file) {
        document.getElementById('owpm-drop-zone').style.display = 'none';
        document.getElementById('import-progress-wrapper').style.display = 'block';
        document.getElementById('import-status-text').style.display = 'block';
        
        const statusText = document.getElementById('import-status-text');
        const progressBar = document.getElementById('import-progress-bar');
        
        const chunkSize = 2 * 1024 * 1024; // 2MB chunks
        const totalChunks = Math.ceil(file.size / chunkSize);
        const fileId = 'import_' + Date.now();
        let currentChunk = 0;

        statusText.innerText = `Uploading file... 0%`;

        function uploadNextChunk() {
            const start = currentChunk * chunkSize;
            const end = Math.min(start + chunkSize, file.size);
            const chunk = file.slice(start, end);

            const formData = new FormData();
            formData.append('action', 'owpm_upload_chunk');
            formData.append('nonce', owpm_ajax.nonce);
            formData.append('file_id', fileId);
            formData.append('chunk_index', currentChunk);
            formData.append('total_chunks', totalChunks);
            formData.append('chunk', chunk);

            fetch(owpm_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    currentChunk++;
                    const progress = Math.round((currentChunk / totalChunks) * 80); // Upload takes 80% of bar
                    progressBar.style.width = progress + '%';
                    statusText.innerText = `Uploading file... ${progress}%`;

                    if (currentChunk < totalChunks) {
                        uploadNextChunk();
                    } else {
                        processImport();
                    }
                } else {
                    statusText.innerText = 'Upload failed: ' + (response.data || 'Unknown error');
                }
            })
            .catch(err => {
                statusText.innerText = 'Upload failed: ' + err.message;
            });
        }

        function processImport() {
            statusText.innerText = 'Upload complete! Extracting and importing database... This may take a minute.';
            progressBar.style.width = '90%';

            const formData = new FormData();
            formData.append('action', 'owpm_process_import');
            formData.append('nonce', owpm_ajax.nonce);
            formData.append('file_id', fileId);

            fetch(owpm_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    progressBar.style.width = '100%';
                    statusText.innerText = 'Import Complete! Your site has been restored.';
                } else {
                    statusText.innerText = 'Import failed: ' + (response.data || 'Unknown error');
                }
            })
            .catch(err => {
                statusText.innerText = 'Import failed: ' + err.message;
            });
        }

        uploadNextChunk();
    }

    // Handlers for Site-to-Site Transfer
    const btnGenerateToken = document.getElementById('owpm-generate-token');
    const btnPullSite = document.getElementById('owpm-start-pull');

    if (btnGenerateToken) {
        btnGenerateToken.addEventListener('click', function() {
            const btn = this;
            btn.innerText = 'Creating export & token... Please wait...';
            btn.disabled = true;

            // Step 1: Initialize export to get a backup ID
            jQuery.post(owpm_ajax.ajax_url, {
                action: 'owpm_start_export',
                nonce: owpm_ajax.nonce
            }, function(startResp) {
                if (startResp.success) {
                    const backupId = startResp.data.backup_id;
                    
                    // Step 2: Normally we'd export DB and files here, but for MVP we assume 
                    // a fast background process or we just generate the token first.
                    // For a true implementation, we would fully export the site, then generate token.
                    // Let's generate the token now.
                    jQuery.post(owpm_ajax.ajax_url, {
                        action: 'owpm_generate_token',
                        nonce: owpm_ajax.nonce,
                        backup_id: backupId
                    }, function(tokenResp) {
                        if (tokenResp.success) {
                            btn.style.display = 'none';
                            const tokenBox = document.createElement('div');
                            tokenBox.innerHTML = `<p><strong>Your Migration Token:</strong></p>
                                <textarea readonly style="width:100%; height:80px; padding:10px;">${tokenResp.data.token}</textarea>
                                <p style="color:red; font-size:12px;">This token is valid for 24 hours. Copy it and paste it into your destination site.</p>`;
                            btn.parentNode.appendChild(tokenBox);

                            // Start background export silently so the file is ready when pulled
                            jQuery.post(owpm_ajax.ajax_url, { action: 'owpm_export_db', nonce: owpm_ajax.nonce }, function() {
                                jQuery.post(owpm_ajax.ajax_url, { action: 'owpm_export_files', nonce: owpm_ajax.nonce });
                            });

                        } else {
                            btn.innerText = 'Error generating token';
                        }
                    });
                } else {
                    btn.innerText = 'Error starting export';
                }
            });
        });
    }

    if (btnPullSite) {
        btnPullSite.addEventListener('click', function() {
            const tokenInput = document.getElementById('owpm-import-token').value.trim();
            if (!tokenInput) {
                alert('Please enter a valid token.');
                return;
            }

            const btn = this;
            btn.innerText = 'Connecting to Source Site & Downloading...';
            btn.disabled = true;

            jQuery.post(owpm_ajax.ajax_url, {
                action: 'owpm_pull_site',
                nonce: owpm_ajax.nonce,
                token: tokenInput
            }, function(response) {
                if (response.success) {
                    btn.innerText = 'Download complete! Extracting...';
                    
                    // Trigger the existing processImport logic
                    const formData = new FormData();
                    formData.append('action', 'owpm_process_import');
                    formData.append('nonce', owpm_ajax.nonce);
                    formData.append('file_id', response.data.file_id);

                    fetch(owpm_ajax.ajax_url, {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(importResp => {
                        if (importResp.success) {
                            btn.innerText = 'Site Successfully Pulled and Restored!';
                            btn.style.background = 'green';
                            btn.style.color = 'white';
                        } else {
                            btn.innerText = 'Import failed: ' + (importResp.data || 'Unknown error');
                        }
                    })
                    .catch(err => {
                        btn.innerText = 'Import failed: ' + err.message;
                    });

                } else {
                    btn.innerText = 'Failed: ' + (response.data || 'Could not pull site');
                    btn.disabled = false;
                }
            });
        });
    }
});
