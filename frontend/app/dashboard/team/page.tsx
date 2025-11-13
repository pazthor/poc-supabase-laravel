import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

export default function TeamPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-gray-900">Team Management</h1>
        <p className="text-gray-600 mt-1">
          View and manage your team members
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Team</CardTitle>
          <CardDescription>
            Manage team members, roles, and assignments
          </CardDescription>
        </CardHeader>
        <CardContent>
          <p className="text-gray-500">Coming soon...</p>
        </CardContent>
      </Card>
    </div>
  )
}
