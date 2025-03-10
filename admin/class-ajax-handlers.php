<?php
class TSTPrep_CC_Ajax_Handlers {
    private $chunk_size = 5; // Process 5 lessons at a time

    public function __construct() {
                add_action('wp_ajax_tstprep_cc_search_courses', array($this, 'search_courses'));
                add_action('wp_ajax_tstprep_cc_search_lessons', array($this, 'search_lessons'));
                add_action('wp_ajax_tstprep_cc_search_topics', array($this, 'search_topics'));
                add_action('wp_ajax_tstprep_cc_process_cleanup', array($this, 'process_cleanup'));
            }
        
            public function search_courses() {
                check_ajax_referer('tstprep_cc_nonce', 'nonce');
            
                // Get the search term and normalize it
                $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
                $search = html_entity_decode($search, ENT_QUOTES, 'UTF-8'); // Decode entities like `&#8211;`
                $search = str_replace(['–', '&#8211;'], '-', $search); // Replace en dashes and entities with hyphen
            
                // Debugging: Log the normalized search term
                error_log('Normalized Search Term (Courses): ' . $search);
            
                // Add a custom filter for the `posts_where` clause
                add_filter('posts_where', function ($where) use ($search) {
                    global $wpdb;
            
                    if (!empty($search)) {
                        $where .= $wpdb->prepare(
                            " AND {$wpdb->posts}.post_title LIKE %s",
                            '%' . $wpdb->esc_like($search) . '%'
                        );
                    }
            
                    return $where;
                });
            
                // WP_Query arguments
                $args = array(
                    'post_type' => 'sfwd-courses',
                    'post_status' => 'publish',
                    'posts_per_page' => 20,
                    'suppress_filters' => false, // Allow our custom filter to apply
                );
            
                // Execute the query
                $query = new WP_Query($args);
            
                // Debugging: Log the SQL query
                error_log('SQL Query (Courses): ' . $query->request);
            
                // Collect results
                $results = array();
                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        $results[] = array(
                            'id' => get_the_ID(),
                            'text' => html_entity_decode(get_the_title(), ENT_QUOTES, 'UTF-8') // Decode the title
                        );
                    }
                }
            
                // Reset the query and remove the filter
                wp_reset_postdata();
                remove_filter('posts_where', '__return_null'); // Ensure the filter doesn't persist
            
                // Return results
                wp_send_json_success($results);
            }            
        
            public function search_lessons() {
                check_ajax_referer('tstprep_cc_nonce', 'nonce');
            
                // Get the search term and normalize it
                $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
                $search = html_entity_decode($search, ENT_QUOTES, 'UTF-8'); // Decode entities like `&#8211;`
                $search = str_replace(['–', '&#8211;'], '-', $search); // Replace en dashes and entities with hyphen
            
                // Debugging: Log the normalized search term
                error_log('Normalized Search Term (Lessons): ' . $search);
            
                // Add a custom filter for the `posts_where` clause
                add_filter('posts_where', function ($where) use ($search) {
                    global $wpdb;
            
                    if (!empty($search)) {
                        $where .= $wpdb->prepare(
                            " AND {$wpdb->posts}.post_title LIKE %s",
                            '%' . $wpdb->esc_like($search) . '%'
                        );
                    }
            
                    return $where;
                });
            
                // WP_Query arguments
                $args = array(
                    'post_type' => 'sfwd-lessons',
                    'post_status' => 'publish',
                    'posts_per_page' => 20,
                    'suppress_filters' => false, // Allow our custom filter to apply
                );
            
                // Execute the query
                $query = new WP_Query($args);
            
                // Debugging: Log the SQL query
                error_log('SQL Query (Lessons): ' . $query->request);
            
                // Collect results
                $results = array();
                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        $results[] = array(
                            'id' => get_the_ID(),
                            'text' => html_entity_decode(get_the_title(), ENT_QUOTES, 'UTF-8') // Decode the title
                        );
                    }
                }
            
                // Reset the query and remove the filter
                wp_reset_postdata();
                remove_filter('posts_where', '__return_null'); // Ensure the filter doesn't persist
            
                // Return results
                wp_send_json_success($results);
            }            
        
            public function search_topics() {
                check_ajax_referer('tstprep_cc_nonce', 'nonce');
            
                // Get the search term and normalize it
                $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
                $search = html_entity_decode($search, ENT_QUOTES, 'UTF-8'); // Decode HTML entities like `&#8211;`
                $search = str_replace(['–', '&#8211;'], '-', $search); // Replace en dashes and entities with hyphen
            
                // Debugging: Log the normalized search term
                error_log('Normalized Search Term (Topics): ' . $search);
            
                // Add a custom filter for the `posts_where` clause
                add_filter('posts_where', function ($where) use ($search) {
                    global $wpdb;
            
                    if (!empty($search)) {
                        $where .= $wpdb->prepare(
                            " AND {$wpdb->posts}.post_title LIKE %s",
                            '%' . $wpdb->esc_like($search) . '%'
                        );
                    }
            
                    return $where;
                });
            
                // WP_Query arguments
                $args = array(
                    'post_type' => 'sfwd-topic',
                    'post_status' => 'publish',
                    'posts_per_page' => 20,
                    'suppress_filters' => false, // Allow our custom filter to apply
                );
            
                // Execute the query
                $query = new WP_Query($args);
            
                // Debugging: Log the SQL query
                error_log('SQL Query (Topics): ' . $query->request);
            
                // Collect results
                $results = array();
                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        $results[] = array(
                            'id' => get_the_ID(),
                            'text' => html_entity_decode(get_the_title(), ENT_QUOTES, 'UTF-8') // Decode the title
                        );
                    }
                }
            
                // Reset the query and remove the filter
                wp_reset_postdata();
                remove_filter('posts_where', '__return_null'); // Ensure the filter doesn't persist
            
                // Return results
                wp_send_json_success($results);
            }            

            public function process_cleanup() {
                check_ajax_referer('tstprep_cc_nonce', 'nonce');
                $course_ids = isset($_POST['course_ids']) ? array_map('intval', $_POST['course_ids']) : array();
                $lesson_ids = isset($_POST['lesson_ids']) ? array_map('intval', $_POST['lesson_ids']) : array();
                $topic_ids = isset($_POST['topic_ids']) ? array_map('intval', $_POST['topic_ids']) : array();
                $cleanup_type = isset($_POST['cleanup_type']) ? sanitize_text_field($_POST['cleanup_type']) : '';
                $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        
                if (empty($course_ids) && empty($lesson_ids) && empty($topic_ids)) {
                    wp_send_json_error('Please select at least one course, lesson, or topic');
                }
        
                if (!$cleanup_type) {
                    wp_send_json_error('Please select a cleanup type');
                }
        
                $processed_items = array();
                $progress_log = array(); // Track progress for logging
                $continue_processing = false;
                
                // Calculate total items to process for progress tracking
                $total_items = 0;
                $total_processed = 0;
                
                // Count course lessons
                foreach ($course_ids as $course_id) {
                    $course_lesson_ids = learndash_course_get_steps_by_type($course_id, 'sfwd-lessons');
                    if (is_array($course_lesson_ids) && !empty($course_lesson_ids)) {
                        $total_items += count($course_lesson_ids);
                        // Count topics in each lesson
                        foreach ($course_lesson_ids as $l_id) {
                            $topics = learndash_get_topic_list($l_id);
                            $total_items += is_array($topics) ? count($topics) : 0;
                        }
                    }
                }
                
                // Count individual lessons and their topics
                foreach ($lesson_ids as $lesson_id) {
                    $total_items++;
                    $topics = learndash_get_topic_list($lesson_id);
                    $total_items += is_array($topics) ? count($topics) : 0;
                }
                
                // Count individual topics
                $total_items += count($topic_ids);
                
                // Add initial progress
                $progress_log[] = array(
                    'type' => 'progress',
                    'message' => "Starting cleanup process - {$offset} of {$total_items} items processed",
                    'percentage' => $offset > 0 ? round(($offset / $total_items) * 100) : 0
                );

                // Process courses
                foreach ($course_ids as $course_id) {
                    $course_lesson_ids = learndash_course_get_steps_by_type($course_id, 'sfwd-lessons');
                    if (is_array($course_lesson_ids) && !empty($course_lesson_ids)) {
                        $course_title = get_the_title($course_id);
                        $progress_log[] = array(
                            'type' => 'progress',
                            'message' => "Processing course: {$course_title} (ID: {$course_id})"
                        );
                        
                        $chunk = array_slice($course_lesson_ids, $offset, $this->chunk_size);
                        foreach ($chunk as $lesson_id) {
                            $lesson_title = get_the_title($lesson_id);
                            $progress_log[] = array(
                                'type' => 'progress',
                                'message' => "Processing lesson: {$lesson_title} (ID: {$lesson_id})",
                            );
                            
                            $this->process_lesson($lesson_id, $cleanup_type, $processed_items);
                            $total_processed++;
                            
                            // Update progress after each lesson (including its topics)
                            $topics = learndash_get_topic_list($lesson_id);
                            $topic_count = is_array($topics) ? count($topics) : 0;
                            $total_processed += $topic_count;
                            
                            $percentage = round(($total_processed / $total_items) * 100);
                            $progress_log[] = array(
                                'type' => 'progress',
                                'message' => "Processed {$total_processed} of {$total_items} items",
                                'percentage' => $percentage
                            );
                        }
                        
                        if (count($course_lesson_ids) > $offset + $this->chunk_size) {
                            $continue_processing = true;
                            break;
                        }
                    } else {
                        $processed_items[] = array(
                            'type' => 'info', 
                            'message' => "No lessons found for Course ID: $course_id"
                        );
                        $progress_log[] = array(
                            'type' => 'progress',
                            'message' => "No lessons found for Course ID: $course_id"
                        );
                    }
                }
        
                // If we're not continuing to process courses, move on to individual lessons
                if (!$continue_processing) {
                    foreach ($lesson_ids as $lesson_id) {
                        $lesson_title = get_the_title($lesson_id);
                        $progress_log[] = array(
                            'type' => 'progress',
                            'message' => "Processing individual lesson: {$lesson_title} (ID: {$lesson_id})"
                        );
                        
                        $this->process_lesson($lesson_id, $cleanup_type, $processed_items);
                        $total_processed++;
                        
                        // Update progress
                        $percentage = round(($total_processed / $total_items) * 100);
                        $progress_log[] = array(
                            'type' => 'progress',
                            'message' => "Processed {$total_processed} of {$total_items} items",
                            'percentage' => $percentage
                        );
                    }
                }
        
                // If we're not continuing to process courses or lessons, move on to individual topics
                if (!$continue_processing) {
                    foreach ($topic_ids as $topic_id) {
                        $topic_title = get_the_title($topic_id);
                        $progress_log[] = array(
                            'type' => 'progress',
                            'message' => "Processing individual topic: {$topic_title} (ID: {$topic_id})"
                        );
                        
                        $this->process_topic($topic_id, $cleanup_type, $processed_items);
                        $total_processed++;
                        
                        // Update progress
                        $percentage = round(($total_processed / $total_items) * 100);
                        $progress_log[] = array(
                            'type' => 'progress',
                            'message' => "Processed {$total_processed} of {$total_items} items",
                            'percentage' => $percentage
                        );
                    }
                }
        
                $log_content = $this->generate_log_content($processed_items);
                $log_id = uniqid('cleanup_log_');
                $set_transient_result = set_transient($log_id, $log_content, HOUR_IN_SECONDS);
                
                error_log("Cleanup process completed. Log ID: $log_id");
                error_log("Log content (first 100 chars): " . substr($log_content, 0, 100));
                error_log("Set transient result: " . ($set_transient_result ? 'true' : 'false'));
            
                // Add completion message to progress log
                if (!$continue_processing) {
                    $progress_log[] = array(
                        'type' => 'progress',
                        'message' => "Cleanup process completed successfully!",
                        'percentage' => 100
                    );
                }
                
                wp_send_json_success(array(
                    'processed_items' => $processed_items,
                    'progress_log' => $progress_log,
                    'continue' => $continue_processing,
                    'offset' => $offset + $this->chunk_size,
                    'log_id' => $log_id
                ));
            }
                        
            private function generate_log_content($processed_items) {
                $log_content = "";
                foreach ($processed_items as $item) {
                    switch ($item['type']) {
                        case 'lesson':
                        case 'topic':
                            $log_content .= "{$item['type']} ID: {$item['id']}\n";
                            $log_content .= "Before:\n{$item['before']}\n\n";
                            $log_content .= "After:\n{$item['after']}\n\n";
                            $log_content .= "------------------------\n\n";
                            break;
                        case 'error':
                        case 'info':
                            $log_content .= "{$item['type']}: {$item['message']}\n\n";
                            break;
                    }
                }
                return $log_content;
            }

            public function download_log() {
                error_log("Download log method called");
            
                check_ajax_referer('tstprep_cc_nonce', 'nonce');
                $log_id = isset($_GET['log_id']) ? sanitize_text_field($_GET['log_id']) : '';
                error_log("Log ID: $log_id");
            
                $log_content = get_transient($log_id);
                error_log("Log content retrieved: " . ($log_content !== false ? 'yes' : 'no'));
            
                if ($log_content !== false) {
                    error_log("Attempting to send file");
                    header('Content-Type: text/plain');
                    header('Content-Disposition: attachment; filename="cleanup_log_' . date('Y-m-d_H-i-s') . '.txt"');
                    header('Content-Length: ' . strlen($log_content));
                    echo $log_content;
                    delete_transient($log_id);
                    error_log("File sent and transient deleted");
                    exit;
                } else {
                    error_log("Log not found or expired");
                    wp_send_json_error('Log not found or expired');
                }
            }
                        
            
            private function process_lesson($lesson_id, $cleanup_type, &$processed_items) {
                $lesson_content_before = get_post_field('post_content', $lesson_id);
                $processed_content = TSTPrep_CC_Content_Processor::process_content($lesson_content_before, $cleanup_type);
                $update_result = wp_update_post(array(
                    'ID' => $lesson_id,
                    'post_content' => $processed_content
                ), true);
            
                if (is_wp_error($update_result)) {
                    $processed_items[] = array(
                        'type' => 'error',
                        'message' => "Error updating Lesson ID: $lesson_id - " . $update_result->get_error_message()
                    );
                } else {
                    $lesson_content_after = get_post_field('post_content', $lesson_id);
                    $processed_items[] = array(
                        'type' => 'lesson',
                        'id' => $lesson_id,
                        'before' => $lesson_content_before,
                        'after' => $lesson_content_after
                    );
                }
            
                $topics = learndash_get_topic_list($lesson_id);
                if (is_array($topics) && !empty($topics)) {
                    foreach ($topics as $topic) {
                        $this->process_topic($topic->ID, $cleanup_type, $processed_items);
                    }
                } else {
                    $processed_items[] = array(
                        'type' => 'info',
                        'message' => "No topics found for Lesson ID: $lesson_id"
                    );
                }
            }
            
            private function process_topic($topic_id, $cleanup_type, &$processed_items) {
                $topic_content_before = get_post_field('post_content', $topic_id);
                $processed_content = TSTPrep_CC_Content_Processor::process_content($topic_content_before, $cleanup_type);
                $update_result = wp_update_post(array(
                    'ID' => $topic_id,
                    'post_content' => $processed_content
                ), true);
            
                if (is_wp_error($update_result)) {
                    $processed_items[] = array(
                        'type' => 'error',
                        'message' => "Error updating Topic ID: $topic_id - " . $update_result->get_error_message()
                    );
                } else {
                    $topic_content_after = get_post_field('post_content', $topic_id);
                    $processed_items[] = array(
                        'type' => 'topic',
                        'id' => $topic_id,
                        'before' => $topic_content_before,
                        'after' => $topic_content_after
                    );
                }
            }
}