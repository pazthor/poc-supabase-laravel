# Manager Performance Dashboard - Frontend

Next.js 15 frontend application for the Manager Performance Dashboard POC.

## Tech Stack

- **Next.js 15** - React framework with App Router
- **TypeScript** - Type safety
- **Tailwind CSS** - Utility-first styling
- **Radix UI** - Accessible UI components
- **Supabase** - Backend services (auth, database, storage)
- **React Hook Form + Zod** - Form validation
- **Recharts** - Data visualization

## Getting Started

### Prerequisites

- Node.js 18+ installed
- Supabase project set up (see parent README)

### Installation

1. Install dependencies:

```bash
npm install
```

2. Create `.env.local` file:

```bash
cp .env.local.example .env.local
```

3. Update `.env.local` with your Supabase credentials:

```env
NEXT_PUBLIC_SUPABASE_URL=https://your-project.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=your-anon-key-here
NEXT_PUBLIC_API_URL=https://manager-dashboard.ddev.site/api
```

### Development

Run the development server:

```bash
npm run dev
```

Open [http://localhost:3000](http://localhost:3000) in your browser.

### Build

```bash
npm run build
npm run start
```

## Project Structure

```
frontend/
├── app/                    # Next.js App Router
│   ├── dashboard/         # Dashboard pages
│   ├── login/             # Login page
│   ├── register/          # Register page
│   ├── layout.tsx         # Root layout with AuthProvider
│   └── page.tsx           # Home page
├── components/            # React components
│   ├── ui/               # Reusable UI components
│   └── dashboard/        # Dashboard-specific components
├── contexts/             # React contexts
│   └── AuthContext.tsx   # Authentication context
├── hooks/                # Custom React hooks
│   ├── useUser.ts        # User hook
│   └── useTeams.ts       # Teams hook
├── lib/                  # Utilities
│   ├── supabase/         # Supabase clients
│   └── utils.ts          # Helper functions
└── types/                # TypeScript types
    ├── database.ts       # Database types
    ├── auth.ts           # Auth types
    └── api.ts            # API types
```

## Features

### Implemented

- ✅ Authentication (login/register)
- ✅ Protected routes with middleware
- ✅ Dashboard layout with navigation
- ✅ Profile management
- ✅ Team listing
- ✅ TypeScript types for all database tables

### Coming Soon

- 📊 Performance metrics CRUD
- 📈 Charts and visualizations
- 📁 Document management
- 🔔 Real-time activity feed
- 👥 Team management

## Authentication Flow

1. User registers at `/register`
2. Profile is created in Supabase
3. User is redirected to `/dashboard`
4. Middleware protects all `/dashboard/*` routes
5. Auth context provides user and profile data

## Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `NEXT_PUBLIC_SUPABASE_URL` | Supabase project URL | `https://xxx.supabase.co` |
| `NEXT_PUBLIC_SUPABASE_ANON_KEY` | Supabase anonymous key | `eyJh...` |
| `NEXT_PUBLIC_API_URL` | Laravel API URL | `https://api.example.com/api` |

## Development Notes

- All client components are marked with `'use client'`
- Server components use the server Supabase client
- Middleware handles auth and redirects
- Forms use React Hook Form with Zod validation
- UI components follow shadcn/ui patterns

## Troubleshooting

### "Module not found" errors

Make sure all dependencies are installed:

```bash
npm install
```

### Authentication not working

1. Check Supabase environment variables in `.env.local`
2. Verify Supabase project is active
3. Check browser console for errors

### Middleware redirecting incorrectly

Clear cookies and restart the dev server:

```bash
# Clear browser cookies for localhost:3000
# Then restart:
npm run dev
```

## License

This is a POC project for demonstration purposes.
