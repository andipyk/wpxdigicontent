includes/Core/Services/EncryptionService.php (2)
7-10: Consider increasing PBKDF2 iteration count for enhanced security.
While 1,000 iterations can be sufficient for some scenarios, security best practices often recommend a higher iteration count (e.g., 10,000+) to strengthen resistance against brute-force attacks.

40-40: Optional: separate IV, salt, and ciphertext with a delimiter or structured format.
Although direct concatenation works, using a distinct delimiter (e.g., :) or storing each piece in a structured format (JSON or serialized) can eliminate confusion when parsing the data.

digicontent.php (3)
57-58: Ensure proper configuration for encryption.
Great to see EncryptionService in use. If you plan to handle sensitive user data, confirm that the environment and keys are securely managed.

63-64: Confirm references to new classes across the admin flow.
Since you're now instantiating Settings and Editor, ensure any old references to PostEditor are fully replaced or removed.

126-133: Clarify inline LoggerService initialization for REST API.
Defining $logger = new DigiContent\Core\Services\LoggerService() within the same statement as $database can reduce readability. Consider separating the logger creation for clarity. Also confirm your REST routes are secured by appropriate capabilities or authentication.

includes/Admin/Settings.php (1)
160-161: Handle invalid or empty decrypted keys in UI.
If decryption fails or the stored key is empty, consider displaying a user-friendly warning to ensure the user knows their key might be missing or invalid.

includes/Core/TemplateManager.php (4)
18-18: LGTM! Consider adding a descriptive comment.

The constant is well-placed, appropriately scoped as private, and consistently used throughout the class. Consider adding a descriptive comment to document its purpose.

+    /** WordPress option key for storing templates */
     private const TEMPLATE_OPTION_KEY = 'digicontent_templates';
Line range hint 127-146: Inconsistent data access pattern detected.

The get_template_versions method bypasses the template repository and directly accesses WordPress options, while other methods like save_template use the repository pattern. This inconsistency could lead to maintenance issues.

Consider refactoring to consistently use the repository pattern:

 public function get_template_versions(\WP_REST_Request $request): \WP_REST_Response {
     try {
         $template_id = $request['id'];
-        $templates = get_option(self::TEMPLATE_OPTION_KEY, []);
-        
-        $template = current(array_filter($templates, fn($t) => $t['id'] === $template_id));
+        $template = $this->template_repository->get($template_id);
         
         if (!$template) {
             return new \WP_REST_Response(['message' => 'Template not found'], 404);
         }
         
         return new \WP_REST_Response($template);
     } catch (\Exception $e) {
         return new \WP_REST_Response(
             ['message' => 'Error retrieving template versions: ' . $e->getMessage()],
             500
         );
     }
 }
Line range hint 14-14: Incomplete caching implementation.

The CACHE_EXPIRATION constant is defined but the caching strategy isn't fully implemented. While cache invalidation is handled via delete_transient, there's no cache warming strategy.

Consider implementing a comprehensive caching strategy:

Cache warming after updates
Batch processing for large template sets
Proper expiration handling
Example implementation:

private function warm_cache(): void {
    $templates = $this->template_repository->get_all();
    set_transient(
        'digicontent_templates_cache',
        $templates,
        self::CACHE_EXPIRATION
    );
}
Line range hint 186-194: Enhance error message specificity.

While error handling is robust, consider making error messages more specific to aid debugging. For example, in save_template, differentiate between validation errors and database errors.

Example improvement:

 if ($template_id === false) {
     return new \WP_REST_Response(
-        ['message' => 'Failed to create template'],
+        ['message' => 'Database error: Failed to create template. Please check the logs for details.'],
         500
     );
 }

includes/Core/Services/EncryptionService.php (1)
53-63: Validate decoded data length before substr operations.
If the base64 input is tampered with or malformed, the substr calls might read beyond the buffer. Adding a length check can avoid edge-case errors or warnings.

digicontent.php (1)
85-85: Verify database initialization errors are handled.
If init() fails or throws exceptions, consider logging or communicating the issue to the user for diagnostic purposes.

includes/Admin/Settings.php
106-107: Verify old API key data handling.
Using encrypt_api_key as a sanitize callback is great, but ensure any previously stored plaintext keys are gracefully migrated or re-encrypted to avoid confusion.

377-380: Check for encryption failure.
If encrypt() returns '', it’s treated as a successful save. Consider logging or notifying the user when encryption fails, so they realize the stored key is invalid.

includes/Core/Services/EncryptionService.php
        try {
            $ivlen = openssl_cipher_iv_length(self::CIPHER);
            $iv = openssl_random_pseudo_bytes($ivlen);
            $salt = wp_generate_password(64, true, true);

Use cryptographically secure salt generation.
wp_generate_password() is convenient, but for maximum cryptographic strength, consider using random_bytes() or a similar CSPRNG function to ensure the salt is always generated with high entropy.