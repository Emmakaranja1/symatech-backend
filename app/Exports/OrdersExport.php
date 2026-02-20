<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function query()
    {
        $query = Order::with('product', 'user');
        
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
        }
        
        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Customer Name',
            'Customer Email',
            'Product Name',
            'Quantity',
            'Unit Price',
            'Total Price',
            'Status',
            'Order Date'
        ];
    }

    public function map($order): array
    {
        return [
            $order->id,
            $order->user->name,
            $order->user->email,
            $order->product->name,
            $order->quantity,
            $order->product->price,
            $order->total_price,
            ucfirst($order->status),
            $order->created_at->format('Y-m-d H:i:s')
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ]
        ];
    }
}
