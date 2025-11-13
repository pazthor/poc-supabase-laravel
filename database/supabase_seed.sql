-- ========================================
-- Manager Performance Dashboard
-- Sample Data Seed Script
-- ========================================
-- NOTE: Run this AFTER creating users via Supabase Auth UI or API
-- Replace the UUIDs below with actual user IDs from your auth.users table

-- ========================================
-- Sample Profiles (managers and employees)
-- ========================================
-- These will be auto-created by the trigger when users sign up
-- But you can manually insert if needed for testing

-- Example manual inserts (use actual auth.users IDs):
-- INSERT INTO public.profiles (id, email, full_name, role) VALUES
--   ('11111111-1111-1111-1111-111111111111', 'manager1@example.com', 'Alice Johnson', 'manager'),
--   ('22222222-2222-2222-2222-222222222222', 'manager2@example.com', 'Bob Smith', 'manager'),
--   ('33333333-3333-3333-3333-333333333333', 'employee1@example.com', 'Carol Williams', 'employee'),
--   ('44444444-4444-4444-4444-444444444444', 'employee2@example.com', 'David Brown', 'employee'),
--   ('55555555-5555-5555-5555-555555555555', 'employee3@example.com', 'Eve Davis', 'employee'),
--   ('66666666-6666-6666-6666-666666666666', 'employee4@example.com', 'Frank Miller', 'employee'),
--   ('77777777-7777-7777-7777-777777777777', 'admin@example.com', 'Admin User', 'admin');

-- ========================================
-- Sample Teams
-- ========================================

-- Create sample teams (update manager_id with real UUIDs from profiles)
DO $$
DECLARE
  manager1_id uuid;
  manager2_id uuid;
  team1_id uuid;
  team2_id uuid;
BEGIN
  -- Get manager IDs (adjust emails to match your actual data)
  SELECT id INTO manager1_id FROM public.profiles WHERE email = 'manager1@example.com' LIMIT 1;
  SELECT id INTO manager2_id FROM public.profiles WHERE email = 'manager2@example.com' LIMIT 1;

  -- Only proceed if managers exist
  IF manager1_id IS NOT NULL AND manager2_id IS NOT NULL THEN
    -- Insert teams
    INSERT INTO public.teams (id, name, description, manager_id) VALUES
      (gen_random_uuid(), 'Engineering Team', 'Software development and engineering', manager1_id),
      (gen_random_uuid(), 'Sales Team', 'Sales and business development', manager2_id)
    RETURNING id INTO team1_id;

    RAISE NOTICE 'Sample teams created successfully';
  ELSE
    RAISE NOTICE 'Managers not found. Please create manager users first.';
  END IF;
END $$;

-- ========================================
-- Sample Team Members
-- ========================================

-- Assign employees to teams
DO $$
DECLARE
  eng_team_id uuid;
  sales_team_id uuid;
  emp1_id uuid;
  emp2_id uuid;
  emp3_id uuid;
  emp4_id uuid;
BEGIN
  -- Get team IDs
  SELECT id INTO eng_team_id FROM public.teams WHERE name = 'Engineering Team' LIMIT 1;
  SELECT id INTO sales_team_id FROM public.teams WHERE name = 'Sales Team' LIMIT 1;

  -- Get employee IDs
  SELECT id INTO emp1_id FROM public.profiles WHERE email = 'employee1@example.com' LIMIT 1;
  SELECT id INTO emp2_id FROM public.profiles WHERE email = 'employee2@example.com' LIMIT 1;
  SELECT id INTO emp3_id FROM public.profiles WHERE email = 'employee3@example.com' LIMIT 1;
  SELECT id INTO emp4_id FROM public.profiles WHERE email = 'employee4@example.com' LIMIT 1;

  -- Only proceed if data exists
  IF eng_team_id IS NOT NULL AND sales_team_id IS NOT NULL THEN
    IF emp1_id IS NOT NULL THEN
      INSERT INTO public.team_members (team_id, employee_id) VALUES
        (eng_team_id, emp1_id),
        (eng_team_id, emp2_id);
    END IF;

    IF emp3_id IS NOT NULL THEN
      INSERT INTO public.team_members (team_id, employee_id) VALUES
        (sales_team_id, emp3_id),
        (sales_team_id, emp4_id);
    END IF;

    RAISE NOTICE 'Team members assigned successfully';
  ELSE
    RAISE NOTICE 'Teams or employees not found. Please create them first.';
  END IF;
END $$;

-- ========================================
-- Sample Performance Metrics
-- ========================================

