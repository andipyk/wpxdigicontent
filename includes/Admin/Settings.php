<?php

declare(strict_types=1);

namespace DigiContent\Admin;

use DigiContent\Core\Services\TemplateService;
use DigiContent\Core\Services\LoggerService;
use DigiContent\Core\Services\EncryptionService;

/**
 * Handles plugin settings and admin interface.
 *
 * @since 1.0.0
 */
final class Settings {
    private string $page_title = 'DigiContent Settings';
    private string $menu_title = 'DigiContent';
    private string $capability = 'manage_options';
    private string $menu_slug  = 'digicontent-settings';
    private TemplateService $template_service;
    private LoggerService $logger;
    private EncryptionService $encryption;

    /**
     * Initialize settings page and register hooks.
     */
    public function __construct(
        TemplateService $template_service,
        LoggerService $logger,
        EncryptionService $encryption
    ) {
        $this->template_service = $template_service;
        $this->logger = $logger;
        $this->encryption = $encryption;
        
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'show_settings_notices']);
    }

    /**
     * Add settings page to WordPress admin menu.
     */
    public function add_settings_page(): void 
    {
        add_options_page(
            $this->page_title,
            $this->menu_title,
            $this->capability,
            $this->menu_slug,
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register all settings.
     */
    public function register_settings(): void 
    {
        $this->register_debug_settings();
        $this->register_api_settings();
    }

    /**
     * Register debug settings.
     */
    private const DEBUG_SETTINGS_NONCE_ACTION = 'digicontent_debug_settings';
    private const OPTION_ANTHROPIC_KEY = 'digicontent_anthropic_key';
    private const OPTION_OPENAI_KEY = 'digicontent_openai_key';
    private const OPTION_PAGE_DEBUG = 'digicontent_debug_settings';
    private const OPTION_PAGE_API = 'digicontent_api_settings';

    private function register_debug_settings(): void 
    {
        // Add nonce field to debug settings
        add_settings_field(
            'digicontent_debug_nonce',
            '',
            function() {
                wp_nonce_field(self::DEBUG_SETTINGS_NONCE_ACTION, 'digicontent_debug_nonce');
            },
            self::OPTION_PAGE_DEBUG,
            'digicontent_debug_section'
        );

        // Add debug toggle field
        add_settings_field(
            'digicontent_debug_enabled',
            __('Enable Debug Mode', 'digicontent'),
            [$this, 'render_debug_toggle_field'],
            self::OPTION_PAGE_DEBUG,
            'digicontent_debug_section'
        );

        register_setting(
            self::OPTION_PAGE_DEBUG,
            'digicontent_debug_enabled',
            [
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean',
                'validate_callback' => function($value) {
                    if (!isset($_POST['digicontent_debug_nonce']) || 
                        !wp_verify_nonce($_POST['digicontent_debug_nonce'], self::DEBUG_SETTINGS_NONCE_ACTION)) {
                        add_settings_error(
                            self::OPTION_PAGE_DEBUG,
                            'invalid_nonce',
                            __('Security check failed. Please try again.', 'digicontent')
                        );
                        return get_option('digicontent_debug_enabled');
                    }
                    return $value;
                },
            ]
        );

        add_settings_section(
            'digicontent_debug_section',
            __('Debug Settings', 'digicontent'),
            [$this, 'render_debug_section'],
            self::OPTION_PAGE_DEBUG
        );
    }

    /**
     * Register API settings.
     */
    private function register_api_settings(): void 
    {
        register_setting(
            'digicontent_api_settings',
            'digicontent_anthropic_key',
            [
                'type' => 'string',
                'sanitize_callback' => [$this, 'encrypt_api_key'],
            ]
        );

        register_setting(
            'digicontent_api_settings',
            'digicontent_openai_key',
            [
                'type' => 'string',
                'sanitize_callback' => [$this, 'encrypt_api_key'],
            ]
        );

        add_settings_section(
            'digicontent_api_section',
            __('API Settings', 'digicontent'),
            [$this, 'render_api_section'],
            'digicontent_api_settings'
        );

        add_settings_field(
            'anthropic_key',
            __('Anthropic API Key', 'digicontent'),
            [$this, 'render_api_key_field'],
            'digicontent_api_settings',
            'digicontent_api_section',
            ['label_for' => 'digicontent_anthropic_key']
        );

        add_settings_field(
            'openai_key',
            __('OpenAI API Key', 'digicontent'),
            [$this, 'render_api_key_field'],
            'digicontent_api_settings',
            'digicontent_api_section',
            ['label_for' => 'digicontent_openai_key']
        );
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page(): void 
    {
        if (!current_user_can($this->capability)) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'digicontent'));
        }

        $this->enqueue_assets();
        
        try {
            $templates = $this->template_service->getTemplates();
            $categories = $this->get_template_categories();
            
            $anthropic_key = $this->get_decrypted_key('digicontent_anthropic_key');
            $openai_key = $this->get_decrypted_key('digicontent_openai_key');
            
            include dirname(__FILE__) . '/views/template-form.php';
        } catch (\Exception $e) {
            $this->logger->error('Error loading templates', ['error' => $e->getMessage()]);
            echo '<div class="notice notice-error"><p>' . 
                esc_html__('Error loading templates. Please try again later.', 'digicontent') . 
                '</p></div>';
        }

        // Render Debug Settings
        echo '<div class="digicontent-settings-section">';
        echo '<form method="post" action="options.php" class="digicontent-debug-settings">';
        settings_fields('digicontent_debug_settings');
        do_settings_sections('digicontent_debug_settings');
        submit_button(__('Save Debug Settings', 'digicontent'));
        echo '</form>';
        echo '</div>';

        // Render API Settings
        echo '<div class="digicontent-settings-section">';
        echo '<form method="post" action="options.php" class="digicontent-api-settings">';
        settings_fields('digicontent_api_settings');
        do_settings_sections('digicontent_api_settings');
        submit_button(__('Save API Settings', 'digicontent'));
        echo '</form>';
        echo '</div>';
    }

    /**
     * Enqueue required assets.
     */
    private function enqueue_assets(): void 
    {
        // Enqueue styles
        wp_enqueue_style(
            'digicontent-admin',
            plugins_url('assets/css/admin.css', dirname(__DIR__)),
            [],
            DIGICONTENT_VERSION
        );

        // Enqueue scripts
        wp_enqueue_script(
            'digicontent-template-manager',
            plugins_url('assets/js/admin/template-manager.js', dirname(__DIR__)),
            ['jquery', 'wp-api', 'wp-api-request'],
            DIGICONTENT_VERSION,
            true
        );

        // Localize script
        wp_localize_script('digicontent-template-manager', 'digiContentSettings', [
            'root' => esc_url_raw(rest_url('digicontent/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'i18n' => [
                'confirmDelete' => __('Are you sure you want to delete this template?', 'digicontent'),
                'saving' => __('Saving...', 'digicontent'),
                'saved' => __('Template saved successfully!', 'digicontent'),
                'deleted' => __('Template deleted successfully!', 'digicontent'),
                'error' => __('An error occurred. Please try again.', 'digicontent')
            ]
        ]);
    }

    /**
     * Get available template categories.
     *
     * @return array<string, string>
     */
    private function get_template_categories(): array 
    {
        $default_categories = [
            'blog_post' => __('Blog Post', 'digicontent'),
            'product_description' => __('Product Description', 'digicontent'),
            'news_article' => __('News Article', 'digicontent'),
            'social_media' => __('Social Media Post', 'digicontent'),
            'email' => __('Email Template', 'digicontent'),
            'seo' => __('SEO Content', 'digicontent'),
        ];
        
        return apply_filters('digicontent_template_categories', $default_categories);
    }

    /**
     * Render debug settings section description.
     */
    public function render_debug_section(): void 
    {
        echo '<p>' . esc_html__('Configure debug logging settings for troubleshooting.', 'digicontent') . '</p>';
    }

    /**
     * Render debug toggle field.
     */
    public function render_debug_toggle_field(): void 
    {
        $debug_enabled = get_option('digicontent_debug_enabled', false);
        ?>
        <label class="switch">
            <input type="checkbox"
                   name="digicontent_debug_enabled"
                   <?php checked($debug_enabled); ?>
                   value="1"
            />
            <span class="slider round"></span>
        </label>
        <p class="description">
            <?php esc_html_e('Toggle debug logging for detailed operation logs.', 'digicontent'); ?>
        </p>
        <?php
    }

    /**
     * Render API settings section description.
     */
    public function render_api_section(): void 
    {
        echo '<p>' . esc_html__('Enter your API keys for AI content generation services.', 'digicontent') . '</p>';
    }

    /**
     * Render API key field.
     *
     * @param array<string, string> $args Field arguments.
     */
    public function render_api_key_field(array $args): void 
    {
        $field_id = $args['label_for'];
        $encrypted_value = get_option($field_id);
        $value = '';
        
        if (!empty($encrypted_value)) {
            try {
                $value = $this->get_decrypted_key($field_id);
            } catch (\Exception $e) {
                $this->logger->error('Failed to decrypt API key', ['error' => $e->getMessage()]);
                add_settings_error(
                    'digicontent_api_settings',
                    'invalid_key',
                    __('Stored API key is invalid. Please re-enter your key.', 'digicontent')
                );
            }
        }

        $tooltip_text = $this->get_api_key_tooltip($field_id);
        ?>
        <div class="digicontent-api-field">
            <input type="password"
                   id="<?php echo esc_attr($field_id); ?>"
                   name="<?php echo esc_attr($field_id); ?>"
                   value="<?php echo esc_attr($value); ?>"
                   class="regular-text"
            />
            <span class="tooltip dashicons dashicons-editor-help">
                <span class="tooltip-content">
                    <?php echo wp_kses(
                        $tooltip_text,
                        [
                            'a' => [
                                'href' => [],
                                'target' => [],
                            ],
                        ]
                    ); ?>
                </span>
            </span>
        </div>
        <?php
    }

    /**
     * Get tooltip text for API key field.
     *
     * @param string $field_id Field ID.
     * @return string Tooltip text with HTML.
     */
    private function get_api_key_tooltip(string $field_id): string 
    {
        if ($field_id === 'digicontent_anthropic_key') {
            /* translators: %1$s: opening link tag, %2$s: closing link tag */
            return sprintf(
                __('Enter your Anthropic API key to use Claude AI for content generation. You can obtain this from %1$sAnthropics API key settings%2$s.', 'digicontent'),
                '<a href="https://console.anthropic.com/settings/keys" target="_blank">',
                '</a>'
            );
        }

        /* translators: %1$s: opening link tag, %2$s: closing link tag */
        return sprintf(
            __('Enter your OpenAI API key to use GPT models for content generation. You can obtain this from %1$sOpenAIs API key settings%2$s.', 'digicontent'),
            '<a href="https://platform.openai.com/api-keys" target="_blank">',
            '</a>'
        );
    }

    /**
     * Show settings notices.
     */
    public function show_settings_notices(): void 
    {
        if (!isset($_GET['page']) || $_GET['page'] !== $this->menu_slug) {
            return;
        }

        // Validate debug settings
        if (isset($_POST['digicontent_debug_nonce'])) {
            $nonce = wp_unslash(sanitize_text_field($_POST['digicontent_debug_nonce']));
            if (!wp_verify_nonce($nonce, 'digicontent_debug_settings')) {
                wp_die(esc_html__('Invalid nonce verification', 'digicontent'));
            }
        }

        // Validate settings update
        if (isset($_GET['settings-updated'])) {
            $settings_updated = wp_unslash(sanitize_text_field($_GET['settings-updated']));
            $option_page = isset($_GET['option_page']) ? wp_unslash(sanitize_text_field($_GET['option_page'])) : '';
            
            if ($settings_updated === 'true' && $option_page === 'digicontent_api_settings') {
                add_settings_error(
                    'digicontent_messages',
                    'digicontent_message',
                    esc_html__('Settings Saved', 'digicontent'),
                    'updated'
                );
            }
        }
    }

    /**
     * Encrypts API key before saving to database.
     *
     * @param mixed $value The API key to encrypt.
     * @return string The encrypted API key or empty string on failure.
     */
    public function encrypt_api_key($value): string {
        if (empty($value)) {
            return '';
        }

        // Get current option name being processed
        $option_name = current_filter();
        if (!$option_name) {
            return '';
        }
        
        // Remove 'sanitize_option_' prefix to get the actual option name
        $key_type = str_replace('sanitize_option_', '', $option_name);

        // Validate Anthropic key format (starts with 'sk-ant-')
        if ($key_type === self::OPTION_ANTHROPIC_KEY && !preg_match('/^sk-ant-[A-Za-z0-9]+$/', $value)) {
            add_settings_error(
                self::OPTION_PAGE_API,
                'invalid_key_format',
                __('Invalid Anthropic API key format. Key should start with "sk-ant-".', 'digicontent')
            );
            return '';
        }

        // Validate OpenAI key format (starts with 'sk-')
        if ($key_type === self::OPTION_OPENAI_KEY && !preg_match('/^sk-[A-Za-z0-9]+$/', $value)) {
            add_settings_error(
                self::OPTION_PAGE_API,
                'invalid_key_format',
                __('Invalid OpenAI API key format. Key should start with "sk-".', 'digicontent')
            );
            return '';
        }

        $encrypted = $this->encryption->encrypt($value);
        if ($encrypted === '' && !empty($value)) {
            $this->logger->error('API key encryption failed');
            add_settings_error(
                self::OPTION_PAGE_API,
                'encryption_failed',
                __('Failed to securely store API key. Please try again.', 'digicontent')
            );
        }
        return $encrypted;
    }

    /**
     * Decrypts API key from database.
     *
     * @param string $option_name The option name.
     * @return string The decrypted API key.
     */
    public function get_decrypted_key(string $option_name): string {
        $encrypted = get_option($option_name, '');
        if (empty($encrypted)) {
            return '';
        }
        
        $decrypted = $this->encryption->decrypt($encrypted);
        if ($decrypted === '' && !empty($encrypted)) {
            $this->logger->error('API key decryption failed', ['option' => $option_name]);
            add_settings_error(
                'digicontent_api_settings',
                'decryption_failed',
                __('Failed to retrieve stored API key. Please re-enter your key.', 'digicontent')
            );
        }
        return $decrypted;
    }
}