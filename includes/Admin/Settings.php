<?php

declare(strict_types=1);

namespace DigiContent\Admin;

use DigiContent\Core\Services\TemplateService;
use DigiContent\Core\Services\LoggerService;

class Settings {
    private string $page_title = 'DigiContent Settings';
    private string $menu_title = 'DigiContent';
    private string $capability = 'manage_options';
    private string $menu_slug = 'digicontent-settings';
    private TemplateService $template_service;
    private LoggerService $logger;

    public function __construct(TemplateService $template_service, LoggerService $logger) {
        $this->template_service = $template_service;
        $this->logger = $logger;
        
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'show_api_settings_notice']);
    }

    public function add_settings_page(): void {
        add_options_page(
            $this->page_title,
            $this->menu_title,
            $this->capability,
            $this->menu_slug,
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void {
        // Register API settings
        register_setting('digicontent_api_settings', 'digicontent_anthropic_key', [
            'sanitize_callback' => [$this, 'encrypt_api_key']
        ]);
        register_setting('digicontent_api_settings', 'digicontent_openai_key', [
            'sanitize_callback' => [$this, 'encrypt_api_key']
        ]);

        // Register API settings section
        add_settings_section(
            'digicontent_api_section',
            __('API Settings', 'digicontent'),
            [$this, 'render_api_section'],
            $this->menu_slug
        );

        add_settings_field(
            'anthropic_key',
            __('Anthropic API Key', 'digicontent'),
            [$this, 'render_api_key_field'],
            $this->menu_slug,
            'digicontent_api_section',
            ['label_for' => 'digicontent_anthropic_key']
        );

        add_settings_field(
            'openai_key',
            __('OpenAI API Key', 'digicontent'),
            [$this, 'render_api_key_field'],
            $this->menu_slug,
            'digicontent_api_section',
            ['label_for' => 'digicontent_openai_key']
        );
    }

    public function render_settings_page(): void {
        if (!current_user_can($this->capability)) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Enqueue styles and scripts
        wp_enqueue_style('digicontent-admin', plugins_url('assets/css/admin.css', dirname(__DIR__)));
        
        wp_enqueue_script('digicontent-template-manager', 
            plugins_url('assets/js/admin/template-manager.js', dirname(__DIR__)),
            ['wp-api'],
            DIGICONTENT_VERSION,
            true
        );
        
        wp_localize_script('digicontent-template-manager', 'wpApiSettings', [
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest')
        ]);

        // Render template section
        try {
            $templates = $this->template_service->getTemplates();
            $categories = [
                'blog_post' => __('Blog Post', 'digicontent'),
                'product_description' => __('Product Description', 'digicontent'),
                'news_article' => __('News Article', 'digicontent'),
                'social_media' => __('Social Media Post', 'digicontent'),
                'email' => __('Email Template', 'digicontent'),
                'seo' => __('SEO Content', 'digicontent')
            ];
            
            include dirname(__FILE__) . '/views/template-form.php';
        } catch (\Exception $e) {
            $this->logger->error('Error loading templates', ['error' => $e->getMessage()]);
            echo '<div class="notice notice-error"><p>' . esc_html__('Error loading templates. Please try again later.', 'digicontent') . '</p></div>';
        }

        // Render API settings section
        ?>
        <div class="wrap digicontent-api-settings">
              <form method="post" action="options.php">
                <?php
                settings_fields('digicontent_api_settings');
                do_settings_sections($this->menu_slug);
                submit_button(__('Save API Settings', 'digicontent'));
                ?>
            </form>
        </div>
        <?php
    }

    public function render_api_section(): void {
        echo '<p>' . esc_html__('Enter your API keys for AI content generation services.', 'digicontent') . '</p>';
    }

    public function render_api_key_field(array $args): void {
        $field_id = $args['label_for'];
        $encrypted_value = get_option($field_id);
        $value = '';
        
        if (!empty($encrypted_value)) {
            try {
                $value = \DigiContent\Core\Encryption::decrypt($encrypted_value);
            } catch (\Exception $e) {
                $this->logger->error('Failed to decrypt API key', ['error' => $e->getMessage()]);
            }
        }

        $tooltip_text = $field_id === 'digicontent_anthropic_key' 
            ? sprintf(
                __('Enter your Anthropic API key to use Claude AI for content generation. You can obtain this from %sAnthropics API key settings%s.', 'digicontent'),
                '<a href="https://console.anthropic.com/settings/keys" target="_blank">',
                '</a>'
            )
            : sprintf(
                __('Enter your OpenAI API key to use GPT models for content generation. You can obtain this from %sOpenAIs API key settings%s.', 'digicontent'),
                '<a href="https://platform.openai.com/api-keys" target="_blank">',
                '</a>'
            );
        ?>
        <div class="digicontent-api-field">
            <input type="password"
                   id="<?php echo esc_attr($field_id); ?>"
                   name="<?php echo esc_attr($field_id); ?>"
                   value="<?php echo esc_attr($value); ?>"
                   class="regular-text"
            />
            <span class="tooltip dashicons dashicons-editor-help">
                <span class="tooltip-content"><?php echo wp_kses($tooltip_text, [
                    'a' => [
                        'href' => [],
                        'target' => []
                    ]
                ]); ?></span>
            </span>
        </div>
        <?php
    }

    public function show_api_settings_notice(): void {
        if (!isset($_GET['page']) || $_GET['page'] !== $this->menu_slug) {
            return;
        }

        if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('API settings saved successfully.', 'digicontent'); ?></p>
            </div>
            <?php
        }
    }

    private function encrypt_api_key(string $value): string {
        if (empty($value)) {
            return '';
        }

        try {
            return \DigiContent\Core\Encryption::encrypt($value);
        } catch (\Exception $e) {
            $this->logger->error('Failed to encrypt API key', ['error' => $e->getMessage()]);
            return '';
        }
    }
}