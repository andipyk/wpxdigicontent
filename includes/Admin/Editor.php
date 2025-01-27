<?php
declare(strict_types=1);

namespace DigiContent\Admin;

use DigiContent\Core\Services\TemplateService;
use DigiContent\Core\Services\LoggerService;
use DigiContent\Core\AIGenerator;

/**
 * Handles integration with WordPress post editor
 */
class Editor {
    private TemplateService $template_service;
    private LoggerService $logger;
    private AIGenerator $ai_generator;

    public function __construct(TemplateService $template_service, LoggerService $logger, AIGenerator $ai_generator) {
        $this->template_service = $template_service;
        $this->logger = $logger;
        $this->ai_generator = $ai_generator;
        
        $this->init();
    }

    private function init(): void {
        // Add meta box to post editor
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        
        // Register AJAX handlers
        add_action('wp_ajax_get_template_content', [$this, 'getTemplateContent']);
        add_action('wp_ajax_generate_ai_content', [$this, 'generateAIContent']);
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addMetaBox(): void {
        add_meta_box(
            'digicontent-generator',
            __('AI Content Generator', 'digicontent'),
            [$this, 'renderMetaBox'],
            'post',
            'normal',
            'high'
        );
    }

    public function renderMetaBox(): void {
        try {
            $templates = $this->template_service->getTemplates();
            include dirname(__FILE__) . '/views/editor.php';
        } catch (\Exception $e) {
            $this->logger->error('Failed to render editor meta box', ['error' => $e->getMessage()]);
            echo '<div class="notice notice-error"><p>' . 
                esc_html__('Error loading content generator. Please try again later.', 'digicontent') . 
                '</p></div>';
        }
    }

    public function enqueueAssets(string $hook): void {
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            'digicontent-admin',
            plugins_url('assets/css/admin.css', dirname(__DIR__)),
            ['dashicons'],
            DIGICONTENT_VERSION
        );

        // Enqueue scripts
        wp_enqueue_script(
            'digicontent-editor',
            plugins_url('assets/js/admin/editor.js', dirname(__DIR__)),
            ['jquery', 'wp-blocks', 'wp-data', 'wp-editor', 'wp-api', 'wp-api-request'],
            DIGICONTENT_VERSION,
            true
        );

        // Pass data to JavaScript
        wp_localize_script('digicontent-editor', 'digiContentEditor', [
            'root' => esc_url_raw(rest_url('digicontent/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'templates' => $this->getTemplatesForJs(),
            'i18n' => [
                'error' => __('An error occurred. Please try again.', 'digicontent'),
                'generating' => __('Generating...', 'digicontent'),
                'templateLoadError' => __('Failed to load template. Please try again.', 'digicontent'),
                'emptyPrompt' => __('Please enter a prompt.', 'digicontent'),
                'insertContent' => __('Insert Generated Content', 'digicontent'),
                'cancel' => __('Cancel', 'digicontent')
            ]
        ]);
    }

    /**
     * Get templates for JavaScript
     */
    private function getTemplatesForJs(): array {
        try {
            $templates = $this->template_service->getTemplates();
            return array_map(function($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'category' => $template->category,
                    'prompt' => $template->prompt,
                    'variables' => maybe_unserialize($template->variables)
                ];
            }, $templates);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get templates for editor', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * AJAX handler for getting template content
     */
    public function getTemplateContent(): void {
        try {
            check_ajax_referer('wp_rest', 'nonce');

            if (!current_user_can('edit_posts')) {
                throw new \Exception(__('You do not have permission to perform this action.', 'digicontent'));
            }

            $template_id = filter_input(INPUT_POST, 'template_id', FILTER_VALIDATE_INT);
            if (!$template_id) {
                throw new \Exception(__('Invalid template ID.', 'digicontent'));
            }

            $template = $this->template_service->getTemplate($template_id);
            if (!$template) {
                throw new \Exception(__('Template not found.', 'digicontent'));
            }

            wp_send_json_success([
                'prompt' => $template->prompt,
                'variables' => maybe_unserialize($template->variables)
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get template content', [
                'error' => $e->getMessage(),
                'template_id' => $template_id ?? null
            ]);
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX handler for generating AI content
     */
    public function generateAIContent(): void {
        try {
            check_ajax_referer('wp_rest', 'nonce');

            if (!current_user_can('edit_posts')) {
                throw new \Exception(__('You do not have permission to perform this action.', 'digicontent'));
            }

            $prompt = filter_input(INPUT_POST, 'prompt', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            if (empty($prompt)) {
                throw new \Exception(__('Prompt is required.', 'digicontent'));
            }

            $model = filter_input(INPUT_POST, 'model', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            if (empty($model)) {
                throw new \Exception(__('AI model is required.', 'digicontent'));
            }

            $variables = filter_input(INPUT_POST, 'variables', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?: [];
            $variables = array_map('sanitize_text_field', $variables);

            // Replace variables in prompt
            foreach ($variables as $key => $value) {
                $prompt = str_replace('((' . $key . '))', $value, $prompt);
            }

            $content = $this->ai_generator->generate($prompt, $model);
            if (empty($content)) {
                throw new \Exception(__('Failed to generate content. Please try again.', 'digicontent'));
            }

            wp_send_json_success(['content' => $content]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate content', [
                'error' => $e->getMessage(),
                'prompt' => $prompt ?? null,
                'model' => $model ?? null,
                'variables' => $variables ?? null
            ]);
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
} 