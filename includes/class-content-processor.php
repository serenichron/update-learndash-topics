<?php
class TSTPrep_CC_Content_Processor {
    public static function process_content($content, $cleanup_type) {
        switch ($cleanup_type) {
            case 'shortcode':
                return self::shortcode_cleanup($content);
            case 'divi_to_html':
                return self::divi_to_html_cleanup($content);
            default:
                return $content;
        }
    }

    private static function shortcode_cleanup($content) {
        // Preserve the existing shortcode cleanup logic
        $pattern = '/\[([^\]]*)\]/';
        preg_match($pattern, $content, $matches);
        return isset($matches[0]) ? $matches[0] : '';
    }

    private static function divi_to_html_cleanup($content) {
        // Remove outer Divi section and row, but preserve IDs
        $content = preg_replace('/\[et_pb_section.*?module_id="([^"]*)".*?\](.*)\[\/et_pb_section\]/s', '<div id="$1">$2</div>', $content);
        $content = preg_replace('/\[et_pb_row.*?module_id="([^"]*)".*?\](.*)\[\/et_pb_row\]/s', '<div id="$1">$2</div>', $content);
    
        // Convert Divi columns to div elements, preserving type, module_class, and module_id
        $content = preg_replace('/\[et_pb_column.*?type="([^"]*)".*?module_class="([^"]*)".*?module_id="([^"]*)".*?\](.*?)\[\/et_pb_column\]/s', '<div class="et_pb_column et_pb_column_$1 $2" id="$3">$4</div>', $content);
    
        // Convert Divi text modules to div elements, preserving module_class and module_id
        $content = preg_replace('/\[et_pb_text.*?module_class="([^"]*)".*?module_id="([^"]*)".*?\](.*?)\[\/et_pb_text\]/s', '<div class="et_pb_text $1" id="$2">$3</div>', $content);
    
        // Convert Divi code modules to div elements, preserving module_id
        $content = preg_replace('/\[et_pb_code.*?module_id="([^"]*)".*?\](.*?)\[\/et_pb_code\]/s', '<div class="et_pb_code" id="$1">$2</div>', $content);
    
        // Convert Divi sidebar modules to div elements, preserving module_id
        $content = preg_replace('/\[et_pb_sidebar.*?module_id="([^"]*)".*?\](.*?)\[\/et_pb_sidebar\]/s', '<div class="et_pb_sidebar" id="$1">$2</div>', $content);
    
        // Handle cases where module_id might not be present
        $content = preg_replace('/\[et_pb_column.*?type="([^"]*)".*?module_class="([^"]*)".*?\](.*?)\[\/et_pb_column\]/s', '<div class="et_pb_column et_pb_column_$1 $2">$3</div>', $content);
        $content = preg_replace('/\[et_pb_text.*?module_class="([^"]*)".*?\](.*?)\[\/et_pb_text\]/s', '<div class="et_pb_text $1">$2</div>', $content);
        $content = preg_replace('/\[et_pb_code.*?\](.*?)\[\/et_pb_code\]/s', '<div class="et_pb_code">$1</div>', $content);
        $content = preg_replace('/\[et_pb_sidebar.*?\](.*?)\[\/et_pb_sidebar\]/s', '<div class="et_pb_sidebar">$1</div>', $content);
    
        // Remove any remaining shortcode brackets
        $content = preg_replace('/\[(\/?)et_pb_[^\]]+\]/', '', $content);
    
        // Clean up any empty paragraphs that Divi might have added
        $content = preg_replace('/<p>\s*<\/p>/', '', $content);
    
        return $content;
    }
}