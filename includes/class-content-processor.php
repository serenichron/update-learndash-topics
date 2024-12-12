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
        $content = preg_replace_callback('/\[et_pb_section([^\]]*)\](.*?)\[\/et_pb_section\]/s', function($matches) {
            static $section_count = 0;
            $section_count++;
            $attrs = self::parse_attributes($matches[1]);
            $classes = ['et_pb_section', "et_pb_section_{$section_count}", 'et_section_regular'];
            if (!isset($attrs['fullwidth']) || $attrs['fullwidth'] !== 'on') {
                $classes[] = 'et_section_transparent';
            }
            $attr_string = self::build_attributes($attrs, $classes);
            return "<div {$attr_string}>{$matches[2]}</div>";
        }, $content);
    
        // Convert Divi rows
        $content = preg_replace_callback('/\[et_pb_row([^\]]*)\](.*?)\[\/et_pb_row\]/s', function($matches) {
            static $row_count = 0;
            $row_count++;
            $attrs = self::parse_attributes($matches[1]);
            $classes = ['et_pb_row', "et_pb_row_{$row_count}"];
            if (isset($attrs['column_structure'])) {
                $classes[] = 'et_pb_row_' . str_replace(',', '_', $attrs['column_structure']);
            }
            $attr_string = self::build_attributes($attrs, $classes);
            return "<div {$attr_string}>{$matches[2]}</div>";
        }, $content);
    
        // Convert Divi columns
        $content = preg_replace_callback('/\[et_pb_column([^\]]*)\](.*?)\[\/et_pb_column\]/s', function($matches) {
            static $column_count = 0;
            $column_count++;
            $attrs = self::parse_attributes($matches[1]);
            $classes = ['et_pb_column', "et_pb_column_{$attrs['type']}", "et_pb_column_{$column_count}"];
            $classes[] = 'et_pb_css_mix_blend_mode_passthrough';
            if ($column_count % 2 == 0) {
                $classes[] = 'et-last-child';
            }
            $attr_string = self::build_attributes($attrs, $classes);
            return "<div {$attr_string}>{$matches[2]}</div>";
        }, $content);
    
        // Convert Divi modules (text, code, sidebar)
        $module_types = ['et_pb_text', 'et_pb_code', 'et_pb_sidebar'];
        foreach ($module_types as $type) {
            static $module_counts = [];
            if (!isset($module_counts[$type])) {
                $module_counts[$type] = 0;
            }
            $content = preg_replace_callback('/\['.$type.'([^\]]*)\](.*?)\[\/'.$type.'\]/s', function($matches) use ($type, &$module_counts) {
                $module_counts[$type]++;
                $attrs = self::parse_attributes($matches[1]);
                $classes = ['et_pb_module', $type, "{$type}_{$module_counts[$type]}", 'et_pb_text_align_left', 'et_pb_bg_layout_light'];
                $attr_string = self::build_attributes($attrs, $classes);
                $inner_content = $matches[2];
                if ($type === 'et_pb_sidebar') {
                    return "<div {$attr_string}></div>";
                }
                return "<div {$attr_string}><div class='{$type}_inner'>{$inner_content}</div></div>";
            }, $content);
        }
    
        // Handle video wrapper
        $content = preg_replace('/<iframe([^>]*)><\/iframe>/', '<div class="fluid-width-video-wrapper" style="padding-top: 56.25%;"><iframe$1></iframe></div>', $content);
    
        // Clean up any empty paragraphs
        $content = preg_replace('/<p>\s*<\/p>/', '', $content);
    
        return $content;
    }
    
    private static function parse_attributes($attr_string) {
        $attrs = [];
        preg_match_all('/(\w+)="([^"]*)"/', $attr_string, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $attrs[$match[1]] = $match[2];
        }
        return $attrs;
    }
    
    private static function build_attributes($attrs, $classes) {
        if (isset($attrs['module_class'])) {
            $classes = array_merge($classes, explode(' ', $attrs['module_class']));
            unset($attrs['module_class']);
        }
        $attr_string = 'class="' . implode(' ', array_unique($classes)) . '"';
        $allowed_attrs = ['custom_margin', 'custom_padding', 'custom_css_main_element'];
        foreach ($attrs as $key => $value) {
            if (in_array($key, $allowed_attrs)) {
                $attr_string .= " {$key}=\"{$value}\"";
            }
        }
        return $attr_string;
    }
}