<?php
/**
 * Editor integration view
 * 
 * @package DigiContent
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="digicontent-editor-wrapper">
    <div id="digicontent-notifications"></div>
    
    <div class="digicontent-editor">
        <?php wp_nonce_field('digicontent_editor_action', 'digicontent_editor_nonce'); ?>
        
        <div class="digicontent-field">
            <label class="toggle-switch">
                <input type="checkbox" id="use-template-toggle" checked>
                <span class="slider round"></span>
                <?php esc_html_e('Use Template', 'digicontent'); ?>
            </label>
        </div>

        <div id="template-section">
            <div class="digicontent-field">
                <label for="digicontent-template"><?php esc_html_e('Select Template', 'digicontent'); ?></label>
                <select id="digicontent-template" class="widefat">
                    <option value=""><?php esc_html_e('Choose a template...', 'digicontent'); ?></option>
                    <?php foreach ($templates as $template): ?>
                        <option value="<?php echo esc_attr($template->id); ?>">
                            <?php echo esc_html($template->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="template-variables" class="digicontent-field" style="display: none;">
                <h3><?php esc_html_e('Template Variables', 'digicontent'); ?></h3>
                <div class="variable-fields">
                    <!-- Variable fields will be dynamically added here -->
                </div>
            </div>
            
            <div class="digicontent-field">
                <label for="digicontent-prompt"><?php esc_html_e('Content Prompt', 'digicontent'); ?></label>
                <textarea id="digicontent-prompt" class="widefat" rows="4" readonly></textarea>
                <p class="description"><?php esc_html_e('This prompt will be used to generate your content. Fill in the variables above to customize it.', 'digicontent'); ?></p>
            </div>
        </div>

        <div id="direct-prompt-section" style="display: none;">
            <div class="digicontent-field">
                <label for="digicontent-direct-prompt"><?php esc_html_e('Direct Prompt', 'digicontent'); ?></label>
                <textarea id="digicontent-direct-prompt" class="widefat" rows="4"></textarea>
                <p class="description"><?php esc_html_e('Enter your prompt directly to generate content without using a template.', 'digicontent'); ?></p>
            </div>
        </div>
        
        <div class="digicontent-field">
            <label for="digicontent-model"><?php esc_html_e('AI Model', 'digicontent'); ?></label>
            <select id="digicontent-model" class="widefat">
                <option value="gpt-4-turbo-preview"><?php esc_html_e('GPT-4 Turbo', 'digicontent'); ?></option>
                <option value="claude-3-sonnet"><?php esc_html_e('Claude 3 Sonnet', 'digicontent'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('Select the AI model to use for content generation.', 'digicontent'); ?></p>
        </div>
        
        <div class="digicontent-actions">
            <button type="button" id="digicontent-generate" class="button button-primary" data-loading-text="<?php esc_attr_e('Generating...', 'digicontent'); ?>">
                <span class="button-text"><?php esc_html_e('Generate Content', 'digicontent'); ?></span>
                <span class="spinner"></span>
            </button>
        </div>
    </div>
</div>