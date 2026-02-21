<?php

namespace App\Exports;

use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActivityLogExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithMapping
{
    protected $request;
    protected $normalUsersOnly;

    public function __construct(array $request, $normalUsersOnly = false)
    {
        $this->request = $request;
        $this->normalUsersOnly = $normalUsersOnly;
    }

    public function collection()
    {
        $query = Activity::with('causer');

        // Filter for normal users only if requested
        if ($this->normalUsersOnly) {
            $query->whereHas('causer', function($q) {
                $q->where('role', 'user');
            });
        }

        // Date range filter
        if (isset($this->request['start_date'])) {
            $query->where('created_at', '>=', $this->request['start_date']);
        }
        if (isset($this->request['end_date'])) {
            $query->where('created_at', '<=', $this->request['end_date'] . ' 23:59:59');
        }

        // User filter
        if (isset($this->request['user_id'])) {
            $query->where('causer_id', $this->request['user_id']);
        }

        // Activity type filter
        if (isset($this->request['activity_type'])) {
            $query->where('description', 'like', '%' . $this->request['activity_type'] . '%');
        }

        // Status filter
        if (isset($this->request['status'])) {
            $query->where('properties->status', $this->request['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function map($activity): array
    {
        $properties = $activity->properties ? json_decode($activity->properties, true) : [];
        
        return [
            $activity->id,
            $activity->created_at->format('Y-m-d H:i:s'),
            $activity->causer ? $activity->causer->name : 'System',
            $activity->causer ? $activity->causer->email : 'N/A',
            $activity->description,
            $activity->subject_type ?? 'N/A',
            $activity->subject_id ?? 'N/A',
            $properties['status'] ?? 'N/A',
            isset($properties['email']) ? $properties['email'] : 'N/A',
            isset($properties['activated_user_id']) ? $properties['activated_user_id'] : 'N/A',
            isset($properties['deactivated_user_id']) ? $properties['deactivated_user_id'] : 'N/A',
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Timestamp',
            'User Name',
            'User Email',
            'Activity Description',
            'Subject Type',
            'Subject ID',
            'Status',
            'Related Email',
            'Activated User ID',
            'Deactivated User ID',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A1:L1' => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E3F2FD']]],
        ];
    }

    public function title(): string
    {
        return 'Activity Log';
    }
}
