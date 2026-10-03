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
    active boolean not null default true,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists public.subscriptions (
    id uuid primary key default gen_random_uuid(),
    account_id uuid not null references public.secondary_accounts(id) on delete cascade,
    plan_id uuid references public.subscription_plans(id) on delete set null,
    status text not null default 'active' check (status in ('trialing','active','canceled','expired')),
    starts_at timestamptz not null default now(),
    expires_at timestamptz,
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
    updated_at timestamptz not null default now(),
    last_ip text
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


create index if not exists subscriptions_account_id_idx on public.subscriptions(account_id);
create index if not exists subscriptions_plan_id_idx on public.subscriptions(plan_id);
create index if not exists subscriptions_status_idx on public.subscriptions(status);
create index if not exists subscriptions_expires_at_idx on public.subscriptions(expires_at);
create index if not exists subscription_events_subscription_id_idx on public.subscription_events(subscription_id);
create index if not exists devices_account_id_idx on public.devices(account_id);
create index if not exists devices_last_seen_at_idx on public.devices(last_seen_at);
create index if not exists devices_last_ip_idx on public.devices(last_ip);
create index if not exists activity_logs_created_at_idx on public.activity_logs(created_at desc);
create index if not exists activity_logs_entity_idx on public.activity_logs(entity_type, entity_id);
create index if not exists app_releases_status_idx on public.app_releases(status, release_date desc);

alter table public.admin_users enable row level security;
alter table public.subscription_plans enable row level security;
alter table public.subscriptions enable row level security;
alter table public.subscription_events enable row level security;
alter table public.devices enable row level security;
alter table public.activity_logs enable row level security;
alter table public.app_releases enable row level security;
alter table public.system_settings enable row level security;

revoke all on table public.admin_users, public.subscription_plans, public.subscriptions, public.subscription_events, public.devices, public.activity_logs, public.app_releases, public.system_settings from public, anon, authenticated;
grant all on table public.admin_users, public.subscription_plans, public.subscriptions, public.subscription_events, public.devices, public.activity_logs, public.app_releases, public.system_settings to service_role;

create policy admin_users_server_only on public.admin_users for all to anon, authenticated using (false) with check (false);
create policy subscription_plans_server_only on public.subscription_plans for all to anon, authenticated using (false) with check (false);
create policy subscriptions_server_only on public.subscriptions for all to anon, authenticated using (false) with check (false);
create policy subscription_events_server_only on public.subscription_events for all to anon, authenticated using (false) with check (false);
create policy devices_server_only on public.devices for all to anon, authenticated using (false) with check (false);
create policy activity_logs_server_only on public.activity_logs for all to anon, authenticated using (false) with check (false);
create policy app_releases_server_only on public.app_releases for all to anon, authenticated using (false) with check (false);
create policy system_settings_server_only on public.system_settings for all to anon, authenticated using (false) with check (false);
