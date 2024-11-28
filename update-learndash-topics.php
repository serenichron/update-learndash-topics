<?php
/*
 Plugin Name: Update LearnDash Topics
 Description: A tool to update LearnDash topics based on a given course ID.
 Version: 1.2
 Author: Your Name
*/
add_action('admin_menu', 'add_update_learndash_topics_page');

function add_update_learndash_topics_page() {
    add_menu_page(
        'Update LearnDash Topics', // Page title
        'Update Topics',           // Menu title
        'manage_options',          // Capability
        'update-learndash-topics', // Menu slug
        'render_update_topics_page', // Callback function
        'dashicons-admin-tools',   // Icon
        100                        // Position
    );
}

function render_update_topics_page() {
    ?>
    <div class="wrap">
        <h1>Update LearnDash Topics</h1>
        <form id="update_topics_form" method="post">
            <p>
                <label for="course_id">Enter Course ID:</label>
                <input type="number" name="course_id" id="course_id" required>
                <span title="Enter the ID of the LearnDash course for which you want to update topic content.">❓</span>
            </p>
            <p>
                <button type="submit" class="button button-primary">Run Script</button>
            </p>
        </form>

        <h2>Execution Log</h2>
        <div id="execution-log" style="padding: 10px; background: #f9f9f9; border: 1px solid #ccc; max-height: 500px; overflow-y: auto;"></div>
    </div>
    <?php
}

add_action('admin_footer', function() {
    // Check if we are on the right admin page
    if (isset($_GET['page']) && $_GET['page'] === 'update-learndash-topics') {
        ?>
        <script>
            (function($) {
                let lessons = [];
                let courseId = 0;
                let currentIndex = 0;

                $('#update_topics_form').on('submit', function(e) {
                    e.preventDefault();

                    courseId = $('#course_id').val();
                    if (!courseId) {
                        alert('Please enter a Course ID.');
                        return;
                    }

                    $('#execution-log').html('<p>Fetching lessons...</p>');

                    $.post(ajaxurl, { action: 'fetch_lessons', course_id: courseId }, function(response) {
                        lessons = response.data;
                        if (lessons.length > 0) {
                            $('#execution-log').append('<p>Found ' + lessons.length + ' lessons. Starting...</p>');
                            processLesson();
                        } else {
                            $('#execution-log').append('<p>No lessons found for course ID ' + courseId + '.</p>');
                        }
                    });
                });

                function processLesson() {
                    if (currentIndex >= lessons.length) {
                        $('#execution-log').append('<p>All lessons processed successfully.</p>');
                        return;
                    }

                    let lessonId = lessons[currentIndex];
                    $('#execution-log').append('<p>Processing lesson ID: ' + lessonId + '...</p>');

                    $.post(ajaxurl, { action: 'process_lesson', lesson_id: lessonId }, function(response) {
                        $('#execution-log').append('<pre>' + response.data + '</pre>');
                        currentIndex++;
                        processLesson();
                    });
                }
            })(jQuery);
        </script>
        <?php
    }
});

add_action('admin_init', 'process_update_learndash_topics');

