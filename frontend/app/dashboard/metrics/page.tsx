import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

export default function MetricsPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-gray-900">Performance Metrics</h1>
        <p className="text-gray-600 mt-1">
          Track and manage team performance metrics
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Metrics</CardTitle>
          <CardDescription>
            This page will display performance metrics, charts, and analytics
          </CardDescription>
        </CardHeader>
        <CardContent>
          <p className="text-gray-500">Coming soon...</p>
        </CardContent>
      </Card>
    </div>
  )
}
