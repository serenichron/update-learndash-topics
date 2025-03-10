# TST Prep Content Cleanup - Development Guidelines

## Commands
- **Activate Plugin**: `wp plugin activate tstprep-content-cleanup`
- **Deactivate Plugin**: `wp plugin deactivate tstprep-content-cleanup`
- **Lint**: `phpcs --standard=WordPress ./`
- **Fix Lint Issues**: `phpcbf --standard=WordPress ./`
- **Debug**: `define('WP_DEBUG', true);` in wp-config.php

## Code Style
- **Naming**: 
  - Classes: `TSTPrep_CC_ClassName` (PascalCase with prefix)
  - Functions: `tstprep_cc_function_name` (snake_case with prefix)
  - Constants: `TSTPREP_CC_CONSTANT_NAME` (UPPERCASE with prefix)
- **File Structure**: Class files named `class-{name}.php`
- **Documentation**: Use WordPress-style PHP DocBlocks for classes, methods, functions
- **Formatting**: Follow WordPress Coding Standards
- **Error Handling**: Use try/catch for recoverable errors, WordPress functions for logging
- **Version Control**: Follow semantic versioning (MAJOR.MINOR.PATCH-alpha)
- **Changelog**: Update CHANGELOG.md with each version under Added/Changed/Fixed sections