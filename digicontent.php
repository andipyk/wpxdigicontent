<?php
/**
 * DigiContent
 *
 * @package           DigiContent
 * @author            Andi Syafrianda
 * @copyright         2024 Digikuy
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       DigiContent
 * Plugin URI:        https://digikuy.com/digicontent
 * Description:       AI-powered content generation using OpenAI GPT-4 and Anthropic Claude
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Andi Syafrianda
 * Author URI:        https://digikuy.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       digicontent
 * Domain Path:       /languages
 * Update URI:        https://digikuy.com/digicontent/update
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

declare(strict_types=1);

namespace DigiContent;

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit('Direct script access denied.');
}

// Check minimum PHP version
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    add_action('admin_notices', function() {
        $message = sprintf(
            /* translators: %s: PHP version */
            esc_html__('DigiContent requires PHP version %s or higher.', 'digicontent'),
            '8.0.0'
        );
        printf('<div class="notice notice-error"><p>%s</p></div>', $message);
    });
    return;
}

// Check minimum WordPress version
if (version_compare($GLOBALS['wp_version'], '6.0', '<')) {
    add_action('admin_notices', function() {
        $message = sprintf(
            /* translators: %s: WordPress version */
            esc_html__('DigiContent requires WordPress version %s or higher.', 'digicontent'),
            '6.0'
        );
        printf('<div class="notice notice-error"><p>%s</p></div>', $message);
    });
    return;
}

// Plugin constants
define('DIGICONTENT_VERSION', '1.1.0');
define('DIGICONTENT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DIGICONTENT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DIGICONTENT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoload classes
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'DigiContent\\';
    $base_dir = plugin_dir_path(__FILE__) . 'includes/';
    
    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separators with directory separators
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize plugin
add_action('plugins_loaded', function () {
    try {
        // Load text domain
        load_plugin_textdomain('digicontent', false, dirname(DIGICONTENT_PLUGIN_BASENAME) . '/languages');
        
        // Initialize services
        $logger = new Core\Services\LoggerService();
        $encryption = new Core\Services\EncryptionService($logger);
        $database = new Core\Database($logger);
        $template_repository = new Core\Repository\TemplateRepository($database);
        $template_service = new Core\Services\TemplateService($template_repository, $logger);
        
        // Initialize plugin components with services
        new Admin\Settings($template_service, $logger, $encryption);
        new Admin\Editor($template_service, $logger);
        
    } catch (\Exception $e) {
        if (isset($logger)) {
            $logger->error('Plugin initialization error', ['error' => $e->getMessage()]);
        }
        add_action('admin_notices', function() use ($e) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html__('DigiContent initialization failed. Please check error logs.', 'digicontent')
            );
        });
    }
});

// Add privacy policy section
add_action('admin_init', function() {
    if (function_exists('wp_add_privacy_policy_content')) {
        wp_add_privacy_policy_content(
            'DigiContent',
            sprintf(
                /* translators: 1: OpenAI Privacy Policy URL, 2: Anthropic Privacy Policy URL */
                esc_html__('DigiContent uses OpenAI and Anthropic APIs for content generation. Content prompts are sent to these services. For details, see %1$sOpenAI Privacy Policy%3$s and %2$sAnthropic Privacy Policy%3$s.', 'digicontent'),
                '<a href="https://openai.com/privacy" target="_blank">',
                '<a href="https://anthropic.com/privacy" target="_blank">',
                '</a>'
            )
        );
    }
});

// Add settings link to plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url(admin_url('options-general.php?page=digicontent-settings')),
        esc_html__('Settings', 'digicontent')
    );
    array_unshift($links, $settings_link);
    return $links;
});

// Activation hook
register_activation_hook(__FILE__, function () {
    try {
        $logger = new Core\Services\LoggerService();
        $logger->info('Plugin activation started');
        
        // Initialize database
        $database = new Core\Database($logger);
        $database->init();
        
        // Add default options with proper sanitization
        add_option('digicontent_anthropic_key', '');
        add_option('digicontent_openai_key', '');
        add_option('digicontent_settings', [
            'default_model' => 'gpt-4-turbo-preview',
            'max_tokens' => 1000,
            'temperature' => 0.7
        ]);
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        $logger->info('Plugin activation completed successfully');
        
    } catch (\Exception $e) {
        if (isset($logger)) {
            $logger->error('Plugin activation error', ['error' => $e->getMessage()]);
        }
        wp_die(
            esc_html__('Failed to activate DigiContent. Please check error logs.', 'digicontent'),
            '',
            ['back_link' => true]
        );
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, function () {
    try {
        $logger = new Core\Services\LoggerService();
        $logger->info('Plugin deactivation started');
        
        // Clear scheduled hooks
        wp_clear_scheduled_hook('digicontent_daily_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        $logger->info('Plugin deactivation completed successfully');
        
    } catch (\Exception $e) {
        if (isset($logger)) {
            $logger->error('Plugin deactivation error', ['error' => $e->getMessage()]);
        }
    }
});

// Initialize REST API routes
add_action('rest_api_init', function() {
    try {
        // Initialize services with proper error handling
        $logger = new Core\Services\LoggerService();
        
        $database = new Core\Database($logger);
        if (!$database->check_tables_exist()) {
            $logger->error('Required database tables are missing');
            return;
        }
        
        $template_repository = new Core\Repository\TemplateRepository($database);
        $template_service = new Core\Services\TemplateService($template_repository, $logger);
        
        // Register REST routes with proper authentication
        $template_controller = new Core\REST\TemplateController($template_service, $logger);
        $template_controller->register_routes();
        
    } catch (\Exception $e) {
        if (isset($logger)) {
            $logger->error('REST API initialization error', ['error' => $e->getMessage()]);
        }
    }
});