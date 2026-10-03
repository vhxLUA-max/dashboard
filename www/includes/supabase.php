<?php
declare(strict_types=1);

$config = require __DIR__ . '/../../config.php';

function supabase_is_configured(): bool
{
    global $config;

    return $config['supabase_url'] !== '' && $config['supabase_secret_key'] !== '';
}

function supabase_request(string $path, string $method = 'GET', ?array $body = null): array
{
    global $config;

    if (!supabase_is_configured()) {
        throw new RuntimeException('Supabase server key is not configured.');
    }

    $ch = curl_init($config['supabase_url'] . '/rest/v1/' . ltrim($path, '/'));

    if ($ch === false) {
        throw new RuntimeException('Unable to initialize Supabase request.');
    }

    $headers = [
        'apikey: ' . $config['supabase_secret_key'],
        'Authorization: Bearer ' . $config['supabase_secret_key'],
        'Accept: application/json',
    ];

    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
    }

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException($error !== '' ? $error : 'Supabase request failed.');
    }

    $data = json_decode($response, true);

    if ($status < 200 || $status >= 300) {
        $message = is_array($data) && isset($data['message']) ? $data['message'] : 'Supabase request failed.';
        throw new RuntimeException($message);
    }

    return is_array($data) ? $data : [];
}

function get_secondary_accounts(): array
{
    return supabase_request('secondary_accounts?select=id,discord_user_id,username,enabled,created_at,updated_at&order=created_at.desc');
}
