<?php
declare(strict_types=1);

$config = require __DIR__ . '/../../config.php';

function supabase_is_configured(): bool
{
    global $config;

    return $config['supabase_url'] !== '' && $config['supabase_secret_key'] !== '';
}

function supabase_request(string $path, string $method = 'GET', ?array $body = null, array $extraHeaders = []): array
{
    global $config;

    if (!supabase_is_configured()) {
        throw new RuntimeException('Supabase server key is not configured.');
    }

    $url = $config['supabase_url'] . '/rest/v1/' . ltrim($path, '/');
    $ch = curl_init($url);

    if ($ch === false) {
        throw new RuntimeException('Unable to initialize Supabase request.');
    }

    $headers = array_merge([
        'apikey: ' . $config['supabase_secret_key'],
        'Authorization: Bearer ' . $config['supabase_secret_key'],
        'Accept: application/json',
    ], $extraHeaders);

    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
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

    $data = $response === '' ? [] : json_decode($response, true);

    if ($status < 200 || $status >= 300) {
        $message = is_array($data) && isset($data['message'])
            ? (string) $data['message']
            : 'Supabase request failed.';
        throw new RuntimeException($message);
    }

    return is_array($data) ? $data : [];
}

function supabase_id_filter(string $column, string $id): string
{
    return $column . '=eq.' . rawurlencode($id);
}

function get_secondary_accounts(string $search = '', string $status = 'all'): array
{
    $query = 'select=id,discord_user_id,username,enabled,created_at,updated_at&order=created_at.desc';

    if ($status === 'active') {
        $query .= '&enabled=eq.true';
    } elseif ($status === 'disabled') {
        $query .= '&enabled=eq.false';
    }

    $search = trim($search);
    if ($search !== '') {
        $search = preg_replace('/[^A-Za-z0-9@._\- ]/u', '', $search) ?? '';
        if ($search !== '') {
            $filter = rawurlencode('*' . $search . '*');
            $query .= '&or=(username.ilike.' . $filter . ',discord_user_id.ilike.' . $filter . ')';
        }
    }

    return supabase_request('secondary_accounts?' . $query);
}

function get_secondary_account(string $id): ?array
{
    $rows = supabase_request('secondary_accounts?select=id,discord_user_id,username,enabled,created_at,updated_at&' . supabase_id_filter('id', $id) . '&limit=1');
    return $rows[0] ?? null;
}

function update_secondary_account(string $id, array $data): array
{
    return supabase_request(
        'secondary_accounts?' . supabase_id_filter('id', $id),
        'PATCH',
        $data,
        ['Prefer: return=representation']
    );
}

function delete_secondary_account(string $id): array
{
    return supabase_request('secondary_accounts?' . supabase_id_filter('id', $id), 'DELETE', null, ['Prefer: return=representation']);
}

function get_provision_request(string $discordUserId): ?array
{
    $rows = supabase_request(
        'secondary_provision_requests?select=id,discord_user_id,status,attempts,error_message,created_at,updated_at,sent_at&discord_user_id=eq.' .
        rawurlencode($discordUserId) . '&limit=1'
    );

    return $rows[0] ?? null;
}

function queue_provision_request(string $discordUserId): array
{
    return supabase_request(
        'secondary_provision_requests?on_conflict=discord_user_id',
        'POST',
        [
            'discord_user_id' => $discordUserId,
            'status' => 'pending',
            'attempts' => 0,
            'error_message' => null,
            'sent_at' => null,
            'updated_at' => gmdate('c'),
        ],
        ['Prefer: resolution=merge-duplicates,return=representation']
    );
}

function get_subscription_plans(bool $activeOnly = false): array
{
    $query = 'select=id,code,name,description,billing_interval,duration_days,price,currency,active,created_at,updated_at&order=price.asc,name.asc';

    if ($activeOnly) {
        $query .= '&active=eq.true';
    }

    return supabase_request('subscription_plans?' . $query);
}

function get_subscription_plan(string $id): ?array
{
    $rows = supabase_request('subscription_plans?select=*&' . supabase_id_filter('id', $id) . '&limit=1');
    return $rows[0] ?? null;
}

function create_subscription_plan(array $data): array
{
    return supabase_request('subscription_plans', 'POST', $data, ['Prefer: return=representation']);
}

function update_subscription_plan(string $id, array $data): array
{
    return supabase_request(
        'subscription_plans?' . supabase_id_filter('id', $id),
        'PATCH',
        $data,
        ['Prefer: return=representation']
    );
}

function delete_subscription_plan(string $id): array
{
    return supabase_request('subscription_plans?' . supabase_id_filter('id', $id), 'DELETE', null, ['Prefer: return=representation']);
}

function get_subscriptions(): array
{
    return supabase_request(
        'subscriptions?select=id,account_id,plan_id,status,starts_at,expires_at,auto_renew,payment_status,amount_paid,payment_reference,external_reference,notes,created_at,updated_at,account:secondary_accounts(id,username,discord_user_id),plan:subscription_plans(id,code,name,billing_interval,duration_days,price,currency)&order=created_at.desc'
    );
}

function get_subscription(string $id): ?array
{
    $rows = supabase_request(
        'subscriptions?select=id,account_id,plan_id,status,starts_at,expires_at,auto_renew,payment_status,amount_paid,payment_reference,external_reference,notes,created_at,updated_at,account:secondary_accounts(id,username,discord_user_id),plan:subscription_plans(id,code,name,billing_interval,duration_days,price,currency)&' .
        supabase_id_filter('id', $id) . '&limit=1'
    );

    return $rows[0] ?? null;
}

