<?php
class TSTPrep_CC_Cleanup_Types {
    public static function get_cleanup_types() {
        return array(
            'shortcode' => 'Shortcode Cleanup',
            'placeholder1' => 'Placeholder Cleanup 1',
            'placeholder2' => 'Placeholder Cleanup 2',
        );
    }

    public static function get_cleanup_class($type) {
        $class_map = array(
            'shortcode' => 'TSTPrep_CC_Shortcode_Cleanup',
            'placeholder1' => 'TSTPrep_CC_Placeholder_Cleanup_1',
            'placeholder2' => 'TSTPrep_CC_Placeholder_Cleanup_2',
        );

        return isset($class_map[$type]) ? $class_map[$type] : false;
    }
}