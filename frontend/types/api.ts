// API types

export type ApiResponse<T = any> = {
  success: boolean
  data?: T
  error?: string
  message?: string
}

export type PaginatedResponse<T> = {
  data: T[]
  meta: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

export type MetricStatistics = {
  total_metrics: number
  average_value: number
  average_target: number
  success_rate: number
  above_target: number
  below_target: number
  metrics_by_type: Record<string, number>
}

export type CreateMetricRequest = {
  employee_id: string
  team_id: string
  metric_type: string
  metric_value: number
  metric_target?: number
  period_start: string
  period_end: string
  notes?: string
}

export type UploadDocumentRequest = {
  team_id: string
  title: string
  category: 'performance_review' | 'report' | 'presentation' | 'other'
  file: File
  description?: string
  employee_id?: string
}
