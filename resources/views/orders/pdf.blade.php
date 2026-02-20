<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orders Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #333;
        }
        .date-range {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Orders Report</h1>
        @if($startDate && $endDate)
            <div class="date-range">
                Date Range: {{ date('M d, Y', strtotime($startDate)) }} - {{ date('M d, Y', strtotime($endDate)) }}
            </div>
        @else
            <div class="date-range">
                All Orders
            </div>
        @endif
        <div class="date-range">
            Generated on: {{ date('M d, Y H:i:s') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer Name</th>
                <th>Customer Email</th>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total Price</th>
                <th>Status</th>
                <th>Order Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->user->name }}</td>
                    <td>{{ $order->user->email }}</td>
                    <td>{{ $order->product->name }}</td>
                    <td>{{ $order->quantity }}</td>
                    <td>${{ number_format($order->product->price, 2) }}</td>
                    <td>${{ number_format($order->total_price, 2) }}</td>
                    <td>{{ ucfirst($order->status) }}</td>
                    <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="6"><strong>Total:</strong></td>
                <td><strong>${{ number_format($orders->sum('total_price'), 2) }}</strong></td>
                <td colspan="2">{{ $orders->count() }} Orders</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>This is a computer-generated report. No signature required.</p>
    </div>
</body>
</html>
