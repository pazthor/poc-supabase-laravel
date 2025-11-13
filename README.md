# Manager Performance Dashboard - POC

A proof-of-concept application demonstrating **Laravel 12 + Supabase** integration with a TypeScript-based Next.js frontend.

## 🎯 Project Overview

This is a manager dashboard where team managers can:
- 📊 View team performance metrics and analytics
- 📁 Manage documents (upload, view, delete)
- 🔔 Receive real-time updates on team activities
- 🔐 Access data with role-based security (Row Level Security)
- 👥 Manage team members and assignments

### Why This Stack?

- **Supabase**: Provides PostgreSQL database, realtime subscriptions, storage, and authentication
- **Laravel 12**: Modern PHP backend API with excellent HTTP client for Supabase integration
- **Next.js + TypeScript**: Type-safe frontend with server-side rendering capabilities

## 🛠 Tech Stack

### Backend
- **Laravel 12.10** - PHP backend API
- **Laravel Sanctum** - API authentication
- **Supabase PHP Integration** - Custom service class using Guzzle HTTP client
- **DDEV** - Local development environment

### Frontend (To be implemented - see NEXT_STEPS.md)
- **Next.js 15** - React framework with TypeScript
- **Supabase JS Client** - Direct database access with real-time subscriptions
- **Tailwind CSS + shadcn/ui** - Modern UI components
- **Recharts** - Performance charts and data visualization

### Database & Services
- **Supabase (PostgreSQL)** - Database with Row Level Security
- **Supabase Storage** - Document file storage
- **Supabase Auth** - User authentication
- **Supabase Realtime** - Live database subscriptions

## ✨ Features Demonstrated

### ✅ Supabase Features
- [x] **Authentication** - Email/password sign up and login via Supabase Auth
- [x] **Database (PostgREST)** - CRUD operations on PostgreSQL via REST API
- [x] **Storage** - File upload and management in Supabase buckets
- [x] **Row Level Security (RLS)** - Managers only see their team's data
- [x] **Realtime** - Database changes streamed to frontend (schema configured)

### ✅ Laravel Features
- [x] **API Routes** - RESTful endpoints for all resources
- [x] **Custom Supabase Service** - Wrapper for Supabase HTTP APIs
- [x] **Request Validation** - Input validation for all endpoints
- [x] **File Upload Handling** - Document upload with metadata tracking

## 📁 Project Structure

```
/home/pazthor/Code/laravel/           # Laravel 12 API
├── app/
│   ├── Http/Controllers/Api/         # API controllers
│   │   ├── AuthController.php        # Authentication endpoints
│   │   ├── MetricsController.php     # Performance metrics CRUD
│   │   └── DocumentsController.php   # Document management
│   └── Services/
│       └── SupabaseService.php       # Supabase HTTP client wrapper
├── config/
│   └── supabase.php                  # Supabase configuration
├── database/
│   ├── supabase_schema.md            # Complete database schema documentation
│   ├── supabase_setup.sql            # SQL script to create tables and RLS
│   └── supabase_seed.sql             # Sample data for testing
├── routes/
│   └── api.php                       # API route definitions
└── .env                              # Environment configuration
```

## 🚀 Setup Instructions

### Prerequisites

- **DDEV** installed (for local PHP/Laravel development)
- **Supabase Account** (free tier works fine)
- **Node.js 18+** (for Next.js frontend)
- **Git** (for version control)

### 1. Supabase Setup

#### 1.1 Create Supabase Project

