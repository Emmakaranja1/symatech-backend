<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserRegistrationTrendsExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $request;

    public function __construct(array $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $startDate = $this->request['start_date'];
        $endDate = $this->request['end_date'];
        $groupBy = $this->request['group_by'] ?? 'daily';

        $query = User::whereBetween('created_at', [$startDate, $endDate]);
        
        switch ($groupBy) {
            case 'daily':
                return $query->selectRaw('DATE(created_at) as date, COUNT(*) as registrations')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
            case 'weekly':
                return $query->selectRaw('YEARWEEK(created_at) as week, COUNT(*) as registrations')
                    ->groupBy('week')
                    ->orderBy('week')
                    ->get();
            case 'monthly':
                return $query->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as registrations')
                    ->groupBy('year', 'month')
                    ->orderBy('year')
                    ->orderBy('month')
                    ->get();
        }
    }

    public function headings(): array
    {
        $groupBy = $this->request['group_by'] ?? 'daily';
        
        switch ($groupBy) {
            case 'daily':
                return ['Date', 'Number of Registrations'];
            case 'weekly':
                return ['Week', 'Number of Registrations'];
            case 'monthly':
                return ['Year', 'Month', 'Number of Registrations'];
        }
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A1:Z1' => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E3F2FD']]],
        ];
    }

    public function title(): string
    {
        return 'Registration Trends';
    }
}
