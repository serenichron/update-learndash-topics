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

        // Handle form submission
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

            $submitButton.prop('disabled', true);
            $resultsArea.html('<p>Processing...</p>');

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'tstprep_cc_process_cleanup',
                    course_ids: courseIds,
                    lesson_ids: lessonIds,
                    topic_ids: topicIds,
                    cleanup_type: cleanupType,
                    nonce: tstprep_cc_vars.nonce
                },

                success: function(response) {
                    if (response.success) {
                        $resultsArea.html('<p>Cleanup completed successfully!</p>');
                        if (response.data.processed_items) {
                            $resultsArea.append('<ul class="processed-items">');
                            $.each(response.data.processed_items, function(index, item) {
                                if (typeof item === 'string') {
                                    $resultsArea.append('<li>' + item + '</li>');
                                } else {
                                    $resultsArea.append('<li>' + item.type + ' ID: ' + item.id + '</li>');
                                    $resultsArea.append('<details><summary>View changes</summary>');
                                    $resultsArea.append('<h4>Before:</h4><pre class="content-display">' + escapeHtml(item.before) + '</pre>');
                                    $resultsArea.append('<h4>After:</h4><pre class="content-display">' + escapeHtml(item.after) + '</pre>');
                                    $resultsArea.append('</details>');
                                }
                            });
                            $resultsArea.append('</ul>');
                        }
                    } else {
                        $resultsArea.html('<p>Error: ' + response.data + '</p>');
                    }
                    $submitButton.prop('disabled', false);
                },
                error: function() {
                    $resultsArea.html('<p>An error occurred. Please try again.</p>');
                    $submitButton.prop('disabled', false);
                }
            });
        });
        $(document).on('click', '.download-log', function(e) {
            e.preventDefault();
            var logId = $(this).data('log-id');
            window.location.href = ajaxurl + '?action=tstprep_cc_download_log&log_id=' + logId + '&nonce=' + tstprep_cc_vars.nonce;
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
                $resultsArea.append('<ul>');
                $.each(response.data.processed_items, function(index, item) {
                    $resultsArea.append('<li>' + item + '</li>');
                });
                $resultsArea.append('</ul>');

                if (response.data.continue) {
                    // Continue processing
                    processCleanup(courseIds, lessonIds, topicIds, cleanupType, response.data.offset);
                } else {
                    $resultsArea.append('<p>Cleanup completed successfully!</p>');
                    $submitButton.prop('disabled', false);
                }
                if (!response.data.continue) {
                    $resultsArea.append('<p>Cleanup completed successfully!</p>');
                    $resultsArea.append('<a href="#" class="button download-log" data-log-id="' + response.data.log_id + '">Download Log</a>');
                    $submitButton.prop('disabled', false);
                }
            } else {
                $resultsArea.html('<p>Error: ' + response.data + '</p>');
                $submitButton.prop('disabled', false);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            $resultsArea.html('<p>An error occurred: ' + textStatus + ' - ' + errorThrown + '</p>');
            $submitButton.prop('disabled', false);
        }
    });
}

// Update the submit button click handler
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
