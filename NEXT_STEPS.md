# Next Steps - Future Development Tasks

This document outlines the tasks needed to complete the Manager Performance Dashboard POC.

## 🎯 Immediate Priorities

### 1. Frontend Implementation (Next.js + TypeScript)

#### 1.1 Initialize Next.js Project

```bash
# Navigate to parent directory
cd /home/pazthor/Code

# Create Next.js app with TypeScript
npx create-next-app@latest laravel-frontend --typescript --tailwind --app --no-src-dir
cd laravel-frontend
```

#### 1.2 Install Dependencies

```bash
# Supabase client
npm install @supabase/supabase-js @supabase/auth-helpers-nextjs

# UI Components
npm install @radix-ui/react-dialog @radix-ui/react-dropdown-menu
npm install @radix-ui/react-label @radix-ui/react-select
npm install @radix-ui/react-tabs @radix-ui/react-toast

# Charts
npm install recharts

# Forms
npm install react-hook-form @hookform/resolvers zod

# Utils
npm install clsx tailwind-merge lucide-react date-fns

# Dev dependencies
npm install -D @types/node @types/react @types/react-dom
```

#### 1.3 Configure Supabase Client

Create `lib/supabase.ts`:

```typescript
import { createClient } from '@supabase/supabase-js'

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL!
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!

export const supabase = createClient(supabaseUrl, supabaseAnonKey, {
  auth: {
    persistSession: true,
    autoRefreshToken: true,
  },
  realtime: {
    params: {
      eventsPerSecond: 10,
    },
  },
})

// Database types
export type Database = {
  public: {
    Tables: {
      profiles: { ... }
      teams: { ... }
      performance_metrics: { ... }
      documents: { ... }
      activity_logs: { ... }
    }
  }
}
```

#### 1.4 Environment Variables

Create `.env.local`:

```env
NEXT_PUBLIC_SUPABASE_URL=https://your-project.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=your-anon-key-here
NEXT_PUBLIC_API_URL=https://manager-dashboard.ddev.site/api
```

### 2. Authentication UI

#### 2.1 Login Page

Create `app/login/page.tsx`:
- Email/password form
- Remember me checkbox
- Error handling
- Redirect to dashboard on success

#### 2.2 Register Page

Create `app/register/page.tsx`:
- Sign up form with full name, email, password
- Role selection (manager/employee)
- Validation with Zod
- Auto-login after registration

#### 2.3 Auth Context

Create `app/contexts/AuthContext.tsx`:
- Global auth state
- Login/logout functions
- User profile data
- Token refresh handling

### 3. Dashboard Layout

#### 3.1 Main Layout

Create `app/dashboard/layout.tsx`:
- Sidebar navigation
- Header with user menu
- Team selector dropdown
- Logout button

#### 3.2 Dashboard Home

Create `app/dashboard/page.tsx`:
- Overview cards (total metrics, documents, team size)
- Recent activity feed
- Quick actions
- Performance charts

### 4. Performance Metrics Module

#### 4.1 Metrics List Page

Create `app/dashboard/metrics/page.tsx`:
- Data table with sorting/filtering
- Search by metric type
- Date range picker
- Export to CSV button

#### 4.2 Metrics Charts

Create `app/dashboard/metrics/charts/page.tsx`:
- Line chart for trends over time
- Bar chart for metric comparison
- Success rate pie chart
- Filter by employee/team

#### 4.3 Add/Edit Metric Form

Create `app/dashboard/metrics/new/page.tsx`:
- Form with validation
- Employee selector
- Metric type dropdown
- Target vs actual input
- Date range picker

### 5. Documents Module

#### 5.1 Documents List

Create `app/dashboard/documents/page.tsx`:
- Grid or table view toggle
- Filter by category
- Search by title
- Preview modal

#### 5.2 Document Upload

Create `app/dashboard/documents/upload/page.tsx`:
- Drag-and-drop file upload
- Multiple file support
- Progress indicator
- Category selection
- Preview before upload

#### 5.3 Document Viewer

Create `app/dashboard/documents/[id]/page.tsx`:
- File preview (PDF, images)
- Download button
- Edit metadata
- Delete confirmation

### 6. Real-time Features

#### 6.1 Realtime Subscriptions

Create `hooks/useRealtimeMetrics.ts`:

```typescript
export function useRealtimeMetrics(teamId: string) {
  const [metrics, setMetrics] = useState([])

  useEffect(() => {
    const subscription = supabase
      .channel('performance_metrics_changes')
      .on(
        'postgres_changes',
        {
          event: '*',
          schema: 'public',
          table: 'performance_metrics',
          filter: `team_id=eq.${teamId}`,
        },
        (payload) => {
          // Handle insert/update/delete
          console.log('Change received!', payload)
          // Update state
        }
      )
      .subscribe()

    return () => {
      subscription.unsubscribe()
    }
  }, [teamId])

  return metrics
}
```

