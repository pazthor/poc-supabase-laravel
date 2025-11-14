# CLAUDE.md - AI Assistant Guide

**Last Updated:** 2025-11-13
**Project:** Manager Performance Dashboard POC
**Status:** Backend Complete | Frontend Planned

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Technology Stack](#technology-stack)
3. [Architecture & Patterns](#architecture--patterns)
4. [Development Environment](#development-environment)
5. [Codebase Structure](#codebase-structure)
6. [Key Conventions](#key-conventions)
7. [Common Development Tasks](#common-development-tasks)
8. [Database & Supabase Integration](#database--supabase-integration)
9. [API Endpoints Reference](#api-endpoints-reference)
10. [Testing Strategy](#testing-strategy)
11. [Important Constraints & Gotchas](#important-constraints--gotchas)
12. [Git Workflow](#git-workflow)

---

## Project Overview

This is a **Laravel 12 backend API** for a manager performance dashboard that integrates with **Supabase** for database, authentication, file storage, and real-time features. The backend is production-ready, while the frontend (Next.js + TypeScript) is planned but not yet implemented.

### Purpose
Enable managers to:
- Track team performance metrics (KPIs)
- View employee statistics and trends
- Manage team documents
- Monitor real-time activity logs

### Key Features
- ✅ Complete RESTful API with 17+ endpoints
- ✅ Supabase integration (PostgREST, Storage, Auth)
- ✅ Row-Level Security (RLS) for multi-tenant data access
- ✅ File upload/download with metadata management
- ✅ Activity logging for audit trails
- ✅ DDEV-based development environment
- ⏳ Frontend implementation (Next.js) - See NEXT_STEPS.md

---

## Technology Stack

### Backend
| Component | Technology | Version |
|-----------|-----------|---------|
| Framework | Laravel | 12.10.1 |
| Language | PHP | 8.3 |
| API Auth | Laravel Sanctum | 4.2 |
| HTTP Client | Guzzle (via Laravel Http) | Latest |
| Dev Environment | DDEV | v1.24.10 |
| Web Server | Nginx-FPM | - |
| Testing | PHPUnit | 11.5.3 |
| Linting | Laravel Pint | Latest |

### Frontend (Planned)
- **Next.js 15** with TypeScript
- **Tailwind CSS 4.0** + shadcn/ui
- **Recharts** for data visualization
- **Supabase JS Client** for real-time subscriptions

### External Services
- **Supabase** (PostgreSQL, PostgREST, GoTrue Auth, Storage, Realtime)

---

## Architecture & Patterns

### Service Layer Pattern
Business logic is encapsulated in **`app/Services/SupabaseService.php`**, which provides a clean interface to Supabase operations:

```php
// Dependency injection in controllers
public function __construct(protected SupabaseService $supabase) {}

// Usage
$data = $this->supabase->from('metrics', ['team_id' => 'eq.'.$teamId]);
$url = $this->supabase->uploadFile('documents', $path, $file);
```

**Benefits:**
- Controllers stay thin and focused on request/response
- Supabase logic is testable and reusable
- Easy to mock for testing

### Consistent API Response Format

**All API responses follow this structure:**

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

**Error responses:**
```json
{
  "success": false,
  "message": "Error description",
  "errors": { ... }
}
```

### Input Validation Pattern

Always validate requests before processing:

```php
$validator = Validator::make($request->all(), [
    'email' => 'required|email',
    'metric_value' => 'required|numeric|min:0'
]);

if ($validator->fails()) {
    return response()->json([
        'success' => false,
        'errors' => $validator->errors()
    ], 422);
}
```

### Activity Logging Pattern

Critical operations automatically log to `activity_logs` table:

```php
$this->supabase->insert('activity_logs', [
    'team_id' => $teamId,
    'user_id' => $userId,
    'action_type' => 'metric_created',
    'description' => "Created metric: {$metric['metric_type']}",
    'metadata' => json_encode($metric)
]);
```

---

## Development Environment

### DDEV Configuration

**Project Details:**
- **Name:** `manager-dashboard`
- **Type:** Laravel
- **PHP Version:** 8.3
- **Database:** MariaDB 10.11 (local dev only - Supabase is primary)
- **Primary URL:** https://manager-dashboard.ddev.site

### Essential DDEV Commands

```bash
# Start/stop environment
ddev start
ddev stop
ddev restart

# Access services
ddev ssh                    # SSH into web container
ddev mysql                  # Access MariaDB CLI
ddev logs -f                # Follow application logs
ddev describe               # Show project info

# Run commands inside container
ddev php artisan tinker
ddev composer install
ddev npm install
ddev npm run dev

# Execute Artisan commands
ddev artisan migrate
ddev artisan route:list
ddev artisan config:clear
```

### Environment Variables

**Required `.env` variables for Supabase:**

```env
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_ANON_KEY=your-anon-key
SUPABASE_SERVICE_ROLE_KEY=your-service-role-key
```

**Configuration loaded in:** `config/supabase.php`

---

## Codebase Structure

### Core Directories

```
├── app/
│   ├── Http/Controllers/Api/    # API endpoint handlers
│   │   ├── AuthController.php           # Authentication
│   │   ├── MetricsController.php        # Performance metrics CRUD
│   │   └── DocumentsController.php      # Document management
│   ├── Services/
│   │   └── SupabaseService.php          # ⭐ Core business logic
│   ├── Models/
│   │   └── User.php                     # Laravel User model (local)
│   └── Providers/
│       └── AppServiceProvider.php       # Service bindings
│
├── config/
│   ├── supabase.php                     # ⭐ Supabase configuration
│   ├── sanctum.php                      # API authentication
│   └── [standard Laravel configs]
│
├── database/
│   ├── supabase_setup.sql               # ⭐ Supabase schema (331 lines)
│   ├── supabase_seed.sql                # Sample data (221 lines)
│   ├── supabase_schema.md               # Schema documentation
│   └── migrations/                      # Laravel migrations (local dev)
│
├── routes/
│   ├── api.php                          # ⭐ All API route definitions
│   ├── web.php                          # Minimal web routes
│   └── console.php                      # Artisan commands
│
├── tests/
│   ├── Feature/                         # Feature tests
│   └── Unit/                            # Unit tests
│
├── .ddev/
│   └── config.yaml                      # DDEV configuration
│
├── README.md                            # Main project documentation
├── PROJECT_SUMMARY.md                   # Implementation summary
├── NEXT_STEPS.md                        # Frontend roadmap
└── CLAUDE.md                            # ⭐ This file
```

### File Importance Legend
- ⭐ **Critical** - Core functionality, frequently modified
- 🔧 **Configuration** - Change rarely, important for setup
- 📝 **Documentation** - Reference material

---

## Key Conventions

### 1. Naming Conventions

**Controllers:**
- Location: `app/Http/Controllers/Api/`
- Naming: `{Resource}Controller.php` (e.g., `MetricsController.php`)
- Methods: RESTful verbs (index, show, store, update, destroy)

**Routes:**
- Prefix: `/api/`
- Middleware: `api` (applied globally)
- Auth protection: `auth:sanctum` middleware

**Database (Supabase):**
- Tables: Plural, snake_case (e.g., `performance_metrics`)
- Columns: snake_case (e.g., `created_at`, `team_id`)
- Primary keys: `id` (UUID)
- Foreign keys: `{table}_id` (e.g., `team_id`)

### 2. Error Handling

**Always return appropriate HTTP status codes:**
- `200` - Success (GET, PATCH, DELETE)
- `201` - Created (POST)
- `400` - Bad request
- `401` - Unauthorized
- `404` - Not found
- `422` - Validation error
- `500` - Server error

**Include detailed error messages:**
```php
return response()->json([
    'success' => false,
    'message' => 'Resource not found',
    'errors' => ['id' => 'No metric found with the given ID']
], 404);
```

### 3. Code Style

**Laravel Pint enforces PSR-12 standards:**
```bash
./vendor/bin/pint          # Auto-fix code style
./vendor/bin/pint --test   # Check without fixing
```

**Key rules:**
- Use type hints for parameters and return types
- Prefer dependency injection over facades when possible
- Use short array syntax `[]` instead of `array()`
- Single quotes for strings without interpolation

### 4. Comments & Documentation

**Add docblocks for public methods:**
```php
/**
 * Retrieve all metrics with optional filtering.
 *
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function index(Request $request)
{
    // Implementation
}
```

**Inline comments for complex logic:**
```php
// Build PostgREST filters for team scoping
$filters = ['team_id' => 'eq.'.$teamId];
```

---

## Common Development Tasks

### Task 1: Adding a New API Endpoint

**Example: Add a "Mark Metric as Reviewed" endpoint**

1. **Add route** in `routes/api.php`:
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/metrics/{id}/review', [MetricsController::class, 'markAsReviewed']);
});
```

2. **Add controller method** in `app/Http/Controllers/Api/MetricsController.php`:
```php
public function markAsReviewed(Request $request, string $id)
{
    $userId = $request->user()->id;

    $result = $this->supabase->update('performance_metrics',
        ['id' => 'eq.'.$id],
        ['reviewed_by' => $userId, 'reviewed_at' => now()->toIso8601String()]
    );

    if (!$result || empty($result)) {
        return response()->json([
            'success' => false,
            'message' => 'Metric not found or unauthorized'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'message' => 'Metric marked as reviewed',
        'data' => $result[0]
    ]);
}
```

3. **Test the endpoint:**
```bash
ddev artisan route:list | grep review
curl -X PATCH https://manager-dashboard.ddev.site/api/metrics/{id}/review \
  -H "Authorization: Bearer {token}"
```

### Task 2: Adding a New SupabaseService Method

**Example: Add batch insert support**

Edit `app/Services/SupabaseService.php`:

```php
/**
 * Insert multiple records into a Supabase table.
 *
 * @param string $table
 * @param array $records Array of records to insert
 * @param array $options Additional options (e.g., prefer: return=representation)
 * @return array|null
 */
public function insertMany(string $table, array $records, array $options = []): ?array
{
    $endpoint = $this->baseUrl . '/rest/v1/' . $table;

    $headers = [
        'apikey' => $this->serviceRoleKey,
        'Authorization' => 'Bearer ' . $this->serviceRoleKey,
        'Content-Type' => 'application/json',
        'Prefer' => $options['prefer'] ?? 'return=representation'
    ];

    try {
        $response = Http::withHeaders($headers)->post($endpoint, $records);
        return $response->successful() ? $response->json() : null;
    } catch (\Exception $e) {
        \Log::error('Supabase batch insert failed: ' . $e->getMessage());
        return null;
    }
}
```

### Task 3: Running Database Migrations (Supabase)

**⚠️ Important:** Do NOT use `php artisan migrate` for production data!

**Setup Supabase database:**

1. Access Supabase SQL Editor (https://supabase.com/dashboard)
2. Run `database/supabase_setup.sql` to create tables/policies
3. Run `database/supabase_seed.sql` for test data

**Or use Supabase CLI:**
```bash
supabase db push --file database/supabase_setup.sql
```

**Local Laravel migrations** are only for DDEV's MariaDB (used for local testing, not production).

### Task 4: Testing API Endpoints

**Manual testing with curl:**

```bash
# Login to get token
TOKEN=$(curl -s -X POST https://manager-dashboard.ddev.site/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"manager@example.com","password":"password123"}' \
  | jq -r '.data.access_token')

# Use token for authenticated requests
curl -H "Authorization: Bearer $TOKEN" \
  https://manager-dashboard.ddev.site/api/metrics
```

**Automated testing with PHPUnit:**
```bash
ddev composer test
```

### Task 5: Debugging Issues

**Check application logs:**
```bash
ddev logs -f                         # Follow live logs
tail -f storage/logs/laravel.log     # View Laravel logs
```

**Enable debug mode** in `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

**Test Supabase connectivity:**
```bash
ddev artisan tinker

$supabase = app(\App\Services\SupabaseService::class);
$result = $supabase->from('profiles', [], ['limit' => 1]);
dd($result);
```

---

## Database & Supabase Integration

### Database Schema

**6 Core Tables:**

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `profiles` | User profiles (extends Supabase Auth) | id, email, full_name, role, avatar_url |
| `teams` | Organizational teams | id, name, manager_id, description |
| `team_members` | Team membership mapping | id, team_id, employee_id, joined_at |
| `performance_metrics` | KPI tracking | id, employee_id, team_id, metric_type, metric_value, metric_target, period_start, period_end |
| `documents` | File metadata | id, team_id, employee_id, uploaded_by, title, file_path, file_type, file_size |
| `activity_logs` | Audit trail | id, team_id, user_id, action_type, description, metadata (JSONB) |

**Complete schema:** See `database/supabase_schema.md` (331 lines)

### Row-Level Security (RLS)

**All tables have RLS policies enforced at the database level:**

**Profiles:**
- Users can view all profiles
- Users can only update their own profile

**Teams:**
- Managers can view teams they manage
- Admins can view all teams

**Performance Metrics:**
- Managers can view/edit their team's metrics
- Employees can view their own metrics (read-only)

**Documents:**
- Team members can view team documents
- Only document uploader or manager can delete

**Example RLS Policy:**
```sql
CREATE POLICY "Managers can view their team's metrics"
ON performance_metrics FOR SELECT
USING (
  team_id IN (
    SELECT id FROM teams WHERE manager_id = auth.uid()
  )
);
```

### PostgREST Filter Syntax

**SupabaseService uses PostgREST operators:**

```php
// Equality
$filters = ['team_id' => 'eq.123e4567-e89b-12d3-a456-426614174000'];

// Comparison
$filters = ['metric_value' => 'gte.80', 'metric_target' => 'lte.100'];

// Date range
$filters = [
    'period_start' => 'gte.2025-01-01',
    'period_end' => 'lte.2025-12-31'
];
```

**Common operators:**
- `eq.` - Equals
- `neq.` - Not equals
- `gt.` - Greater than
- `gte.` - Greater than or equal
- `lt.` - Less than
- `lte.` - Less than or equal
- `like.` - Pattern match
- `ilike.` - Case-insensitive pattern match
- `is.null` - Is null
- `not.is.null` - Is not null

### Storage Bucket Configuration

**Bucket:** `documents`

**Policies:**
- Public read access: No
- Authenticated users can upload
- Team-scoped access enforced via RLS
- File size limit: 50MB (configurable)

**File path convention:**
```
{team_id}/{document_id}/{filename}
```

---

## API Endpoints Reference

### Authentication Endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/auth/register` | POST | No | Register new user |
| `/api/auth/login` | POST | No | Login with email/password |
| `/api/auth/me` | GET | Yes | Get current user profile |
| `/api/auth/logout` | POST | Yes | Logout (client-side token invalidation) |

**Request/Response Examples:**

**POST `/api/auth/register`**
```json
// Request
{
  "email": "user@example.com",
  "password": "SecurePass123!",
  "full_name": "John Doe",
  "role": "manager"
}

// Response (201)
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": { "id": "...", "email": "..." },
    "access_token": "eyJhbGc...",
    "refresh_token": "...",
    "expires_in": 3600
  }
}
```

### Metrics Endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/metrics` | GET | Yes | List metrics with filters |
| `/api/metrics/statistics` | GET | Yes | Get aggregate statistics |
| `/api/metrics/{id}` | GET | Yes | Get single metric |
| `/api/metrics` | POST | Yes | Create new metric |
| `/api/metrics/{id}` | PATCH | Yes | Update metric |
| `/api/metrics/{id}` | DELETE | Yes | Delete metric |

**Query Parameters (GET):**
- `team_id` - Filter by team
- `employee_id` - Filter by employee
- `metric_type` - Filter by type
- `period_start` - Start date (ISO 8601)
- `period_end` - End date (ISO 8601)
- `limit` - Max results (default: 50)
- `offset` - Pagination offset (default: 0)

**POST `/api/metrics`**
```json
// Request
{
  "employee_id": "uuid",
  "team_id": "uuid",
  "metric_type": "sales_conversion",
  "metric_value": 85.5,
  "metric_target": 90.0,
  "period_start": "2025-01-01T00:00:00Z",
  "period_end": "2025-01-31T23:59:59Z"
}

// Response (201)
{
  "success": true,
  "message": "Metric created successfully",
  "data": { "id": "...", ... }
}
```

### Documents Endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/documents` | GET | Yes | List documents with filters |
| `/api/documents/{id}` | GET | Yes | Get document details |
| `/api/documents/upload` | POST | Yes | Upload file to storage |
| `/api/documents/{id}/download` | GET | Yes | Get download URL |
| `/api/documents/{id}` | PATCH | Yes | Update metadata |
| `/api/documents/{id}` | DELETE | Yes | Delete document + file |

**POST `/api/documents/upload`**
```bash
curl -X POST https://manager-dashboard.ddev.site/api/documents/upload \
  -H "Authorization: Bearer {token}" \
  -F "file=@report.pdf" \
  -F "team_id=uuid" \
  -F "employee_id=uuid" \
  -F "title=Q1 Performance Report" \
  -F "category=performance_review"
```

---

## Testing Strategy

### Test Structure

```
tests/
├── Feature/              # Integration tests (HTTP requests)
│   └── ExampleTest.php
└── Unit/                 # Unit tests (isolated logic)
    └── ExampleTest.php
```

### Running Tests

```bash
# Run all tests
ddev composer test

# Run specific test file
ddev php artisan test tests/Feature/AuthTest.php

# Run with coverage
ddev php artisan test --coverage
```

### Writing Tests

**Feature test example:**

```php
use Tests\TestCase;

class MetricsControllerTest extends TestCase
{
    public function test_can_create_metric()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/metrics', [
            'employee_id' => 'uuid',
            'team_id' => 'uuid',
            'metric_type' => 'sales',
            'metric_value' => 100,
            'metric_target' => 120
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['success', 'message', 'data']);
    }
}
```

**Unit test example:**

```php
public function test_supabase_service_builds_correct_filters()
{
    $service = app(\App\Services\SupabaseService::class);

    $result = $service->from('metrics', [
        'team_id' => 'eq.123',
        'metric_value' => 'gte.80'
    ]);

    $this->assertNotNull($result);
}
```

### Test Database

**Uses SQLite in-memory database** (configured in `phpunit.xml`):

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

**⚠️ Note:** Tests use local SQLite, not Supabase. Mock SupabaseService for integration tests.

---

## Important Constraints & Gotchas

### 1. Database Migrations

**⚠️ CRITICAL:** Do NOT use Eloquent models or Laravel migrations for Supabase tables!

- Supabase is the source of truth for production data
- Laravel migrations (`database/migrations/`) are only for local DDEV MariaDB
- Always modify Supabase schema via SQL Editor or Supabase CLI
- Update `database/supabase_setup.sql` when schema changes

### 2. Authentication Token Management

**Tokens are managed by Supabase Auth, NOT Laravel Sanctum:**

- Laravel Sanctum middleware validates tokens but doesn't generate them
- Tokens expire after 1 hour (configurable in Supabase dashboard)
- Client must handle token refresh using `refresh_token`
- Store tokens securely (httpOnly cookies recommended for web)

### 3. File Upload Limitations

**Max file size:** 50MB (configurable via Supabase dashboard)

**Allowed file types:** Enforced client-side + validation in controller:
```php
$validator = Validator::make($request->all(), [
    'file' => 'required|file|max:51200|mimes:pdf,doc,docx,xls,xlsx,png,jpg'
]);
```

**Storage path convention must be followed:**
```
{team_id}/{document_id}/{filename}
```

### 4. RLS Policy Debugging

**If queries return empty arrays unexpectedly:**

1. Check RLS policies in Supabase dashboard
2. Verify user has correct `role` in `profiles` table
3. Test query with service role key (bypasses RLS):
```php
// Temporarily use service role key for debugging
$this->supabase->from('metrics', [], [], useServiceKey: true);
```

4. Check Supabase logs in dashboard under "Logs" > "Postgres Logs"

### 5. CORS Configuration

**Laravel API expects frontend at:**
- Development: `http://localhost:3000` (Next.js default)
- Production: Configure in `config/cors.php`

**Update allowed origins:**
```php
'allowed_origins' => [
    env('FRONTEND_URL', 'http://localhost:3000')
],
```

### 6. Real-time Subscriptions

**Real-time is configured in Supabase but not yet implemented in backend:**

- Frontend will use Supabase JS client for real-time
- Backend doesn't need to handle WebSocket connections
- Real-time channels enabled: `performance_metrics`, `activity_logs`

### 7. Performance Considerations

**For large datasets:**

- Use pagination (`limit` and `offset` parameters)
- Consider caching frequently accessed data
- Optimize PostgREST queries with proper indexes (already created)
- Monitor Supabase dashboard for slow queries

**Indexes already created:**
- Foreign keys: `team_id`, `employee_id`, `uploaded_by`
- Date ranges: `period_start`, `period_end`, `created_at`
- Lookups: `email` (unique), `role`

---

## Git Workflow

### Branch Strategy

**Development happens on feature branches:**

- **Main branch:** `main` (production-ready code)
- **Feature branches:** `claude/claude-md-{session-id}` (auto-generated)

### Commit Message Convention

**Format:** `{type}: {description}`

**Types:**
- `feat:` - New feature
- `fix:` - Bug fix
- `refactor:` - Code refactoring
- `docs:` - Documentation changes
- `test:` - Test additions/changes
- `chore:` - Maintenance tasks

**Examples:**
```bash
git commit -m "feat: add metric review endpoint"
git commit -m "fix: correct file upload error handling"
git commit -m "docs: update API endpoint documentation"
```

### Push Strategy

**Always push to feature branch:**
```bash
git push -u origin claude/claude-md-{session-id}
```

**Retry logic for network failures:**
- Retry up to 4 times with exponential backoff (2s, 4s, 8s, 16s)
- Example:
```bash
git push || sleep 2 && git push || sleep 4 && git push
```

### Creating Pull Requests

**Use gh CLI** (if available):
```bash
gh pr create --title "feat: add metric review feature" \
  --body "## Summary
  - Added new endpoint for marking metrics as reviewed
  - Updated documentation

  ## Test Plan
  - [ ] Test endpoint with valid token
  - [ ] Test authorization rules
  - [ ] Verify activity log entry created"
```

**Or create PR via GitHub UI:**
1. Push feature branch
2. Navigate to repository on GitHub
3. Click "Compare & pull request"
4. Fill in description following template above

---

## Quick Reference: File Locations

### Controllers
- **Auth:** `app/Http/Controllers/Api/AuthController.php`
- **Metrics:** `app/Http/Controllers/Api/MetricsController.php`
- **Documents:** `app/Http/Controllers/Api/DocumentsController.php`

### Services
- **Supabase:** `app/Services/SupabaseService.php`

### Configuration
- **Supabase:** `config/supabase.php`
- **API routes:** `routes/api.php`
- **Environment:** `.env` (copy from `.env.example`)

### Database
- **Schema:** `database/supabase_schema.md`
- **Setup SQL:** `database/supabase_setup.sql`
- **Seed SQL:** `database/supabase_seed.sql`

### Documentation
- **Main README:** `README.md`
- **Project Summary:** `PROJECT_SUMMARY.md`
- **Frontend Roadmap:** `NEXT_STEPS.md`
- **AI Guide:** `CLAUDE.md` (this file)

---

## Getting Started Checklist

When starting work on this project, follow these steps:

- [ ] **Read this file completely** (CLAUDE.md)
- [ ] **Review README.md** for project overview
- [ ] **Start DDEV:** `ddev start`
- [ ] **Verify environment:** `ddev artisan route:list`
- [ ] **Check Supabase connection:** `ddev artisan tinker` → test SupabaseService
- [ ] **Review API routes:** `routes/api.php`
- [ ] **Understand schema:** `database/supabase_schema.md`
- [ ] **Test a sample endpoint:** `curl /api/health`

---

## Need Help?

### Documentation Files
1. **README.md** - Project overview and setup instructions
2. **PROJECT_SUMMARY.md** - Detailed implementation summary
3. **NEXT_STEPS.md** - Frontend development roadmap
4. **database/supabase_schema.md** - Complete database schema reference

### External Resources
- **Laravel Documentation:** https://laravel.com/docs/12.x
- **Supabase Documentation:** https://supabase.com/docs
- **DDEV Documentation:** https://ddev.readthedocs.io/
- **PostgREST API Reference:** https://postgrest.org/en/stable/api.html

### Debugging Tips
1. Enable debug mode: `APP_DEBUG=true` in `.env`
2. Check logs: `storage/logs/laravel.log`
3. Use Tinker for testing: `ddev artisan tinker`
4. Verify Supabase dashboard for query issues
5. Test with curl before writing integration tests

---

**Last Updated:** 2025-11-13
**Maintained By:** AI Assistants working on this project
**Update Frequency:** After significant architectural changes or new feature additions
