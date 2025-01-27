<?php

declare(strict_types=1);

namespace DigiContent\Core;

use DigiContent\Core\Services\LoggerService;

/**
 * Handles encryption and decryption of sensitive data.
 *
 * @since 1.0.0
 */
final class Encryption {
    private const CIPHER_ALGO = 'aes-256-cbc';
    private const HASH_ALGO = 'sha256';
    private const KEY_LENGTH = 32;
    private const IV_LENGTH = 16;

    /**
     * Encrypt data using WordPress salt keys.
     *
     * @param string $data Data to encrypt.
     * @return string Encrypted data.
     * @throws \RuntimeException If encryption fails.
     */
    public static function encrypt(string $data): string 
    {
        if (empty($data)) {
            return '';
        }

        try {
            $key = self::generate_key();
            $iv = random_bytes(self::IV_LENGTH);
            
            $encrypted = openssl_encrypt(
                $data,
                self::CIPHER_ALGO,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($encrypted === false) {
                throw new \RuntimeException('Encryption failed: ' . openssl_error_string());
            }

            return base64_encode($iv . $encrypted);
            
        } catch (\Exception $e) {
            throw new \RuntimeException('Encryption failed: ' . $e->getMessage());
        }
    }

    /**
     * Decrypt data using WordPress salt keys.
     *
     * @param string $encrypted_data Encrypted data to decrypt.
     * @return string Decrypted data.
     * @throws \RuntimeException If decryption fails.
     */
    public static function decrypt(string $encrypted_data): string 
    {
        if (empty($encrypted_data)) {
            return '';
        }

        try {
            $decoded = base64_decode($encrypted_data, true);
            
            if ($decoded === false) {
                throw new \RuntimeException('Invalid base64 encoding');
            }

            $iv = substr($decoded, 0, self::IV_LENGTH);
            $encrypted = substr($decoded, self::IV_LENGTH);
            
            if (strlen($iv) !== self::IV_LENGTH) {
                throw new \RuntimeException('Invalid IV length');
            }

            $key = self::generate_key();
            
            $decrypted = openssl_decrypt(
                $encrypted,
                self::CIPHER_ALGO,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                throw new \RuntimeException('Decryption failed: ' . openssl_error_string());
            }

            return $decrypted;
            
        } catch (\Exception $e) {
            throw new \RuntimeException('Decryption failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate encryption key from WordPress salt keys.
     *
     * @return string Generated key.
     */
    private static function generate_key(): string 
    {
        $salt = AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY . NONCE_KEY;
        return substr(hash(self::HASH_ALGO, $salt), 0, self::KEY_LENGTH);
    }
}