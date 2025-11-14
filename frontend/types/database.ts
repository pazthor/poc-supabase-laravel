// Database types for Supabase tables

export type Profile = {
  id: string
  email: string
  full_name: string | null
  avatar_url: string | null
  role: 'manager' | 'employee' | 'admin'
  created_at: string
  updated_at: string
}

export type Team = {
  id: string
  name: string
  description: string | null
  manager_id: string | null
  created_at: string
  updated_at: string
}

export type TeamMember = {
  id: string
  team_id: string
  employee_id: string
  joined_at: string
}

export type PerformanceMetric = {
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

export type Document = {
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

export type ActivityLog = {
  id: string
  team_id: string
  user_id: string
  action_type: string
  action_description: string
  metadata: Record<string, any> | null
  created_at: string
}

// Extended types with relations
export type PerformanceMetricWithEmployee = PerformanceMetric & {
  employee?: Profile
}

export type DocumentWithUploader = Document & {
  uploader?: Profile
}

export type TeamWithManager = Team & {
  manager?: Profile
  member_count?: number
}

export type ActivityLogWithUser = ActivityLog & {
  user?: Profile
}

// Database types
export type Database = {
  public: {
    Tables: {
      profiles: {
        Row: Profile
        Insert: Omit<Profile, 'created_at' | 'updated_at'>
        Update: Partial<Omit<Profile, 'id' | 'created_at' | 'updated_at'>>
      }
      teams: {
        Row: Team
        Insert: Omit<Team, 'id' | 'created_at' | 'updated_at'>
        Update: Partial<Omit<Team, 'id' | 'created_at' | 'updated_at'>>
      }
      team_members: {
        Row: TeamMember
        Insert: Omit<TeamMember, 'id' | 'joined_at'>
        Update: Partial<Omit<TeamMember, 'id' | 'joined_at'>>
      }
      performance_metrics: {
        Row: PerformanceMetric
        Insert: Omit<PerformanceMetric, 'id' | 'created_at' | 'updated_at'>
        Update: Partial<Omit<PerformanceMetric, 'id' | 'created_at' | 'updated_at'>>
      }
      documents: {
        Row: Document
        Insert: Omit<Document, 'id' | 'created_at' | 'updated_at'>
        Update: Partial<Omit<Document, 'id' | 'created_at' | 'updated_at'>>
      }
      activity_logs: {
        Row: ActivityLog
        Insert: Omit<ActivityLog, 'id' | 'created_at'>
        Update: Partial<Omit<ActivityLog, 'id' | 'created_at'>>
      }
    }
  }
}
