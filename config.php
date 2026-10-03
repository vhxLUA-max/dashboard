<?php
declare(strict_types=1);

return [
    'supabase_url' => rtrim((string) (getenv('SUPABASE_URL') ?: 'https://tfdirvgrsecgwpghnjlf.supabase.co'), '/'),
    'supabase_secret_key' => (string) (getenv('SUPABASE_SECRET_KEY') ?: ''),
    'admin_username' => trim((string) (getenv('SOLIS_ADMIN_USERNAME') ?: '')),
    'admin_password_hash' => (string) (getenv('SOLIS_ADMIN_PASSWORD_HASH') ?: ''),
    'admin_password' => (string) (getenv('SOLIS_ADMIN_PASSWORD') ?: ''),
    'timezone' => (string) (getenv('SOLIS_TIMEZONE') ?: 'Asia/Manila'),
    'release_repository' => trim((string) (getenv('SOLIS_RELEASE_REPOSITORY') ?: 'solis-syst/solis-app-updates')),
];
