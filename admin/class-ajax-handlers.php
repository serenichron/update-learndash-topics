<?php
class TSTPrep_CC_Ajax_Handlers {
    private $chunk_size = 5; // Process 5 items at a time
    private $state_transient_prefix = 'tstprep_cc_state_';
    
    public function __construct() {
        add_action('wp_ajax_tstprep_cc_search_courses', array($this, 'search_courses'));
        add_action('wp_ajax_tstprep_cc_search_lessons', array($this, 'search_lessons'));
        add_action('wp_ajax_tstprep_cc_search_topics', array($this, 'search_topics'));
        add_action('wp_ajax_tstprep_cc_process_cleanup', array($this, 'process_cleanup'));
    }
        
    public function search_courses() {
        check_ajax_referer('tstprep_cc_nonce', 'nonce');
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        
        $args = array(
            'post_type' => 'sfwd-courses',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            's' => $search
        );

        $query = new WP_Query($args);
        $results = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $results[] = array(
                    'id' => get_the_ID(),
                    'text' => get_the_title()
                );
            }
        }

        wp_reset_postdata();
        wp_send_json_success($results);
    }

    public function search_lessons() {
        check_ajax_referer('tstprep_cc_nonce', 'nonce');
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        
        $args = array(
            'post_type' => 'sfwd-lessons',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            's' => $search
        );

        $query = new WP_Query($args);
        $results = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $results[] = array(
                    'id' => get_the_ID(),
                    'text' => get_the_title()
                );
            }
        }

        wp_reset_postdata();
        wp_send_json_success($results);
    }

    public function search_topics() {
        check_ajax_referer('tstprep_cc_nonce', 'nonce');
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        
        $args = array(
            'post_type' => 'sfwd-topic',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            's' => $search
        );

        $query = new WP_Query($args);
        $results = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $results[] = array(
                    'id' => get_the_ID(),
                    'text' => get_the_title()
                );
            }
        }

        wp_reset_postdata();
        wp_send_json_success($results);
    }

    public function process_cleanup() {
        check_ajax_referer('tstprep_cc_nonce', 'nonce');
        
        $state_id = isset($_POST['state_id']) ? sanitize_text_field($_POST['state_id']) : '';
        $cleanup_type = isset($_POST['cleanup_type']) ? sanitize_text_field($_POST['cleanup_type']) : '';
        
        // Initialize state if it's a new request
        if (empty($state_id)) {
            $course_ids = isset($_POST['course_ids']) ? array_map('intval', $_POST['course_ids']) : array();
            $lesson_ids = isset($_POST['lesson_ids']) ? array_map('intval', $_POST['lesson_ids']) : array();
            $topic_ids = isset($_POST['topic_ids']) ? array_map('intval', $_POST['topic_ids']) : array();
            
            if (empty($course_ids) && empty($lesson_ids) && empty($topic_ids)) {
                wp_send_json_error('Please select at least one course, lesson, or topic');
            }
            
            if (!$cleanup_type) {
                wp_send_json_error('Please select a cleanup type');
            }
            
            // Prepare a queue of items to process
            $queue = $this->prepare_process_queue($course_ids, $lesson_ids, $topic_ids);
            
            // Create a new state
            $state = array(
                'cleanup_type' => $cleanup_type,
                'queue' => $queue,
                'processed_items' => array(),
                'total_items' => count($queue)
            );
            
            $state_id = uniqid($this->state_transient_prefix);
            set_transient($state_id, $state, 12 * HOUR_IN_SECONDS);
        } else {
            // Retrieve existing state
            $state = get_transient($state_id);
            if (false === $state) {
                wp_send_json_error('Processing state has expired. Please start again.');
            }
            $cleanup_type = $state['cleanup_type'];
        }
        
        // Process chunk of items
        $processed_items = array();
        $continue_processing = false;
        
        // Take a chunk from the queue
        $chunk = array_slice($state['queue'], 0, $this->chunk_size);
        
        if (!empty($chunk)) {
            $continue_processing = true;
            
            // Process each item in the chunk
            foreach ($chunk as $item) {
                switch ($item['type']) {
                    case 'lesson':
                        $this->process_lesson_optimized($item['id'], $cleanup_type, $processed_items);
                        break;
                    case 'topic':
                        $this->process_topic_optimized($item['id'], $cleanup_type, $processed_items);
                        break;
                }
            }
            
            // Update state
            $state['queue'] = array_slice($state['queue'], $this->chunk_size);
            $state['processed_items'] = array_merge($state['processed_items'], $processed_items);
            set_transient($state_id, $state, 12 * HOUR_IN_SECONDS);
        }
        
        // If queue is empty, we're done
        if (empty($state['queue'])) {
            $continue_processing = false;
            
            // Generate log
            $log_content = $this->generate_log_content($state['processed_items']);
            $log_id = uniqid('cleanup_log_');
            set_transient($log_id, $log_content, HOUR_IN_SECONDS);
            
            // Clean up state
            delete_transient($state_id);
            
            wp_send_json_success(array(
                'processed_items' => $processed_items,
                'continue' => false,
                'progress' => 100,
                'log_id' => $log_id,
                'complete' => true
            ));
        } else {
            // Calculate progress
            $progress = round(((count($state['processed_items']) / $state['total_items']) * 100), 2);
            
            wp_send_json_success(array(
                'processed_items' => $processed_items,
                'continue' => $continue_processing,
                'state_id' => $state_id,
                'progress' => $progress,
                'remaining' => count($state['queue'])
            ));
        }
    }
                
    /**
     * Prepare a flat queue of all items to process
     */
    private function prepare_process_queue($course_ids, $lesson_ids, $topic_ids) {
        $queue = array();
        
        // Add individual topics to queue
        foreach ($topic_ids as $topic_id) {
            $queue[] = array(
                'type' => 'topic',
                'id' => $topic_id
            );
        }
        
        // Add individual lessons and their topics to queue
        foreach ($lesson_ids as $lesson_id) {
            $queue[] = array(
                'type' => 'lesson',
                'id' => $lesson_id
            );
            
            // Get topics associated with this lesson
            $topics = learndash_get_topic_list($lesson_id);
            if (is_array($topics) && !empty($topics)) {
                foreach ($topics as $topic) {
                    $queue[] = array(
                        'type' => 'topic',
                        'id' => $topic->ID
                    );
                }
            }
        }
        
        // Add course lessons and topics to queue
        foreach ($course_ids as $course_id) {
            $course_lesson_ids = learndash_course_get_steps_by_type($course_id, 'sfwd-lessons');
            if (is_array($course_lesson_ids) && !empty($course_lesson_ids)) {
                foreach ($course_lesson_ids as $lesson_id) {
                    $queue[] = array(
                        'type' => 'lesson',
                        'id' => $lesson_id
                    );
                    
                    // Get topics associated with this lesson
                    $topics = learndash_get_topic_list($lesson_id);
                    if (is_array($topics) && !empty($topics)) {
                        foreach ($topics as $topic) {
                            $queue[] = array(
                                'type' => 'topic',
                                'id' => $topic->ID
                            );
                        }
                    }
                }
            }
        }
        
        // Remove duplicate items by creating a map keyed by "type-id"
        $unique_queue = array();
        foreach ($queue as $item) {
            $key = $item['type'] . '-' . $item['id'];
            $unique_queue[$key] = $item;
        }
        
        return array_values($unique_queue);
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
        check_ajax_referer('tstprep_cc_nonce', 'nonce');
        $log_id = isset($_GET['log_id']) ? sanitize_text_field($_GET['log_id']) : '';
        
        $log_content = get_transient($log_id);
        
        if ($log_content !== false) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="cleanup_log_' . date('Y-m-d_H-i-s') . '.txt"');
            header('Content-Length: ' . strlen($log_content));
            echo $log_content;
            delete_transient($log_id);
            exit;
        } else {
            wp_send_json_error('Log not found or expired');
        }
    }
    
    /**
     * Optimized lesson processing that combines database operations
     */
    private function process_lesson_optimized($lesson_id, $cleanup_type, &$processed_items) {
        global $wpdb;
        
        // Get current content directly from database (single query)
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
        
        // Update directly with database query if content has changed (single query)
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
                $processed_items[] = array(
                    'type' => 'lesson',
                    'id' => $lesson_id,
                    'before' => $lesson_content_before,
                    'after' => $processed_content
                );
                
                // Clean post cache to ensure wp_update_post hooks run
                clean_post_cache($lesson_id);
            }
        } else {
            $processed_items[] = array(
                'type' => 'info',
                'message' => "No changes needed for Lesson ID: $lesson_id"
            );
        }
    }
    
    /**
     * Optimized topic processing that combines database operations
     */
    private function process_topic_optimized($topic_id, $cleanup_type, &$processed_items) {
        global $wpdb;
        
        // Get current content directly from database (single query)
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
        
        // Update directly with database query if content has changed (single query)
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
                $processed_items[] = array(
                    'type' => 'topic',
                    'id' => $topic_id,
                    'before' => $topic_content_before,
                    'after' => $processed_content
                );
                
                // Clean post cache to ensure wp_update_post hooks run
                clean_post_cache($topic_id);
            }
        } else {
            $processed_items[] = array(
                'type' => 'info',
                'message' => "No changes needed for Topic ID: $topic_id"
            );
        }
    }
}