#### 6.2 Activity Feed

Create `components/ActivityFeed.tsx`:
- Subscribe to activity_logs table
- Show real-time notifications
- Toast notifications for new activities
- Auto-refresh feed

### 7. Team Management (Optional Enhancement)

#### 7.1 Team List

Create `app/dashboard/teams/page.tsx`:
- List all teams (admin only)
- Team member counts
- Manager assignments

#### 7.2 Team Details

Create `app/dashboard/teams/[id]/page.tsx`:
- Team overview
- Member list
- Add/remove members
- Assign manager

### 8. UI Components

Create reusable components:

- `components/ui/button.tsx`
- `components/ui/card.tsx`
- `components/ui/input.tsx`
- `components/ui/table.tsx`
- `components/ui/dialog.tsx`
- `components/ui/toast.tsx`
- `components/ui/chart.tsx`

Use shadcn/ui for base components:

```bash
npx shadcn-ui@latest init
npx shadcn-ui@latest add button card input table dialog toast
```

### 9. TypeScript Types

Create `types/database.ts`:

```typescript
export interface Profile {
  id: string
  email: string
  full_name: string | null
  avatar_url: string | null
  role: 'manager' | 'employee' | 'admin'
  created_at: string
  updated_at: string
}

export interface Team {
  id: string
  name: string
  description: string | null
  manager_id: string | null
  created_at: string
  updated_at: string
}

export interface PerformanceMetric {
  id: string
  employee_id: string
  team_id: string
  metric_type: string
  metric_value: number
  metric_target: number | null
  period_start: string
  period_end: string
  notes: string | null
  created_at: string
  updated_at: string
}

export interface Document {
  id: string
  team_id: string
  employee_id: string | null
  uploaded_by: string
  title: string
  description: string | null
  file_path: string
  file_type: string
  file_size: number
  bucket_name: string
  category: 'performance_review' | 'report' | 'presentation' | 'other'
  created_at: string
  updated_at: string
}

export interface ActivityLog {
  id: string
  team_id: string
  user_id: string
  action_type: string
  action_description: string
  metadata: Record<string, any> | null
  created_at: string
}
```

## 🚀 Backend Enhancements

### 10. Laravel Improvements

#### 10.1 Add API Resource Classes

Create proper JSON transformers for consistent API responses:

```bash
php artisan make:resource ProfileResource
php artisan make:resource MetricResource
php artisan make:resource DocumentResource
```

#### 10.2 Add Request Classes

Create form request validators:

```bash
php artisan make:request StoreMetricRequest
php artisan make:request UpdateMetricRequest
php artisan make:request UploadDocumentRequest
```

#### 10.3 Add Middleware

Create custom middleware for Supabase token validation:

```bash
php artisan make:middleware ValidateSupabaseToken
```

#### 10.4 Add Tests

Create feature tests for API endpoints:

```bash
php artisan make:test Api/AuthControllerTest
php artisan make:test Api/MetricsControllerTest
php artisan make:test Api/DocumentsControllerTest
```

### 11. Additional API Endpoints

#### 11.1 Teams API

Create `TeamsController.php`:
- GET /api/teams - List teams
- GET /api/teams/{id} - Get team details
- POST /api/teams - Create team (admin only)
- PATCH /api/teams/{id} - Update team
- DELETE /api/teams/{id} - Delete team
- GET /api/teams/{id}/members - Get team members
- POST /api/teams/{id}/members - Add member
- DELETE /api/teams/{id}/members/{userId} - Remove member

#### 11.2 Activity Logs API

Create `ActivityLogsController.php`:
- GET /api/activity - List recent activity
- GET /api/activity?team_id={id} - Filter by team

## 📊 Data Visualization

### 12. Dashboard Charts

Implement the following charts using Recharts:

1. **Performance Trends**
   - Line chart showing metric values over time
   - Multiple lines for different metric types
   - Comparison with targets

2. **Team Comparison**
   - Bar chart comparing teams
   - Average metrics by team
   - Goal achievement rates

3. **Individual Performance**
   - Radar chart for employee metrics
   - Multi-dimensional performance view

4. **Success Rate**
   - Pie chart showing above/below target distribution
   - Donut chart with percentage labels

## 🔒 Security Enhancements

### 13. Security Improvements

1. **Rate Limiting**
   - Implement rate limiting on Laravel API
   - Protect against brute force attacks

2. **CORS Configuration**
   - Configure CORS for Next.js frontend
   - Whitelist specific origins

