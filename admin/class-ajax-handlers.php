<?php
class TSTPrep_CC_Ajax_Handlers {
    private $chunk_size = 10; // Process 10 topics at a time
    private $state_transient_prefix = 'tstprep_cc_state_';

    public function __construct() {
                add_action('wp_ajax_tstprep_cc_search_courses', array($this, 'search_courses'));
                add_action('wp_ajax_tstprep_cc_search_lessons', array($this, 'search_lessons'));
                add_action('wp_ajax_tstprep_cc_search_topics', array($this, 'search_topics'));
                add_action('wp_ajax_tstprep_cc_process_cleanup', array($this, 'process_cleanup'));
                add_action('wp_ajax_tstprep_cc_download_log', array($this, 'download_log'));
            }
        
            public function search_courses() {
                check_ajax_referer('tstprep_cc_nonce', 'nonce');
            
                // Get the search term and normalize it
                $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
                $search = html_entity_decode($search, ENT_QUOTES, 'UTF-8'); // Decode entities like `&#8211;`
                $search = str_replace(['–', '&#8211;'], '-', $search); // Replace en dashes and entities with hyphen
            
                // Debugging: Log the normalized search term
                error_log('Normalized Search Term (Courses): ' . $search);
            
                // Create a named function for the filter
                $custom_where_filter = function ($where) use ($search) {
                    global $wpdb;
            
                    if (!empty($search)) {
                        $where .= $wpdb->prepare(
                            " AND {$wpdb->posts}.post_title LIKE %s",
                            '%' . $wpdb->esc_like($search) . '%'
                        );
                    }
            
                    return $where;
                };
                
                // Add the filter using the function variable
                add_filter('posts_where', $custom_where_filter);
            
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
                remove_filter('posts_where', $custom_where_filter); // Remove the specific filter
            
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
            
                // Create a named function for the filter
                $custom_where_filter = function ($where) use ($search) {
                    global $wpdb;
            
                    if (!empty($search)) {
                        $where .= $wpdb->prepare(
                            " AND {$wpdb->posts}.post_title LIKE %s",
                            '%' . $wpdb->esc_like($search) . '%'
                        );
                    }
            
                    return $where;
                };
                
                // Add the filter using the function variable
                add_filter('posts_where', $custom_where_filter);
            
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
                remove_filter('posts_where', $custom_where_filter); // Remove the specific filter
            
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
            
                // Create a named function for the filter
                $custom_where_filter = function ($where) use ($search) {
                    global $wpdb;
            
                    if (!empty($search)) {
                        $where .= $wpdb->prepare(
                            " AND {$wpdb->posts}.post_title LIKE %s",
                            '%' . $wpdb->esc_like($search) . '%'
                        );
                    }
            
                    return $where;
                };
                
                // Add the filter using the function variable
                add_filter('posts_where', $custom_where_filter);
            
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
                remove_filter('posts_where', $custom_where_filter); // Remove the specific filter
            
                // Return results
                wp_send_json_success($results);
            }            

            public function process_cleanup() {
                check_ajax_referer('tstprep_cc_nonce', 'nonce');
                
                // Get initialization parameters or load from state
                $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : uniqid('cleanup_');
                $state_key = $this->state_transient_prefix . $session_id;
                
                // Get state from transient or initialize new state
                $state = get_transient($state_key);
                
                if ($state === false) {
                    // This is a new cleanup session, initialize state
                    $course_ids = isset($_POST['course_ids']) ? array_map('intval', $_POST['course_ids']) : array();
                    $lesson_ids = isset($_POST['lesson_ids']) ? array_map('intval', $_POST['lesson_ids']) : array();
                    $topic_ids = isset($_POST['topic_ids']) ? array_map('intval', $_POST['topic_ids']) : array();
                    $cleanup_type = isset($_POST['cleanup_type']) ? sanitize_text_field($_POST['cleanup_type']) : '';
                    
                    if (empty($course_ids) && empty($lesson_ids) && empty($topic_ids)) {
                        wp_send_json_error('Please select at least one course, lesson, or topic');
                        return;
                    }
                    
                    if (!$cleanup_type) {
                        wp_send_json_error('Please select a cleanup type');
                        return;
                    }
                    
                    // Create an organized queue of all items to process
                    $queue = $this->build_processing_queue($course_ids, $lesson_ids, $topic_ids);
                    
                    // Initialize the state object
                    $state = array(
                        'session_id' => $session_id,
                        'course_ids' => $course_ids,
                        'lesson_ids' => $lesson_ids,
                        'topic_ids' => $topic_ids,
                        'cleanup_type' => $cleanup_type,
                        'queue' => $queue,
                        'total_items' => count($queue),
                        'processed_count' => 0,
                        'processed_items' => array(),
                        'log_entries' => array(),
                        'progress_log' => array(
                            array(
                                'type' => 'progress',
                                'message' => "Starting cleanup process - 0 of " . count($queue) . " items processed",
                                'percentage' => 0
                            )
                        ),
                        'current_course' => null,
                        'current_lesson' => null,
                        'is_complete' => false,
                        'log_id' => null
                    );
                    
                    // Save the initial state
                    set_transient($state_key, $state, 6 * HOUR_IN_SECONDS); // Keep state for 6 hours
                }
                
                // Process the next batch of items
                $state = $this->process_batch($state);
                
                // Save the updated state
                set_transient($state_key, $state, 6 * HOUR_IN_SECONDS);
                
                // Check if all processing is complete
                if ($state['is_complete']) {
                    // Generate and store the log file
                    $log_content = $this->generate_log_content($state['processed_items']);
                    $log_id = uniqid('cleanup_log_');
                    set_transient($log_id, $log_content, HOUR_IN_SECONDS);
                    $state['log_id'] = $log_id;
                    
                    // Update the state one last time with the log ID
                    set_transient($state_key, $state, 6 * HOUR_IN_SECONDS);
                    
                    // Add completion message to progress log
                    $state['progress_log'][] = array(
                        'type' => 'progress',
                        'message' => "Cleanup process completed successfully!",
                        'percentage' => 100
                    );
                }
                
                // Prepare response data
                $response = array(
                    'session_id' => $session_id,
                    'progress_log' => $state['progress_log'],
                    'processed_items' => array_slice($state['processed_items'], -10), // Only return the most recent items
                    'continue' => !$state['is_complete'],
                    'total_items' => $state['total_items'],
                    'processed_count' => $state['processed_count'],
                    'percentage' => $state['total_items'] > 0 ? round(($state['processed_count'] / $state['total_items']) * 100) : 0
                );
                
                if ($state['is_complete'] && isset($state['log_id'])) {
                    $response['log_id'] = $state['log_id'];
                }
                
                wp_send_json_success($response);
            }
            
