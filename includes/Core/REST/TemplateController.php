<?php
declare(strict_types=1);

namespace DigiContent\Core\REST;

use DigiContent\Core\Services\TemplateService;
use DigiContent\Core\Services\LoggerService;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class TemplateController {
    private TemplateService $template_service;
    private LoggerService $logger;
    
    public function __construct(TemplateService $template_service, LoggerService $logger) {
        $this->template_service = $template_service;
        $this->logger = $logger;
    }
    
    public function register_routes(): void {
        // Get all templates and create new template
        register_rest_route('digicontent/v1', '/templates', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_templates'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_template'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'name' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'category' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'prompt' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'wp_kses_post',
                    ],
                    'variables' => [
                        'type' => 'array',
                        'default' => [],
                    ],
                ],
            ],
        ]);

        // Single template operations (get, update, delete)
        register_rest_route('digicontent/v1', '/templates/(?P<id>\d+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_template'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_template'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_template'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        // Generate content endpoint
        register_rest_route('digicontent/v1', '/generate', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'generate_content'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'prompt' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                    'model' => [
                        'required' => true,
                        'type' => 'string',
                        'enum' => ['gpt-4-turbo-preview', 'claude-3-sonnet'],
                    ],
                ],
            ],
        ]);
    }
    
    public function check_permission(): bool {
        return current_user_can('edit_posts');
    }
    
    public function get_templates(WP_REST_Request $request): WP_REST_Response {
        try {
            $templates = $this->template_service->getTemplates();
            return new WP_REST_Response($templates);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get templates', ['error' => $e->getMessage()]);
            return new WP_REST_Response(['message' => $e->getMessage()], 500);
        }
    }
    
    public function get_template(WP_REST_Request $request): WP_REST_Response {
        try {
            $template = $this->template_service->getTemplate((int) $request['id']);
            if (!$template) {
                return new WP_REST_Response(['message' => 'Template not found'], 404);
            }
            return new WP_REST_Response($template);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get template', [
                'error' => $e->getMessage(),
                'id' => $request['id']
            ]);
            return new WP_REST_Response(['message' => $e->getMessage()], 500);
        }
    }
    
    public function create_template(WP_REST_Request $request): WP_REST_Response {
        try {
            $template_id = $this->template_service->createTemplate($request->get_params());
            $template = $this->template_service->getTemplate($template_id);
            return new WP_REST_Response($template, 201);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create template', [
                'error' => $e->getMessage(),
                'data' => $request->get_params()
            ]);
            return new WP_REST_Response(['message' => $e->getMessage()], 500);
        }
    }
    
    public function update_template(WP_REST_Request $request): WP_REST_Response {
        try {
            $this->template_service->updateTemplate(
                (int) $request['id'],
                $request->get_params()
            );
            $template = $this->template_service->getTemplate((int) $request['id']);
            return new WP_REST_Response($template);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update template', [
                'error' => $e->getMessage(),
                'id' => $request['id'],
                'data' => $request->get_params()
            ]);
            return new WP_REST_Response(['message' => $e->getMessage()], 500);
        }
    }
    
    public function delete_template(WP_REST_Request $request): WP_REST_Response {
        try {
            $this->template_service->deleteTemplate((int) $request['id']);
            return new WP_REST_Response(null, 204);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete template', [
                'error' => $e->getMessage(),
                'id' => $request['id']
            ]);
            return new WP_REST_Response(['message' => $e->getMessage()], 500);
        }
    }
}