3. **Input Sanitization**
   - Add XSS protection
   - Validate file uploads strictly

4. **Token Refresh**
   - Implement automatic token refresh
   - Handle expired tokens gracefully

5. **RLS Testing**
   - Write tests to verify RLS policies
   - Ensure data isolation between teams

## 🧪 Testing

### 14. Test Suite

#### 14.1 Backend Tests

```bash
# API tests
php artisan test --filter=AuthControllerTest
php artisan test --filter=MetricsControllerTest
php artisan test --filter=DocumentsControllerTest

# Integration tests with Supabase
php artisan test --group=integration
```

#### 14.2 Frontend Tests

```bash
# Install testing dependencies
npm install -D @testing-library/react @testing-library/jest-dom jest

# Component tests
npm run test

# E2E tests with Playwright
npx playwright install
npm run test:e2e
```

## 📦 Deployment

### 15. Production Deployment

#### 15.1 Backend (Laravel)

Options:
- **Laravel Forge** - Managed Laravel hosting
- **DigitalOcean App Platform** - Containerized deployment
- **AWS ECS/Fargate** - Full control with Docker

#### 15.2 Frontend (Next.js)

Options:
- **Vercel** - Zero-config Next.js deployment
- **Netlify** - Alternative with edge functions
- **Cloudflare Pages** - Fast global CDN

#### 15.3 Environment Setup

Production checklist:
- [ ] Set up production Supabase project
- [ ] Configure environment variables
- [ ] Set up SSL certificates
- [ ] Configure CDN for static assets
- [ ] Set up monitoring (Sentry, New Relic)
- [ ] Configure backup strategy
- [ ] Set up CI/CD pipeline

## 🔄 CI/CD Pipeline

### 16. GitHub Actions Workflow

Create `.github/workflows/laravel.yml`:

```yaml
name: Laravel CI

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test
```

Create `.github/workflows/nextjs.yml`:

```yaml
name: Next.js CI

on: [push, pull_request]

jobs:
  build:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2
      - name: Setup Node
        uses: actions/setup-node@v2
        with:
          node-version: '18'
      - name: Install Dependencies
        run: npm ci
      - name: Build
        run: npm run build
      - name: Test
        run: npm test
```

## 📈 Performance Optimization

### 17. Optimization Tasks

1. **Database Indexes**
   - Already created in schema
   - Monitor slow queries

2. **API Caching**
   - Cache frequently accessed data
   - Use Redis for session storage

3. **Frontend Optimization**
   - Implement code splitting
   - Lazy load components
   - Optimize images with Next.js Image

4. **Real-time Optimization**
   - Debounce subscription updates
   - Batch real-time events

## 📚 Documentation

### 18. Additional Documentation

Create the following docs:

1. **API_REFERENCE.md** - Complete API documentation
2. **CONTRIBUTING.md** - Contribution guidelines
3. **DEPLOYMENT.md** - Deployment instructions
4. **TROUBLESHOOTING.md** - Common issues and solutions
5. **ARCHITECTURE.md** - System architecture diagram

## 🎨 UI/UX Enhancements

### 19. User Experience Improvements

1. **Dark Mode**
   - Toggle between light/dark themes
   - Respect system preferences

2. **Responsive Design**
   - Mobile-first approach
   - Tablet optimization

3. **Accessibility**
   - ARIA labels
   - Keyboard navigation
   - Screen reader support

4. **Loading States**
   - Skeleton loaders
   - Progress indicators
   - Error boundaries

## 🔔 Notifications

### 20. Notification System

1. **In-app Notifications**
   - Real-time activity notifications
   - Toast messages
   - Notification center

2. **Email Notifications** (Optional)
   - Weekly digest emails
   - Important activity alerts
   - Configure with Supabase Edge Functions

## 📱 Mobile App (Future)

### 21. React Native App

Consider building a mobile app:
- Use React Native with Supabase JS client
- Shared types with web app
- Push notifications
- Offline support

## 🏁 Summary

### Priority Order

**Phase 1** (Essential):
1. Frontend setup (Tasks 1-4)
2. Real-time features (Task 6)
3. Basic testing (Task 14.1)

**Phase 2** (Important):
4. Backend enhancements (Tasks 10-11)
5. Charts and visualizations (Task 12)
6. Security improvements (Task 13)

**Phase 3** (Nice to have):
7. Team management (Task 7)
8. Full test suite (Task 14)
9. CI/CD pipeline (Task 16)
10. Performance optimization (Task 17)

**Phase 4** (Future):
11. Deployment (Task 15)
12. Additional documentation (Task 18)
13. UI/UX enhancements (Task 19)
14. Notification system (Task 20)

---

**Note**: This document should be updated as tasks are completed and new requirements emerge.
