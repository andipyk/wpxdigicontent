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
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($this->page_title); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('digicontent_settings');
                do_settings_sections($this->menu_slug);
                submit_button();
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
                error_log('DigiContent: Failed to decrypt API key - ' . $e->getMessage());
            }
        }
        ?>
        <input type="password"
               id="<?php echo esc_attr($field_id); ?>"
               name="<?php echo esc_attr($field_id); ?>"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
        />
        <?php
    }

    public function encrypt_api_key(string $value): string {
        if (empty($value)) {
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