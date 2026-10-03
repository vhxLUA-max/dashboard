<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/supabase.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('index.php');
}

verify_csrf();

$action = post_string('action');
$returnTo = safe_return_to(post_string('return_to'), 'index.php');

function action_id(): string
{
    $id = post_string('id');

    if ($id === '' || !preg_match('/^[0-9a-fA-F-]{16,}$/', $id)) {
        throw new InvalidArgumentException('A valid record ID is required.');
    }

    return $id;
}

function log_action(string $action, ?string $entityType = null, ?string $entityId = null, array $details = []): void
{
    try {
        create_activity_log($action, $entityType, $entityId, $details);
    } catch (Throwable) {
    }
}

try {
    switch ($action) {
        case 'user_toggle':
            require_permission('users.manage');
            $id = action_id();
            $account = get_secondary_account($id);
            if (!$account) {
                throw new RuntimeException('User not found.');
            }

            $enabled = !(bool) $account['enabled'];
            update_secondary_account($id, [
                'enabled' => $enabled,
                'updated_at' => gmdate('c'),
            ]);

            log_action('user.' . ($enabled ? 'enabled' : 'disabled'), 'user', $id, [
                'username' => $account['username'],
                'enabled' => $enabled,
            ]);

            flash('success', $enabled ? 'User enabled.' : 'User disabled.');
            break;

        case 'user_update':
            require_permission('users.manage');
            $id = action_id();
            $username = post_string('username');

            if ($username === '' || !preg_match('/^[A-Za-z0-9._-]{3,32}$/', $username)) {
                throw new InvalidArgumentException('Username must be 3-32 characters and use letters, numbers, dots, underscores, or dashes.');
            }

            $enabled = post_bool('enabled');
            update_secondary_account($id, [
                'username' => $username,
                'enabled' => $enabled,
                'updated_at' => gmdate('c'),
            ]);

            log_action('user.updated', 'user', $id, [
                'username' => $username,
                'enabled' => $enabled,
            ]);

            flash('success', 'User updated.');
            break;

        case 'user_delete':
            require_permission('users.manage');
            $id = action_id();
            $account = get_secondary_account($id);
            if (!$account) {
                throw new RuntimeException('User not found.');
            }

            delete_secondary_account($id);

            log_action('user.deleted', 'user', $id, [
                'username' => $account['username'],
                'discord_user_id' => $account['discord_user_id'],
            ]);

            flash('success', 'User deleted.');
            $returnTo = 'users.php';
            break;

        case 'user_provision':
            require_permission('users.manage');
            $id = action_id();
            $account = get_secondary_account($id);
            if (!$account) {
                throw new RuntimeException('User not found.');
            }

            queue_provision_request((string) $account['discord_user_id']);

            log_action('user.credentials_queued', 'user', $id, [
                'username' => $account['username'],
                'discord_user_id' => $account['discord_user_id'],
            ]);

            flash('success', 'Credential provisioning request queued.');
            break;

        case 'plan_save':
            require_permission('subscriptions.manage');
            $id = post_string('id');
            $code = strtolower(post_string('code'));
            $name = post_string('name');
            $description = post_string('description');
            $interval = post_string('billing_interval', 'month');
            $durationDays = post_string('duration_days');
            $currency = strtoupper(post_string('currency', 'PHP'));
            $price = (float) post_string('price', '0');
            $active = post_bool('active');

            if (!preg_match('/^[a-z0-9][a-z0-9_-]{1,31}$/', $code)) {
                throw new InvalidArgumentException('Plan code must be 2-32 lowercase letters, numbers, dashes, or underscores.');
            }

            if ($name === '') {
                throw new InvalidArgumentException('Plan name is required.');
            }

            if (!in_array($interval, ['month', 'year', 'lifetime'], true)) {
                throw new InvalidArgumentException('Invalid billing interval.');
            }

            $days = $interval === 'lifetime' ? null : (int) $durationDays;
            if ($interval !== 'lifetime' && $days < 1) {
                throw new InvalidArgumentException('Duration must be at least 1 day for recurring plans.');
            }

            if ($price < 0) {
                throw new InvalidArgumentException('Price cannot be negative.');
            }

            $data = [
                'code' => $code,
                'name' => $name,
                'description' => $description !== '' ? $description : null,
                'billing_interval' => $interval,
                'duration_days' => $days,
                'price' => $price,
                'currency' => $currency !== '' ? $currency : 'PHP',
                'active' => $active,
                'updated_at' => gmdate('c'),
            ];

            if ($id === '') {
                $created = create_subscription_plan($data);
                $newId = (string) ($created[0]['id'] ?? '');
                log_action('plan.created', 'subscription_plan', $newId, ['code' => $code, 'name' => $name]);
                flash('success', 'Subscription plan created.');
            } else {
                update_subscription_plan($id, $data);
                log_action('plan.updated', 'subscription_plan', $id, ['code' => $code, 'name' => $name]);
                flash('success', 'Subscription plan updated.');
            }
            break;

        case 'plan_delete':
            require_permission('subscriptions.manage');
            $id = action_id();
            $plan = get_subscription_plan($id);
            if (!$plan) {
                throw new RuntimeException('Plan not found.');
            }

            delete_subscription_plan($id);
            log_action('plan.deleted', 'subscription_plan', $id, ['code' => $plan['code'], 'name' => $plan['name']]);
            flash('success', 'Subscription plan deleted.');
            $returnTo = 'subscriptions.php?view=plans';
            break;

        case 'subscription_save':
            require_permission('subscriptions.manage');
            $id = post_string('id');
            $accountId = post_string('account_id');
            $planId = post_string('plan_id');
            $status = post_string('status', 'active');
            $startsAt = normalize_datetime(post_string('starts_at'));
            $expiresAt = normalize_datetime(post_string('expires_at'));
            $autoRenew = post_bool('auto_renew');
            $paymentStatus = post_string('payment_status', 'unpaid');
            $amountPaid = (float) post_string('amount_paid', '0');
            $paymentReference = post_string('payment_reference');
            $externalReference = post_string('external_reference');
            $notes = post_string('notes');

            if ($accountId === '' || !get_secondary_account($accountId)) {
                throw new InvalidArgumentException('A valid Solis user is required.');
            }

            if ($planId !== '' && !get_subscription_plan($planId)) {
                throw new InvalidArgumentException('Selected plan was not found.');
            }

            if (!in_array($status, ['trialing', 'active', 'past_due', 'canceled', 'expired'], true)) {
                throw new InvalidArgumentException('Invalid subscription status.');
            }

            if (!in_array($paymentStatus, ['pending', 'paid', 'unpaid', 'refunded'], true)) {
                throw new InvalidArgumentException('Invalid payment status.');
            }

            if ($amountPaid < 0) {
                throw new InvalidArgumentException('Amount paid cannot be negative.');
            }

            $existing = $id !== '' ? get_subscription($id) : null;
            if ($id !== '' && !$existing) {
                throw new RuntimeException('Subscription not found.');
            }

            $data = [
                'account_id' => $accountId,
                'plan_id' => $planId !== '' ? $planId : null,
                'status' => $status,
                'starts_at' => $startsAt ?? gmdate('c'),
                'expires_at' => $expiresAt,
                'auto_renew' => $autoRenew,
                'payment_status' => $paymentStatus,
                'amount_paid' => $amountPaid,
                'payment_reference' => $paymentReference !== '' ? $paymentReference : null,
                'external_reference' => $externalReference !== '' ? $externalReference : null,
                'notes' => $notes !== '' ? $notes : null,
                'updated_at' => gmdate('c'),
            ];

            if ($id === '') {
                $created = create_subscription($data);
                $newId = (string) ($created[0]['id'] ?? '');
                create_subscription_event([
                    'subscription_id' => $newId,
                    'event_type' => 'created',
                    'old_status' => null,
                    'new_status' => $status,
                    'details' => ['source' => 'admin'],
                ]);
                log_action('subscription.created', 'subscription', $newId, ['status' => $status]);
                flash('success', 'Subscription created.');
            } else {
                update_subscription($id, $data);
                if ((string) ($existing['status'] ?? '') !== $status) {
                    create_subscription_event([
                        'subscription_id' => $id,
                        'event_type' => 'status_changed',
                        'old_status' => $existing['status'] ?? null,
                        'new_status' => $status,
                        'details' => ['source' => 'admin'],
                    ]);
                }
                log_action('subscription.updated', 'subscription', $id, ['status' => $status]);
                flash('success', 'Subscription updated.');
            }
            break;

        case 'subscription_cancel':
            require_permission('subscriptions.manage');
            $id = action_id();
            $existing = get_subscription($id);
            if (!$existing) {
                throw new RuntimeException('Subscription not found.');
            }

            update_subscription($id, [
                'status' => 'canceled',
                'auto_renew' => false,
                'updated_at' => gmdate('c'),
            ]);

            create_subscription_event([
                'subscription_id' => $id,
                'event_type' => 'canceled',
                'old_status' => $existing['status'],
                'new_status' => 'canceled',
                'details' => ['source' => 'admin'],
            ]);

            log_action('subscription.canceled', 'subscription', $id);
            flash('success', 'Subscription canceled.');
            break;

        case 'subscription_renew':
            require_permission('subscriptions.manage');
            $id = action_id();
            $existing = get_subscription($id);
            if (!$existing) {
                throw new RuntimeException('Subscription not found.');
            }

            $plan = $existing['plan'] ?? null;
            if (!is_array($plan)) {
                throw new RuntimeException('A plan is required to renew this subscription.');
            }

            $interval = (string) ($plan['billing_interval'] ?? 'month');
            $durationDays = isset($plan['duration_days']) ? (int) $plan['duration_days'] : 0;

            if ($interval === 'lifetime') {
                $newExpiry = null;
            } else {
                if ($durationDays < 1) {
                    throw new RuntimeException('This plan has no valid duration.');
                }

                $base = new DateTimeImmutable('now');
                if (!empty($existing['expires_at'])) {
                    try {
                        $existingExpiry = new DateTimeImmutable((string) $existing['expires_at']);
                        if ($existingExpiry > $base) {
                            $base = $existingExpiry;
                        }
                    } catch (Throwable) {
                    }
                }

                $newExpiry = $base->modify('+' . $durationDays . ' days')->format(DateTimeInterface::ATOM);
            }

            update_subscription($id, [
                'status' => 'active',
                'expires_at' => $newExpiry,
                'auto_renew' => (bool) ($existing['auto_renew'] ?? false),
                'updated_at' => gmdate('c'),
            ]);

            create_subscription_event([
                'subscription_id' => $id,
                'event_type' => 'renewed',
                'old_status' => $existing['status'],
                'new_status' => 'active',
                'details' => ['expires_at' => $newExpiry],
            ]);

            log_action('subscription.renewed', 'subscription', $id, ['expires_at' => $newExpiry]);
            flash('success', 'Subscription renewed.');
            break;

        case 'subscription_delete':
            require_permission('subscriptions.manage');
            $id = action_id();
            $existing = get_subscription($id);
            if (!$existing) {
                throw new RuntimeException('Subscription not found.');
            }

            delete_subscription($id);
            log_action('subscription.deleted', 'subscription', $id);
            flash('success', 'Subscription deleted.');
            $returnTo = 'subscriptions.php';
            break;

        case 'device_save':
            require_permission('devices.manage');
            $id = post_string('id');
            $accountId = post_string('account_id');
            $deviceUid = post_string('device_uid');
            $deviceName = post_string('device_name');
            $appVersion = post_string('app_version');
            $osVersion = post_string('os_version');
            $lastSeen = normalize_datetime(post_string('last_seen_at'));
            $notes = post_string('notes');

            if ($accountId === '' || !get_secondary_account($accountId)) {
                throw new InvalidArgumentException('A valid Solis user is required.');
            }

            if ($deviceUid === '' || !preg_match('/^[A-Za-z0-9._:-]{3,128}$/', $deviceUid)) {
                throw new InvalidArgumentException('Device ID must be 3-128 characters.');
            }

            $data = [
                'account_id' => $accountId,
                'device_uid' => $deviceUid,
                'device_name' => $deviceName !== '' ? $deviceName : null,
                'app_version' => $appVersion !== '' ? $appVersion : null,
                'os_version' => $osVersion !== '' ? $osVersion : null,
                'last_seen_at' => $lastSeen,
                'notes' => $notes !== '' ? $notes : null,
                'updated_at' => gmdate('c'),
            ];

            if ($id === '') {
                $created = create_device($data);
                $newId = (string) ($created[0]['id'] ?? '');
                log_action('device.created', 'device', $newId, ['device_uid' => $deviceUid]);
                flash('success', 'Device added.');
            } else {
                update_device($id, $data);
                log_action('device.updated', 'device', $id, ['device_uid' => $deviceUid]);
                flash('success', 'Device updated.');
            }
            break;

        case 'device_revoke':
            require_permission('devices.manage');
            $id = action_id();
            $device = get_device($id);
            if (!$device) {
                throw new RuntimeException('Device not found.');
            }

            update_device($id, [
                'revoked_at' => gmdate('c'),
                'updated_at' => gmdate('c'),
            ]);

            log_action('device.revoked', 'device', $id, ['device_uid' => $device['device_uid']]);
            flash('success', 'Device revoked.');
            break;

        case 'device_restore':
            require_permission('devices.manage');
            $id = action_id();
            $device = get_device($id);
            if (!$device) {
                throw new RuntimeException('Device not found.');
            }

            update_device($id, [
                'revoked_at' => null,
                'updated_at' => gmdate('c'),
            ]);

            log_action('device.restored', 'device', $id, ['device_uid' => $device['device_uid']]);
            flash('success', 'Device restored.');
            break;

        case 'device_delete':
            require_permission('devices.manage');
            $id = action_id();
            $device = get_device($id);
            if (!$device) {
                throw new RuntimeException('Device not found.');
            }

            delete_device($id);
            log_action('device.deleted', 'device', $id, ['device_uid' => $device['device_uid']]);
            flash('success', 'Device deleted.');
            $returnTo = 'devices.php';
            break;

        case 'admin_save':
            require_permission('permissions.manage');
            $id = post_string('id');
            $username = strtolower(post_string('username'));
            $role = post_string('role', 'viewer');
            $password = (string) ($_POST['password'] ?? '');
            $enabled = post_bool('enabled');

            if (!preg_match('/^[a-z0-9][a-z0-9._-]{2,31}$/', $username)) {
                throw new InvalidArgumentException('Admin username must be 3-32 lowercase characters.');
            }

            if (!in_array($role, ['owner', 'administrator', 'support', 'viewer'], true)) {
                throw new InvalidArgumentException('Invalid admin role.');
            }

            $data = [
                'username' => $username,
                'role' => $role,
                'enabled' => $enabled,
                'updated_at' => gmdate('c'),
            ];

            if ($id === '') {
                if (strlen($password) < 8) {
                    throw new InvalidArgumentException('New admin passwords must be at least 8 characters.');
                }

                $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                $created = create_admin_user($data);
                $newId = (string) ($created[0]['id'] ?? '');
                log_action('admin.created', 'admin', $newId, ['username' => $username, 'role' => $role]);
                flash('success', 'Admin account created.');
            } else {
                if ($password !== '') {
                    if (strlen($password) < 8) {
                        throw new InvalidArgumentException('Passwords must be at least 8 characters.');
                    }
                    $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                }

                update_admin_user($id, $data);
                log_action('admin.updated', 'admin', $id, ['username' => $username, 'role' => $role]);
                flash('success', 'Admin account updated.');
            }
            break;

        case 'admin_delete':
            require_permission('permissions.manage');
            $id = action_id();
            $admin = current_admin();

            if ((string) ($admin['id'] ?? '') === $id) {
                throw new InvalidArgumentException('You cannot delete your own admin account.');
            }

            delete_admin_user($id);
            log_action('admin.deleted', 'admin', $id);
            flash('success', 'Admin account deleted.');
            $returnTo = 'permissions.php';
            break;

        case 'release_save':
            require_permission('releases.manage');
            $id = post_string('id');
            $version = post_string('version');
            $channel = post_string('channel', 'stable');
            $status = post_string('status', 'draft');
            $minimum = post_string('min_supported_version');
            $releaseDate = normalize_datetime(post_string('release_date'));
            $downloadUrl = post_string('download_url');
            $notes = post_string('notes');

            if (!preg_match('/^[0-9A-Za-z][0-9A-Za-z._-]{0,31}$/', $version)) {
                throw new InvalidArgumentException('Invalid version string.');
            }

            if (!in_array($channel, ['stable', 'beta', 'nightly'], true) || !in_array($status, ['draft', 'published', 'retired'], true)) {
                throw new InvalidArgumentException('Invalid release state.');
            }

            $data = [
                'version' => $version,
                'channel' => $channel,
                'status' => $status,
                'min_supported_version' => $minimum !== '' ? $minimum : null,
                'release_date' => $releaseDate,
                'download_url' => $downloadUrl !== '' ? $downloadUrl : null,
                'notes' => $notes !== '' ? $notes : null,
                'updated_at' => gmdate('c'),
            ];

            if ($id === '') {
                $created = create_release($data);
                $newId = (string) ($created[0]['id'] ?? '');
                log_action('release.created', 'release', $newId, ['version' => $version, 'status' => $status]);
                flash('success', 'Release added.');
            } else {
                update_release($id, $data);
                log_action('release.updated', 'release', $id, ['version' => $version, 'status' => $status]);
                flash('success', 'Release updated.');
            }
            break;

        case 'release_delete':
            require_permission('releases.manage');
            $id = action_id();
            $release = get_release($id);
            if (!$release) {
                throw new RuntimeException('Release not found.');
            }

            delete_release($id);
            log_action('release.deleted', 'release', $id, ['version' => $release['version']]);
            flash('success', 'Release deleted.');
            $returnTo = 'releases.php';
            break;

        case 'settings_save':
            require_permission('settings.manage');
            $allowed = [
                'site_name',
                'maintenance_mode',
                'registration_enabled',
                'minimum_supported_version',
            ];

            foreach ($allowed as $key) {
                $value = post_string($key);
                if ($key === 'maintenance_mode' || $key === 'registration_enabled') {
                    $value = isset($_POST[$key]) ? 'true' : 'false';
                }

                set_setting($key, $value);
                log_action('setting.updated', 'setting', $key, ['value' => $value]);
            }

            flash('success', 'Settings saved.');
            break;

        default:
            throw new InvalidArgumentException('Unknown admin action.');
    }
} catch (Throwable $e) {
    flash('danger', $e->getMessage());
}

redirect_to($returnTo);
