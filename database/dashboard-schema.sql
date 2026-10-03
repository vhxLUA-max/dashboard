create table if not exists public.admin_users (
    id uuid primary key default gen_random_uuid(),
    username text not null unique,
    password_hash text not null,
    role text not null default 'viewer' check (role in ('owner','administrator','support','viewer')),
    enabled boolean not null default true,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists public.subscription_plans (
    id uuid primary key default gen_random_uuid(),
    code text not null unique,
    name text not null,
    description text,
    billing_interval text not null default 'month' check (billing_interval in ('month','year','lifetime')),
    duration_days integer,
    price numeric(10,2) not null default 0 check (price >= 0),
    currency text not null default 'PHP',
    active boolean not null default true,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists public.subscriptions (
    id uuid primary key default gen_random_uuid(),
    account_id uuid not null references public.secondary_accounts(id) on delete cascade,
    plan_id uuid references public.subscription_plans(id) on delete set null,
    status text not null default 'active' check (status in ('trialing','active','past_due','canceled','expired')),
    starts_at timestamptz not null default now(),
    expires_at timestamptz,
    auto_renew boolean not null default false,
    payment_status text not null default 'unpaid' check (payment_status in ('pending','paid','unpaid','refunded')),
    amount_paid numeric(10,2) not null default 0 check (amount_paid >= 0),
    payment_reference text,
    external_reference text,
    notes text,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists public.subscription_events (
    id uuid primary key default gen_random_uuid(),
    subscription_id uuid not null references public.subscriptions(id) on delete cascade,
    event_type text not null,
    old_status text,
    new_status text,
    details jsonb not null default '{}'::jsonb,
    created_at timestamptz not null default now()
);

create table if not exists public.devices (
    id uuid primary key default gen_random_uuid(),
    account_id uuid not null references public.secondary_accounts(id) on delete cascade,
    device_uid text not null unique,
    device_name text,
    app_version text,
    os_version text,
    first_seen_at timestamptz not null default now(),
    last_seen_at timestamptz,
    revoked_at timestamptz,
    notes text,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists public.activity_logs (
    id uuid primary key default gen_random_uuid(),
    actor_type text not null default 'admin',
    actor_id text,
    actor_name text,
    action text not null,
    entity_type text,
    entity_id text,
    details jsonb not null default '{}'::jsonb,
    ip_address text,
    created_at timestamptz not null default now()
);

create table if not exists public.app_releases (
    id uuid primary key default gen_random_uuid(),
    version text not null unique,
    channel text not null default 'stable' check (channel in ('stable','beta','nightly')),
    status text not null default 'draft' check (status in ('draft','published','retired')),
    min_supported_version text,
    release_date timestamptz,
    download_url text,
    notes text,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists public.system_settings (
    key text primary key,
    value text not null default '',
    updated_at timestamptz not null default now()
);
