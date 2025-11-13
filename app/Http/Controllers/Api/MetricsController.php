<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class MetricsController extends Controller
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Get all performance metrics (filtered by team for managers)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [];

        // Filter by team_id if provided
        if ($request->has('team_id')) {
            $filters['team_id'] = 'eq.' . $request->input('team_id');
        }

        // Filter by employee_id if provided
        if ($request->has('employee_id')) {
            $filters['employee_id'] = 'eq.' . $request->input('employee_id');
        }

        // Filter by metric_type if provided
        if ($request->has('metric_type')) {
            $filters['metric_type'] = 'eq.' . $request->input('metric_type');
        }

        // Date range filters
        if ($request->has('start_date')) {
            $filters['period_start'] = 'gte.' . $request->input('start_date');
        }

        if ($request->has('end_date')) {
            $filters['period_end'] = 'lte.' . $request->input('end_date');
        }

        // Pagination and ordering
        $options = [
            'order' => $request->input('order', 'created_at.desc'),
            'limit' => $request->input('limit', 50),
        ];

        if ($request->has('offset')) {
            $options['offset'] = $request->input('offset');
        }

        $response = $this->supabase->from('performance_metrics', $filters, $options);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch metrics',
                'error' => $response->json()
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $response->json()
        ]);
    }

    /**
     * Get a single performance metric
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $response = $this->supabase->from('performance_metrics', ['id' => 'eq.' . $id]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Metric not found',
                'error' => $response->json()
            ], 404);
        }

        $data = $response->json();

        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Metric not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data[0]
        ]);
    }

    /**
     * Create a new performance metric
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|uuid',
            'team_id' => 'required|uuid',
            'metric_type' => 'required|string|max:255',
            'metric_value' => 'required|numeric',
            'metric_target' => 'nullable|numeric',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $response = $this->supabase->insert('performance_metrics', $request->all());

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create metric',
                'error' => $response->json()
            ], 400);
        }

        // Create activity log
        $this->createActivityLog(
            $request->input('team_id'),
            $request->bearerToken(),
            'metric_added',
            'Added new ' . $request->input('metric_type') . ' metric',
            ['metric_type' => $request->input('metric_type')]
        );

        return response()->json([
            'success' => true,
            'message' => 'Metric created successfully',
            'data' => $response->json()
        ], 201);
    }

    /**
     * Update a performance metric
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'metric_type' => 'sometimes|string|max:255',
            'metric_value' => 'sometimes|numeric',
            'metric_target' => 'nullable|numeric',
            'period_start' => 'sometimes|date',
            'period_end' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $response = $this->supabase->update(
            'performance_metrics',
            ['id' => 'eq.' . $id],
            $request->all()
        );

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update metric',
                'error' => $response->json()
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Metric updated successfully',
            'data' => $response->json()
        ]);
    }

    /**
     * Delete a performance metric
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $response = $this->supabase->delete('performance_metrics', ['id' => 'eq.' . $id]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete metric',
                'error' => $response->json()
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Metric deleted successfully'
        ]);
    }

    /**
     * Get aggregate statistics for metrics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        $teamId = $request->input('team_id');
        $employeeId = $request->input('employee_id');

        $filters = [];

        if ($teamId) {
            $filters['team_id'] = 'eq.' . $teamId;
        }

        if ($employeeId) {
            $filters['employee_id'] = 'eq.' . $employeeId;
        }

        $response = $this->supabase->from('performance_metrics', $filters);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $response->json()
            ], 400);
        }

        $metrics = $response->json();

        // Calculate statistics
        $statistics = $this->calculateStatistics($metrics);

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Calculate statistics from metrics data
     *
     * @param array $metrics
     * @return array
     */
    private function calculateStatistics(array $metrics): array
    {
        $byType = [];
        $totalMetrics = count($metrics);
        $metricsAboveTarget = 0;

        foreach ($metrics as $metric) {
            $type = $metric['metric_type'];

            if (!isset($byType[$type])) {
                $byType[$type] = [
                    'count' => 0,
                    'total_value' => 0,
                    'total_target' => 0,
                    'above_target' => 0,
                ];
            }

            $byType[$type]['count']++;
            $byType[$type]['total_value'] += $metric['metric_value'];

            if ($metric['metric_target']) {
                $byType[$type]['total_target'] += $metric['metric_target'];

                if ($metric['metric_value'] >= $metric['metric_target']) {
                    $byType[$type]['above_target']++;
                    $metricsAboveTarget++;
                }
            }
        }

        // Calculate averages
        foreach ($byType as $type => &$stats) {
            $stats['average_value'] = $stats['count'] > 0 ? $stats['total_value'] / $stats['count'] : 0;
            $stats['average_target'] = $stats['count'] > 0 ? $stats['total_target'] / $stats['count'] : 0;
            $stats['success_rate'] = $stats['count'] > 0 ? ($stats['above_target'] / $stats['count']) * 100 : 0;
        }

        return [
            'total_metrics' => $totalMetrics,
            'metrics_above_target' => $metricsAboveTarget,
            'overall_success_rate' => $totalMetrics > 0 ? ($metricsAboveTarget / $totalMetrics) * 100 : 0,
            'by_metric_type' => $byType,
        ];
    }

    /**
     * Helper to create activity log
     *
     * @param string $teamId
     * @param string|null $token
     * @param string $actionType
     * @param string $description
     * @param array $metadata
     * @return void
     */
    private function createActivityLog(
        string $teamId,
        ?string $token,
        string $actionType,
        string $description,
        array $metadata = []
    ): void {
        if (!$token) {
            return;
        }

        // Get user ID from token
        $userResponse = $this->supabase->getUser($token);

        if ($userResponse->successful()) {
            $user = $userResponse->json();

            $this->supabase->insert('activity_logs', [
                'team_id' => $teamId,
                'user_id' => $user['id'],
                'action_type' => $actionType,
                'action_description' => $description,
                'metadata' => $metadata,
            ]);
        }
    }
}