            /**
             * Builds a flat processing queue of all items that need to be processed
             */
            private function build_processing_queue($course_ids, $lesson_ids, $topic_ids) {
                $queue = array();
                
                // Add course lessons and topics to queue
                foreach ($course_ids as $course_id) {
                    $course_lesson_ids = learndash_course_get_steps_by_type($course_id, 'sfwd-lessons');
                    
                    if (is_array($course_lesson_ids) && !empty($course_lesson_ids)) {
                        foreach ($course_lesson_ids as $lesson_id) {
                            // Add lesson to queue
                            $queue[] = array(
                                'type' => 'lesson',
                                'id' => $lesson_id,
                                'parent_course' => $course_id
                            );
                            
                            // Add lesson topics to queue
                            $topics = learndash_get_topic_list($lesson_id);
                            if (is_array($topics) && !empty($topics)) {
                                foreach ($topics as $topic) {
                                    $queue[] = array(
                                        'type' => 'topic',
                                        'id' => $topic->ID,
                                        'parent_lesson' => $lesson_id,
                                        'parent_course' => $course_id
                                    );
                                }
                            }
                        }
                    }
                }
                
                // Add individual lessons and their topics to queue
                foreach ($lesson_ids as $lesson_id) {
                    // Add lesson to queue
                    $queue[] = array(
                        'type' => 'lesson',
                        'id' => $lesson_id
                    );
                    
                    // Add lesson topics to queue
                    $topics = learndash_get_topic_list($lesson_id);
                    if (is_array($topics) && !empty($topics)) {
                        foreach ($topics as $topic) {
                            $queue[] = array(
                                'type' => 'topic',
                                'id' => $topic->ID,
                                'parent_lesson' => $lesson_id
                            );
                        }
                    }
                }
                
                // Add individual topics to queue
                foreach ($topic_ids as $topic_id) {
                    $queue[] = array(
                        'type' => 'topic',
                        'id' => $topic_id
                    );
                }
                
                return $queue;
            }
            
