<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="digicontent-settings">
        <div class="digicontent-settings-section">
            <h2><?php echo esc_html__('Content Templates', 'digicontent'); ?></h2>
            
            <?php if (!empty($templates)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Name', 'digicontent'); ?></th>
                            <th><?php echo esc_html__('Category', 'digicontent'); ?></th>
                            <th><?php echo esc_html__('Actions', 'digicontent'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $template): ?>
                            <tr>
                                <td><?php echo esc_html($template->name); ?></td>
                                <td><?php echo esc_html($template->category); ?></td>
                                <td>
                                    <button class="button edit-template" 
                                            data-id="<?php echo esc_attr($template->id); ?>"
                                            data-name="<?php echo esc_attr($template->name); ?>"
                                            data-category="<?php echo esc_attr($template->category); ?>"
                                            data-prompt="<?php echo esc_attr($template->prompt); ?>">
                                        <?php echo esc_html__('Edit', 'digicontent'); ?>
                                    </button>
                                    <button class="button delete-template" 
                                            data-id="<?php echo esc_attr($template->id); ?>">
                                        <?php echo esc_html__('Delete', 'digicontent'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php echo esc_html__('No templates found.', 'digicontent'); ?></p>
            <?php endif; ?>

            <button class="button button-primary add-template">
                <?php echo esc_html__('Add New Template', 'digicontent'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Template Form Modal -->
<div id="template-modal" class="digicontent-modal" style="display: none;">
    <div class="digicontent-modal-content">
        <span class="close">&times;</span>
        <h2 id="modal-title"><?php echo esc_html__('Add New Template', 'digicontent'); ?></h2>
        
        <form id="template-form">
            <input type="hidden" id="template-id" name="id" value="">
            
            <div class="form-field">
                <label for="template-name"><?php echo esc_html__('Template Name', 'digicontent'); ?></label>
                <input type="text" id="template-name" name="name" required>
            </div>
            
            <div class="form-field">
                <label for="template-category"><?php echo esc_html__('Category', 'digicontent'); ?></label>
                <select id="template-category" name="category" required>
                    <?php foreach ($categories as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>">
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-field">
                <label for="template-prompt"><?php echo esc_html__('Prompt Template', 'digicontent'); ?></label>
                <textarea id="template-prompt" name="prompt" required></textarea>
                <p class="description">
                    <?php echo esc_html__('Use ((variable_name)) syntax for variables that will be replaced with user input.', 'digicontent'); ?>
                </p>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="button button-primary">
                    <?php echo esc_html__('Save Template', 'digicontent'); ?>
                </button>
                <button type="button" class="button cancel-template">
                    <?php echo esc_html__('Cancel', 'digicontent'); ?>
                </button>
            </div>
        </form>
    </div>
</div> 