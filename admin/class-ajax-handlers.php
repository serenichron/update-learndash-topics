<?php
class TSTPrep_CC_Ajax_Handlers {
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
        $course_ids = isset($_POST['course_ids']) ? array_map('intval', $_POST['course_ids']) : array();
        $lesson_ids = isset($_POST['lesson_ids']) ? array_map('intval', $_POST['lesson_ids']) : array();
        $topic_ids = isset($_POST['topic_ids']) ? array_map('intval', $_POST['topic_ids']) : array();
        $cleanup_type = isset($_POST['cleanup_type']) ? sanitize_text_field($_POST['cleanup_type']) : '';

        if (empty($course_ids) && empty($lesson_ids) && empty($topic_ids)) {
            wp_send_json_error('Please select at least one course, lesson, or topic');
        }

        if (!$cleanup_type) {
            wp_send_json_error('Please select a cleanup type');
        }

        $processed_items = array();

        // Process courses
        foreach ($course_ids as $course_id) {
            $course_lesson_ids = learndash_course_get_steps_by_type($course_id, 'sfwd-lessons');
            foreach ($course_lesson_ids as $lesson_id) {
                $this->process_lesson($lesson_id, $cleanup_type, $processed_items);
            }
        }

        // Process additional lessons
        foreach ($lesson_ids as $lesson_id) {
            if (!in_array("Lesson ID: $lesson_id", $processed_items)) {
                $this->process_lesson($lesson_id, $cleanup_type, $processed_items);
            }
        }

        // Process additional topics
        foreach ($topic_ids as $topic_id) {
            if (!in_array("Topic ID: $topic_id", $processed_items)) {
                $this->process_topic($topic_id, $cleanup_type, $processed_items);
            }
        }

        if (empty($processed_items)) {
            wp_send_json_error('No items were processed');
        }

        wp_send_json_success(array('processed_items' => $processed_items));
    }

    private function process_lesson($lesson_id, $cleanup_type, &$processed_items) {
        $lesson_content = TSTPrep_CC_Lesson_Topic_Handler::get_content($lesson_id);
        $processed_content = TSTPrep_CC_Content_Processor::process_content($lesson_content, $cleanup_type);
        TSTPrep_CC_Lesson_Topic_Handler::update_content($lesson_id, $processed_content);
        $processed_items[] = "Lesson ID: $lesson_id";

        $topics = TSTPrep_CC_Lesson_Topic_Handler::get_associated_topics($lesson_id);
        foreach ($topics as $topic_id => $topic_title) {
            $this->process_topic($topic_id, $cleanup_type, $processed_items);
        }
    }

    private function process_topic($topic_id, $cleanup_type, &$processed_items) {
        $topic_content = TSTPrep_CC_Lesson_Topic_Handler::get_content($topic_id);
        $processed_content = TSTPrep_CC_Content_Processor::process_content($topic_content, $cleanup_type);
        TSTPrep_CC_Lesson_Topic_Handler::update_content($topic_id, $processed_content);
        $processed_items[] = "Topic ID: $topic_id";
    }
}