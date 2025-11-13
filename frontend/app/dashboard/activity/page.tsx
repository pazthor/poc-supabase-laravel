import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

export default function ActivityPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-gray-900">Activity Feed</h1>
        <p className="text-gray-600 mt-1">
          Real-time updates on team activities
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Activity</CardTitle>
          <CardDescription>
            Monitor real-time team activities and updates
          </CardDescription>
        </CardHeader>
        <CardContent>
          <p className="text-gray-500">Coming soon...</p>
        </CardContent>
      </Card>
    </div>
  )
}
