<!DOCTYPE html>
<html>
<head>
    <title>User Registration Trends Report</title>
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
    </style>
</head>
<body>
    <div class="header">
        <h1>User Registration Trends Report</h1>
        <p>Generated on: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <div class="summary">
        <h2>Summary Statistics</h2>
        <table class="summary-table">
            <tr>
                <th>Total Registrations</th>
                <td>{{ $summary->total_registrations }}</td>
            </tr>
            <tr>
                <th>Average per {{ $summary->period_type }}</th>
                <td>{{ $summary->average_per_period }}</td>
            </tr>
            <tr>
                <th>Peak Registrations</th>
                <td>{{ $summary->peak_registrations }}</td>
            </tr>
            <tr>
                <th>Period Type</th>
                <td>{{ ucfirst($summary->period_type) }}</td>
            </tr>
            <tr>
                <th>Date Range</th>
                <td>{{ $summary->date_range->start }} to {{ $summary->date_range->end }}</td>
            </tr>
        </table>
    </div>

    <div class="data">
        <h2>Registration Data</h2>
        <table class="data-table">
            <thead>
                <tr>
                    @if($summary->period_type === 'daily')
                        <th>Date</th>
                    @elseif($summary->period_type === 'weekly')
                        <th>Week</th>
                    @else
                        <th>Year</th>
                        <th>Month</th>
                    @endif
                    <th>Registrations</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                    <tr>
                        @if($summary->period_type === 'daily')
                            <td>{{ $item->date }}</td>
                        @elseif($summary->period_type === 'weekly')
                            <td>{{ $item->week }}</td>
                        @else
                            <td>{{ $item->year }}</td>
                            <td>{{ $item->month }}</td>
                        @endif
                        <td>{{ $item->registrations }}</td>
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