1. Go to [https://supabase.com](https://supabase.com)
2. Click "Start your project"
3. Create a new project:
   - **Name**: `manager-dashboard`
   - **Database Password**: Save this securely
   - **Region**: Choose closest to you

#### 1.2 Set Up Database Schema

1. Go to **SQL Editor** in Supabase dashboard
2. Copy contents of `database/supabase_setup.sql`
3. Paste and run the SQL script
4. This creates:
   - 6 tables (profiles, teams, team_members, performance_metrics, documents, activity_logs)
   - All indexes for performance
   - Row Level Security policies
   - Triggers for auto-updating timestamps
   - Realtime enabled on key tables

#### 1.3 Create Storage Bucket

1. Go to **Storage** in Supabase dashboard
2. The `documents` bucket should be created automatically by the setup script
3. If not, create it manually:
   - **Name**: `documents`
   - **Public**: No (private bucket with RLS)

#### 1.4 Create Test Users

1. Go to **Authentication > Users** in Supabase dashboard
2. Click "Add user" and create test accounts:

**Manager User:**
```
Email: manager1@example.com
Password: TestPassword123!
User Metadata (JSON):
{
  "full_name": "Alice Johnson",
  "role": "manager"
}
```

**Employee User:**
```
Email: employee1@example.com
Password: TestPassword123!
User Metadata (JSON):
{
  "full_name": "Carol Williams",
  "role": "employee"
}
```

#### 1.5 Seed Sample Data

1. Go to **SQL Editor**
2. Copy contents of `database/supabase_seed.sql`
3. **IMPORTANT**: Update the email addresses in the script to match the users you created
4. Run the script to populate teams, metrics, and activity logs

#### 1.6 Get API Keys

1. Go to **Project Settings > API**
2. Copy the following:
   - **Project URL**: `https://xxxxx.supabase.co`
   - **anon public key**: Starts with `eyJh...`
   - **service_role key**: Starts with `eyJh...` (different from anon key)

### 2. Laravel Backend Setup

#### 2.1 Clone/Navigate to Project

```bash
cd /home/pazthor/Code/laravel
```

#### 2.2 Configure Environment

1. Update `.env` file with your Supabase credentials:

```env
APP_NAME="Manager Dashboard"
APP_URL="https://manager-dashboard.ddev.site"

# Supabase Configuration
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_KEY=your-anon-key-here
SUPABASE_SERVICE_ROLE_KEY=your-service-role-key-here
```

#### 2.3 Start DDEV

```bash
ddev start
```

The Laravel API will be available at: `https://manager-dashboard.ddev.site`

#### 2.4 Test the API

```bash
# Health check
curl https://manager-dashboard.ddev.site/api/health

# Test login (use the manager user you created)
curl -X POST https://manager-dashboard.ddev.site/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"manager1@example.com","password":"TestPassword123!"}'
```

You should receive an access token in the response.

### 3. Next.js Frontend Setup

**Note:** The frontend implementation is documented in `NEXT_STEPS.md` for future development.

For now, you can test the API using:
- **Thunder Client** (VS Code extension)
- **Postman**
- **curl** commands

## 📚 API Documentation

### Base URL

```
https://manager-dashboard.ddev.site/api
```

### Authentication Endpoints

#### Register User
```http
POST /auth/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "full_name": "John Doe",
  "role": "employee"
}
```

#### Login
```http
POST /auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}

Response:
{
  "success": true,
  "data": {
    "access_token": "eyJh...",
    "refresh_token": "...",
    "user": { ... }
  }
}
```

#### Get Current User
```http
GET /auth/me
Authorization: Bearer {access_token}
```

### Performance Metrics Endpoints

#### List Metrics
```http
GET /metrics?team_id={uuid}&metric_type={type}&start_date={date}
Authorization: Bearer {access_token}
```

#### Get Statistics
```http
GET /metrics/statistics?team_id={uuid}&employee_id={uuid}
Authorization: Bearer {access_token}
```

#### Create Metric
```http
POST /metrics
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "employee_id": "uuid",
  "team_id": "uuid",
  "metric_type": "tasks_completed",
  "metric_value": 45,
  "metric_target": 40,
  "period_start": "2024-01-01",
  "period_end": "2024-01-31",
  "notes": "Exceeded target"
}
```

### Documents Endpoints

#### List Documents
```http
GET /documents?team_id={uuid}&category={category}
Authorization: Bearer {access_token}
```

#### Upload Document
```http
POST /documents/upload
Authorization: Bearer {access_token}
Content-Type: multipart/form-data

team_id: uuid
title: "Q1 Performance Review"
category: "performance_review"
file: (binary)
```

#### Get Download URL
```http
GET /documents/{id}/download
Authorization: Bearer {access_token}
```

## 🔐 Row Level Security (RLS)

The database implements strict RLS policies:

- **Managers** can:
  - View all data for their assigned teams
  - Create/update metrics for their team members
  - Upload/view documents for their teams

- **Employees** can:
  - View their own performance metrics
  - View documents in their teams
  - Upload documents to their teams

## 🧪 Testing the POC

### 1. Test Authentication

```bash
# Login as manager
curl -X POST https://manager-dashboard.ddev.site/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "manager1@example.com",
    "password": "TestPassword123!"
  }'
```

Save the `access_token` from the response.

### 2. Test Metrics API

```bash
# Get metrics (replace {TOKEN})
curl -X GET "https://manager-dashboard.ddev.site/api/metrics" \
  -H "Authorization: Bearer {TOKEN}"

# Get statistics
curl -X GET "https://manager-dashboard.ddev.site/api/metrics/statistics" \
  -H "Authorization: Bearer {TOKEN}"
```

### 3. Verify Row Level Security

1. Login as a manager and note the team_id
2. Try to access another team's data - should return empty or error
3. Login as an employee and try to create metrics - should fail (only managers can)

## 📖 Documentation

- `database/supabase_schema.md` - Complete database schema
- `database/supabase_setup.sql` - Database setup script
- `database/supabase_seed.sql` - Sample data script
- `NEXT_STEPS.md` - Frontend implementation guide

## 🔧 Troubleshooting

### DDEV Issues

```bash
ddev restart    # Restart DDEV
ddev describe   # Check status
ddev logs       # View logs
```

### API Errors

1. Check `.env` file has correct Supabase credentials
2. Verify Supabase project is active
3. Check RLS policies are created correctly

## 📝 Next Steps

See `NEXT_STEPS.md` for:
- Frontend implementation guide (Next.js + TypeScript)
- Real-time subscription setup
- Additional features to implement
- Performance optimizations

---

**Built with ❤️ using Laravel 12 + Supabase**
