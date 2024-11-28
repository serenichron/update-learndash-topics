# LearnDash Topic Content Cleaner

**LearnDash Topic Content Cleaner** is a WordPress plugin designed to streamline the content of LearnDash topics built with Divi. It isolates and retains only the `[ld_quiz]` shortcode in topics, removing unnecessary content such as Divi Builder elements.

## Features
- Clean up Divi content in LearnDash topics.
- Retain only the `[ld_quiz]` shortcode in topic content.
- Specify the LearnDash course ID to process topics for.
- Real-time logs and progress tracking via the admin panel.
- Securely restricted to admin users.

## Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- LearnDash LMS installed and configured

## Installation
1. Download or clone this repository into your WordPress plugins directory: `/wp-content/plugins/`.
2. Activate the plugin from the WordPress admin dashboard under **Plugins > Installed Plugins**.

## Usage
1. Navigate to **Topic Cleaner** in the WordPress admin menu.
2. Enter the ID of the LearnDash course whose topics you want to clean up.
3. Click **Run Script** to start the cleaning process.
4. Monitor the progress and logs in the execution log area.

## Security
- The plugin is fully namespaced to prevent conflicts with other WordPress functionality.
- Only admin users can access and execute the plugin’s functionality.
- AJAX handlers are secured and restricted to valid AJAX requests.

## License
This plugin is open-source and licensed under the [MIT License](LICENSE).

## Contributing
Contributions, issues, and feature requests are welcome! Feel free to create a pull request or open an issue.

---

### Author
Developed by Vlad Tudorie (vlad@serenichron.com)
