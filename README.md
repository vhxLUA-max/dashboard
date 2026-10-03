# Solis Dashboard

PHP admin dashboard for the Solis account and service management backend.

## Hosting

The public web root is \`www/\`. On Bot-Hosting, upload or deploy the repository so the host serves \`www/\` as the PHP site root.

Do not put the Supabase secret key in GitHub.

## Environment variables

Set these in the hosting environment:

- \`SUPABASE_URL\`
- \`SUPABASE_SECRET_KEY\`
- \`SOLIS_ADMIN_USERNAME\`
- \`SOLIS_ADMIN_PASSWORD_HASH\`
- \`SOLIS_TIMEZONE\` (optional, defaults to \`Asia/Manila\`)

The bootstrap admin uses the username and password hash from the \`SOLIS_ADMIN_*\` variables. After logging in, additional admin accounts can be created from Permissions.

Generate a password hash on a machine with PHP:

\`\`\`bash
php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
\`\`\`

Use the generated hash as \`SOLIS_ADMIN_PASSWORD_HASH\`.

## Supabase

The dashboard uses the server-side Supabase REST API. The secret key stays in the hosting environment and is never sent to the browser.

The dashboard uses these application tables:

- \`secondary_accounts\`
- \`secondary_provision_requests\`
- \`admin_users\`
- \`subscription_plans\`
- \`subscriptions\`
- \`subscription_events\`
- \`devices\`
- \`activity_logs\`
- \`app_releases\`
- \`system_settings\`

The dashboard schema is recorded in \`database/dashboard-schema.sql\`.

## Features

Dashboard, users, credential provisioning requests, user enable/disable, subscriptions, subscription plans, manual payment records, devices, activity logs, admin roles, release management, system settings, and health checks.
