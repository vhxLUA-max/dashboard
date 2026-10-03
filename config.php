<?php
declare(strict_types=1);

return [
    'supabase_url' => rtrim((string) (getenv('SUPABASE_URL') ?: 'https://tfdirvgrsecgwpghnjlf.supabase.co'), '/'),
    'supabase_secret_key' => (string) (getenv('SUPABASE_SECRET_KEY') ?: ''),
];