function create_subscription(array $data): array
{
    return supabase_request('subscriptions', 'POST', $data, ['Prefer: return=representation']);
}

function update_subscription(string $id, array $data): array
{
    return supabase_request(
        'subscriptions?' . supabase_id_filter('id', $id),
        'PATCH',
        $data,
        ['Prefer: return=representation']
    );
}

function delete_subscription(string $id): array
{
    return supabase_request('subscriptions?' . supabase_id_filter('id', $id), 'DELETE', null, ['Prefer: return=representation']);
}

function create_subscription_event(array $data): array
{
    return supabase_request('subscription_events', 'POST', $data, ['Prefer: return=representation']);
}

function get_subscription_events(string $subscriptionId): array
{
    return supabase_request(
        'subscription_events?select=id,subscription_id,event_type,old_status,new_status,details,created_at&subscription_id=eq.' .
        rawurlencode($subscriptionId) . '&order=created_at.desc'
    );
}

function get_devices(): array
{
    return supabase_request(
        'devices?select=id,account_id,device_uid,device_name,app_version,os_version,first_seen_at,last_seen_at,revoked_at,last_ip,notes,created_at,updated_at,account:secondary_accounts(id,username,discord_user_id)&order=last_seen_at.desc.nullslast,created_at.desc'
    );
}

function get_device(string $id): ?array
{
    $rows = supabase_request(
        'devices?select=id,account_id,device_uid,device_name,app_version,os_version,first_seen_at,last_seen_at,revoked_at,last_ip,notes,created_at,updated_at,account:secondary_accounts(id,username,discord_user_id)&' .
        supabase_id_filter('id', $id) . '&limit=1'
    );

    return $rows[0] ?? null;
}

function create_device(array $data): array
{
    return supabase_request('devices', 'POST', $data, ['Prefer: return=representation']);
}

function update_device(string $id, array $data): array
{
    return supabase_request('devices?' . supabase_id_filter('id', $id), 'PATCH', $data, ['Prefer: return=representation']);
}

function delete_device(string $id): array
{
    return supabase_request('devices?' . supabase_id_filter('id', $id), 'DELETE', null, ['Prefer: return=representation']);
}

function get_activity_logs(int $limit = 200): array
{
    $limit = max(1, min($limit, 500));

    return supabase_request(
        'activity_logs?select=id,actor_type,actor_id,actor_name,action,entity_type,entity_id,details,ip_address,created_at&order=created_at.desc&limit=' . $limit
    );
}

function create_activity_log(string $action, ?string $entityType = null, ?string $entityId = null, array $details = []): array
{
    $admin = function_exists('current_admin') ? current_admin() : [];

    return supabase_request(
        'activity_logs',
        'POST',
        [
            'actor_type' => 'admin',
            'actor_id' => $admin['id'] ?? null,
            'actor_name' => $admin['username'] ?? null,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]
    );
}

function get_admin_users(): array
{
    return supabase_request(
        'admin_users?select=id,username,role,enabled,created_at,updated_at&order=created_at.asc'
    );
}

function get_admin_user_by_username(string $username): ?array
{
    $rows = supabase_request(
        'admin_users?select=id,username,password_hash,role,enabled,created_at,updated_at&username=eq.' . rawurlencode($username) . '&limit=1'
    );

    return $rows[0] ?? null;
}

function create_admin_user(array $data): array
{
    return supabase_request('admin_users', 'POST', $data, ['Prefer: return=representation']);
}

function update_admin_user(string $id, array $data): array
{
    return supabase_request(
        'admin_users?' . supabase_id_filter('id', $id),
        'PATCH',
        $data,
        ['Prefer: return=representation']
    );
}

function delete_admin_user(string $id): array
{
    return supabase_request('admin_users?' . supabase_id_filter('id', $id), 'DELETE', null, ['Prefer: return=representation']);
}

function get_releases(): array
{
    return supabase_request(
        'app_releases?select=id,version,channel,status,min_supported_version,release_date,download_url,notes,created_at,updated_at&order=release_date.desc.nullslast,created_at.desc'
    );
}

function get_release(string $id): ?array
{
    $rows = supabase_request('app_releases?select=*&' . supabase_id_filter('id', $id) . '&limit=1');
    return $rows[0] ?? null;
}

function create_release(array $data): array
{
    return supabase_request('app_releases', 'POST', $data, ['Prefer: return=representation']);
}

function update_release(string $id, array $data): array
{
    return supabase_request('app_releases?' . supabase_id_filter('id', $id), 'PATCH', $data, ['Prefer: return=representation']);
}

function delete_release(string $id): array
{
    return supabase_request('app_releases?' . supabase_id_filter('id', $id), 'DELETE', null, ['Prefer: return=representation']);
}

function get_settings(): array
{
    $rows = supabase_request('system_settings?select=key,value,updated_at&order=key.asc');
    $settings = [];

    foreach ($rows as $row) {
        $settings[(string) $row['key']] = (string) $row['value'];
    }

    return $settings;
}

function set_setting(string $key, string $value): array
{
    return supabase_request(
        'system_settings?on_conflict=key',
        'POST',
        ['key' => $key, 'value' => $value, 'updated_at' => gmdate('c')],
        ['Prefer: resolution=merge-duplicates,return=representation']
    );
}
