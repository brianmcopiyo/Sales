@extends('layouts.portal')

@section('title', 'Reports')

@section('content')
<div class="py-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-xl font-bold" style="color:#111827;">Reports</h1>
        <a href="{{ route('portal.reports.export', array_merge(request()->query(), ['type' => $reportType, 'period' => $period])) }}"
           class="text-sm px-4 py-2 rounded-lg font-medium text-white transition-opacity hover:opacity-90"
           style="background-color:#006F78;">
            Export Excel
        </a>
    </div>

    {{-- Report Type Tabs --}}
    <div class="flex gap-2 mb-5 border-b" style="border-color:#e5e7eb;">
        @foreach ([
            'sales_by_product'   => 'Sales by Product',
            'revenue_trend'      => 'Revenue Trend',
            'outlet_performance' => 'Outlet Performance',
        ] as $type => $label)
            <a href="{{ route('portal.reports.index', array_merge(request()->query(), ['type' => $type])) }}"
               class="px-4 py-2 text-sm font-medium -mb-px border-b-2 transition-colors
                      {{ $reportType === $type ? 'border-primary text-primary' : 'border-transparent hover:text-gray-700' }}"
               style="{{ $reportType === $type ? 'border-color:#006F78; color:#006F78;' : 'color:#6b7280;' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Period Filter --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-6">
        <input type="hidden" name="type" value="{{ $reportType }}">
        <select name="period" class="text-sm border rounded-lg px-3 py-2 focus:outline-none" style="border-color:#e5e7eb;">
            @foreach ([
                'this_month' => 'This Month',
                'last_month' => 'Last Month',
                'this_year'  => 'This Year',
                'custom'     => 'Custom Range',
            ] as $val => $label)
                <option value="{{ $val }}" {{ $period === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @if ($period === 'custom')
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="text-sm border rounded-lg px-3 py-2" style="border-color:#e5e7eb;">
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="text-sm border rounded-lg px-3 py-2" style="border-color:#e5e7eb;">
        @endif
        <button type="submit" class="text-sm px-4 py-2 rounded-lg font-medium text-white" style="background-color:#006F78;">
            Apply
        </button>
    </form>

    <p class="text-xs mb-4" style="color:#6b7280;">
        Period: <strong>{{ $start->format('M d, Y') }}</strong> — <strong>{{ $end->format('M d, Y') }}</strong>
    </p>

    {{-- Report Table --}}
    <div class="rounded-xl border shadow-sm overflow-hidden" style="background:#fff; border-color:#e5e7eb;">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background:#f9fafb;">
                        @foreach ($reportData['headers'] as $header)
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">
                                {{ $header }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reportData['rows'] as $row)
                        <tr class="border-t" style="border-color:#f3f4f6;">
                            @foreach ($row as $cell)
                                <td class="px-4 py-3" style="color:#374151;">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($reportData['headers']) }}" class="px-4 py-10 text-center text-sm" style="color:#6b7280;">
                                No data available for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
