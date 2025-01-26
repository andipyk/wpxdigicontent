<?php
if (!defined('ABSPATH')) {
    exit;
}

use DigiContent\Core\Services\TemplateService;
?>

<div class="wrap digicontent-templates-wrap">
    <h2><?php esc_html_e('Content Templates', 'digicontent'); ?></h2>
    
    <div id="template-notice" class="notice" style="display: none;">
        <p></p>
    </div>

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
                                <td><?php echo esc_html($template->name); ?></td>
                                <td><?php echo esc_html($categories[$template->category] ?? $template->category); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($template->created_at))); ?></td>
                                <td>
                                    <button class="button button-small edit-template" data-id="<?php echo esc_attr($template->id); ?>">
                                        <?php esc_html_e('Edit', 'digicontent'); ?>
                                    </button>
                                    <button class="button button-small button-link-delete delete-template" data-id="<?php echo esc_attr($template->id); ?>">
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
</div>