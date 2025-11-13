# Supabase Database Schema - Manager Performance Dashboard

## Overview
This schema supports a multi-tenant manager dashboard where managers can only view their own team's data through Row Level Security policies.

## Tables

### 1. profiles
Extends Supabase Auth users with additional profile information.

```sql
create table public.profiles (
  id uuid references auth.users on delete cascade primary key,
  email text unique not null,
  full_name text,
  avatar_url text,
  role text check (role in ('manager', 'employee', 'admin')) not null default 'employee',
  created_at timestamptz default now(),
  updated_at timestamptz default now()
);
```

### 2. teams
Organizational teams/departments.

```sql
create table public.teams (
  id uuid default gen_random_uuid() primary key,
  name text not null,
  description text,
  manager_id uuid references public.profiles(id) on delete set null,
  created_at timestamptz default now(),
  updated_at timestamptz default now()
);
```

### 3. team_members
Junction table for team membership (employees can be in multiple teams).

```sql
create table public.team_members (
  id uuid default gen_random_uuid() primary key,
  team_id uuid references public.teams(id) on delete cascade not null,
  employee_id uuid references public.profiles(id) on delete cascade not null,
  joined_at timestamptz default now(),
  unique(team_id, employee_id)
);
```

### 4. performance_metrics
KPIs and performance data for employees.

```sql
create table public.performance_metrics (
  id uuid default gen_random_uuid() primary key,
  employee_id uuid references public.profiles(id) on delete cascade not null,
  team_id uuid references public.teams(id) on delete cascade not null,
  metric_type text not null, -- e.g., 'tasks_completed', 'sales_revenue', 'customer_satisfaction'
  metric_value numeric not null,
  metric_target numeric,
  period_start date not null,
  period_end date not null,
  notes text,
  created_at timestamptz default now(),
  updated_at timestamptz default now()
);
```

### 5. documents
File metadata for uploaded documents.

```sql
create table public.documents (
  id uuid default gen_random_uuid() primary key,
  team_id uuid references public.teams(id) on delete cascade not null,
  employee_id uuid references public.profiles(id) on delete cascade,
  uploaded_by uuid references public.profiles(id) on delete cascade not null,
  title text not null,
  description text,
  file_path text not null, -- Path in Supabase Storage
  file_type text not null, -- MIME type
  file_size bigint not null, -- Size in bytes
  bucket_name text not null default 'documents',
  category text check (category in ('performance_review', 'report', 'presentation', 'other')) default 'other',
  created_at timestamptz default now(),
  updated_at timestamptz default now()
);
```

### 6. activity_logs
Real-time activity feed for dashboard updates.

```sql
create table public.activity_logs (
  id uuid default gen_random_uuid() primary key,
  team_id uuid references public.teams(id) on delete cascade not null,
  user_id uuid references public.profiles(id) on delete cascade not null,
  action_type text not null, -- e.g., 'metric_added', 'document_uploaded', 'team_updated'
  action_description text not null,
  metadata jsonb, -- Additional data about the action
  created_at timestamptz default now()
);
```

## Indexes

```sql
-- Performance optimizations
create index idx_team_members_team_id on public.team_members(team_id);
create index idx_team_members_employee_id on public.team_members(employee_id);
create index idx_performance_metrics_employee_id on public.performance_metrics(employee_id);
create index idx_performance_metrics_team_id on public.performance_metrics(team_id);
create index idx_performance_metrics_period on public.performance_metrics(period_start, period_end);
create index idx_documents_team_id on public.documents(team_id);
create index idx_documents_employee_id on public.documents(employee_id);
create index idx_activity_logs_team_id on public.activity_logs(team_id);
create index idx_activity_logs_created_at on public.activity_logs(created_at desc);
```

## Row Level Security (RLS) Policies

### profiles

```sql
alter table public.profiles enable row level security;

-- Users can view their own profile
create policy "Users can view own profile"
  on public.profiles for select
  using (auth.uid() = id);

-- Users can update their own profile
create policy "Users can update own profile"
  on public.profiles for update
  using (auth.uid() = id);
```

