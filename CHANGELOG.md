# Changelog

All notable changes to this project will be documented in this file.

## [2.0.21-alpha] - 2025-02-11
### Added
- n/a

### Changed
- n/a

### Fixed
- Undefined array issue on Download Log

## [2.0.20-alpha] - 2025-01-29
### Added
- n/a

### Changed
- n/a

### Fixed
- Fixed incrementation of $module_counts variable logic

## [2.0.19-alpha] - 2025-01-27
### Added
- n/a

### Changed
- n/a

### Fixed
- Fixed incrementation of $module_counts variable used for class numbering

## [2.0.18-alpha] - 2025-01-22
### Added
- Support for et_pb_text classes

### Changed
- Updated logic to not skip classes
- Updated logic to correctly number et_pb_text classes

### Fixed
- Fixed search query to return results that include special characters

## [2.0.17-alpha] - 2025-01-17
### Added
- Support for et_pb_team_member & et_pb_divider divi modules

### Changed
- Updated logic to also process et_pb_team_member & et_pb_divider

### Fixed
- Prevented et_pb_code from adding empty paragraphs

## [2.0.16-alpha] - 2025-01-17
### Added
- Support for et_pb_tabs divi module

### Changed
- Updated logic to also process et_pb_tabs

### Fixed
- n/a

## [2.0.15-alpha] - 2025-01-16
### Added
- n/a

### Changed
- Updated logic for generating toggles with correct structure
- Updated logic for generating rows with correct structure
- Updated logic for generating columns with correct structure

### Fixed
- Resolved issue where the element id was not transfered to the generated html

## [2.0.14-alpha] - 2025-01-13
### Added
- Support for additional divi modules

### Changed
- Updated logic to include image and raw html elements

### Fixed
- Resolved issue where additional paragraphs and line breaks were added

## [2.0.13-alpha] - 2025-01-09
### Added
- Support for additional divi modules

### Changed
- Updated parsing logic to better account for nested elements
- Updated logic to include elements that are defined as divi shortcode parameters

### Fixed
- Resolved issue where numbered elements count was starting at 1 instead of 0

## [2.0.12-alpha] - 2023-12-14
### Added
- Restored before/after content comparison for processed lessons and topics
- Enhanced logging to include detailed information about each processed item

### Changed
- Improved display of processing results in the admin interface
- Updated error and info message handling in the cleanup process

### Fixed
- Resolved issue where before/after content comparison was not visible in the results

## [2.0.11-alpha] - 2023-12-14
### Added
- Implemented log download feature after cleanup process
- Added new wrapper div with classes "et-db et_divi_builder" around cleaned content

### Changed
- Improved content processing to ensure wrappers are correctly placed around the content
- Enhanced error handling and timeout management for large course processing

### Fixed
- Resolved issue with potential over-processing of content
- Corrected placement of Divi wrappers in cleaned content

### Security
- Implemented nonce checking for log download feature

## [2.0.10-alpha] - 2023-12-13
### Added
- Implemented chunked processing for large courses to prevent timeouts
- Added more detailed progress reporting during cleanup process

### Changed
- Improved error handling and reporting in lesson and topic processing
- Updated AJAX handling to support continued processing for large datasets

### Fixed
- Resolved issue where cleanup process would appear stuck on large courses

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
