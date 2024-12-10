<?php
class TSTPrep_CC_Ajax_Handlers {
    public function __construct() {
        add_action('wp_ajax_tstprep_cc_get_lessons', array($this, 'get_lessons'));
        add_action('wp_ajax_tstprep_cc_get_topics', array($this, 'get_topics'));
        add_action('wp_ajax_tstprep_cc_process_cleanup', array($this, 'process_cleanup'));
    }

    public function get_lessons() {
        check_ajax_referer('tstprep_cc_nonce', 'nonce');
        $course_ids = isset($_POST['course_ids']) ? array_map('intval', $_POST['course_ids']) : array();
        if (empty($course_ids)) {
            wp_send_json_error('Invalid course IDs');
        }
        $lessons = $this->get_courses_lessons($course_ids);
        if (empty($lessons)) {
            wp_send_json_error('No lessons found for these courses');
        }
        wp_send_json_success($lessons);
    }

    private function get_courses_lessons($course_ids) {
        $lessons = array();
        foreach ($course_ids as $course_id) {
            $lesson_ids = learndash_course_get_steps_by_type($course_id, 'sfwd-lessons');
            if (!empty($lesson_ids)) {
                foreach ($lesson_ids as $lesson_id) {
                    $lessons[$lesson_id] = get_the_title($lesson_id);
                }
            }
        }
        return $lessons;
    }

    public function get_topics() {
        check_ajax_referer('tstprep_cc_nonce', 'nonce');
        $lesson_ids = isset($_POST['lesson_ids']) ? array_map('intval', $_POST['lesson_ids']) : array();
        if (empty($lesson_ids)) {
            wp_send_json_error('Invalid lesson IDs');
        }
        $topics = $this->get_lessons_topics($lesson_ids);
        if (empty($topics)) {
            wp_send_json_error('No topics found for these lessons');
        }
        wp_send_json_success($topics);
    }

    private function get_lessons_topics($lesson_ids) {
        $topics = array();
        foreach ($lesson_ids as $lesson_id) {
            $lesson_topics = TSTPrep_CC_Lesson_Topic_Handler::get_associated_topics($lesson_id);
            $topics = array_merge($topics, $lesson_topics);
        }
        return $topics;
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