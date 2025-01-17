<?php
class TSTPrep_CC_Content_Processor {
    public static function process_content($content, $cleanup_type) {
        switch ($cleanup_type) {
            case 'divi_to_html':
                $cleaned_content = self::divi_to_html_cleanup($content);
                // Ensure the wrappers are only added if they don't already exist
                if (strpos($cleaned_content, 'class="et-db et_divi_builder"') === false) {
                    $cleaned_content = '<div class="et-db et_divi_builder"><div id="et-boc" class="et-boc">' . $cleaned_content . '</div></div>';
                }
                return $cleaned_content;
            default:
                return $content;
        }
    }

    private static function divi_to_html_cleanup($content) {
        // Wrap content in Divi outer structure
        $content = '<div class="et-db et_divi_builder"><div id="et-boc" class="et-boc">
            <div id="et_builder_outer_content" class="et_builder_outer_content">
                <div class="et-l et-l--post">
                    <div class="et_builder_inner_content et_pb_gutters3">' . $content . '</div>
                </div>
            </div>
        </div></div>';

        // Process all known Divi shortcodes
        $content = self::process_divi_shortcodes($content);

        // Remove any empty paragraphs
        $content = preg_replace('/<p>\s*<\/p>/', '', $content);

        return $content;
    }

    // Process [et_pb_column]
    private static function process_divi_columns($content, $columns_in_current_row) {
        $processed_columns = 0; // Number of processed columns within the current row
    
        return preg_replace_callback('/\[et_pb_column([^\]]*)\](.*?)\[\/et_pb_column\]/s', function ($matches) use (&$processed_columns, $columns_in_current_row) {
            static $column_count = 0; // Global column counter
    
            $attrs = self::parse_attributes($matches[1]);
    
            // Base classes for the column
            $classes = ['et_pb_column', "et_pb_column_{$attrs['type']}", "et_pb_column_{$column_count}"];
            $classes[] = 'et_pb_css_mix_blend_mode_passthrough';
    
            // Check if this is the last column in the current row
            if ($processed_columns + 1 === $columns_in_current_row) {
                $classes[] = 'et-last-child';
            }
    
            // Build attributes and process inner content
            $attr_string = self::build_attributes($attrs, $classes);
            $inner_content = self::process_divi_shortcodes($matches[2]);
    
            // Increment column counters
            $processed_columns++;
            $column_count++;
    
            return "<div {$attr_string}>{$inner_content}</div>";
        }, $content);
    }

