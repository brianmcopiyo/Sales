@extends('layouts.app')

@section('title', 'Customer Disbursements')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Disbursements for {{ $customer->name }}</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Support payments for this customer</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('customer-disbursements.create', ['customer_id' => $customer->id]) }}"
                    class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>New Disbursement</span>
                </a>
                <a href="{{ route('customers.show', $customer) }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Back to Customer</span>
                </a>
            </div>
        </div>

        <!-- Customer Summary -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <div class="text-sm font-medium text-themeMuted mb-1">Customer</div>
                    <div class="text-lg font-semibold text-primary">{{ $customer->name }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-themeMuted mb-1">Total Disbursed</div>
                    <div class="text-2xl font-semibold text-red-600">TSh
                        {{ number_format($customer->total_disbursed ?? 0, 2) }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-themeMuted mb-1">Total Disbursements</div>
                    <div class="text-2xl font-semibold text-primary">{{ $disbursements->total() }}</div>
                </div>
            </div>
        </div>

        <!-- Disbursements Table -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Device (IMEI)</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Sale</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Disbursed By</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($disbursements as $disbursement)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($disbursement->device)
                                        <div class="text-sm font-medium text-themeHeading">{{ $disbursement->device->imei }}
                                        </div>
                                        @if ($disbursement->device->product)
                                            <div class="text-sm font-medium text-themeMuted">
                                                {{ $disbursement->device->product->name }}</div>
                                        @endif
                                    @else
                                        <span class="text-sm font-medium text-themeMuted">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-amber-600">TSh
                                        {{ number_format($disbursement->amount, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">
                                        {{ $disbursement->disbursement_phone ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($disbursement->sale)
                                        <a href="{{ route('sales.show', $disbursement->sale) }}"
                                            class="text-sm font-medium text-primary hover:text-primary-dark">
                                            {{ $disbursement->sale->sale_number }}
                                        </a>
                                    @else
                                        <span class="text-sm font-medium text-themeMuted">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $disbursement->disbursedBy->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">
                                        {{ $disbursement->created_at->format('M d, Y') }}</div>
                                    <div class="text-sm font-medium text-themeMuted">
                                        {{ $disbursement->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('customer-disbursements.show', $disbursement) }}"
                                        class="font-medium text-primary hover:text-primary-dark">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-themeMuted font-medium">No disbursements
                                    found for this customer.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($disbursements->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $disbursements->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

