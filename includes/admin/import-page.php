<?php
/**
 * Admin Import Page
 * Interface for importing locations via CSV
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check user permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Get current step
$step = isset($_GET['step']) ? sanitize_text_field($_GET['step']) : 'upload';

?>
<div class="wrap frd-import-wrap">
    <h1>Import Food Resource Locations</h1>
    
    <?php
    // Display messages
    if (isset($_GET['message'])) {
        $message = sanitize_text_field($_GET['message']);
        if ($message === 'success') {
            echo '<div class="notice notice-success"><p>Import completed successfully!</p></div>';
        } elseif ($message === 'error') {
            echo '<div class="notice notice-error"><p>An error occurred during import.</p></div>';
        }
    }
    ?>
    
    <div class="frd-import-container">
        
        <?php if ($step === 'upload'): ?>
            
            <!-- Step 1: Upload File -->
            <div class="frd-import-step">
                <h2>Step 1: Upload CSV File</h2>
                
                <div class="frd-import-instructions">
                    <h3>Instructions</h3>
                    <ol>
                        <li>Download the CSV template below</li>
                        <li>Fill in your location data following the format</li>
                        <li>Upload the completed CSV file</li>
                    </ol>
                    
                    <h4>Required Fields:</h4>
                    <ul>
                        <li><strong>County</strong> - One of: Macomb County, Oakland County, Wayne County</li>
                        <li><strong>Organization</strong> - Location name</li>
                        <li><strong>Type</strong> - Service type (Food Pantry, Soup Kitchen, or Other)</li>
                        <li><strong>Street Address</strong> - Street address</li>
                        <li><strong>City</strong> - City name</li>
                        <li><strong>State</strong> - 2-letter state code (e.g., MI)</li>
                        <li><strong>ZIP Code</strong> - 5-digit ZIP code</li>
                    </ul>
                    
                    <h4>Format Guidelines:</h4>
                    <ul>
                        <li><strong>Phone:</strong> 10 digits, no special characters (e.g., 3135550100)</li>
                        <li><strong>Type:</strong> Comma-separated service types (e.g., "Food Pantry, Soup Kitchen")</li>
                        <li><strong>Other Hours:</strong> Leave blank for regular hours, or select: Appointment only, Hours unknown, Call to confirm</li>
                        <li><strong>Open Days:</strong> TRUE or FALSE for "Open X?" fields</li>
                        <li><strong>Times:</strong> Any format (e.g., 9:00 AM, 9am, 09:00)</li>
                    </ul>
                </div>
                
                <div class="frd-import-actions">
                    <a href="<?php echo admin_url('admin-post.php?action=frd_download_template'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-download"></span> Download CSV Template
                    </a>
                </div>
                
                <hr>
                
                <form method="post" enctype="multipart/form-data" id="frd-upload-form">
                    <?php wp_nonce_field('frd_import_upload', 'frd_import_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="import_file">CSV File</label>
                            </th>
                            <td>
                                <input type="file" name="import_file" id="import_file" accept=".csv" required>
                                <p class="description">Maximum file size: 5MB</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary" id="frd-upload-btn">
                            <span class="dashicons dashicons-upload"></span> Upload & Preview
                        </button>
                        <span class="spinner"></span>
                    </p>
                </form>
                
                <div id="frd-upload-result"></div>
                
            </div>
            
        <?php elseif ($step === 'preview'): ?>
            
            <!-- Step 2: Preview & Confirm -->
            <div class="frd-import-step">
                <h2>Step 2: Preview Import</h2>
                
                <div id="frd-preview-container">
                    <p>Loading preview...</p>
                </div>
                
                <p class="submit">
                    <button type="button" class="button button-primary" id="frd-confirm-import" disabled>
                        <span class="dashicons dashicons-yes"></span> Import Valid Rows
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=food-resource-directory-import'); ?>" class="button">
                        Cancel
                    </a>
                    <span class="spinner"></span>
                </p>
            </div>
            
        <?php elseif ($step === 'results'): ?>
            
            <!-- Step 3: Results -->
            <div class="frd-import-step">
                <h2>Import Results</h2>
                
                <div id="frd-results-container">
                    <p>Processing import...</p>
                </div>
                
                <p class="submit">
                    <a href="<?php echo admin_url('admin.php?page=food-resource-directory-import'); ?>" class="button button-primary">
                        Import More Locations
                    </a>
                    <a href="<?php echo admin_url('edit.php?post_type=food-resource'); ?>" class="button">
                        View All Locations
                    </a>
                </p>
            </div>
            
        <?php endif; ?>
        
    </div>
</div>

<style>
.frd-import-wrap {
    max-width: 1200px;
}

.frd-import-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
    margin-top: 20px;
}

.frd-import-step h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.frd-import-instructions {
    background: #f0f6fc;
    border: 1px solid #c3dfff;
    border-radius: 4px;
    padding: 15px 20px;
    margin: 20px 0;
}

.frd-import-instructions h3 {
    margin-top: 0;
    color: #0073aa;
}

.frd-import-instructions h4 {
    margin-bottom: 8px;
    margin-top: 15px;
}

.frd-import-instructions ul,
.frd-import-instructions ol {
    margin: 8px 0;
}

.frd-import-instructions li {
    margin: 5px 0;
}

.frd-import-actions {
    margin: 20px 0;
}

.frd-import-actions .button .dashicons {
    margin-top: 3px;
}

#frd-upload-form .spinner {
    float: none;
    margin: 0 10px;
}

#frd-upload-result {
    margin-top: 20px;
}

.frd-preview-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.frd-preview-table th,
.frd-preview-table td {
    padding: 8px;
    border: 1px solid #ddd;
    text-align: left;
}

.frd-preview-table th {
    background: #f0f0f1;
    font-weight: 600;
}

.frd-preview-table tr.valid {
    background: #f0f9ff;
}

.frd-preview-table tr.invalid {
    background: #fff5f5;
}

.frd-status-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.frd-status-badge.valid {
    background: #d1e7dd;
    color: #0f5132;
}

.frd-status-badge.invalid {
    background: #f8d7da;
    color: #842029;
}

.frd-error-list {
    margin: 5px 0;
    padding-left: 20px;
    font-size: 12px;
    color: #d63638;
}

.frd-warning-list {
    margin: 5px 0;
    padding-left: 20px;
    font-size: 12px;
    color: #b47d00;
}

.frd-import-summary {
    background: #f0f0f1;
    border-left: 4px solid #2271b1;
    padding: 15px;
    margin: 20px 0;
}

.frd-import-summary h3 {
    margin-top: 0;
}

.frd-summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 15px 0;
}

.frd-stat-box {
    background: #fff;
    padding: 15px;
    border-radius: 4px;
    text-align: center;
}

.frd-stat-number {
    font-size: 32px;
    font-weight: 700;
    line-height: 1;
}

.frd-stat-number.success {
    color: #2271b1;
}

.frd-stat-number.failed {
    color: #d63638;
}

.frd-stat-number.skipped {
    color: #dba617;
}

.frd-stat-label {
    font-size: 14px;
    color: #646970;
    margin-top: 5px;
}

.frd-progress-bar {
    background: #f0f0f1;
    border-radius: 4px;
    height: 30px;
    margin: 20px 0;
    overflow: hidden;
    position: relative;
}

.frd-progress-fill {
    background: #2271b1;
    height: 100%;
    transition: width 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 600;
}

.button .dashicons {
    margin-top: 3px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle file upload and preview
    $('#frd-upload-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'frd_upload_preview');
        
        $('#frd-upload-btn').prop('disabled', true);
        $('.spinner').css('visibility', 'visible');
        $('#frd-upload-result').html('');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Store data in session and redirect to preview
                    window.location.href = '<?php echo admin_url('admin.php?page=food-resource-directory-import&step=preview'); ?>';
                } else {
                    $('#frd-upload-result').html(
                        '<div class="notice notice-error"><p>' + response.data.message + '</p></div>'
                    );
                }
            },
            error: function() {
                $('#frd-upload-result').html(
                    '<div class="notice notice-error"><p>An error occurred while uploading the file.</p></div>'
                );
            },
            complete: function() {
                $('#frd-upload-btn').prop('disabled', false);
                $('.spinner').css('visibility', 'hidden');
            }
        });
    });
    
    // Load preview if on preview step
    <?php if ($step === 'preview'): ?>
    loadPreview();
    <?php endif; ?>
    
    // Load results if on results step
    <?php if ($step === 'results'): ?>
    loadResults();
    <?php endif; ?>
    
    // Handle import confirmation
    $(document).on('click', '#frd-confirm-import', function() {
        if (!confirm('Are you sure you want to import these locations? This action cannot be undone.')) {
            return;
        }
        
        $(this).prop('disabled', true);
        $('.spinner').css('visibility', 'visible');
        
        $.post(ajaxurl, {
            action: 'frd_confirm_import',
            nonce: '<?php echo wp_create_nonce('frd_import_confirm'); ?>'
        }, function(response) {
            if (response.success) {
                window.location.href = '<?php echo admin_url('admin.php?page=food-resource-directory-import&step=results'); ?>';
            } else {
                alert('Import failed: ' + response.data.message);
                $('.spinner').css('visibility', 'hidden');
            }
        });
    });
    
    function loadPreview() {
        $.post(ajaxurl, {
            action: 'frd_get_preview',
            nonce: '<?php echo wp_create_nonce('frd_import_preview'); ?>'
        }, function(response) {
            if (response.success) {
                displayPreview(response.data);
            } else {
                $('#frd-preview-container').html(
                    '<div class="notice notice-error"><p>' + response.data.message + '</p></div>'
                );
            }
        });
    }
    
    function displayPreview(data) {
        var html = '<div class="frd-import-summary">';
        html += '<h3>Import Summary</h3>';
        html += '<p><strong>Total Rows:</strong> ' + data.total_rows + '</p>';
        html += '<p><strong>Valid Rows:</strong> ' + data.valid_rows + ' (will be imported)</p>';
        html += '<p><strong>Invalid Rows:</strong> ' + data.invalid_rows + ' (will be skipped)</p>';
        html += '</div>';
        
        if (data.valid_rows > 0) {
            html += '<h3>Preview (First 10 Rows)</h3>';
            html += '<table class="frd-preview-table"><thead><tr>';
            html += '<th>Row</th><th>Organization</th><th>Address</th><th>County</th><th>Status</th>';
            html += '</tr></thead><tbody>';
            
            var preview = data.preview.slice(0, 10);
            $.each(preview, function(i, row) {
                var rowClass = row._validation.valid ? 'valid' : 'invalid';
                var statusBadge = row._validation.valid ? 
                    '<span class="frd-status-badge valid">Valid</span>' : 
                    '<span class="frd-status-badge invalid">Invalid</span>';
                
                html += '<tr class="' + rowClass + '">';
                html += '<td>' + row._row_number + '</td>';
                html += '<td>' + escapeHtml(row.Organization || '') + '</td>';
                html += '<td>' + escapeHtml(row['Street Address'] || '') + ', ' + escapeHtml(row.City || '') + '</td>';
                html += '<td>' + escapeHtml(row.County || '') + '</td>';
                html += '<td>' + statusBadge;
                
                if (row._validation.errors && row._validation.errors.length > 0) {
                    html += '<ul class="frd-error-list">';
                    $.each(row._validation.errors, function(j, error) {
                        html += '<li>' + escapeHtml(error) + '</li>';
                    });
                    html += '</ul>';
                }
                
                if (row._validation.warnings && row._validation.warnings.length > 0) {
                    html += '<ul class="frd-warning-list">';
                    $.each(row._validation.warnings, function(j, warning) {
                        html += '<li>' + escapeHtml(warning) + '</li>';
                    });
                    html += '</ul>';
                }
                
                html += '</td></tr>';
            });
            
            html += '</tbody></table>';
        }
        
        if (data.invalid_rows > 0) {
            html += '<div class="notice notice-warning">';
            html += '<p><strong>Warning:</strong> ' + data.invalid_rows + ' row(s) have errors and will be skipped during import.</p>';
            html += '</div>';
        }
        
        $('#frd-preview-container').html(html);
        
        if (data.valid_rows > 0) {
            $('#frd-confirm-import').prop('disabled', false);
        }
    }
    
    function loadResults() {
        $.post(ajaxurl, {
            action: 'frd_get_results',
            nonce: '<?php echo wp_create_nonce('frd_import_results'); ?>'
        }, function(response) {
            if (response.success) {
                displayResults(response.data);
            } else {
                $('#frd-results-container').html(
                    '<div class="notice notice-error"><p>' + response.data.message + '</p></div>'
                );
            }
        });
    }
    
    function displayResults(data) {
        var html = '<div class="frd-import-summary">';
        html += '<h3>Import Complete!</h3>';
        html += '<div class="frd-summary-stats">';
        html += '<div class="frd-stat-box"><div class="frd-stat-number success">' + data.success + '</div><div class="frd-stat-label">Successfully Imported</div></div>';
        html += '<div class="frd-stat-box"><div class="frd-stat-number skipped">' + data.skipped + '</div><div class="frd-stat-label">Skipped (Errors)</div></div>';
        html += '<div class="frd-stat-box"><div class="frd-stat-number failed">' + data.failed + '</div><div class="frd-stat-label">Failed</div></div>';
        html += '</div></div>';
        
        if (data.errors && data.errors.length > 0) {
            html += '<div class="notice notice-warning"><p><strong>Some rows were skipped due to errors:</strong></p></div>';
            html += '<table class="frd-preview-table"><thead><tr>';
            html += '<th>Row</th><th>Title</th><th>Errors</th>';
            html += '</tr></thead><tbody>';
            
            $.each(data.errors.slice(0, 20), function(i, error) {
                html += '<tr class="invalid">';
                html += '<td>' + error.row + '</td>';
                html += '<td>' + escapeHtml(error.title || '') + '</td>';
                html += '<td><ul class="frd-error-list">';
                $.each(error.errors, function(j, err) {
                    html += '<li>' + escapeHtml(err) + '</li>';
                });
                html += '</ul></td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            
            if (data.errors.length > 20) {
                html += '<p><em>Showing first 20 errors. Download full error report below.</em></p>';
            }
        }
        
        $('#frd-results-container').html(html);
    }
    
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>
