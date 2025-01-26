<?php

declare(strict_types=1);

namespace DigiContent\Admin;

class Settings {
    private string $page_title = 'DigiContent Settings';
    private string $menu_title = 'DigiContent';
    private string $capability = 'manage_options';
    private string $menu_slug = 'digicontent-settings';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
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
        register_setting('digicontent_settings', 'digicontent_anthropic_key', [
            'sanitize_callback' => [$this, 'encrypt_api_key']
        ]);
        register_setting('digicontent_settings', 'digicontent_openai_key', [
            'sanitize_callback' => [$this, 'encrypt_api_key']
        ]);
        register_setting('digicontent_settings', 'digicontent_settings');

        // Register template management section
        add_settings_section(
            'digicontent_templates_section',
            __('Content Templates', 'digicontent'),
            [$this, 'render_templates_section'],
            $this->menu_slug
        );

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
        wp_enqueue_style('digicontent-settings', plugins_url('assets/css/settings.css', dirname(__DIR__)));
        wp_enqueue_style('wp-jquery-ui-dialog');
        
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('digicontent-template-editor', 
            plugins_url('assets/js/template-editor.js', dirname(__DIR__)),
            ['jquery', 'jquery-ui-dialog', 'wp-api'],
            '1.0.0',
            true
        );
        
        wp_localize_script('digicontent-template-editor', 'wpApiSettings', [
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest')
        ]);
        ?>
        <div class="wrap digicontent-settings-wrap">
            <h1><?php echo esc_html($this->page_title); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('digicontent_settings');
                do_settings_sections($this->menu_slug);
                submit_button();
                submit_button(__('Reset to Defaults', 'digicontent'), 'secondary button-reset digicontent-reset-button', 'reset_settings', false);
                ?>
            </form>
        </div>
        <?php
    }

    public function render_api_section(): void {
        echo '<p>' . esc_html__('Enter your API keys for AI content generation services.', 'digicontent') . '</p>';
    }

    public function render_templates_section(): void {
        $template_manager = new \DigiContent\Core\TemplateManager();
        $templates = get_option('digicontent_templates', []);
        $categories = $template_manager->get_categories();
        ?>
        <div class="digicontent-templates-wrapper">
            <p><?php esc_html_e('Manage your content templates here. These templates can be used to generate AI content with custom variables.', 'digicontent'); ?></p>
            
            <div class="digicontent-templates-list">
                <?php if (empty($templates)): ?>
                    <p><?php esc_html_e('No templates found. Create your first template to get started.', 'digicontent'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Name', 'digicontent'); ?></th>
                                <th><?php esc_html_e('Category', 'digicontent'); ?></th>
                                <th><?php esc_html_e('Created', 'digicontent'); ?></th>
                                <th><?php esc_html_e('Actions', 'digicontent'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td><?php echo esc_html($template['name']); ?></td>
                                    <td><?php echo esc_html($categories[$template['category']] ?? $template['category']); ?></td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($template['created_at']))); ?></td>
                                    <td>
                                        <button class="button button-small edit-template" data-id="<?php echo esc_attr($template['id']); ?>">
                                            <?php esc_html_e('Edit', 'digicontent'); ?>
                                        </button>
                                        <button class="button button-small button-link-delete delete-template" data-id="<?php echo esc_attr($template['id']); ?>">
                                            <?php esc_html_e('Delete', 'digicontent'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="digicontent-template-form">
                <h3><?php esc_html_e('Add New Template', 'digicontent'); ?></h3>
                <form id="digicontent-new-template-form">
                    <p>
                        <label for="template-name"><?php esc_html_e('Template Name', 'digicontent'); ?></label>
                        <input type="text" id="template-name" name="name" class="regular-text" required>
                    </p>
                    <p>
                        <label for="template-category"><?php esc_html_e('Category', 'digicontent'); ?></label>
                        <select id="template-category" name="category" required>
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <div class="prompt-template-wrapper">
                        <div class="prompt-header">
                            <label for="template-prompt"><?php esc_html_e('Prompt Template', 'digicontent'); ?></label>
                            <span class="tooltip dashicons dashicons-editor-help">
                                <span class="tooltip-content">
                                    <?php esc_html_e('Use ((variable)) syntax to add dynamic variables to your template. Click the Insert Variable button or type (( to see available variables.', 'digicontent'); ?>
                                </span>
                            </span>
                        </div>
                        <div class="prompt-editor">
                            <textarea id="template-prompt" name="prompt" class="large-text" rows="5" required
                                placeholder="<?php esc_attr_e('Write a blog post about ((topic)) with ((tone)) tone...', 'digicontent'); ?>"></textarea>
                            <button type="button" class="button insert-variable-button">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <?php esc_html_e('Insert Variable', 'digicontent'); ?>
                            </button>
                        </div>
                        <div class="variable-preview" style="display: none;">
                            <h4><?php esc_html_e('Preview', 'digicontent'); ?></h4>
                            <div class="preview-content"></div>
                        </div>
                        <div class="common-variables">
                            <h4><?php esc_html_e('Common Variables', 'digicontent'); ?></h4>
                            <div class="variable-list">
                                <span class="variable-chip" data-variable="topic" title="<?php esc_attr_e('Main topic or subject of the content', 'digicontent'); ?>">((topic))</span>
                                <span class="variable-chip" data-variable="tone" title="<?php esc_attr_e('Writing tone (e.g., professional, casual)', 'digicontent'); ?>">((tone))</span>
                                <span class="variable-chip" data-variable="length" title="<?php esc_attr_e('Content length (e.g., short, long)', 'digicontent'); ?>">((length))</span>
                                <span class="variable-chip" data-variable="style" title="<?php esc_attr_e('Writing style (e.g., informative, persuasive)', 'digicontent'); ?>">((style))</span>
                                <span class="variable-chip" data-variable="keywords" title="<?php esc_attr_e('Target keywords to include', 'digicontent'); ?>">((keywords))</span>
                            </div>
                        </div>
                    </div>
                    <p>
                        <button type="submit" class="button button-primary"><?php esc_html_e('Save Template', 'digicontent'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    public function render_api_key_field(array $args): void {
        $field_id = $args['label_for'];
        $encrypted_value = get_option($field_id);
        $value = '';
        
        if (!empty($encrypted_value)) {
            try {
                $value = \DigiContent\Core\Encryption::decrypt($encrypted_value);
            } catch (\Exception $e) {
                error_log('DigiContent: Failed to decrypt API key - ' . $e->getMessage());
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

    public function encrypt_api_key($value): string {
        if (empty($value) || !is_string($value)) {
            return '';
        }

        try {
            return \DigiContent\Core\Encryption::encrypt($value);
        } catch (\Exception $e) {
            error_log('DigiContent: Failed to encrypt API key - ' . $e->getMessage());
            return '';
        }
    }
}