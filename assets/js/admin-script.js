(function($) {
    $(document).ready(function() {
        var $courseSelect = $('#tstprep-cc-course-select');
        var $lessonSelect = $('#tstprep-cc-lesson-select');
        var $topicSelect = $('#tstprep-cc-topic-select');
        var $cleanupTypeSelect = $('#tstprep-cc-cleanup-type');
        var $submitButton = $('#tstprep-cc-submit');
        var $resultsArea = $('#tstprep-cc-results');

        // Initialize select2 for multiple selections
        $courseSelect.select2({
            placeholder: 'Select courses',
            allowClear: true
        });

        $lessonSelect.select2({
            placeholder: 'Select lessons',
            allowClear: true
        });

        $topicSelect.select2({
            placeholder: 'Select topics',
            allowClear: true
        });

        $cleanupTypeSelect.select2({
            placeholder: 'Select cleanup type',
            allowClear: true
        });

        // Handle course selection change
        $courseSelect.on('change', function() {
            var courseIds = $(this).val();
            if (courseIds && courseIds.length > 0) {
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'tstprep_cc_get_lessons',
                        course_ids: courseIds,
                        nonce: tstprep_cc_vars.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $lessonSelect.html('<option value="">Select lessons</option>');
                            $.each(response.data, function(id, title) {
                                $lessonSelect.append($('<option></option>').val(id).text(title));
                            });
                            $lessonSelect.trigger('change');
                        } else {
                            alert('Error loading lessons: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Error loading lessons. Please try again.');
                    }
                });
            }
        });

        // Handle lesson selection change
        $lessonSelect.on('change', function() {
            var lessonIds = $(this).val();
            if (lessonIds && lessonIds.length > 0) {
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'tstprep_cc_get_topics',
                        lesson_ids: lessonIds,
                        nonce: tstprep_cc_vars.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $topicSelect.html('<option value="">Select topics</option>');
                            $.each(response.data, function(id, title) {
                                $topicSelect.append($('<option></option>').val(id).text(title));
                            });
                            $topicSelect.trigger('change');
                        } else {
                            alert('Error loading topics: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Error loading topics. Please try again.');
                    }
                });
            }
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
                            $resultsArea.append('<ul>');
                            $.each(response.data.processed_items, function(index, item) {
                                $resultsArea.append('<li>' + item + '</li>');
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
    });
})(jQuery);