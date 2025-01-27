<?php defined('ABSPATH') || exit; ?>

<div class="wrap digicontent-templates-wrap">
    <h1><?php esc_html_e('Content Templates', 'digicontent'); ?></h1>

    <div id="template-notice" class="notice" style="display: none;">
        <p></p>
    </div>

    <div class="digicontent-templates-wrapper">
        <p class="description"><?php esc_html_e('Manage your content templates here. These templates can be used to generate AI content with custom variables.', 'digicontent'); ?></p>

        <?php if (!empty($templates)): ?>
            <div class="digicontent-templates-list">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Name', 'digicontent'); ?></th>
                            <th scope="col"><?php esc_html_e('Category', 'digicontent'); ?></th>
                            <th scope="col"><?php esc_html_e('Created', 'digicontent'); ?></th>
                            <th scope="col"><?php esc_html_e('Actions', 'digicontent'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $template): ?>
                            <tr>
                                <td><?php echo esc_html($template->name); ?></td>
                                <td><?php echo esc_html($categories[$template->category] ?? $template->category); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($template->created_at))); ?></td>
                                <td class="template-actions">
                                    <button type="button" class="button button-small edit-template" data-id="<?php echo esc_attr($template->id); ?>">
                                        <?php esc_html_e('Edit', 'digicontent'); ?>
                                    </button>
                                    <button type="button" class="button button-small button-link-delete delete-template" data-id="<?php echo esc_attr($template->id); ?>">
                                        <?php esc_html_e('Delete', 'digicontent'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="notice notice-info">
                <p><?php esc_html_e('No templates found. Create your first template below.', 'digicontent'); ?></p>
            </div>
        <?php endif; ?>

        <div class="digicontent-template-form">
            <h2><?php esc_html_e('Add New Template', 'digicontent'); ?></h2>
            <form id="digicontent-new-template-form" class="template-form" novalidate>
                <?php wp_nonce_field('digicontent_template_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="template-name"><?php esc_html_e('Template Name', 'digicontent'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="template-name" name="name" class="regular-text" required>
                            <p class="description"><?php esc_html_e('Enter a descriptive name for your template.', 'digicontent'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="template-category"><?php esc_html_e('Category', 'digicontent'); ?></label>
                        </th>
                        <td>
                            <select id="template-category" name="category" class="regular-text" required>
                                <option value=""><?php esc_html_e('Select category...', 'digicontent'); ?></option>
                                <?php foreach ($categories as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Choose the type of content this template will generate.', 'digicontent'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="template-prompt"><?php esc_html_e('Prompt Template', 'digicontent'); ?></label>
                        </th>
                        <td>
                            <div class="prompt-editor">
                                <textarea id="template-prompt" name="prompt" class="large-text code" rows="10" required></textarea>
                                <div class="variable-tools">
                                    <button type="button" class="button insert-variable">
                                        <span class="dashicons dashicons-plus-alt2"></span>
                                        <?php esc_html_e('Insert Variable', 'digicontent'); ?>
                                    </button>
                                    <div class="variable-list" style="display: none;">
                                        <div class="variable-group">
                                            <h4><?php esc_html_e('Post Variables', 'digicontent'); ?></h4>
                                            <button type="button" class="variable-item" data-variable="post_title">((post_title))</button>
                                            <button type="button" class="variable-item" data-variable="post_excerpt">((post_excerpt))</button>
                                            <button type="button" class="variable-item" data-variable="post_category">((post_category))</button>
                                            <button type="button" class="variable-item" data-variable="post_tags">((post_tags))</button>
                                        </div>
                                        <div class="variable-group">
                                            <h4><?php esc_html_e('Content Variables', 'digicontent'); ?></h4>
                                            <button type="button" class="variable-item" data-variable="topic">((topic))</button>
                                            <button type="button" class="variable-item" data-variable="tone">((tone))</button>
                                            <button type="button" class="variable-item" data-variable="keywords">((keywords))</button>
                                            <button type="button" class="variable-item" data-variable="length">((length))</button>
                                            <button type="button" class="variable-item" data-variable="style">((style))</button>
                                        </div>
                                    </div>
                                </div>
                                <p class="description">
                                    <?php esc_html_e('Write your prompt template. Use variables like ((topic)) or ((keywords)) that will be replaced with actual values when generating content.', 'digicontent'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Save Template', 'digicontent'); ?></button>
                </p>
            </form>
        </div>
    </div>
</div>