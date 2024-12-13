# Changelog

All notable changes to this project will be documented in this file.


## [2.0.9-alpha] - 2023-12-13
### Fixed
- Resolved issue where processing courses or lessons failed to loop through them correctly
- Added null checks and error handling for course lessons and lesson topics
- Updated error reporting to provide more informative messages when no lessons or topics are found

### Changed
- Improved robustness of the cleanup process to handle cases where courses have no lessons or lessons have no topics


## [2.0.8-alpha] - 2023-06-XX
### Changed
- Refined Divi to HTML conversion process for better compatibility with Divi's rendered output
- Updated `divi_to_html_cleanup` function to more accurately preserve Divi's HTML structure and classes
- Improved handling of section, row, column, and module classes and numbering
- Added preservation of `et_pb_text_align_left` and `et_pb_bg_layout_light` classes
- Adjusted sidebar module handling to match Divi's output

### Fixed
- Corrected issues with module numbering in converted HTML
- Fixed inconsistencies in class preservation for various Divi elements

## [2.0.7-alpha] - 2023-06-XX
### Added
- Implemented AJAX-based search functionality for courses, lessons, and topics
- Added ability to select multiple courses, lessons, and topics independently

### Changed
- Updated admin interface to use Select2 for enhanced dropdown functionality
- Modified cleanup process to handle any combination of selected courses, lessons, and topics

### Fixed
- Resolved issues with course, lesson, and topic selection dependencies

## [2.0.6-alpha] - 2023-06-XX
### Added
- Introduced logging of before and after content for each processed item
- Implemented expandable sections in the admin interface to display cleanup results

### Changed
- Enhanced error handling and validation in AJAX handlers
- Improved the display of cleanup results in the admin interface

### Fixed
- Addressed word-wrapping issues in the admin interface for long content strings

## [2.0.5-alpha] - 2023-06-XX
### Added
- Implemented preservation of Divi module IDs in the cleanup process
- Added handling for `module_id` attributes in Divi shortcodes

### Changed
- Updated `divi_to_html_cleanup` function to convert `module_id` attributes to HTML `id` attributes
- Refined regex patterns to capture and preserve Divi module IDs

## [2.0.4-alpha] - 2023-06-XX
### Fixed
- Resolved issue with topics not looping and updating correctly
- Corrected the processing of lessons and their associated topics

### Changed
- Improved the cleanup process to ensure all selected items are processed
- Enhanced error checking and reporting in the cleanup process

## [1.1.4] - 2024-11-28
### Bugfix
- Fixed issue with topics not looping and updating.

## [1.1.3] - 2024-11-28
### Security
- Namespaced all plugin functions and hooks.
- Restricted plugin execution to admin users only.
- Protected AJAX handlers to validate against unauthorized access.

## [1.1.2] - 2024-11-28
### Chore
- Added plugin descriptions and usage instructions for users in the admin panel.

## [1.1.1] - 2024-11-28
### Bugfix
- Fixed an issue where topics were mistakenly treated as lessons and processed redundantly.

## [1.1] - 2024-11-28
### Features
- Introduced AJAX functionality for processing lessons and topics asynchronously.
- Added real-time logging and progress tracking in the admin panel.

## [1.0.0] - 2024-11-28
### Initial Release
- First working state of the plugin.
