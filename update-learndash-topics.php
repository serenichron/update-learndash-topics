<?php
namespace LDTopicCleaner;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/*
Plugin Name: LearnDash Topic Content Cleaner
Description: A tool to clean up Divi content in LearnDash topics by isolating [ld_quiz] shortcodes. Specifically designed for LearnDash courses with topics built using the Divi Builder.
Version: 1.1.3
Author: Vlad Tudorie
*/


// Load admin-only functionality
if (is_admin()) {
    add_action('admin_menu', __NAMESPACE__ . '\\add_update_learndash_topics_page');
    add_action('admin_footer', __NAMESPACE__ . '\\enqueue_admin_scripts');
}

// Register AJAX handlers
add_action('wp_ajax_fetch_lessons', __NAMESPACE__ . '\\fetch_lessons');
add_action('wp_ajax_process_lesson', __NAMESPACE__ . '\\process_lesson');

/**
 * Add the admin menu page.
 */
function add_update_learndash_topics_page() {
    add_menu_page(
        'LearnDash Topic Content Cleaner',
        'Topic Cleaner',
        'manage_options',
        'update-learndash-topics',
        __NAMESPACE__ . '\\render_update_topics_page',
        'dashicons-admin-tools',
        100
    );
}

/**
 * Render the admin page.
 */
function render_update_topics_page() {
    ?>
    <div class="wrap">
        <h1>LearnDash Topic Content Cleaner</h1>
        <p>This plugin cleans up Divi content in LearnDash topics. It isolates the <code>[ld_quiz]</code> shortcode and removes other unnecessary content from topics within a specified course.</p>
        <p>To use this tool:
            <ol>
                <li>Enter the Course ID for the LearnDash course whose topics you want to clean up.</li>
                <li>Click <strong>Run Script</strong> to process the topics.</li>
            </ol>
        </p>
        <form id="update_topics_form" method="post">
            <p>
                <label for="course_id">Enter Course ID:</label>
                <input type="number" name="course_id" id="course_id" required>
                <span title="Enter the ID of the LearnDash course for which you want to clean up topic content.">❓</span>
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

/**
 * Enqueue admin scripts.
 */
function enqueue_admin_scripts() {
    // Ensure scripts are only added on our admin page
    if (isset($_GET['page']) && $_GET['page'] === 'update-learndash-topics') {
        ?>
        <script>
            (function($) {
                let lessons = [];
                let courseId = 0;
                let currentIndex = 0;

                $('#update_topics_form').on('submit', function(e) {
                    e.preventDefault();

                    // Reset state variables
                    lessons = [];
                    courseId = 0;
                    currentIndex = 0;

                    courseId = $('#course_id').val();
                    if (!courseId) {
                        alert('Please enter a Course ID.');
                        return;
                    }

                    $('#execution-log').html('<p>Fetching lessons...</p>');

                    // Fetch lessons for the course
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
}

/**
 * Fetch lessons for the given course ID.
 */
function fetch_lessons() {
    if (!defined('DOING_AJAX') || !DOING_AJAX) {
        exit; // Exit if not an AJAX request
    }

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
}

/**
 * Process topics for a given lesson ID.
 */
function process_lesson() {
    if (!defined('DOING_AJAX') || !DOING_AJAX) {
        exit; // Exit if not an AJAX request
    }

    global $wpdb;
    $lesson_id = intval($_POST['lesson_id']);

    // Validate the post type
    $is_lesson = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}posts 
         WHERE ID = %d AND post_type = 'sfwd-lessons'", 
        $lesson_id
    ));

    if (!$is_lesson) {
        wp_send_json_success("Skipped post ID $lesson_id: Not a lesson.");
        return;
    }

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

    foreach ($topics as $topic_id) {
        $topic_content = $wpdb->get_var($wpdb->prepare(
            "SELECT post_content FROM {$wpdb->prefix}posts 
             WHERE ID = %d", 
            $topic_id
        ));

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
}