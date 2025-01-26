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
define('DIGICONTENT_VERSION', '1.1.0');
define('DIGICONTENT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DIGICONTENT_PLUGIN_URL', plugin_dir_url(__FILE__));

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
        // Initialize services
        $logger = new DigiContent\Core\Services\LoggerService();
        $database = new DigiContent\Core\Database();
        $template_repository = new DigiContent\Core\Repository\TemplateRepository($database);
        $template_service = new DigiContent\Core\Services\TemplateService($template_repository, $logger);
        
        // Initialize plugin components with services
        new DigiContent\Admin\Settings($template_service, $logger);
        new DigiContent\Admin\Editor($template_service, $logger);
        
        // Load text domain
        load_plugin_textdomain('digicontent', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
    } catch (\Exception $e) {
        if (isset($logger)) {
            $logger->error('Plugin initialization error', ['error' => $e->getMessage()]);
        } else {
            error_log('DigiContent Plugin Error: ' . $e->getMessage());
        }
    }
});

// Activation hook
register_activation_hook(__FILE__, function () {
    try {
        $logger = new DigiContent\Core\Services\LoggerService();
        $logger->info('Plugin activation started');
        
        // Initialize database
        $database = new DigiContent\Core\Database();
        $database->init();
        $logger->info('Database tables created');
        
        // Add default options
        add_option('digicontent_anthropic_key', '');
        add_option('digicontent_openai_key', '');
        add_option('digicontent_settings', [
            'default_model' => 'gpt-4-turbo-preview',
            'max_tokens' => 1000,
            'temperature' => 0.7
        ]);
        
        $logger->info('Plugin activation completed successfully');
        
    } catch (\Exception $e) {
        if (isset($logger)) {
            $logger->error('Plugin activation error', ['error' => $e->getMessage()]);
        }
        error_log('DigiContent Plugin Activation Error: ' . $e->getMessage());
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, function () {
    try {
        $logger = new DigiContent\Core\Services\LoggerService();
        $logger->info('Plugin deactivation started');
        
        // Cleanup code here if needed
        
        $logger->info('Plugin deactivation completed successfully');
        
    } catch (\Exception $e) {
        if (isset($logger)) {
            $logger->error('Plugin deactivation error', ['error' => $e->getMessage()]);
        }
        error_log('DigiContent Plugin Deactivation Error: ' . $e->getMessage());
    }
});

// Initialize REST API routes
add_action('rest_api_init', function() {
    $database = new DigiContent\Core\Database();
    $logger = new DigiContent\Core\Services\LoggerService();
    $template_repository = new DigiContent\Core\Repository\TemplateRepository($database);
    $template_service = new DigiContent\Core\Services\TemplateService($template_repository, $logger);
    
    $template_controller = new DigiContent\Core\REST\TemplateController($template_service, $logger);
    $template_controller->register_routes();
});