    private static function process_divi_shortcodes($content) {
        // Process [et_pb_section]
        $content = preg_replace_callback('/\[et_pb_section([^\]]*)\](.*?)\[\/et_pb_section\]/s', function ($matches) {
            static $section_count = 0;
            $attrs = self::parse_attributes($matches[1]);
            $classes = ['et_pb_section', "et_pb_section_{$section_count}", 'et_section_regular'];
            if (!isset($attrs['fullwidth']) || $attrs['fullwidth'] !== 'on') {
                $classes[] = 'et_section_transparent';
            }
            $attr_string = self::build_attributes($attrs, $classes);
            $inner_content = self::process_divi_shortcodes($matches[2]); // Recursive call
            $output = "<div {$attr_string}>{$inner_content}</div>";
            $section_count++;
            return $output;
        }, $content);

        // Process [et_pb_row]
        $content = preg_replace_callback('/\[et_pb_row([^\]]*)\](.*?)\[\/et_pb_row\]/s', function ($matches) {
            static $row_count = 0; // Global counter for rows

            $attrs = self::parse_attributes($matches[1]);

            // Base classes for the row
            $classes = ['et_pb_row', "et_pb_row_{$row_count}"];

            // Add module_class if provided
            if (isset($attrs['module_class'])) {
                $classes[] = $attrs['module_class'];
            }

            // Parse column_structure to determine the number of columns
            $columns_in_current_row = 0; // Default to 0
            if (isset($attrs['column_structure'])) {
                $columns_in_current_row = count(explode(',', $attrs['column_structure']));
            }

            // Check for custom gutter usage
            if (isset($attrs['use_custom_gutter']) && $attrs['use_custom_gutter'] === 'on') {
                if (isset($attrs['gutter_width']) && is_numeric($attrs['gutter_width'])) {
                    $classes[] = "et_pb_gutters{$attrs['gutter_width']}";
                }
            }

            // Build the attribute string for the row
            $attr_string = self::build_attributes($attrs, $classes);

            // Process inner content and pass the column count to process_divi_columns
            $inner_content = self::process_divi_columns($matches[2], $columns_in_current_row);

            // Increment the row count
            $row_count++;

            // Return the processed row with the correct classes
            return "<div {$attr_string}>{$inner_content}</div>";
        }, $content);

        // Handle [et_pb_tabs]
        $content = preg_replace_callback('/\[et_pb_tabs([^\]]*)\](.*?)\[\/et_pb_tabs\]/s', function ($matches) {
            static $tabs_count = 0; // Global counter for tabs modules

            $attrs = self::parse_attributes($matches[1]);
            $tabs_classes = ['et_pb_module', 'et_pb_tabs', "et_pb_tabs_{$tabs_count}", 'et_slide_transition_to_1', 'et_slide_transition_to_0'];
            $tabs_attr_string = self::build_attributes($attrs, $tabs_classes);

            // Extract individual tabs
            $tabs_inner_content = $matches[2];
            $tabs_list_items = [];
            $tabs_content_items = [];
            $tab_count = 0;

            preg_replace_callback('/\[et_pb_tab([^\]]*)\](.*?)\[\/et_pb_tab\]/s', function ($tab_matches) use (&$tabs_list_items, &$tabs_content_items, &$tab_count) {
                $tab_attrs = self::parse_attributes($tab_matches[1]);
                $tab_title = isset($tab_attrs['title']) ? htmlspecialchars($tab_attrs['title'], ENT_QUOTES, 'UTF-8') : 'Tab Title';
                $tab_classes = ["et_pb_tab_{$tab_count}"];
                $tab_content_classes = ['et_pb_tab', "et_pb_tab_{$tab_count}", 'clearfix'];

                // Add active class to the first tab
                if ($tab_count === 0) {
                    $tab_classes[] = 'et_pb_tab_active';
                    $tab_content_classes[] = 'et_pb_active_content et-pb-active-slide';
                } else {
                    $tab_content_classes[] = 'et-pb-moved-slide';
                }

                $tabs_list_items[] = "<li class='" . implode(' ', $tab_classes) . "'><a href='#'>{$tab_title}</a></li>";
                $tabs_content_items[] = "<div class='" . implode(' ', $tab_content_classes) . "' style='z-index: " . ($tab_count + 1) . "; display: " . ($tab_count === 0 ? 'block' : 'none') . "; opacity: " . ($tab_count === 0 ? '1' : '0') . ";'>
                    <div class='et_pb_tab_content'>{$tab_matches[2]}</div>
                </div>";

                $tab_count++;
            }, $tabs_inner_content);

            // Build the tabs list and content
            $tabs_list_html = '<ul class="et_pb_tabs_controls clearfix">' . implode('', $tabs_list_items) . '</ul>';
            $tabs_content_html = '<div class="et_pb_all_tabs">' . implode('', $tabs_content_items) . '</div>';

            $tabs_count++;
            return "<div {$tabs_attr_string}>{$tabs_list_html}{$tabs_content_html}</div>";
        }, $content);

        // Process Divi modules (e.g., [et_pb_text], [et_pb_toggle], [et_pb_code], etc.)
        $module_types = ['et_pb_text', 'et_pb_code', 'et_pb_sidebar', 'et_pb_toggle', 'et_pb_video', 'et_pb_image'];

        foreach ($module_types as $type) {
            static $module_counts = [];
            if (!isset($module_counts[$type])) {
                $module_counts[$type] = 0;
            }

            // Remove auto-paragraphs temporarily
            remove_filter('the_content', 'wpautop');
            remove_filter('the_excerpt', 'wpautop');

            $content = preg_replace_callback('/\[' . preg_quote($type, '/') . '([^\]]*)\](.*?)\[\/' . preg_quote($type, '/') . '\]/s', function ($matches) use ($type, &$module_counts) {
                $attrs = self::parse_attributes($matches[1]);
                $classes = ['et_pb_module', $type, "{$type}_{$module_counts[$type]}", 'et_pb_text_align_left', 'et_pb_bg_layout_light'];

                // Recursively process inner content
                $inner_content = self::process_divi_shortcodes($matches[2]);

                if ($type === 'et_pb_video') {
                    // Handle YouTube video embedding
                    if (isset($attrs['src']) && preg_match('/youtu(?:\.be|be\.com)\/(?:watch\?v=|embed\/|)([^\s&?]+)/', $attrs['src'], $video_match)) {
                        $video_id = $video_match[1];
                        $iframe = "<iframe width=\"640\" height=\"360\" src=\"https://www.youtube.com/embed/{$video_id}?feature=oembed\" title=\"YouTube video player\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share\" referrerpolicy=\"strict-origin-when-cross-origin\" allowfullscreen></iframe>";
                        $module_counts[$type]++;
                        return "<div style=\"text-align:center;\">{$iframe}</div>";
                    }
                    return ''; // If no valid video URL is found, return nothing
                }

                if ($type === 'et_pb_toggle') {
                    // Use the $module_counts array to ensure unique IDs for each toggle
                    if (!isset($module_counts['et_pb_toggle'])) {
                        $module_counts['et_pb_toggle'] = 0;
                    }
                    
                    $toggle_id = 'toggle_' . $module_counts['et_pb_toggle']; // Generate a unique ID for this toggle
                
                    // Extract the title attribute (default to "Toggle Title" if not set)
                    $title = isset($attrs['title']) ? htmlspecialchars($attrs['title'], ENT_QUOTES, 'UTF-8') : 'Toggle Title';
                
                    // Add additional classes specific to toggle
                    $classes[] = 'et_pb_toggle_item';
                    $classes[] = 'et_pb_toggle_close'; // Default state is closed
                
                    // Build the HTML structure for the toggle
                    $output = '<div ' . self::build_attributes($attrs, $classes) . '>'
                            . "<div class='et_pb_toggle_title' data-bs-target='#{$toggle_id}' style='cursor: pointer;'>{$title}</div>"
                            . "<div id='{$toggle_id}' class='et_pb_toggle_content clearfix' style='display: none;'>{$inner_content}</div>"
                            . '</div>';
                
                    $module_counts['et_pb_toggle']++;
                    return $output;
                }                            

                if ($type === 'et_pb_code') {
                    // Extract optional attributes
                    $module_class = isset($attrs['module_class']) ? htmlspecialchars($attrs['module_class'], ENT_QUOTES, 'UTF-8') : '';
                    $custom_css = isset($attrs['custom_css_main_element']) ? htmlspecialchars($attrs['custom_css_main_element'], ENT_QUOTES, 'UTF-8') : '';
                
                    // Ensure the content is directly used without modification
                    $raw_content = isset($matches[2]) ? $matches[2] : '';
                
                    // Wrap the raw HTML content inside the custom div structure
                    $output = '<div class="et_pb_module et_pb_code ' . "{$type}_{$module_counts[$type]} {$module_class}\" style=\"{$custom_css}\">"
                            . '<div class="et_pb_code_inner">'
                            . $raw_content // Use raw HTML content from the shortcode
                            . '</div>'
                            . '</div>';
                
                    $module_counts[$type]++;
                    return $output;
                }                

                if ($type === 'et_pb_image') {
                    // Extract necessary attributes for image
                    $src = isset($attrs['src']) ? htmlspecialchars($attrs['src'], ENT_QUOTES, 'UTF-8') : '';
                    $title_text = isset($attrs['title_text']) ? htmlspecialchars($attrs['title_text'], ENT_QUOTES, 'UTF-8') : '';
                    $module_class = isset($attrs['module_class']) ? htmlspecialchars($attrs['module_class'], ENT_QUOTES, 'UTF-8') : '';
                    $custom_css = isset($attrs['custom_css_main_element']) ? htmlspecialchars($attrs['custom_css_main_element'], ENT_QUOTES, 'UTF-8') : '';

                    // Define additional image attributes
                    $width = '2040';
                    $height = '1070';
                    $sizes = '(max-width: 2040px) 100vw, 2040px';
                    $srcset = "{$src} 2040w, {$src} 300w, {$src} 1024w, {$src} 768w, {$src} 1536w, {$src} 1080w, {$src} 510w";

                    // Construct the image HTML without unnecessary spaces or newlines
                    $output = '<div class="et_pb_module et_pb_image ' . "{$type}_{$module_counts[$type]} {$module_class}\" style=\"{$custom_css}\">"
                            . '<span class="et_pb_image_wrap">'
                            . '<img fetchpriority="high" decoding="async" width="' . $width . '" height="' . $height . '" src="' . $src . '" alt="" '
                            . 'title="' . $title_text . '" srcset="' . $srcset . '" sizes="' . $sizes . '" class="wp-image">'
                            . '</span>'
                            . '</div>';

                    $module_counts[$type]++;
                    return $output;
                }

                // Generic handling for other module types
                $attr_string = self::build_attributes($attrs, $classes);
                $output = "<div {$attr_string}><div class='{$type}_inner'>{$inner_content}</div></div>";

                $module_counts[$type]++;
                return $output;
            }, $content);

            // Re-enable auto-paragraphs
            add_filter('the_content', 'wpautop');
            add_filter('the_excerpt', 'wpautop');
        }

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
        // Handle classes
        if (isset($attrs['module_class'])) {
            $classes = array_merge($classes, explode(' ', $attrs['module_class']));
            unset($attrs['module_class']);
        }
        $attr_string = 'class="' . implode(' ', array_unique($classes)) . '"';
    
        // Add the "module_id" as the ID attribute if it's defined
        if (isset($attrs['module_id'])) {
            $attr_string .= ' id="' . htmlspecialchars($attrs['module_id'], ENT_QUOTES, 'UTF-8') . '"';
            unset($attrs['module_id']); // Remove it to avoid duplication in other attributes
        }
    
        // Add other allowed attributes
        $allowed_attrs = ['custom_margin', 'custom_padding', 'custom_css_main_element', 'hover_enabled', 'sticky_enabled'];
        foreach ($attrs as $key => $value) {
            if (in_array($key, $allowed_attrs)) {
                $attr_string .= " {$key}=\"" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "\"";
            }
        }
    
        return $attr_string;
    }    
}