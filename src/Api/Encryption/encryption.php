<?php
// Base64-encoded secret key
$base64_key = 'ZGNIVm5FNkVNeStmdnozaWdYSDRwNGwvckIwSjVMNDJ2OXN0dnl6ckVOc0MrZmh5UzEwRG83bkx6VXJkWTMzZUlSWXEybVk1NUNZbmQyZ3lhMmw1MEtoeUZMRjFGUlZnTFhidGRXU251S3M9OjqrcZLErLUyMmxE9ETC_SLASH_cYF';

// Decode base64 key and convert to hexadecimal
$hex_key = bin2hex(base64_decode($base64_key));

function encrypt(array $data): string
{
    global $hex_key; // Use the dynamically created key
    $secret_key = hex2bin($hex_key);
    $data_json_64 = base64_encode(json_encode($data));
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-gcm'));
    $tag = '';
    $encrypted_64 = openssl_encrypt($data_json_64, 'aes-256-gcm', $secret_key, 0, $iv, $tag);
    $json = new stdClass();
    $json->iv = base64_encode($iv);
    $json->data = $encrypted_64;
    $json->tag = base64_encode($tag);
    return base64_encode(json_encode($json));
}

function decrypt(string $data): array
{
    global $hex_key; // Use the dynamically created key
    $secret_key = hex2bin($hex_key);
    $json = json_decode(base64_decode($data), true);
    $iv = base64_decode($json['iv']);
    $tag = base64_decode($json['tag']);
    $encrypted_data = base64_decode($json['data']);
    $decrypted_data = openssl_decrypt($encrypted_data, 'aes-256-gcm', $secret_key, OPENSSL_RAW_DATA, $iv, $tag);
    return json_decode(base64_decode($decrypted_data), true);
}

// Example usage
$original_data = ['key' => 'value'];
$encrypted = encrypt($original_data);
$decrypted = decrypt($encrypted);

echo "Original Data: " . json_encode($original_data) . PHP_EOL;
echo "Encrypted Data: " . $encrypted . PHP_EOL;
echo "Decrypted Data: " . json_encode($decrypted) . PHP_EOL;
