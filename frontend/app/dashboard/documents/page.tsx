import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

export default function DocumentsPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-gray-900">Documents</h1>
        <p className="text-gray-600 mt-1">
          Manage team documents and files
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Documents</CardTitle>
          <CardDescription>
            Upload, view, and manage documents for your team
          </CardDescription>
        </CardHeader>
        <CardContent>
          <p className="text-gray-500">Coming soon...</p>
        </CardContent>
      </Card>
    </div>
  )
}
