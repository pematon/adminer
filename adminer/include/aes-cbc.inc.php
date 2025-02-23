<?php

/**
 * Generates a secure IV compatible with PHP 5 and PHP 7+.
 *
 * @param  int $length IV length.
 * @return string Generated IV.
 */
function generate_iv($length)
{
    if (function_exists('random_bytes')) {
        return random_bytes($length);
    }
    // Fallback for PHP <7 if random_bytes does not exist
    $iv = '';
    for ($i = 0; $i < $length; $i++) {
        $iv .= chr(mt_rand(0, 255));
    }
    return $iv;
}

/**
 * Encrypts a string using AES-256-CBC.
 *
 * @param  string $plaintext Plain text to encrypt.
 * @param  string $key       Encryption key.
 * @return string Base64-encoded encrypted text.
 */
function encrypt_string($plaintext, $key)
{
    // Generates a 256-bit (32-byte) key from the SHA-512 hash.
    $key = substr(hash('sha512', $key, true), 0, 32);
    // Generates a secure 16-byte IV.
    $iv = generate_iv(16);
    // Encrypts the text using AES-256-CBC.
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    if ($ciphertext === false) {
        return false;
    }
    // Generates an HMAC using IV + ciphertext to ensure integrity.
    $hmac = hash_hmac('sha512', $iv . $ciphertext, $key, true);
    // Encodes in base64: IV + HMAC + ciphertext.
    return base64_encode($iv . $hmac . $ciphertext);
}

/**
 * Decrypts an AES-256-CBC encrypted string.
 *
 * @param  string $ciphertext_base64 Base64-encoded encrypted text.
 * @param  string $key               Decryption key.
 * @return string|false Plain text or false if authentication fails.
 */
function decrypt_string($ciphertext_base64, $key)
{
    // Generates a 256-bit (32-byte) key from the SHA-512 hash.
    $key = substr(hash('sha512', $key, true), 0, 32);
    // Decodes the base64 string.
    $data = base64_decode($ciphertext_base64);
    // IV (16) + HMAC (64) minimum
    if ($data === false || strlen($data) < 80) {
        return false;
    }
    // Extracts IV (16 bytes), HMAC (64 bytes), and encrypted text.
    $iv = substr($data, 0, 16);
    $hmac = substr($data, 16, 64);
    $ciphertext = substr($data, 80);
    if ($iv === false || $hmac === false || $ciphertext === false) {
        return false;
    }
    // Verifies integrity using HMAC-SHA512.
    $calculated_hmac = hash_hmac('sha512', $iv . $ciphertext, $key, true);
    // Protection against timing attacks.
    if (!hash_equals($hmac, $calculated_hmac)) {
        return false;
    }
    // Decrypts the text.
    return openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}
