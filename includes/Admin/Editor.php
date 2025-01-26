<?php
declare(strict_types=1);

namespace DigiContent\Admin;

use DigiContent\Core\Services\TemplateService;
use DigiContent\Core\Services\LoggerService;

/**
 * Handles integration with WordPress post editor
 */
class Editor {
    private TemplateService $template_service;
    private LoggerService $logger;

    public function __construct(TemplateService $template_service, LoggerService $logger) {
        $this->template_service = $template_service;
        $this->logger = $logger;
        
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
            ['wp-blocks', 'wp-data', 'wp-editor'],
            DIGICONTENT_VERSION,
            true
        );

        // Pass data to JavaScript
        wp_localize_script('digicontent-editor', 'digiContentEditor', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('digicontent_nonce'),
            'i18n' => [
                'error' => __('An error occurred. Please try again.', 'digicontent'),
                'generating' => __('Generating...', 'digicontent'),
                'templateLoadError' => __('Failed to load template. Please try again.', 'digicontent'),
                'emptyPrompt' => __('Please enter a prompt.', 'digicontent')
            ]
        ]);
    }

    public function getTemplateContent(): void {
        try {
            if (!check_ajax_referer('digicontent_nonce', 'nonce', false)) {
                throw new \Exception('Invalid security token');
            }

            if (!current_user_can('edit_posts')) {
                throw new \Exception('Insufficient permissions');
            }

            $template_id = filter_input(INPUT_POST, 'template_id', FILTER_SANITIZE_NUMBER_INT);
            if (!$template_id) {
                throw new \Exception('Template ID is required');
            }

            $this->logger->info('Loading template content', ['template_id' => $template_id]);
            
            $template = $this->template_service->getTemplate((int) $template_id);
            if (!$template) {
                throw new \Exception('Template not found');
            }

            wp_send_json_success(['prompt' => $template->prompt]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get template content', [
                'error' => $e->getMessage(),
                'template_id' => $template_id ?? null,
                'user_id' => get_current_user_id()
            ]);
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }

    public function generateAIContent(): void {
        try {
            check_ajax_referer('digicontent_nonce');

            if (!current_user_can('edit_posts')) {
                throw new \Exception('Insufficient permissions');
            }

            $prompt = filter_input(INPUT_POST, 'prompt', FILTER_SANITIZE_STRING);
            $model = filter_input(INPUT_POST, 'model', FILTER_SANITIZE_STRING);

            if (!$prompt) {
                throw new \Exception('Prompt is required');
            }

            if (!in_array($model, ['gpt-4-turbo-preview', 'claude-3-sonnet'])) {
                throw new \Exception('Invalid AI model');
            }

            // Generate content using AI service
            $content = $this->template_service->generateContent($prompt, $model);
            
            wp_send_json_success(['content' => $content]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate content', [
                'error' => $e->getMessage(),
                'prompt' => $prompt ?? null,
                'model' => $model ?? null
            ]);
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
} 