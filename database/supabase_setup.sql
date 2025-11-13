-- ========================================
-- Manager Performance Dashboard
-- Supabase Database Setup Script
-- ========================================

-- Create profiles table
create table if not exists public.profiles (
  id uuid references auth.users on delete cascade primary key,
  email text unique not null,
  full_name text,
  avatar_url text,
  role text check (role in ('manager', 'employee', 'admin')) not null default 'employee',
  created_at timestamptz default now(),
  updated_at timestamptz default now()
);

-- Create teams table
create table if not exists public.teams (
  id uuid default gen_random_uuid() primary key,
  name text not null,
  description text,
  manager_id uuid references public.profiles(id) on delete set null,
  created_at timestamptz default now(),
  updated_at timestamptz default now()
);

-- Create team_members junction table
create table if not exists public.team_members (
  id uuid default gen_random_uuid() primary key,
  team_id uuid references public.teams(id) on delete cascade not null,
  employee_id uuid references public.profiles(id) on delete cascade not null,
  joined_at timestamptz default now(),
  unique(team_id, employee_id)
);

-- Create performance_metrics table
create table if not exists public.performance_metrics (
  id uuid default gen_random_uuid() primary key,
  employee_id uuid references public.profiles(id) on delete cascade not null,
  team_id uuid references public.teams(id) on delete cascade not null,
  metric_type text not null,
  metric_value numeric not null,
  metric_target numeric,
  period_start date not null,
  period_end date not null,
  notes text,
  created_at timestamptz default now(),
  updated_at timestamptz default now()
);

-- Create documents table
create table if not exists public.documents (
  id uuid default gen_random_uuid() primary key,
  team_id uuid references public.teams(id) on delete cascade not null,
  employee_id uuid references public.profiles(id) on delete cascade,
  uploaded_by uuid references public.profiles(id) on delete cascade not null,
  title text not null,
  description text,
  file_path text not null,
  file_type text not null,
  file_size bigint not null,
  bucket_name text not null default 'documents',
  category text check (category in ('performance_review', 'report', 'presentation', 'other')) default 'other',
  created_at timestamptz default now(),
  updated_at timestamptz default now()
);

-- Create activity_logs table
create table if not exists public.activity_logs (
  id uuid default gen_random_uuid() primary key,
  team_id uuid references public.teams(id) on delete cascade not null,
  user_id uuid references public.profiles(id) on delete cascade not null,
  action_type text not null,
  action_description text not null,
  metadata jsonb,
  created_at timestamptz default now()
);

-- ========================================
-- Indexes
-- ========================================

create index if not exists idx_team_members_team_id on public.team_members(team_id);
create index if not exists idx_team_members_employee_id on public.team_members(employee_id);
create index if not exists idx_performance_metrics_employee_id on public.performance_metrics(employee_id);
create index if not exists idx_performance_metrics_team_id on public.performance_metrics(team_id);
create index if not exists idx_performance_metrics_period on public.performance_metrics(period_start, period_end);
create index if not exists idx_documents_team_id on public.documents(team_id);
create index if not exists idx_documents_employee_id on public.documents(employee_id);
create index if not exists idx_activity_logs_team_id on public.activity_logs(team_id);
create index if not exists idx_activity_logs_created_at on public.activity_logs(created_at desc);

-- ========================================
-- Functions & Triggers
-- ========================================

-- Function to auto-update updated_at timestamp
create or replace function public.handle_updated_at()
returns trigger as $$
begin
  new.updated_at = now();
  return new;
end;
$$ language plpgsql;

-- Apply updated_at triggers
drop trigger if exists set_updated_at on public.profiles;
create trigger set_updated_at before update on public.profiles
  for each row execute function public.handle_updated_at();

drop trigger if exists set_updated_at on public.teams;
create trigger set_updated_at before update on public.teams
  for each row execute function public.handle_updated_at();

drop trigger if exists set_updated_at on public.performance_metrics;
create trigger set_updated_at before update on public.performance_metrics
  for each row execute function public.handle_updated_at();

drop trigger if exists set_updated_at on public.documents;
create trigger set_updated_at before update on public.documents
  for each row execute function public.handle_updated_at();

-- Function to auto-create profile on user signup
create or replace function public.handle_new_user()
returns trigger as $$
begin
  insert into public.profiles (id, email, full_name, role)
  values (
    new.id,
    new.email,
    new.raw_user_meta_data->>'full_name',
    coalesce(new.raw_user_meta_data->>'role', 'employee')
  );
  return new;
end;
$$ language plpgsql security definer;

drop trigger if exists on_auth_user_created on auth.users;
create trigger on_auth_user_created
  after insert on auth.users
  for each row execute function public.handle_new_user();

-- ========================================
-- Row Level Security Policies
-- ========================================

