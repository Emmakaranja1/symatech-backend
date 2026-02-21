<!DOCTYPE html>
<html>
<head>
    <title>Activity Log Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .summary {
            margin-bottom: 30px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .summary-table th, .summary-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .summary-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .data-table th {
            background-color: #e3f2fd;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .activity-description {
            max-width: 200px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Activity Log Report</h1>
        <p>Generated on: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <div class="summary">
        <h2>Summary Statistics</h2>
        <table class="summary-table">
            <tr>
                <th>Total Activities</th>
                <td>{{ $summary['total_activities'] }}</td>
            </tr>
            <tr>
                <th>Unique Users</th>
                <td>{{ $summary['unique_users'] }}</td>
            </tr>
            <tr>
                <th>Date Range</th>
                <td>
                    @if($summary['date_range']['start'])
                        {{ $summary['date_range']['start'] }} to {{ $summary['date_range']['end'] ?? 'Present' }}
                    @else
                        All time
                    @endif
                </td>
            </tr>
            @if($summary['filters_applied']['user_id'])
                <tr>
                    <th>Filtered User ID</th>
                    <td>{{ $summary['filters_applied']['user_id'] }}</td>
                </tr>
            @endif
            @if($summary['filters_applied']['activity_type'])
                <tr>
                    <th>Activity Type Filter</th>
                    <td>{{ $summary['filters_applied']['activity_type'] }}</td>
                </tr>
            @endif
            @if($summary['filters_applied']['status'])
                <tr>
                    <th>Status Filter</th>
                    <td>{{ $summary['filters_applied']['status'] }}</td>
                </tr>
            @endif
        </table>
    </div>

    <div class="data">
        <h2>Activity Log Data</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Activity</th>
                    <th>Subject Type</th>
                    <th>Subject ID</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activities as $activity)
                    <tr>
                        <td>{{ $activity->id }}</td>
                        <td>{{ \Carbon\Carbon::parse($activity->created_at)->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $activity->causer ? $activity->causer->name : 'System' }}</td>
                        <td>{{ $activity->causer ? $activity->causer->email : 'N/A' }}</td>
                        <td class="activity-description">{{ $activity->description }}</td>
                        <td>{{ $activity->subject_type ?? 'N/A' }}</td>
                        <td>{{ $activity->subject_id ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>End of Report - Symatech Backend System</p>
    </div>
</body>
</html>
