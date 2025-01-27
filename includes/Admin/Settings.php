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
    private function register_debug_settings(): void 
    {
        register_setting(
            'digicontent_debug_settings',
            'digicontent_debug_enabled',
            [
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ]
        );

        add_settings_section(
            'digicontent_debug_section',
            __('Debug Settings', 'digicontent'),
            [$this, 'render_debug_section'],
            'digicontent_debug_settings'
        );

        add_settings_field(
            'debug_enabled',
            __('Enable Debug Logging', 'digicontent'),
            [$this, 'render_debug_toggle_field'],
            'digicontent_debug_settings',
            'digicontent_debug_section'
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
            wp_die(__('You do not have sufficient permissions to access this page.'));
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
        echo '<h2>' . esc_html__('Debug Settings', 'digicontent') . '</h2>';
        echo '<form method="post" action="options.php" class="digicontent-debug-settings">';
        settings_fields('digicontent_debug_settings');
        do_settings_sections('digicontent_debug_settings');
        submit_button(__('Save Debug Settings', 'digicontent'));
        echo '</form>';
        echo '</div>';

        // Render API Settings
        echo '<div class="digicontent-settings-section">';
        echo '<h2>' . esc_html__('API Settings', 'digicontent') . '</h2>';
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
        wp_enqueue_style(
            'digicontent-admin',
            plugins_url('assets/css/admin.css', dirname(__DIR__))
        );
        
        wp_enqueue_script(
            'digicontent-template-manager',
            plugins_url('assets/js/admin/template-manager.js', dirname(__DIR__)),
            ['wp-api'],
            DIGICONTENT_VERSION,
            true
        );
        
        wp_localize_script(
            'digicontent-template-manager',
            'wpApiSettings',
            [
                'root' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
            ]
        );
    }

    /**
     * Get available template categories.
     *
     * @return array<string, string>
     */
    private function get_template_categories(): array 
    {
        return [
            'blog_post' => __('Blog Post', 'digicontent'),
            'product_description' => __('Product Description', 'digicontent'),
            'news_article' => __('News Article', 'digicontent'),
            'social_media' => __('Social Media Post', 'digicontent'),
            'email' => __('Email Template', 'digicontent'),
            'seo' => __('SEO Content', 'digicontent'),
        ];
    }

    /**
     * Render debug settings section description.
     */
    public function render_debug_section(): void 
    {
        echo '<p>' . 
            esc_html__('Configure debug logging settings for troubleshooting.', 'digicontent') . 
            '</p>';
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
        echo '<p>' . 
            esc_html__('Enter your API keys for AI content generation services.', 'digicontent') . 
            '</p>';
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
            return sprintf(
                __('Enter your Anthropic API key to use Claude AI for content generation. You can obtain this from %sAnthropics API key settings%s.', 'digicontent'),
                '<a href="https://console.anthropic.com/settings/keys" target="_blank">',
                '</a>'
            );
        }

        return sprintf(
            __('Enter your OpenAI API key to use GPT models for content generation. You can obtain this from %sOpenAIs API key settings%s.', 'digicontent'),
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

        if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
            $option_page = $_GET['option_page'] ?? '';
            $message = match ($option_page) {
                'digicontent_debug_settings' => __('Debug settings saved successfully.', 'digicontent'),
                'digicontent_api_settings' => __('API settings saved successfully.', 'digicontent'),
                default => __('Settings saved successfully.', 'digicontent'),
            };
            
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Encrypts API key before saving to database.
     *
     * @param mixed $value The API key to encrypt.
     * @return string The encrypted API key or empty string on failure.
     */
    public function encrypt_api_key($value): string {
        $encrypted = $this->encryption->encrypt($value);
        if ($encrypted === '' && !empty($value)) {
            $this->logger->error('API key encryption failed');
            add_settings_error(
                'digicontent_api_settings',
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