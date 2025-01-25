<?php
/**
 * Plugin Name: DigiContent
 * Plugin URI: https://digikuy.com/digicontent
 * Description: AI-powered content generation plugin using Anthropic Claude 3.5 Sonnet and OpenAI GPT-4 Turbo
 * Version: 1.1.0
 * Author: Andi Syafrianda
 * Author URI: https://digikuy.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: digicontent
 * Domain Path: /languages
 * Requires PHP: 8.0
 */

declare(strict_types=1);

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('DIGICONTENT_VERSION', '1.0.0');
define('DIGICONTENT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DIGICONTENT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader function
spl_autoload_register(function ($class) {
    $prefix = 'DigiContent\\';
    $base_dir = DIGICONTENT_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize plugin
add_action('plugins_loaded', function () {
    // Load text domain
    load_plugin_textdomain('digicontent', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Initialize plugin classes
    try {
        new DigiContent\Admin\Settings();
        new DigiContent\Admin\PostEditor();
        new DigiContent\Core\AIGenerator();
    } catch (Exception $e) {
        error_log('DigiContent Plugin Error: ' . $e->getMessage());
    }
});

// Activation hook
register_activation_hook(__FILE__, function () {
    // Create necessary database tables and options
    add_option('digicontent_anthropic_key', '');
    add_option('digicontent_openai_key', '');
    add_option('digicontent_settings', [
        'default_model' => 'gpt-4-turbo-preview',
        'max_tokens' => 1000,
        'temperature' => 0.7
    ]);
});

// Deactivation hook
register_deactivation_hook(__FILE__, function () {
    // Cleanup if necessary
});