-- Add sample performance metrics
DO $$
DECLARE
  eng_team_id uuid;
  sales_team_id uuid;
  emp1_id uuid;
  emp2_id uuid;
  emp3_id uuid;
  emp4_id uuid;
BEGIN
  -- Get team and employee IDs
  SELECT id INTO eng_team_id FROM public.teams WHERE name = 'Engineering Team' LIMIT 1;
  SELECT id INTO sales_team_id FROM public.teams WHERE name = 'Sales Team' LIMIT 1;
  SELECT id INTO emp1_id FROM public.profiles WHERE email = 'employee1@example.com' LIMIT 1;
  SELECT id INTO emp2_id FROM public.profiles WHERE email = 'employee2@example.com' LIMIT 1;
  SELECT id INTO emp3_id FROM public.profiles WHERE email = 'employee3@example.com' LIMIT 1;
  SELECT id INTO emp4_id FROM public.profiles WHERE email = 'employee4@example.com' LIMIT 1;

  IF eng_team_id IS NOT NULL AND emp1_id IS NOT NULL THEN
    -- Engineering team metrics
    INSERT INTO public.performance_metrics (employee_id, team_id, metric_type, metric_value, metric_target, period_start, period_end, notes) VALUES
      -- Employee 1 (last 3 months)
      (emp1_id, eng_team_id, 'tasks_completed', 45, 40, CURRENT_DATE - INTERVAL '3 months', CURRENT_DATE - INTERVAL '2 months', 'Exceeded monthly target'),
      (emp1_id, eng_team_id, 'code_reviews', 32, 30, CURRENT_DATE - INTERVAL '3 months', CURRENT_DATE - INTERVAL '2 months', 'Good peer review participation'),
      (emp1_id, eng_team_id, 'tasks_completed', 42, 40, CURRENT_DATE - INTERVAL '2 months', CURRENT_DATE - INTERVAL '1 month', 'On track'),
      (emp1_id, eng_team_id, 'code_reviews', 28, 30, CURRENT_DATE - INTERVAL '2 months', CURRENT_DATE - INTERVAL '1 month', 'Slightly below target'),
      (emp1_id, eng_team_id, 'tasks_completed', 48, 40, CURRENT_DATE - INTERVAL '1 month', CURRENT_DATE, 'Excellent performance'),
      (emp1_id, eng_team_id, 'code_reviews', 35, 30, CURRENT_DATE - INTERVAL '1 month', CURRENT_DATE, 'Above target'),

      -- Employee 2
      (emp2_id, eng_team_id, 'tasks_completed', 38, 40, CURRENT_DATE - INTERVAL '3 months', CURRENT_DATE - INTERVAL '2 months', 'Close to target'),
      (emp2_id, eng_team_id, 'bug_fixes', 15, 12, CURRENT_DATE - INTERVAL '3 months', CURRENT_DATE - INTERVAL '2 months', 'Exceeded target'),
      (emp2_id, eng_team_id, 'tasks_completed', 41, 40, CURRENT_DATE - INTERVAL '2 months', CURRENT_DATE - INTERVAL '1 month', 'Met target'),
      (emp2_id, eng_team_id, 'bug_fixes', 14, 12, CURRENT_DATE - INTERVAL '2 months', CURRENT_DATE - INTERVAL '1 month', 'Good performance'),
      (emp2_id, eng_team_id, 'tasks_completed', 43, 40, CURRENT_DATE - INTERVAL '1 month', CURRENT_DATE, 'Above target'),
      (emp2_id, eng_team_id, 'bug_fixes', 18, 12, CURRENT_DATE - INTERVAL '1 month', CURRENT_DATE, 'Excellent bug resolution');
  END IF;

  IF sales_team_id IS NOT NULL AND emp3_id IS NOT NULL THEN
    -- Sales team metrics
    INSERT INTO public.performance_metrics (employee_id, team_id, metric_type, metric_value, metric_target, period_start, period_end, notes) VALUES
      -- Employee 3
      (emp3_id, sales_team_id, 'deals_closed', 12, 10, CURRENT_DATE - INTERVAL '3 months', CURRENT_DATE - INTERVAL '2 months', 'Exceeded target'),
      (emp3_id, sales_team_id, 'revenue_generated', 125000, 100000, CURRENT_DATE - INTERVAL '3 months', CURRENT_DATE - INTERVAL '2 months', 'Strong quarter'),
      (emp3_id, sales_team_id, 'deals_closed', 15, 10, CURRENT_DATE - INTERVAL '2 months', CURRENT_DATE - INTERVAL '1 month', 'Outstanding performance'),
      (emp3_id, sales_team_id, 'revenue_generated', 145000, 100000, CURRENT_DATE - INTERVAL '2 months', CURRENT_DATE - INTERVAL '1 month', 'Best month yet'),
      (emp3_id, sales_team_id, 'deals_closed', 11, 10, CURRENT_DATE - INTERVAL '1 month', CURRENT_DATE, 'Consistent performer'),
      (emp3_id, sales_team_id, 'revenue_generated', 115000, 100000, CURRENT_DATE - INTERVAL '1 month', CURRENT_DATE, 'Above target'),

      -- Employee 4
      (emp4_id, sales_team_id, 'deals_closed', 8, 10, CURRENT_DATE - INTERVAL '3 months', CURRENT_DATE - INTERVAL '2 months', 'Below target - new hire ramp-up'),
      (emp4_id, sales_team_id, 'revenue_generated', 78000, 100000, CURRENT_DATE - INTERVAL '3 months', CURRENT_DATE - INTERVAL '2 months', 'Building pipeline'),
      (emp4_id, sales_team_id, 'deals_closed', 10, 10, CURRENT_DATE - INTERVAL '2 months', CURRENT_DATE - INTERVAL '1 month', 'Met target'),
      (emp4_id, sales_team_id, 'revenue_generated', 95000, 100000, CURRENT_DATE - INTERVAL '2 months', CURRENT_DATE - INTERVAL '1 month', 'Good improvement'),
      (emp4_id, sales_team_id, 'deals_closed', 13, 10, CURRENT_DATE - INTERVAL '1 month', CURRENT_DATE, 'Exceeded target'),
      (emp4_id, sales_team_id, 'revenue_generated', 128000, 100000, CURRENT_DATE - INTERVAL '1 month', CURRENT_DATE, 'Great growth');
  END IF;

  RAISE NOTICE 'Performance metrics created successfully';
