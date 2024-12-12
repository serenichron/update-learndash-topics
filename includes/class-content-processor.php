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
        // Preserve outer structure
        $content = '<div id="et-boc" class="et-boc">
            <div id="et_builder_outer_content" class="et_builder_outer_content">
                <div class="et-l et-l--post">
                    <div class="et_builder_inner_content et_pb_gutters3">' . $content . '</div>
                </div>
            </div>
        </div>';
    
        // Convert Divi sections
        $content = preg_replace('/\[et_pb_section([^\]]*)\](.*?)\[\/et_pb_section\]/s', '<div class="et_pb_section et_pb_section_0 et_section_regular$1">$2</div>', $content);
    
        // Convert Divi rows
        $content = preg_replace('/\[et_pb_row([^\]]*)\](.*?)\[\/et_pb_row\]/s', '<div class="et_pb_row et_pb_row_0$1">$2</div>', $content);
    
        // Convert Divi columns
        $content = preg_replace('/\[et_pb_column([^\]]*)\](.*?)\[\/et_pb_column\]/s', '<div class="et_pb_column$1">$2</div>', $content);
    
        // Convert Divi text modules
        $content = preg_replace('/\[et_pb_text([^\]]*)\](.*?)\[\/et_pb_text\]/s', '<div class="et_pb_module et_pb_text$1"><div class="et_pb_text_inner">$2</div></div>', $content);
    
        // Convert Divi code modules
        $content = preg_replace('/\[et_pb_code([^\]]*)\](.*?)\[\/et_pb_code\]/s', '<div class="et_pb_module et_pb_code$1"><div class="et_pb_code_inner">$2</div></div>', $content);
    
        // Convert Divi sidebar modules
        $content = preg_replace('/\[et_pb_sidebar([^\]]*)\](.*?)\[\/et_pb_sidebar\]/s', '<div class="et_pb_module et_pb_sidebar$1">$2</div>', $content);
    
        // Process module attributes
        $content = preg_replace_callback('/class="([^"]*)"/', function($matches) {
            $classes = explode(' ', $matches[1]);
            $classes[] = 'et_pb_text_align_left';
            $classes[] = 'et_pb_bg_layout_light';
            return 'class="' . implode(' ', array_unique($classes)) . '"';
        }, $content);
    
        // Add module numbering
        $module_types = ['et_pb_text', 'et_pb_code', 'et_pb_sidebar'];
        foreach ($module_types as $type) {
            $count = 0;
            $content = preg_replace_callback('/class="([^"]*)'.$type.'([^"]*)"/', function($matches) use (&$count) {
                $count++;
                return 'class="'.$matches[1].$matches[0].'_'.$count.'"';
            }, $content);
        }
    
        // Handle video wrapper
        $content = preg_replace('/<iframe([^>]*)><\/iframe>/', '<div class="fluid-width-video-wrapper" style="padding-top: 56.25%;"><iframe$1></iframe></div>', $content);
    
        // Clean up any empty paragraphs that Divi might have added
        $content = preg_replace('/<p>\s*<\/p>/', '', $content);
    
        return $content;
    }
}