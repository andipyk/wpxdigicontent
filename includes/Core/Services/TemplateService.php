<?php
declare(strict_types=1);

namespace DigiContent\Core\Services;

use DigiContent\Core\Database;
use DigiContent\Core\Repository\TemplateRepository;
use DigiContent\Core\Exceptions\TemplateException;

class TemplateService {
    private TemplateRepository $template_repository;
    private LoggerService $logger;
    
    public function __construct(
        TemplateRepository $template_repository = null,
        LoggerService $logger = null
    ) {
        $this->template_repository = $template_repository ?? new TemplateRepository(new Database());
        $this->logger = $logger ?? new LoggerService();
        $this->ensureTableExists();
    }
    
    private function ensureTableExists(): void {
        try {
            global $wpdb;
            $table = $this->template_repository->getTableName();
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                $this->logger->info('Templates table does not exist, creating it now');
                $database = new Database();
                $database->init();
                
                // Verify table was created
                if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                    throw new TemplateException('Failed to create templates table');
                }
                
                $this->logger->info('Templates table created successfully');
            }
        } catch (\Exception $e) {
            $this->logger->error('Error ensuring templates table exists', [
                'error' => $e->getMessage()
            ]);
            throw new TemplateException('Database initialization failed: ' . $e->getMessage());
        }
    }
    
    public function createTemplate(array $data): int {
        try {
            $this->logger->info('Creating new template', ['data' => $data]);
            
            $template_id = $this->template_repository->create([
                'name' => sanitize_text_field($data['name']),
                'category' => sanitize_text_field($data['category']),
                'prompt' => wp_kses_post($data['prompt']),
                'variables' => maybe_serialize($data['variables'] ?? [])
            ]);
            
            if ($template_id === false) {
                throw new TemplateException('Failed to create template');
            }
            
            $this->logger->info('Template created successfully', ['template_id' => $template_id]);
            return $template_id;
            
        } catch (\Exception $e) {
            $this->logger->error('Error creating template', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw new TemplateException('Failed to create template: ' . $e->getMessage());
        }
    }
    
    public function getTemplates(array $args = []): array {
        try {
            $this->ensureTableExists();
            $this->logger->info('Fetching templates', ['args' => $args]);
            return $this->template_repository->get_all($args);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching templates', [
                'error' => $e->getMessage(),
                'args' => $args
            ]);
            throw new TemplateException('Failed to fetch templates: ' . $e->getMessage());
        }
    }
    
    public function getTemplate(int $id): ?object {
        try {
            $this->ensureTableExists();
            $this->logger->info('Fetching template', ['id' => $id]);
            $template = $this->template_repository->get($id);
            
            if (!$template) {
                throw new TemplateException('Template not found');
            }
            
            return $template;
        } catch (\Exception $e) {
            $this->logger->error('Error fetching template', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            throw new TemplateException('Failed to fetch template: ' . $e->getMessage());
        }
    }
    
    public function updateTemplate(int $id, array $data): bool {
        try {
            $this->logger->info('Updating template', ['id' => $id, 'data' => $data]);
            
            $result = $this->template_repository->update($id, [
                'name' => sanitize_text_field($data['name']),
                'category' => sanitize_text_field($data['category']),
                'prompt' => wp_kses_post($data['prompt']),
                'variables' => maybe_serialize($data['variables'] ?? [])
            ]);
            
            if (!$result) {
                throw new TemplateException('Failed to update template');
            }
            
            $this->logger->info('Template updated successfully', ['template_id' => $id]);
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Error updating template', [
                'error' => $e->getMessage(),
                'id' => $id,
                'data' => $data
            ]);
            throw new TemplateException('Failed to update template: ' . $e->getMessage());
        }
    }
    
    public function deleteTemplate(int $id): bool {
        try {
            $this->logger->info('Deleting template', ['id' => $id]);
            
            $result = $this->template_repository->delete($id);
            
            if (!$result) {
                throw new TemplateException('Failed to delete template');
            }
            
            $this->logger->info('Template deleted successfully', ['template_id' => $id]);
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Error deleting template', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            throw new TemplateException('Failed to delete template: ' . $e->getMessage());
        }
    }

    public function generateContent(string $prompt, string $model): string {
        try {
            $this->logger->info('Generating AI content', [
                'prompt' => $prompt,
                'model' => $model
            ]);

            // TODO: Implement actual AI content generation
            // For now, return a placeholder
            return sprintf(
                "Generated content for prompt: %s using model: %s\n\nThis is a placeholder. Implement actual AI integration.",
                $prompt,
                $model
            );
            
        } catch (\Exception $e) {
            $this->logger->error('Error generating content', [
                'error' => $e->getMessage(),
                'prompt' => $prompt,
                'model' => $model
            ]);
            throw new TemplateException('Failed to generate content: ' . $e->getMessage());
        }
    }
} 