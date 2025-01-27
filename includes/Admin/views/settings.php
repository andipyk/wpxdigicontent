<?php defined('ABSPATH') || exit; ?>
<?php
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'digicontent'));
}
?>

<div class="wrap digicontent-settings-wrap">
    <h1><?php esc_html_e('DigiContent Settings', 'digicontent'); ?></h1>
    
    <div class="digicontent-api-settings">
        <h2><?php esc_html_e('API Settings', 'digicontent'); ?></h2>
        <p class="description"><?php esc_html_e('Enter your API keys for AI content generation services.', 'digicontent'); ?></p>
        
        <form method="post" action="options.php">
            <?php settings_fields('digicontent_api_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="digicontent_anthropic_key">
                            <?php esc_html_e('Anthropic API Key', 'digicontent'); ?>
                            <span class="tooltip-wrap">
                                <span class="dashicons dashicons-editor-help tooltip-icon"></span>
                                <span class="tooltip-content">
                                    <?php esc_html_e('Enter your Anthropic API key to use Claude AI for content generation. You can obtain this from Anthropic\'s API key settings.', 'digicontent'); ?>
                                </span>
                            </span>
                        </label>
                    </th>
                    <td>
                        <input type="password" 
                            id="digicontent_anthropic_key" 
                            name="digicontent_anthropic_key" 
                            class="regular-text"
                            value="<?php echo esc_attr(get_option('digicontent_anthropic_key')); ?>"
                            autocomplete="new-password"
                        >
                        <p class="description">
                            <?php printf(
                                /* translators: %s: URL to Anthropic's API key settings */
                                esc_html__('Get your API key from %s', 'digicontent'),
                                '<a href="https://console.anthropic.com/account/keys" target="_blank">Anthropic Console</a>'
                            ); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="digicontent_openai_key">
                            <?php esc_html_e('OpenAI API Key', 'digicontent'); ?>
                            <span class="tooltip-wrap">
                                <span class="dashicons dashicons-editor-help tooltip-icon"></span>
                                <span class="tooltip-content">
                                    <?php esc_html_e('Enter your OpenAI API key to use GPT-4 for content generation. You can obtain this from OpenAI\'s API key settings.', 'digicontent'); ?>
                                </span>
                            </span>
                        </label>
                    </th>
                    <td>
                        <input type="password" 
                            id="digicontent_openai_key" 
                            name="digicontent_openai_key" 
                            class="regular-text"
                            value="<?php echo esc_attr(get_option('digicontent_openai_key')); ?>"
                            autocomplete="new-password"
                        >
                        <p class="description">
                            <?php printf(
                                /* translators: %s: URL to OpenAI's API key settings */
                                esc_html__('Get your API key from %s', 'digicontent'),
                                '<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Dashboard</a>'
                            ); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Save API Settings', 'digicontent')); ?>
        </form>
    </div>
</div> 