function process_update_learndash_topics() {
    if (isset($_POST['update_topics']) && !empty($_POST['course_id'])) {
        global $wpdb;

        // Get the course ID from the form input
        $course_id = intval($_POST['course_id']);
        $table_prefix = $wpdb->prefix;

        echo "<p>Processing course ID: $course_id</p>";

        // Fetch all lesson IDs for the course
        $lessons = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$table_prefix}postmeta 
             WHERE meta_key = 'course_id' 
             AND meta_value = %d", 
            $course_id
        ));

        if (empty($lessons)) {
            echo "<p>No lessons found for course ID: $course_id</p>";
            return;
        }

        echo "<p>Found " . count($lessons) . " lessons for course ID: $course_id</p>";

        // Fetch all topic IDs under each lesson
        $topics = [];
        foreach ($lessons as $lesson_id) {
            echo "<p>Processing lesson ID: $lesson_id</p>";
            $lesson_topics = $wpdb->get_col($wpdb->prepare(
                "SELECT post_id FROM {$table_prefix}postmeta 
                 WHERE meta_key = 'lesson_id' 
                 AND meta_value = %d", 
                $lesson_id
            ));

            if (!empty($lesson_topics)) {
                echo "<p>Found " . count($lesson_topics) . " topics for lesson ID: $lesson_id</p>";
                $topics = array_merge($topics, $lesson_topics);
            } else {
                echo "<p>No topics found for lesson ID: $lesson_id</p>";
            }
        }

        if (empty($topics)) {
            echo "<p>No topics found for the course ID: $course_id</p>";
            return;
        }

        // Process each topic
        foreach ($topics as $topic_id) {
            $topic_content = $wpdb->get_var($wpdb->prepare(
                "SELECT post_content FROM {$table_prefix}posts 
                 WHERE ID = %d", 
                $topic_id
            ));

            echo "<p>Processing topic ID: $topic_id</p>";
            echo "<p>Previous content: <pre>" . esc_html($topic_content) . "</pre></p>";

            // Extract the [ld_quiz quiz_id="..."] shortcode
            if (preg_match('/\[ld_quiz quiz_id="[^"]+"\]/', $topic_content, $matches)) {
                $shortcode = $matches[0];

                // Update the topic content to contain only the shortcode
                $result = $wpdb->update(
                    "{$table_prefix}posts",
                    ['post_content' => $shortcode],
                    ['ID' => $topic_id],
                    ['%s'],
                    ['%d']
                );

                if ($result !== false) {
                    echo "<p>Updated content: <pre>" . esc_html($shortcode) . "</pre></p>";
                } else {
                    echo "<p>Failed to update content for topic ID: $topic_id</p>";
                }
            } else {
                echo "<p>No quiz shortcode found in topic ID: $topic_id</p>";
            }
        }

        echo "<p>Script completed successfully.</p>";
    }
}

// Fetch lessons for the course
add_action('wp_ajax_fetch_lessons', function() {
    global $wpdb;

    $course_id = intval($_POST['course_id']);
    $lessons = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT p.ID FROM {$wpdb->prefix}posts p
         INNER JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
         WHERE pm.meta_key = 'course_id'
         AND pm.meta_value = %d
         AND p.post_type = 'sfwd-lessons'", 
        $course_id
    ));

    wp_send_json_success($lessons);
});

// Process a single lesson and its topics
add_action('wp_ajax_process_lesson', function() {
    global $wpdb;

    $lesson_id = intval($_POST['lesson_id']);

    // Validate that the post is actually a lesson
    $is_lesson = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}posts 
         WHERE ID = %d AND post_type = 'sfwd-lessons'", 
        $lesson_id
    ));

    if (!$is_lesson) {
        wp_send_json_success("Skipped post ID $lesson_id: Not a lesson.");
        return;
    }

    // Proceed with processing the lesson
    $logs = [];
    $logs[] = "Processing lesson ID: $lesson_id";

    $topics = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->prefix}postmeta 
         WHERE meta_key = 'lesson_id' 
         AND meta_value = %d", 
        $lesson_id
    ));

    if (empty($topics)) {
        $logs[] = "No topics found for lesson ID: $lesson_id";
        wp_send_json_success(implode("\n", $logs));
        return;
    }

    $logs[] = "Found " . count($topics) . " topics for lesson ID: $lesson_id";

    foreach ($topics as $topic_id) {
        $topic_content = $wpdb->get_var($wpdb->prepare(
            "SELECT post_content FROM {$wpdb->prefix}posts 
             WHERE ID = %d", 
            $topic_id
        ));

        $logs[] = "Processing topic ID: $topic_id";
        if (preg_match('/\[ld_quiz quiz_id="[^"]+"\]/', $topic_content, $matches)) {
            $shortcode = $matches[0];
            $wpdb->update(
                "{$wpdb->prefix}posts",
                ['post_content' => $shortcode],
                ['ID' => $topic_id],
                ['%s'],
                ['%d']
            );
            $logs[] = "Updated content to: $shortcode";
        } else {
            $logs[] = "No quiz shortcode found in topic ID: $topic_id";
        }
    }

    wp_send_json_success(implode("\n", $logs));
});


