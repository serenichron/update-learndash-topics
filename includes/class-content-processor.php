<?php
class TSTPrep_CC_Content_Processor {
    public static function process_content($content, $cleanup_type) {
        $cleanup_class = TSTPrep_CC_Cleanup_Types::get_cleanup_class($cleanup_type);
        
        if ($cleanup_class && class_exists($cleanup_class)) {
            $processor = new $cleanup_class();
            return $processor->cleanup($content);
        }

        return $content;
    }
}