-- Enable RLS on all tables
alter table public.profiles enable row level security;
alter table public.teams enable row level security;
alter table public.team_members enable row level security;
alter table public.performance_metrics enable row level security;
alter table public.documents enable row level security;
alter table public.activity_logs enable row level security;

-- Profiles policies
drop policy if exists "Users can view own profile" on public.profiles;
create policy "Users can view own profile"
  on public.profiles for select
  using (auth.uid() = id);

drop policy if exists "Users can update own profile" on public.profiles;
create policy "Users can update own profile"
  on public.profiles for update
  using (auth.uid() = id);

-- Teams policies
drop policy if exists "Managers can view their teams" on public.teams;
create policy "Managers can view their teams"
  on public.teams for select
  using (
    auth.uid() = manager_id or
    exists (
      select 1 from public.team_members
      where team_id = teams.id and employee_id = auth.uid()
    )
  );

drop policy if exists "Admins can manage teams" on public.teams;
create policy "Admins can manage teams"
  on public.teams for all
  using (
    exists (
      select 1 from public.profiles
      where id = auth.uid() and role = 'admin'
    )
  );

-- Team members policies
drop policy if exists "Users can view team members" on public.team_members;
create policy "Users can view team members"
  on public.team_members for select
  using (
    exists (
      select 1 from public.teams
      where id = team_id and (
        manager_id = auth.uid() or
        exists (
          select 1 from public.team_members tm
          where tm.team_id = teams.id and tm.employee_id = auth.uid()
        )
      )
    )
  );

-- Performance metrics policies
drop policy if exists "Users can view relevant metrics" on public.performance_metrics;
create policy "Users can view relevant metrics"
  on public.performance_metrics for select
  using (
    employee_id = auth.uid() or
    exists (
      select 1 from public.teams
      where id = team_id and manager_id = auth.uid()
    )
  );

drop policy if exists "Managers can manage team metrics" on public.performance_metrics;
create policy "Managers can manage team metrics"
  on public.performance_metrics for all
  using (
    exists (
      select 1 from public.teams
      where id = team_id and manager_id = auth.uid()
    )
  );

-- Documents policies
drop policy if exists "Users can view team documents" on public.documents;
create policy "Users can view team documents"
  on public.documents for select
  using (
    exists (
      select 1 from public.teams
      where id = team_id and (
        manager_id = auth.uid() or
        exists (
          select 1 from public.team_members
          where team_id = teams.id and employee_id = auth.uid()
        )
      )
    )
  );

drop policy if exists "Team members can upload documents" on public.documents;
create policy "Team members can upload documents"
  on public.documents for insert
  with check (
    exists (
      select 1 from public.teams
      where id = team_id and (
        manager_id = auth.uid() or
        exists (
          select 1 from public.team_members
          where team_id = teams.id and employee_id = auth.uid()
        )
      )
    )
  );

drop policy if exists "Users can delete own documents" on public.documents;
create policy "Users can delete own documents"
  on public.documents for delete
  using (uploaded_by = auth.uid());

-- Activity logs policies
drop policy if exists "Users can view team activity" on public.activity_logs;
create policy "Users can view team activity"
  on public.activity_logs for select
  using (
    exists (
      select 1 from public.teams
      where id = team_id and (
        manager_id = auth.uid() or
        exists (
          select 1 from public.team_members
          where team_id = teams.id and employee_id = auth.uid()
        )
      )
    )
  );

drop policy if exists "Authenticated users can create logs" on public.activity_logs;
create policy "Authenticated users can create logs"
  on public.activity_logs for insert
  with check (auth.uid() = user_id);

-- ========================================
-- Storage Bucket & Policies
-- ========================================

-- Create documents bucket (if not exists)
insert into storage.buckets (id, name, public)
values ('documents', 'documents', false)
on conflict (id) do nothing;

-- Storage RLS policies
drop policy if exists "Team members can upload documents" on storage.objects;
create policy "Team members can upload documents"
  on storage.objects for insert
  with check (
    bucket_id = 'documents' and
    auth.role() = 'authenticated'
  );

drop policy if exists "Team members can view documents" on storage.objects;
create policy "Team members can view documents"
  on storage.objects for select
  using (
    bucket_id = 'documents' and
    auth.role() = 'authenticated'
  );

drop policy if exists "Users can delete own documents" on storage.objects;
create policy "Users can delete own documents"
  on storage.objects for delete
  using (
    bucket_id = 'documents' and
    auth.uid()::text = owner
  );

-- ========================================
-- Realtime Configuration
-- ========================================

-- Enable realtime for relevant tables
alter publication supabase_realtime add table public.performance_metrics;
alter publication supabase_realtime add table public.documents;
alter publication supabase_realtime add table public.activity_logs;

-- Done!
-- Next: Run database/supabase_seed.sql to add sample data
