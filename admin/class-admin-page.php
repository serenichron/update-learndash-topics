<?php
class TSTPrep_CC_Admin_Page {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'TSTPrep Content Cleanup',
            'Content Cleanup',
            'manage_options',
            'tstprep-content-cleanup',
            array($this, 'render_admin_page'),
            'dashicons-admin-generic'
        );
    }

    public function render_admin_page() {
        ?>
        <div class="wrap tstprep-cc-admin-wrap">
            <div class="tstprep-cc-admin-header">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            </div>
            <form id="tstprep-cc-form">
                <div class="tstprep-cc-form-row">
                    <label for="tstprep-cc-course-select">Select Courses</label>
                    <select id="tstprep-cc-course-select" name="course_ids[]" multiple>
                        <?php
                        $courses = $this->get_courses();
                        foreach ($courses as $course_id => $course_title) {
                            echo '<option value="' . esc_attr($course_id) . '">' . esc_html($course_title) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="tstprep-cc-form-row">
                    <label for="tstprep-cc-lesson-select">Select Lessons</label>
                    <select id="tstprep-cc-lesson-select" name="lesson_ids[]" multiple>
                        <option value="">Select lessons</option>
                    </select>
                </div>
                <div class="tstprep-cc-form-row">
                    <label for="tstprep-cc-topic-select">Select Topics</label>
                    <select id="tstprep-cc-topic-select" name="topic_ids[]" multiple>
                        <option value="">Select topics</option>
                    </select>
                </div>
                <div class="tstprep-cc-form-row">
                    <label for="tstprep-cc-cleanup-type">Cleanup Type</label>
                    <select id="tstprep-cc-cleanup-type" name="cleanup_type">
                        <option value="">Select cleanup type</option>
                        <?php
                        $cleanup_types = TSTPrep_CC_Cleanup_Types::get_cleanup_types();
                        foreach ($cleanup_types as $type => $label) {
                            echo '<option value="' . esc_attr($type) . '">' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="tstprep-cc-submit-row">
                    <button id="tstprep-cc-submit" type="submit" class="button button-primary">Process Cleanup</button>
                </div>
            </form>
            <div id="tstprep-cc-results"></div>
        </div>
        <?php
    }

    private function get_courses() {
        $courses = array();
        $args = array(
            'post_type' => 'sfwd-courses',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        );
        $course_query = new WP_Query($args);
        if ($course_query->have_posts()) {
            while ($course_query->have_posts()) {
                $course_query->the_post();
                $courses[get_the_ID()] = get_the_title();
            }
        }
        wp_reset_postdata();
        return $courses;
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', array(), '4.0.13');
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);
        wp_enqueue_style('tstprep-cc-admin-styles', TSTPREP_CC_PLUGIN_URL . 'assets/css/admin-styles.css', array(), TSTPREP_CC_VERSION);
        wp_enqueue_script('tstprep-cc-admin-script', TSTPREP_CC_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery', 'select2'), TSTPREP_CC_VERSION, true);
        wp_localize_script('tstprep-cc-admin-script', 'tstprep_cc_vars', array(
            'nonce' => wp_create_nonce('tstprep_cc_nonce')
        ));
    }
}