<?php

/*====================================================
MANAGER PASSWORD ENCRYPTION

Used ONLY for the auto-generated manager password shown
on the Users page. This is separate from the `password`
column on admins, which stays bcrypt-hashed via
password_hash()/password_verify() for actual login checks.

This encryption is reversible (unlike bcrypt) because the
admin needs to be able to view the plaintext password again
later - that's the whole point of the Users page feature.
It protects the password at rest (e.g. in a database dump
or backup) while still letting an authorised admin view it
through the app.

IMPORTANT: If this key is ever lost or changed, any
passwords encrypted with the old key can no longer be
decrypted. Keep it safe, and ideally keep this file out of
version control / public access.
====================================================*/

define("MANAGER_PASSWORD_ENCRYPTION_KEY", "6d4ffc20a875877776a1353682d17ec6f6cfb1cdb7b9dbf6d614d218f912da8b");

define("MANAGER_PASSWORD_ENCRYPTION_METHOD", "aes-256-cbc");


/**
 * Encrypts a plaintext password for storage.
 */
function encryptManagerPassword($plainPassword) {

    $ivLength = openssl_cipher_iv_length(MANAGER_PASSWORD_ENCRYPTION_METHOD);

    $iv = openssl_random_pseudo_bytes($ivLength);

    $encrypted = openssl_encrypt(
        $plainPassword,
        MANAGER_PASSWORD_ENCRYPTION_METHOD,
        MANAGER_PASSWORD_ENCRYPTION_KEY,
        0,
        $iv
    );

    if ($encrypted === false) {
        throw new Exception("Unable to encrypt manager password.");
    }

    /* Store IV alongside the ciphertext - it isn't secret, just needed for decryption */

    return base64_encode($iv . $encrypted);

}


/**
 * Decrypts a stored password back to plaintext for display.
 */
function decryptManagerPassword($encoded) {

    if ($encoded === null || $encoded === "") {
        return "";
    }

    $data = base64_decode($encoded);

    $ivLength = openssl_cipher_iv_length(MANAGER_PASSWORD_ENCRYPTION_METHOD);

    $iv = substr($data, 0, $ivLength);

    $encrypted = substr($data, $ivLength);

    $decrypted = openssl_decrypt(
        $encrypted,
        MANAGER_PASSWORD_ENCRYPTION_METHOD,
        MANAGER_PASSWORD_ENCRYPTION_KEY,
        0,
        $iv
    );

    return ($decrypted === false) ? "" : $decrypted;

}

?>