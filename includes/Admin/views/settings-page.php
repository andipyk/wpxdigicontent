<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <?php if (isset($templates) && isset($categories)): ?>
        <?php include 'template-form.php'; ?>
    <?php endif; ?>

    <div class="digicontent-settings-section">
        <h2><?php echo esc_html__('Debug Settings', 'digicontent'); ?></h2>
        <form method="post" action="options.php" class="digicontent-debug-settings">
            <?php
            settings_fields('digicontent_debug_settings');
            do_settings_sections('digicontent_debug_settings');
            submit_button(__('Save Debug Settings', 'digicontent'));
            ?>
        </form>
    </div>

    <div class="digicontent-settings-section">
        <h2><?php echo esc_html__('API Settings', 'digicontent'); ?></h2>
        <form method="post" action="options.php" class="digicontent-api-settings">
            <?php
            settings_fields('digicontent_api_settings');
            do_settings_sections('digicontent_api_settings');
            submit_button(__('Save API Settings', 'digicontent'));
            ?>
        </form>
    </div>
</div>