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

        function processCleanup(courseIds, lessonIds, topicIds, cleanupType, offset = 0) {
            $submitButton.prop('disabled', true);
            $resultsArea.html('<p>Processing... Please do not close this page.</p>');

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'tstprep_cc_process_cleanup',
                    course_ids: courseIds,
                    lesson_ids: lessonIds,
                    topic_ids: topicIds,
                    cleanup_type: cleanupType,
                    offset: offset,
                    nonce: tstprep_cc_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $.each(response.data.processed_items, function(index, item) {
                            switch(item.type) {
                                case 'lesson':
                                case 'topic':
                                    $resultsArea.append('<h4>' + (item.type === 'lesson' ? 'Lesson' : 'Topic') + ' ID: ' + item.id + '</h4>');
                                    $resultsArea.append('<details><summary>View changes</summary>');
                                    $resultsArea.append('<h5>Before:</h5><pre class="content-display">' + escapeHtml(item.before) + '</pre>');
                                    $resultsArea.append('<h5>After:</h5><pre class="content-display">' + escapeHtml(item.after) + '</pre>');
                                    $resultsArea.append('</details>');
                                    break;
                                case 'error':
                                    $resultsArea.append('<p class="error">' + item.message + '</p>');
                                    break;
                                case 'info':
                                    $resultsArea.append('<p class="info">' + item.message + '</p>');
                                    break;
                            }
                        });

                        if (response.data.continue) {
                            console.log('Continuing processing, offset:', response.data.offset);
                            processCleanup(courseIds, lessonIds, topicIds, cleanupType, response.data.offset);
                        } else {
                            console.log('Processing complete, adding download button');
                            $resultsArea.append('<p>Cleanup completed successfully!</p>');
                            $resultsArea.append('<a href="#" class="button download-log" data-log-id="' + response.data.log_id + '">Download Log</a>');
                            console.log('Download button added, log_id:', response.data.log_id);
                            $submitButton.prop('disabled', false);
                        }
                    } else {
                        console.error('Error in cleanup process:', response.data);
                        $resultsArea.html('<p>Error: ' + response.data + '</p>');
                        $submitButton.prop('disabled', false);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    $resultsArea.html('<p>An error occurred: ' + textStatus + ' - ' + errorThrown + '</p>');
                    $submitButton.prop('disabled', false);
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