### teams

```sql
alter table public.teams enable row level security;

-- Managers can view teams they manage
create policy "Managers can view their teams"
  on public.teams for select
  using (
    auth.uid() = manager_id or
    exists (
      select 1 from public.team_members
      where team_id = teams.id and employee_id = auth.uid()
    )
  );

-- Only admins can create/update/delete teams (requires admin role check)
create policy "Admins can manage teams"
  on public.teams for all
  using (
    exists (
      select 1 from public.profiles
      where id = auth.uid() and role = 'admin'
    )
  );
```

### team_members

```sql
alter table public.team_members enable row level security;

-- Users can view team members of their own teams
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
```

### performance_metrics

```sql
alter table public.performance_metrics enable row level security;

-- Managers can view metrics for their team members
-- Employees can view their own metrics
create policy "Users can view relevant metrics"
  on public.performance_metrics for select
  using (
    employee_id = auth.uid() or
    exists (
      select 1 from public.teams
      where id = team_id and manager_id = auth.uid()
    )
  );

-- Managers can insert/update metrics for their team
create policy "Managers can manage team metrics"
  on public.performance_metrics for all
  using (
    exists (
      select 1 from public.teams
      where id = team_id and manager_id = auth.uid()
    )
  );
```

### documents

```sql
alter table public.documents enable row level security;

-- Users can view documents from their teams
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

-- Managers and team members can upload documents to their teams
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

-- Users can delete documents they uploaded
create policy "Users can delete own documents"
  on public.documents for delete
  using (uploaded_by = auth.uid());
```

### activity_logs

```sql
alter table public.activity_logs enable row level security;

-- Users can view activity logs from their teams
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

-- System/authenticated users can insert activity logs
create policy "Authenticated users can create logs"
  on public.activity_logs for insert
  with check (auth.uid() = user_id);
```

## Storage Buckets

### documents bucket
Create a storage bucket for document uploads with RLS policies.

```sql
-- Create bucket (via Supabase UI or SQL)
insert into storage.buckets (id, name, public)
values ('documents', 'documents', false);
```

### Storage RLS Policies

```sql
-- Users can upload to folders matching their team ID
create policy "Team members can upload documents"
  on storage.objects for insert
  with check (
    bucket_id = 'documents' and
    exists (
      select 1 from public.teams
      where id::text = (storage.foldername(name))[1] and (
        manager_id = auth.uid() or
        exists (
          select 1 from public.team_members
          where team_id = teams.id and employee_id = auth.uid()
        )
      )
    )
  );

-- Users can view documents from their teams
create policy "Team members can view documents"
  on storage.objects for select
  using (
    bucket_id = 'documents' and
    exists (
      select 1 from public.teams
      where id::text = (storage.foldername(name))[1] and (
        manager_id = auth.uid() or
        exists (
          select 1 from public.team_members
          where team_id = teams.id and employee_id = auth.uid()
        )
      )
    )
  );
```

## Realtime Configuration

Enable realtime for tables that need live updates:

```sql
alter publication supabase_realtime add table public.performance_metrics;
alter publication supabase_realtime add table public.documents;
alter publication supabase_realtime add table public.activity_logs;
```

## Functions & Triggers

### Auto-update updated_at timestamp

```sql
create or replace function public.handle_updated_at()
returns trigger as $$
begin
  new.updated_at = now();
  return new;
end;
$$ language plpgsql;

-- Apply to relevant tables
create trigger set_updated_at before update on public.profiles
  for each row execute function public.handle_updated_at();

create trigger set_updated_at before update on public.teams
  for each row execute function public.handle_updated_at();

create trigger set_updated_at before update on public.performance_metrics
  for each row execute function public.handle_updated_at();

create trigger set_updated_at before update on public.documents
  for each row execute function public.handle_updated_at();
```

### Auto-create profile on user signup

```sql
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

create trigger on_auth_user_created
  after insert on auth.users
  for each row execute function public.handle_new_user();
```

## Sample Data Queries

See `database/supabase_seed.sql` for sample data insertion queries.
