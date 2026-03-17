<?php

namespace App\Exports;

use App\Models\PlannedVisit;
use App\Models\CheckIn;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DcrExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected string $userId,
        protected string $date
    ) {}

    public function headings(): array
    {
        return [
            'Outlet',
            'Planned',
            'Checked in',
            'Check-in time',
            'Check-out time',
        ];
    }

    public function collection(): Collection
    {
        $dateParsed = Carbon::parse($this->date)->toDateString();

        $planned = PlannedVisit::with('outlet')
            ->where('user_id', $this->userId)
            ->whereDate('planned_date', $dateParsed)
            ->orderBy('sequence')
            ->get();

        $checkIns = CheckIn::with('outlet')
            ->where('user_id', $this->userId)
            ->whereDate('check_in_at', $dateParsed)
            ->orderBy('check_in_at')
            ->get();

        $plannedOutletIds = $planned->pluck('outlet_id')->all();
        $rows = collect();

        foreach ($planned as $pv) {
            $ci = $checkIns->firstWhere('outlet_id', $pv->outlet_id);
            $rows->push([
                $pv->outlet?->name ?? '—',
                'Yes',
                $ci ? 'Yes' : 'No',
                $ci?->check_in_at?->format('Y-m-d H:i') ?? '—',
                $ci?->check_out_at?->format('Y-m-d H:i') ?? '—',
            ]);
        }
        foreach ($checkIns as $ci) {
            if (in_array($ci->outlet_id, $plannedOutletIds, true)) {
                continue;
            }
            $rows->push([
                $ci->outlet?->name ?? '—',
                'No',
                'Yes',
                $ci->check_in_at?->format('Y-m-d H:i') ?? '—',
                $ci->check_out_at?->format('Y-m-d H:i') ?? '—',
            ]);
        }

        return $rows;
    }
}
