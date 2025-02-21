<?php

/**
 * Encrypts a string using AES.
 *
 * @param  string $plaintext The plaintext to encrypt.
 * @param  string $key       The key used for encryption.
 * @return string The encrypted text in base64.
 */
function encrypt_string($plaintext, $key)
{
    // Use SHA-512 and truncate the output to 32 bytes (256 bits).
    $key = substr(hash('sha512', $key, true), 0, 32);

    // Generate a random IV of the appropriate length.
    $iv = random_bytes(openssl_cipher_iv_length('aes-256-gcm'));

    $tag = ''; 

    // Encrypt the data using AES-256-GCM.
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

    // Encode in base64 the concatenation of IV, tag, and encrypted text.
    return base64_encode($iv . $tag . $ciphertext);
}

/**
 * Decrypts a string using AES with a SHA-512 derived key.
 *
 * @param  string $ciphertext_base64 The encrypted text in base64.
 * @param  string $key               The key used for decryption.
 * @return string|false The decrypted plaintext or false if decryption fails.
 */
function decrypt_string($ciphertext_base64, $key)
{
    // Use SHA-512 and truncate the output to 32 bytes (256 bits).
    $key = substr(hash('sha512', $key, true), 0, 32);

    // Decode the base64 data.
    $data = base64_decode($ciphertext_base64);

    // Extract IV, tag, and encrypted text.
    $iv_length = openssl_cipher_iv_length('aes-256-gcm');
    $iv = substr($data, 0, $iv_length);
    $tag = substr($data, $iv_length, 16); // Authentication tag (16 bytes for GCM).
    $ciphertext = substr($data, $iv_length + 16);

    // Decrypt the data.
    return openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
}