END $$;

-- ========================================
-- Sample Activity Logs
-- ========================================

-- Add recent activity logs
DO $$
DECLARE
  eng_team_id uuid;
  sales_team_id uuid;
  manager1_id uuid;
  emp1_id uuid;
  emp3_id uuid;
BEGIN
  SELECT id INTO eng_team_id FROM public.teams WHERE name = 'Engineering Team' LIMIT 1;
  SELECT id INTO sales_team_id FROM public.teams WHERE name = 'Sales Team' LIMIT 1;
  SELECT id INTO manager1_id FROM public.profiles WHERE email = 'manager1@example.com' LIMIT 1;
  SELECT id INTO emp1_id FROM public.profiles WHERE email = 'employee1@example.com' LIMIT 1;
  SELECT id INTO emp3_id FROM public.profiles WHERE email = 'employee3@example.com' LIMIT 1;

  IF eng_team_id IS NOT NULL AND manager1_id IS NOT NULL THEN
    INSERT INTO public.activity_logs (team_id, user_id, action_type, action_description, metadata, created_at) VALUES
      (eng_team_id, manager1_id, 'metric_added', 'Added performance metrics for Q1 2024',
        '{"metric_type": "tasks_completed", "count": 6}'::jsonb, NOW() - INTERVAL '2 hours'),
      (eng_team_id, emp1_id, 'document_uploaded', 'Uploaded Q1 Performance Review',
        '{"file_name": "q1_review.pdf", "file_size": 245678}'::jsonb, NOW() - INTERVAL '1 hour'),
      (eng_team_id, manager1_id, 'team_updated', 'Updated team description',
        '{"field": "description"}'::jsonb, NOW() - INTERVAL '30 minutes'),
      (sales_team_id, emp3_id, 'document_uploaded', 'Uploaded Sales Report',
        '{"file_name": "sales_report_q1.xlsx", "file_size": 89234}'::jsonb, NOW() - INTERVAL '15 minutes');
  END IF;

  RAISE NOTICE 'Activity logs created successfully';
END $$;

-- ========================================
-- Verification Queries
-- ========================================

-- Count records in each table
SELECT
  'profiles' as table_name, COUNT(*) as count FROM public.profiles
UNION ALL
SELECT 'teams', COUNT(*) FROM public.teams
UNION ALL
SELECT 'team_members', COUNT(*) FROM public.team_members
UNION ALL
SELECT 'performance_metrics', COUNT(*) FROM public.performance_metrics
UNION ALL
SELECT 'activity_logs', COUNT(*) FROM public.activity_logs;

-- Display team structure
SELECT
  t.name as team_name,
  p_manager.full_name as manager_name,
  COUNT(tm.employee_id) as employee_count
FROM public.teams t
LEFT JOIN public.profiles p_manager ON t.manager_id = p_manager.id
LEFT JOIN public.team_members tm ON t.id = tm.team_id
GROUP BY t.name, p_manager.full_name;