            /**
             * Process a batch of items from the queue - but more granularly for progress reporting
             */
            private function process_batch($state) {
                // We'll only process one item at a time to provide the most granular updates
                // but internally we'll still use the chunk concept to control how many items 
                // are processed in a single AJAX request
                
                // Get the next batch for processing
                $batch = array_slice($state['queue'], $state['processed_count'], $this->chunk_size);
                
                // If there's nothing left to process, mark as complete
                if (empty($batch)) {
                    $state['is_complete'] = true;
                    return $state;
                }
                
                // Track how many items we've processed in this batch
                $batch_processed = 0;
                $max_per_request = $this->chunk_size;
                
                // Process items one by one until we hit our per-request limit
                foreach ($batch as $item) {
                    if ($batch_processed >= $max_per_request) {
                        break; // Don't process more than our limit per request
                    }
                    
                    // Update current course/lesson tracking for better progress reporting
                    if (isset($item['parent_course']) && $item['parent_course'] !== $state['current_course']) {
                        $state['current_course'] = $item['parent_course'];
                        $course_title = get_the_title($item['parent_course']);
                        $state['progress_log'][] = array(
                            'type' => 'progress',
                            'message' => "Processing course: {$course_title} (ID: {$item['parent_course']})"
                        );
                    }
                    
                    if ($item['type'] === 'lesson' || (isset($item['parent_lesson']) && $item['parent_lesson'] !== $state['current_lesson'])) {
                        $lesson_id = ($item['type'] === 'lesson') ? $item['id'] : $item['parent_lesson'];
                        $state['current_lesson'] = $lesson_id;
                        $lesson_title = get_the_title($lesson_id);
                        $state['progress_log'][] = array(
                            'type' => 'progress',
                            'message' => "Processing lesson: {$lesson_title} (ID: {$lesson_id})"
                        );
                    }
                    
                    // Process the item based on its type
                    if ($item['type'] === 'lesson') {
                        $this->process_lesson($item['id'], $state['cleanup_type'], $state['processed_items']);
                    } else if ($item['type'] === 'topic') {
                        // If it's a topic, also log that we're processing it
                        $topic_title = get_the_title($item['id']);
                        $state['progress_log'][] = array(
                            'type' => 'progress',
                            'message' => "Processing topic: {$topic_title} (ID: {$item['id']})"
                        );
                        $this->process_topic($item['id'], $state['cleanup_type'], $state['processed_items']);
                    }
                    
                    // Increment counter and update progress
                    $state['processed_count']++;
                    $percentage = round(($state['processed_count'] / $state['total_items']) * 100);
                    
                    // Add progress update after EACH item
                    $state['progress_log'][] = array(
                        'type' => 'progress',
                        'message' => "Processed {$state['processed_count']} of {$state['total_items']} items",
                        'percentage' => $percentage
                    );
                    
                    $batch_processed++;
                }
                
                // Check if we've processed everything
                if ($state['processed_count'] >= $state['total_items']) {
                    $state['is_complete'] = true;
                }
                
                return $state;
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
                global $wpdb;
                
                // Get content directly from database (single query)
                $lesson_content_before = $wpdb->get_var($wpdb->prepare(
                    "SELECT post_content FROM {$wpdb->posts} WHERE ID = %d",
                    $lesson_id
                ));
                
                if ($lesson_content_before === null) {
                    $processed_items[] = array(
                        'type' => 'error',
                        'message' => "Lesson ID: $lesson_id not found"
                    );
                    return;
                }
                
                // Process the content
                $processed_content = TSTPrep_CC_Content_Processor::process_content($lesson_content_before, $cleanup_type);
                
                // Only update if content actually changed
                if ($processed_content !== $lesson_content_before) {
                    $result = $wpdb->update(
                        $wpdb->posts,
                        array('post_content' => $processed_content),
                        array('ID' => $lesson_id),
                        array('%s'),
                        array('%d')
                    );
                    
                    if ($result === false) {
                        $processed_items[] = array(
                            'type' => 'error',
                            'message' => "Error updating Lesson ID: $lesson_id - Database error"
                        );
                    } else {
                        // Clean post cache to ensure hooks run
                        clean_post_cache($lesson_id);
                        
                        $processed_items[] = array(
                            'type' => 'lesson',
                            'id' => $lesson_id,
                            'before' => $lesson_content_before,
                            'after' => $processed_content
                        );
                    }
                } else {
                    $processed_items[] = array(
                        'type' => 'info',
                        'message' => "No changes needed for Lesson ID: $lesson_id"
                    );
                }
                
                // Note: Topics are already added to the processing queue in build_processing_queue
                // No need to process them here as this would cause duplicate processing
            }
            
            private function process_topic($topic_id, $cleanup_type, &$processed_items) {
                global $wpdb;
                
                // Get content directly from database (single query)
                $topic_content_before = $wpdb->get_var($wpdb->prepare(
                    "SELECT post_content FROM {$wpdb->posts} WHERE ID = %d",
                    $topic_id
                ));
                
                if ($topic_content_before === null) {
                    $processed_items[] = array(
                        'type' => 'error',
                        'message' => "Topic ID: $topic_id not found"
                    );
                    return;
                }
                
                // Process the content
                $processed_content = TSTPrep_CC_Content_Processor::process_content($topic_content_before, $cleanup_type);
                
                // Only update if content actually changed
                if ($processed_content !== $topic_content_before) {
                    $result = $wpdb->update(
                        $wpdb->posts,
                        array('post_content' => $processed_content),
                        array('ID' => $topic_id),
                        array('%s'),
                        array('%d')
                    );
                    
                    if ($result === false) {
                        $processed_items[] = array(
                            'type' => 'error',
                            'message' => "Error updating Topic ID: $topic_id - Database error"
                        );
                    } else {
                        // Clean post cache to ensure hooks run
                        clean_post_cache($topic_id);
                        
                        $processed_items[] = array(
                            'type' => 'topic',
                            'id' => $topic_id,
                            'before' => $topic_content_before,
                            'after' => $processed_content
                        );
                    }
                } else {
                    $processed_items[] = array(
                        'type' => 'info',
                        'message' => "No changes needed for Topic ID: $topic_id"
                    );
                }
            }
}