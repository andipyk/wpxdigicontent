# DigiContent - AI-Powered Content Generation Plugin

[![WordPress Compatible](https://img.shields.io/badge/WordPress-Compatible-0073aa.svg)](https://wordpress.org)
[![PHP Required](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## Description

DigiContent is a powerful WordPress plugin that leverages the capabilities of Anthropic's Claude 3.5 Sonnet and OpenAI's GPT-4 Turbo to generate high-quality, AI-powered content for your WordPress website. Developed by [Andi Syafrianda](https://digikuy.com), this plugin seamlessly integrates advanced AI technology into your content creation workflow.

## Features

- 🤖 Dual AI Integration: Support for both Claude 3.5 Sonnet (Anthropic) and GPT-4 Turbo (OpenAI)
- 🔒 Secure API Key Management: Encrypted storage of API keys
- ⚙️ Customizable Settings: Adjust max tokens and temperature for AI responses
- 📝 Post Editor Integration: Generate content directly within the WordPress editor
- 🎯 User-Friendly Interface: Simple and intuitive content generation process
- 🔄 Real-time Content Generation: Fast and efficient AI-powered content creation
- 📱 Mobile-Friendly: Works seamlessly on all devices

## Requirements

### System Requirements
- WordPress 5.0 or higher
- PHP 8.0 or higher
- MySQL 5.6 or higher
- Valid API key from Anthropic and/or OpenAI
- HTTPS enabled website (for security)

### Server Requirements
- PHP memory limit: 256MB or higher recommended
- Max execution time: 120 seconds or higher
- PHP cURL extension enabled
- PHP OpenSSL extension enabled

## Installation

### Standard Installation
1. Download the plugin zip file
2. Go to WordPress admin panel > Plugins > Add New
3. Click "Upload Plugin" and select the downloaded zip file
4. Click "Install Now" and then "Activate"
5. Navigate to Settings > DigiContent to configure your API keys and preferences

### Manual Installation
1. Download and unzip the plugin
2. Upload the 'digicontent' folder to `/wp-content/plugins/`
3. Activate through the 'Plugins' menu in WordPress

## Configuration

### Initial Setup
1. Obtain API keys:
   - Get your Anthropic API key from [Anthropic's website](https://anthropic.com)
   - Get your OpenAI API key from [OpenAI's website](https://openai.com)
2. In WordPress admin, go to Settings > DigiContent
3. Enter your API keys in the respective fields

### Advanced Settings
4. Configure your preferred settings:
   - Default AI model (Claude 3.5 Sonnet or GPT-4 Turbo)
   - Maximum tokens (recommended: 2048-4096)
   - Temperature setting (0.0-1.0, recommended: 0.7)
   - Response format (HTML/Markdown)
5. Save your changes

### Troubleshooting
- Ensure your API keys are valid and have sufficient credits
- Check if your server meets all requirements
- Verify HTTPS is properly configured
- Clear cache after making configuration changes

## Usage

### Basic Usage
1. Create or edit a post/page in WordPress
2. Look for the DigiContent panel in the editor
3. Enter your content prompt
4. Select your preferred AI model
5. Click "Generate Content"
6. Review and edit the generated content
7. Insert it into your post

### Advanced Features
- Use custom prompts for specialized content
- Adjust generation parameters per request
- Save favorite prompts for reuse
- Batch generate content for multiple posts

### Example Prompts
```
# Blog Post Introduction
Write an engaging introduction about [topic] that hooks readers and previews the main points.

# Product Description
Create a compelling product description for [product] highlighting its key features and benefits.

# SEO Optimization
Generate SEO-optimized content about [topic] targeting the keyword [keyword].
```

## Security

### Data Protection
- API keys are stored with AES-256 encryption
- All API communications use HTTPS
- WordPress nonce verification for all AJAX requests
- Proper user capability checks and role management

### Best Practices
- Regularly update API keys
- Limit admin access to trusted users
- Monitor API usage and costs
- Keep the plugin updated

## FAQ

### Common Questions
1. **How much does it cost to use?**
   - The plugin is free, but you need valid API keys from Anthropic and/or OpenAI

2. **Which AI model should I choose?**
   - Claude 3.5 Sonnet: Best for creative and nuanced content
   - GPT-4 Turbo: Excellent for technical and structured content

3. **Is the generated content unique?**
   - Yes, each generation produces unique content based on your prompt

4. **Can I use the content commercially?**
   - Yes, the generated content is yours to use as you wish

### Troubleshooting FAQ
1. **API Key Issues**
   - Verify key format and permissions
   - Check API service status
   - Ensure sufficient API credits

2. **Generation Errors**
   - Check internet connectivity
   - Verify server requirements
   - Review error logs

## Support

For support, feature requests, or bug reports, please visit:
- [Digikuy.com](https://digikuy.com)
- Email: support@digikuy.com
- Documentation: [docs.digikuy.com](https://docs.digikuy.com)

## Contributing

Contributions are welcome! Please follow these steps:
1. Fork the repository
2. Create a feature branch
3. Submit a Pull Request

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- Developed by: [Andi Syafrianda](https://digikuy.com)
- Website: [Digikuy.com](https://digikuy.com)
- Version: 1.1.0

## Changelog

### 1.1.0 (2024-03-15)
- Added support for Claude 3.5 Sonnet
- Upgraded to GPT-4 Turbo
- Enhanced encryption for API keys
- Improved error handling and logging
- Added custom prompt templates
- Updated documentation

### 1.0.0 (2024-02-01)
- Initial release
- Integration with Claude-2 and GPT-4
- Secure API key management
- Custom settings configuration
- WordPress editor integration

## Compatibility

### WordPress Version
- Tested up to: 6.4.3
- Requires at least: 5.0

### PHP Version
- Tested up to: 8.2
- Requires at least: 8.0