<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UserRegistrationTrendsExport;
use App\Exports\ActivityLogExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportingController extends Controller
{
    /**
     * Get user registration trends report
     */
    public function userRegistrationTrends(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'group_by' => 'in:daily,weekly,monthly'
        ]);

        // Default to last 30 days if no dates provided
        $startDate = $request->start_date ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $request->end_date ?? now()->format('Y-m-d');
        $groupBy = $request->group_by ?? 'daily';

        $query = User::whereBetween('created_at', [$startDate, $endDate]);
        
        switch ($groupBy) {
            case 'daily':
                $data = $query->selectRaw('DATE(created_at) as date, COUNT(*) as registrations')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                break;
            case 'weekly':
                $data = $query->selectRaw('YEARWEEK(created_at) as week, COUNT(*) as registrations')
                    ->groupBy('week')
                    ->orderBy('week')
                    ->get();
                break;
            case 'monthly':
                $data = $query->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as registrations')
                    ->groupBy('year', 'month')
                    ->orderBy('year')
                    ->orderBy('month')
                    ->get();
                break;
        }

        // Get summary statistics
        $totalRegistrations = User::whereBetween('created_at', [$startDate, $endDate])->count();
        $averagePerPeriod = $data->count() > 0 ? round($totalRegistrations / $data->count(), 2) : 0;
        $peakPeriod = $data->max('registrations');

        return response()->json([
            'data' => $data,
            'summary' => [
                'total_registrations' => $totalRegistrations,
                'average_per_period' => $averagePerPeriod,
                'peak_registrations' => $peakPeriod,
                'period_type' => $groupBy,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]
        ]);
    }

    /**
     * Get activity log report (all activities)
     */
    public function activityLogReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'user_id' => 'nullable|exists:users,id',
            'activity_type' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $query = Activity::with('causer');

        // Date range filter
        if ($request->start_date) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }

        // User filter
        if ($request->user_id) {
            $query->where('causer_id', $request->user_id);
        }

        // Activity type filter
        if ($request->activity_type) {
            $query->where('description', 'like', '%' . $request->activity_type . '%');
        }

        // Status filter (based on log properties)
        if ($request->status) {
            $query->where('properties->status', $request->status);
        }

        $activities = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        // Get summary statistics
        $summary = [
            'total_activities' => $query->count(),
            'unique_users' => $query->distinct('causer_id')->count('causer_id'),
            'date_range' => [
                'start' => $request->start_date,
                'end' => $request->end_date
            ],
            'filters_applied' => [
                'user_id' => $request->user_id,
                'activity_type' => $request->activity_type,
                'status' => $request->status
            ]
        ];

        return response()->json([
            'activities' => $activities,
            'summary' => $summary
        ]);
    }

    /**
     * Get activity log report for normal users only
     */
    public function normalUserActivityLog(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'user_id' => 'nullable|exists:users,id',
            'activity_type' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $query = Activity::with('causer')
            ->whereHas('causer', function($q) {
                $q->where('role', 'user');
            });

        // Date range filter
        if ($request->start_date) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }

        // User filter
        if ($request->user_id) {
            $query->where('causer_id', $request->user_id);
        }

        // Activity type filter
        if ($request->activity_type) {
            $query->where('description', 'like', '%' . $request->activity_type . '%');
        }

        $activities = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        // Get summary statistics
        $summary = [
            'total_activities' => $query->count(),
            'unique_users' => $query->distinct('causer_id')->count('causer_id'),
            'date_range' => [
                'start' => $request->start_date,
                'end' => $request->end_date
            ],
            'filters_applied' => [
                'user_id' => $request->user_id,
                'activity_type' => $request->activity_type
            ]
        ];

        return response()->json([
            'activities' => $activities,
            'summary' => $summary
        ]);
    }

    /**
     * Export user registration trends to Excel
     */
    public function exportUserRegistrationTrendsExcel(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'group_by' => 'in:daily,weekly,monthly'
        ]);

        // Default to last 30 days if no dates provided
        $requestData = $request->all();
        $requestData['start_date'] = $request->start_date ?? now()->subDays(30)->format('Y-m-d');
        $requestData['end_date'] = $request->end_date ?? now()->format('Y-m-d');
        $requestData['group_by'] = $request->group_by ?? 'daily';

        $filename = 'user_registration_trends_' . now()->format('Y_m_d_His') . '.xlsx';
        
        return Excel::download(
            new UserRegistrationTrendsExport($requestData),
            $filename
        );
    }

    /**
     * Export user registration trends to PDF
     */
    public function exportUserRegistrationTrendsPdf(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'group_by' => 'in:daily,weekly,monthly'
        ]);

        // Default to last 30 days if no dates provided
        $requestData = $request->all();
        $requestData['start_date'] = $request->start_date ?? now()->subDays(30)->format('Y-m-d');
        $requestData['end_date'] = $request->end_date ?? now()->format('Y-m-d');
        $requestData['group_by'] = $request->group_by ?? 'daily';

        // Get the data
        $trendsResponse = $this->userRegistrationTrends($request);
        $data = $trendsResponse->getData();

        $pdf = PDF::loadView('reports.user_registration_trends', [
            'data' => $data->data,
            'summary' => $data->summary
        ]);

        $filename = 'user_registration_trends_' . now()->format('Y_m_d_His') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Export activity log to Excel
     */
    public function exportActivityLogExcel(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'user_id' => 'nullable|exists:users,id',
            'activity_type' => 'nullable|string',
            'status' => 'nullable|string'
        ]);

        $filename = 'activity_log_' . now()->format('Y_m_d_His') . '.xlsx';
        
        return Excel::download(
            new ActivityLogExport($request->all()),
            $filename
        );
    }

    /**
     * Export normal user activity log to Excel
     */
    public function exportNormalUserActivityExcel(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'user_id' => 'nullable|exists:users,id',
            'activity_type' => 'nullable|string'
        ]);

        $filename = 'normal_user_activity_' . now()->format('Y_m_d_His') . '.xlsx';
        
        return Excel::download(
            new ActivityLogExport($request->all(), true), // true for normal users only
            $filename
        );
    }

    /**
     * Export normal user activity log to PDF
     */
    public function exportNormalUserActivityPdf(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'user_id' => 'nullable|exists:users,id',
            'activity_type' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        // Get the data
        $logResponse = $this->normalUserActivityLog($request);
        $data = $logResponse->getData();

        $pdf = PDF::loadView('reports.activity_log', [
            'activities' => $data->activities->data,
            'summary' => $data->summary
        ]);

        $filename = 'normal_user_activity_' . now()->format('Y_m_d_His') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Export activity log to PDF
     */
    public function exportActivityLogPdf(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'user_id' => 'nullable|exists:users,id',
            'activity_type' => 'nullable|string',
            'status' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        // Get the data using same logic as activityLogReport
        $query = Activity::with('causer');

        // Date range filter
        if ($request->start_date) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }

        // User filter
        if ($request->user_id) {
            $query->where('causer_id', $request->user_id);
        }

        // Activity type filter
        if ($request->activity_type) {
            $query->where('description', 'like', '%' . $request->activity_type . '%');
        }

        // Status filter (based on log properties)
        if ($request->status) {
            $query->where('properties->status', $request->status);
        }

        // Get all data for PDF (no pagination)
        $activities = $query->orderBy('created_at', 'desc')->get();

        // Get summary statistics
        $summary = [
            'total_activities' => $query->count(),
            'unique_users' => $query->distinct('causer_id')->count('causer_id'),
            'date_range' => [
                'start' => $request->start_date,
                'end' => $request->end_date
            ],
            'filters_applied' => [
                'user_id' => $request->user_id,
                'activity_type' => $request->activity_type,
                'status' => $request->status
            ]
        ];

        $pdf = PDF::loadView('reports.activity_log', [
            'activities' => $activities,
            'summary' => $summary
        ]);

        $filename = 'activity_log_' . now()->format('Y_m_d_His') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Get real-time dashboard data
     */
    public function realtimeData(Request $request)
    {
        $force = $request->query('force', false);
        
        // Cache for 30 seconds unless force=true
        $cacheKey = 'dashboard_realtime_data';
        if (!$force) {
            $cached = cache()->get($cacheKey);
            if ($cached) {
                return response()->json($cached);
            }
        }
        
        // Get current statistics
        $totalUsers = User::count();
        $activeUsers = User::where('status', true)->count();
        $newUsersToday = User::whereDate('created_at', today())->count();
        
        // Calculate revenue and orders (you'll need to add Order model)
        $totalOrders = 5; // Placeholder - calculate from orders table
        $totalRevenue = 504893; // Placeholder - calculate from orders
        
        // Calculate growth rate (compare with last month)
        $lastMonthUsers = User::where('created_at', '<=', now()->subMonth())->count();
        $growthRate = $lastMonthUsers > 0 
            ? round((($totalUsers - $lastMonthUsers) / $lastMonthUsers) * 100, 1) 
            : 0;
        
        $data = [
            "totalRevenue" => "KES " . number_format($totalRevenue),
            "totalOrders" => $totalOrders,
            "totalUsers" => $totalUsers,
            "growthRate" => $growthRate >= 0 ? "+{$growthRate}%" : "{$growthRate}%",
            "timestamp" => now()->toISOString()
        ];
        
        // Cache for 30 seconds
        cache()->put($cacheKey, $data, 30);
        
        return response()->json($data);
    }

    /**
     * Get comprehensive dashboard statistics
     */
    public function dashboardStats(Request $request)
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:365'
        ]);

        $days = $request->days ?? 30;
        $startDate = now()->subDays($days)->startOfDay();

        // User statistics
        $totalUsers = User::count();
        $activeUsers = User::where('status', true)->count();
        $newUsers = User::where('created_at', '>=', $startDate)->count();

        // Activity statistics
        $totalActivities = Activity::where('created_at', '>=', $startDate)->count();
        $uniqueActiveUsers = Activity::where('created_at', '>=', $startDate)
            ->distinct('causer_id')
            ->count('causer_id');

        // Daily registration trends for the period
        $dailyRegistrations = User::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as registrations')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top activities
        $topActivities = Activity::where('created_at', '>=', $startDate)
            ->selectRaw('description, COUNT(*) as count')
            ->groupBy('description')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json([
            'user_stats' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $totalUsers - $activeUsers,
                'new_users' => $newUsers,
                'activation_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0
            ],
            'activity_stats' => [
                'total_activities' => $totalActivities,
                'unique_active_users' => $uniqueActiveUsers,
                'activities_per_user' => $uniqueActiveUsers > 0 ? round($totalActivities / $uniqueActiveUsers, 2) : 0
            ],
            'trends' => [
                'daily_registrations' => $dailyRegistrations,
                'top_activities' => $topActivities
            ],
            'period' => [
                'days' => $days,
                'start_date' => $startDate->toDateString(),
                'end_date' => now()->toDateString()
            ]
        ]);
    }
}
