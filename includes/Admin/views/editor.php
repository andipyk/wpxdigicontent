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
        
        <div class="digicontent-field">
            <label for="digicontent-prompt"><?php esc_html_e('Content Prompt', 'digicontent'); ?></label>
            <textarea id="digicontent-prompt" class="widefat" rows="4" placeholder="<?php esc_attr_e('Enter your content prompt here...', 'digicontent'); ?>"></textarea>
            <p class="description"><?php esc_html_e('You can use variables like {title}, {excerpt}, {category} in your prompt.', 'digicontent'); ?></p>
        </div>
        
        <div class="digicontent-field">
            <label for="digicontent-model"><?php esc_html_e('AI Model', 'digicontent'); ?></label>
            <select id="digicontent-model" class="widefat">
                <option value="gpt-4-turbo-preview"><?php esc_html_e('GPT-4 Turbo', 'digicontent'); ?></option>
                <option value="claude-3-sonnet"><?php esc_html_e('Claude 3 Sonnet', 'digicontent'); ?></option>
            </select>
        </div>
        
        <div class="digicontent-actions">
            <button type="button" id="digicontent-generate" class="button button-primary">
                <?php esc_html_e('Generate Content', 'digicontent'); ?>
            </button>
        </div>
    </div>
</div> 