<?php

declare(strict_types=1);

namespace DigiContent\Admin;

class PostEditor {
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('wp_ajax_generate_ai_content', [$this, 'generate_ai_content']);
    }

    public function enqueue_scripts(string $hook): void {
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }

        wp_enqueue_script(
            'digicontent-editor',
            DIGICONTENT_PLUGIN_URL . 'assets/js/editor.js',
            ['jquery'],
            DIGICONTENT_VERSION,
            true
        );

        wp_localize_script('digicontent-editor', 'digiContentEditor', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('digicontent_nonce'),
            'generating' => __('Generating content...', 'digicontent'),
            'error' => __('Error generating content. Please try again.', 'digicontent')
        ]);
    }

    public function add_meta_box(): void {
        add_meta_box(
            'digicontent-generator',
            __('AI Content Generator', 'digicontent'),
            [$this, 'render_meta_box'],
            'post',
            'side',
            'high'
        );
    }

    public function render_meta_box(): void {
        $settings = get_option('digicontent_settings', []);
        ?>
        <div class="digicontent-generator-box">
            <p>
                <label for="digicontent-prompt"><?php esc_html_e('Content Prompt:', 'digicontent'); ?></label>
                <textarea id="digicontent-prompt" class="widefat" rows="3"></textarea>
            </p>
            <p>
                <label for="digicontent-model"><?php esc_html_e('AI Model:', 'digicontent'); ?></label>
                <select id="digicontent-model" class="widefat digicontent-select">
                    <option value="gpt-4-turbo-preview"><?php esc_html_e('GPT-4', 'digicontent'); ?></option>
                    <option value="claude-3-sonnet"><?php esc_html_e('Claude', 'digicontent'); ?></option>
                </select>
            </p>
            <p>
                <button type="button" id="digicontent-generate" class="button button-primary">
                    <?php esc_html_e('Generate Content', 'digicontent'); ?>
                </button>
            </p>
        </div>
        <?php
    }

    public function generate_ai_content(): void {
        check_ajax_referer('digicontent_nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        $model = sanitize_text_field($_POST['model'] ?? 'gpt-4');

        if (empty($prompt)) {
            wp_send_json_error('Prompt is required');
        }

        try {
            $generator = new \DigiContent\Core\AIGenerator();
            $content = $generator->generate($prompt, $model);
            wp_send_json_success(['content' => $content]);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
}