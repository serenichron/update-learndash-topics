(function($) {
    $(document).ready(function() {
        var $courseSelect = $('#tstprep-cc-course-select');
        var $lessonSelect = $('#tstprep-cc-lesson-select');
        var $topicSelect = $('#tstprep-cc-topic-select');
        var $cleanupTypeSelect = $('#tstprep-cc-cleanup-type');
        var $submitButton = $('#tstprep-cc-submit');
        var $resultsArea = $('#tstprep-cc-results');

        function initializeSelect2(element, type) {
            element.select2({
                placeholder: 'Search for ' + type + '...',
                allowClear: true,
                minimumInputLength: 3,
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            action: 'tstprep_cc_search_' + type,
                            search: params.term,
                            nonce: tstprep_cc_vars.nonce
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.data
                        };
                    },
                    cache: true
                }
            });
        }

        initializeSelect2($courseSelect, 'courses');
        initializeSelect2($lessonSelect, 'lessons');
        initializeSelect2($topicSelect, 'topics');

        $cleanupTypeSelect.select2({
            placeholder: 'Select cleanup type',
            allowClear: true
        });

        function processCleanup(courseIds, lessonIds, topicIds, cleanupType, sessionId = null) {
            $submitButton.prop('disabled', true);
            
            // Create progress bar if not already present
            if ($('#tstprep-cc-progress-container').length === 0) {
                $resultsArea.html('<div id="tstprep-cc-progress-container">' +
                    '<div class="progress-bar-wrapper" style="background-color: #f0f0f0; border-radius: 3px; margin-bottom: 10px;">' +
                    '<div id="tstprep-cc-progress-bar" style="width: 0%; background-color: #0073aa; height: 20px; border-radius: 3px;"></div>' +
                    '</div>' +
                    '<div id="tstprep-cc-progress-text">Processing... Please do not close this page. (0%)</div>' +
                    '</div>' +
                    '<div id="tstprep-cc-progress-log" style="margin-top: 15px; max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;"></div>' +
                    '<div id="tstprep-cc-processed-items"></div>');
            }
            
            var $progressBar = $('#tstprep-cc-progress-bar');
            var $progressText = $('#tstprep-cc-progress-text');
            var $progressLog = $('#tstprep-cc-progress-log');
            var $processedItems = $('#tstprep-cc-processed-items');

            // Prepare request data
            var requestData = {
                action: 'tstprep_cc_process_cleanup',
                nonce: tstprep_cc_vars.nonce
            };

            // If we have a session ID, we're continuing an existing process
            if (sessionId) {
                requestData.session_id = sessionId;
            } else {
                // Initial request needs selected items
                requestData.course_ids = courseIds;
                requestData.lesson_ids = lessonIds;
                requestData.topic_ids = topicIds;
                requestData.cleanup_type = cleanupType;
            }

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: requestData,
                success: function(response) {
                    if (response.success) {
                        // Get the session ID for continuation
                        var currentSessionId = response.data.session_id;
                        
                        // Update progress percentage
                        if (response.data.percentage !== undefined) {
                            $progressBar.css('width', response.data.percentage + '%');
                            $progressText.text('Processing... Please do not close this page. (' + response.data.percentage + '%)');
                        }
                        
                        // Update progress log messages
                        if (response.data.progress_log && response.data.progress_log.length > 0) {
                            // Determine how many new messages to display
                            // If this is a continuation, we may need to display more than just the last couple messages
                            // since we're now processing one item at a time
                            
                            // Store the last message ID we've seen to avoid duplicates
                            if (!window.lastSeenMessageIndex) {
                                window.lastSeenMessageIndex = -1;
                            }
                            
                            // Display all new messages
                            for (var i = 0; i < response.data.progress_log.length; i++) {
                                if (i > window.lastSeenMessageIndex) {
                                    var logItem = response.data.progress_log[i];
                                    if (logItem.type === 'progress') {
                                        // Add log message
                                        $progressLog.append('<p>' + logItem.message + '</p>');
                                    }
                                    window.lastSeenMessageIndex = i;
                                }
                            }
                            
                            // Auto-scroll to bottom of log
                            $progressLog.scrollTop($progressLog[0].scrollHeight);
                        }
                        
                        // Display processed items
                        if (response.data.processed_items && response.data.processed_items.length > 0) {
                            $.each(response.data.processed_items, function(index, item) {
                                switch(item.type) {
                                    case 'lesson':
                                    case 'topic':
                                        $processedItems.append('<h4>' + (item.type === 'lesson' ? 'Lesson' : 'Topic') + ' ID: ' + item.id + '</h4>');
                                        $processedItems.append('<details><summary>View changes</summary>');
                                        $processedItems.append('<h5>Before:</h5><pre class="content-display">' + escapeHtml(item.before) + '</pre>');
                                        $processedItems.append('<h5>After:</h5><pre class="content-display">' + escapeHtml(item.after) + '</pre>');
                                        $processedItems.append('</details>');
                                        break;
                                    case 'error':
                                        $processedItems.append('<p class="error">' + item.message + '</p>');
                                        break;
                                    case 'info':
                                        $processedItems.append('<p class="info">' + item.message + '</p>');
                                        break;
                                }
                            });
                        }

                        if (response.data.continue) {
                            // If processing should continue, make another request after a short delay
                            // The delay helps prevent server overload and gives the user time to see progress
                            setTimeout(function() {
                                processCleanup(null, null, null, null, currentSessionId);
                            }, 500); // 500ms delay between batches
                        } else {
                            // Processing complete
                            $progressText.text('Cleanup completed successfully! (100%)');
                            $progressBar.css('width', '100%');
                            $processedItems.append('<div class="completion-message" style="margin-top: 15px;">' +
                                '<p>Cleanup completed successfully!</p>' +
                                '<a href="#" class="button download-log" data-log-id="' + response.data.log_id + '">Download Log</a>' +
                                '</div>');
                            $submitButton.prop('disabled', false);
                        }
                    } else {
                        console.error('Error in cleanup process:', response.data);
                        $resultsArea.append('<p class="error">Error: ' + response.data + '</p>');
                        $submitButton.prop('disabled', false);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    
                    // If there's a timeout or server error, we can try to resume after a brief pause
                    if (sessionId && (textStatus === 'timeout' || jqXHR.status >= 500)) {
                        $progressLog.append('<p class="error">Temporary error: ' + textStatus + '. Retrying in 3 seconds...</p>');
                        $progressLog.scrollTop($progressLog[0].scrollHeight);
                        
                        setTimeout(function() {
                            processCleanup(null, null, null, null, sessionId);
                        }, 3000); // Wait 3 seconds before retrying
                    } else {
                        $resultsArea.append('<p class="error">An error occurred: ' + textStatus + ' - ' + errorThrown + '</p>');
                        $submitButton.prop('disabled', false);
                    }
                }
            });
        }

        $submitButton.on('click', function(e) {
            e.preventDefault();
            var courseIds = $courseSelect.val();
            var lessonIds = $lessonSelect.val();
            var topicIds = $topicSelect.val();
            var cleanupType = $cleanupTypeSelect.val();

            if ((!courseIds || courseIds.length === 0) && 
                (!lessonIds || lessonIds.length === 0) && 
                (!topicIds || topicIds.length === 0)) {
                alert('Please select at least one course, lesson, or topic.');
                return;
            }

            if (!cleanupType) {
                alert('Please select a cleanup type.');
                return;
            }

            processCleanup(courseIds, lessonIds, topicIds, cleanupType);
        });

        $(document).on('click', '.download-log', function(e) {
            e.preventDefault();
            var logId = $(this).data('log-id');
            console.log('Download log clicked, log_id:', logId);
            
            $.ajax({
                url: ajaxurl,
                method: 'GET',
                data: {
                    action: 'tstprep_cc_download_log',
                    log_id: logId,
                    nonce: tstprep_cc_vars.nonce
                },
                success: function(response) {
                    if (response.success === false) {
                        console.error('Error downloading log:', response.data);
                        alert('Error downloading log: ' + response.data);
                    } else {
                        // The response should be the file content
                        var blob = new Blob([response], {type: 'text/plain'});
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = 'cleanup_log_' + new Date().toISOString().slice(0,10) + '.txt';
                        link.click();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    alert('Error downloading log: ' + textStatus);
                }
            });
        });
    });
})(jQuery);

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}