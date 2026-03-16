@extends('layouts.app')

@section('title', 'Order ' . $restockOrder->display_order_number)

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('stock-management.restock-orders.index'),
            'label' => 'Back to Restock Orders',
        ])

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-primary tracking-tight">{{ $restockOrder->display_order_number }}</h1>
                @if ($batchOrders->isEmpty())
                    <p class="text-sm font-medium text-themeMuted mt-1">{{ $restockOrder->product->name }}</p>
                @else
                    <p class="text-sm font-medium text-themeMuted mt-1">{{ $batchOrders->count() }} products in this order
                    </p>
                @endif
                <span
                    class="inline-block mt-2 px-3 py-1 text-xs font-medium rounded-lg
                    @if ($restockOrder->status === 'received_full') bg-emerald-100 text-emerald-800
                    @elseif ($restockOrder->status === 'received_partial') bg-sky-100 text-sky-800
                    @elseif ($restockOrder->status === 'cancelled') bg-red-100 text-red-800
                    @else bg-amber-100 text-amber-800 @endif">
                    @if ($restockOrder->status === 'received_full')
                        Received
                    @elseif ($restockOrder->status === 'received_partial')
                        Partial
                    @elseif ($restockOrder->status === 'cancelled')
                        Rejected
                    @else
                        Pending
                    @endif
                </span>
            </div>
        </div>

        @if ($batchOrders->isNotEmpty())
            <!-- Batch: table of lines with receive/approve/reject per line -->
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight p-6 pb-2">Order lines</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-themeBorder">
                        <thead class="bg-themeInput/80">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Ordered</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Received
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Outstanding
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Cost</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-themeBorder">
                            @foreach ($batchOrders as $line)
                                <tr class="hover:bg-themeInput/50">
                                    <td class="px-6 py-4 font-medium text-themeHeading">{{ $line->product->name }}</td>
                                    <td class="px-6 py-4 text-sm text-themeBody">{{ $line->quantity_ordered }}</td>
                                    <td class="px-6 py-4 text-sm text-themeBody">{{ $line->quantity_received }}</td>
                                    <td class="px-6 py-4 text-sm text-themeBody">{{ $line->quantity_outstanding }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-2 py-1 text-xs rounded-lg font-medium
                                            @if ($line->status === 'received_full') bg-emerald-100 text-emerald-800
                                            @elseif ($line->status === 'received_partial') bg-sky-100 text-sky-800
                                            @elseif ($line->status === 'cancelled') bg-red-100 text-red-800
                                            @else bg-amber-100 text-amber-800 @endif">
                                            {{ $line->status === 'received_full' ? 'Received' : ($line->status === 'received_partial' ? 'Partial' : ($line->status === 'cancelled' ? 'Rejected' : 'Pending')) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-themeBody">
                                        {{ $line->total_acquisition_cost !== null ? number_format($line->total_acquisition_cost, 2) : '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ((auth()->user()->isAdmin() || auth()->user()->isHeadBranchManager()) && $line->isReceivable())
                                            @if ($line->quantity_outstanding > 0)
                                                <button type="button"
                                                    onclick="openReceiveModal('{{ $line->id }}', '{{ addslashes($line->product->name) }}', '{{ addslashes($line->display_order_number) }}', {{ $line->quantity_outstanding }})"
                                                    class="text-primary hover:underline font-medium text-sm mr-2">Receive</button>
                                                <button type="button"
                                                    onclick="openApproveModal('{{ $line->id }}', '{{ addslashes($line->product->name) }}', '{{ addslashes($line->display_order_number) }}', {{ $line->quantity_outstanding }})"
                                                    class="text-emerald-600 hover:underline font-medium text-sm mr-2">Approve
                                                    full</button>
                                                <button type="button"
                                                    onclick="openEditQuantityModal('{{ $line->id }}', '{{ addslashes($line->order_number) }}', {{ $line->quantity_received }}, {{ $line->quantity_ordered }})"
                                                    class="text-themeBody hover:underline font-medium text-sm">Edit
                                                    quantity</button>
                                            @endif
                                            @if ($line->canBeRejected())
                                                <button type="button"
                                                    onclick="openRejectModal('{{ $line->id }}', '{{ addslashes($line->product->name) }}', '{{ addslashes($line->display_order_number) }}')"
                                                    class="text-red-600 hover:underline font-medium text-sm mr-2">Reject</button>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Transfer all to branch: when order has devices and there are child branches to send to -->
        <div id="transfer">
            @php
                $canTransferCatalog = $childBranches->isNotEmpty() && (auth()->user()->isAdmin() || auth()->user()->isHeadBranchManager());
                $availableQty = \App\Models\BranchStock::where('branch_id', $restockOrder->branch_id)->where('product_id', $restockOrder->product_id)->value('quantity') ?? 0;
            @endphp
            @if ($canTransferCatalog)
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-2">Transfer stock to branch</h2>
                    <p class="text-sm text-themeBody mb-4">Send quantity of <strong>{{ $restockOrder->product->name ?? 'product' }}</strong> to a child branch. Available at this branch: <strong>{{ $availableQty }}</strong>.</p>
                    <form action="{{ route('stock-management.orders.transfer-catalog', $restockOrder) }}" method="POST"
                        class="flex flex-wrap items-end gap-3"
                        onsubmit="return confirm('Transfer the entered quantity to the selected branch?');">
                        @csrf
                        <div class="min-w-[120px]">
                            <label for="transfer_quantity" class="block text-sm font-medium text-themeBody mb-1">Quantity</label>
                            <input type="number" name="quantity" id="transfer_quantity" required min="1" max="{{ max(1, $availableQty) }}" value="1"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        </div>
                        <div class="min-w-[220px]">
                            <label for="transfer_all_branch_id" class="block text-sm font-medium text-themeBody mb-1">Recipient branch</label>
                            <select name="target_branch_id" id="transfer_all_branch_id" required
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                <option value="">Select branch</option>
                                @foreach ($childBranches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}{{ $branch->code ? ' (' . $branch->code . ')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            <span>Transfer to branch</span>
                        </button>
                    </form>
                    @error('target_branch_id')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                    @error('quantity')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @else
                @if (auth()->user()->isAdmin() || auth()->user()->isHeadBranchManager())
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-2">Transfer stock to branch</h2>
                        <p class="text-sm text-themeBody">
                            @if ($childBranches->isEmpty())
                                No child branches are set up for {{ $restockOrder->branch->name }}. Add child branches to
                                enable transfers.
                            @else
                                Transfer is not available for this order.
                            @endif
                        </p>
                    </div>
                @endif
            @endif
        </div>

        <!-- Actions (single order: when order can still be received or rejected) – placed at top and sticky so they stay accessible with long device lists -->
        @if (
            $batchOrders->isEmpty() &&
                (auth()->user()->isAdmin() || auth()->user()->isHeadBranchManager()) &&
                $restockOrder->isReceivable())
            <div
                class="sticky top-20 z-10 bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Actions</h2>
                <div class="flex flex-wrap gap-3">
                    @if ($restockOrder->quantity_outstanding > 0)
                        <button type="button"
                            onclick="openReceiveModal('{{ $restockOrder->id }}', '{{ addslashes($restockOrder->product->name) }}', '{{ addslashes($restockOrder->order_number) }}', {{ $restockOrder->quantity_outstanding }})"
                            class="inline-flex items-center space-x-2 bg-primary text-white px-4 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z">
                                </path>
                            </svg>
                            <span>Partially approve (record receipt)</span>
                        </button>
                        <button type="button"
                            onclick="openApproveModal('{{ $restockOrder->id }}', '{{ addslashes($restockOrder->product->name) }}', '{{ addslashes($restockOrder->order_number) }}', {{ $restockOrder->quantity_outstanding }})"
                            class="inline-flex items-center space-x-2 bg-emerald-600 text-white px-4 py-2.5 rounded-xl font-medium hover:bg-emerald-700 transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span>Approve full order</span>
                        </button>
                    @endif
                    @if ($restockOrder->canBeRejected())
                        <button type="button"
                            onclick="openRejectModal('{{ $restockOrder->id }}', '{{ addslashes($restockOrder->product->name) }}', '{{ addslashes($restockOrder->order_number) }}')"
                            class="inline-flex items-center space-x-2 bg-red-600 text-white px-4 py-2.5 rounded-xl font-medium hover:bg-red-700 transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span>Reject order</span>
                        </button>
                    @endif
                    <button type="button"
                        onclick="openEditQuantityModal('{{ $restockOrder->id }}', '{{ addslashes($restockOrder->order_number) }}', {{ $restockOrder->quantity_received }}, {{ $restockOrder->quantity_ordered }})"
                        class="inline-flex items-center space-x-2 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm border border-themeBorder">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        <span>Edit quantity</span>
                    </button>
                </div>
            </div>
        @endif

        <!-- Receive/Approve/Reject modals (used by single-order actions and by batch table row buttons) -->
        @if (auth()->user()->isAdmin() || auth()->user()->isHeadBranchManager())
            <!-- Receive Stock Modal -->
            <div id="receive-order-modal"
                class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
                @click.self="document.getElementById('receive-order-modal').classList.add('hidden')">
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-md w-full shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]"
                    @click.stop>
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Receive Stock</h2>
                        <button onclick="document.getElementById('receive-order-modal').classList.add('hidden')"
                            class="text-themeMuted hover:text-themeBody rounded-lg p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-themeBody mb-2"><span class="font-medium" id="receive-product-name"></span>
                    </p>
                    <p class="text-sm text-themeMuted mb-4">Order: <span id="receive-order-number"></span></p>
                    <form id="receive-order-form" method="POST" action="" class="space-y-4"
                        enctype="multipart/form-data">
                        @csrf
                        <div>
                            <label for="receive_quantity" class="block text-sm font-medium text-themeBody mb-2">Quantity
                                Received *</label>
                            <input type="number" name="quantity_received" id="receive_quantity" min="1" required
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <p class="mt-1 text-xs text-themeMuted">Max: <span id="receive-max-qty"></span></p>
                        </div>
                        <div>
                            <label for="receive_notes" class="block text-sm font-medium text-themeBody mb-2">Notes
                                (optional)</label>
                            <textarea name="notes" id="receive_notes" rows="2" maxlength="500"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                                placeholder="e.g. partial delivery, batch number"></textarea>
                        </div>
                        <div>
                            <label for="receive_imeis" class="block text-sm font-medium text-themeBody mb-2">IMEI
                                numbers (optional)</label>
                            <textarea name="imeis" id="receive_imeis" rows="3" maxlength="2000"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading text-sm"
                                placeholder="One IMEI per line or comma-separated"></textarea>
                            <div class="mt-2">
                                <label for="receive_imei_file" class="block text-xs font-medium text-themeBody mb-1">Or
                                    upload CSV/Excel</label>
                                <input type="file" name="imei_file" id="receive_imei_file" accept=".csv,.xlsx,.xls"
                                    class="block w-full text-sm text-themeBody file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-medium hover:file:bg-primary/20">
                                <p class="mt-1 text-xs text-themeMuted"><a href="{{ asset('sample_imei_upload.csv') }}"
                                        download class="text-primary hover:underline font-medium">Download sample
                                        CSV</a> — one IMEI per row, header: <code class="text-themeBody">imei</code>.
                                </p>
                            </div>
                            <p class="mt-1 text-xs text-themeMuted">Pasted IMEIs and uploaded CSV/Excel are validated: each
                                must be exactly 15 digits and pass the validity check. Each must be unique.</p>
                        </div>
                        <div class="flex items-start space-x-3">
                            <input type="checkbox" name="mark_order_complete" id="receive_mark_complete" value="1"
                                class="mt-1 h-4 w-4 rounded border-themeBorder text-primary focus:ring-primary">
                            <label for="receive_mark_complete" class="text-sm text-themeBody">Mark order as complete
                                (no more stock expected). Leave unchecked to keep the order open for more
                                deliveries.</label>
                        </div>
                        <div class="flex items-center space-x-3 pt-4">
                            <button type="submit"
                                class="flex-1 bg-primary text-white px-4 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Record Receipt</span>
                            </button>
                            <button type="button"
                                onclick="document.getElementById('receive-order-modal').classList.add('hidden')"
                                class="flex-1 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center justify-center space-x-2">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Approve (full) Order Modal -->
            <div id="approve-order-modal"
                class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
                @click.self="document.getElementById('approve-order-modal').classList.add('hidden')">
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-md w-full shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]"
                    @click.stop>
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Approve full order</h2>
                        <button onclick="document.getElementById('approve-order-modal').classList.add('hidden')"
                            class="text-themeMuted hover:text-themeBody rounded-lg p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-themeBody mb-2"><span class="font-medium" id="approve-product-name"></span>
                    </p>
                    <p class="text-sm text-themeMuted mb-4">Order: <span id="approve-order-number"></span></p>
                    <form id="approve-order-form" method="POST" action="" class="space-y-4"
                        enctype="multipart/form-data">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-themeBody mb-2">Quantity to receive</label>
                            <div class="w-full px-4 py-2.5 border border-themeBorder rounded-xl bg-themeInput font-medium text-themeBody"
                                id="approve-quantity-display"></div>
                        </div>
                        <div>
                            <label for="approve_imeis" class="block text-sm font-medium text-themeBody mb-2">IMEI
                                numbers (optional)</label>
                            <textarea name="imeis" id="approve_imeis" rows="3" maxlength="2000"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium text-themeHeading text-sm"
                                placeholder="One IMEI per line or comma-separated"></textarea>
                            <div class="mt-2">
                                <label for="approve_imei_file" class="block text-xs font-medium text-themeBody mb-1">Or
                                    upload CSV/Excel</label>
                                <input type="file" name="imei_file" id="approve_imei_file" accept=".csv,.xlsx,.xls"
                                    class="block w-full text-sm text-themeBody file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-600/10 file:text-emerald-700 file:font-medium hover:file:bg-emerald-600/20">
                                <p class="mt-1 text-xs text-themeMuted"><a href="{{ asset('sample_imei_upload.csv') }}"
                                        download class="text-emerald-600 hover:underline font-medium">Download sample
                                        CSV</a> — one IMEI per row, header: <code class="text-themeBody">imei</code>.
                                </p>
                            </div>
                            <p class="mt-1 text-xs text-themeMuted">Pasted IMEIs and uploaded CSV/Excel are validated: each
                                must be exactly 15 digits and pass the validity check. Each must be unique.</p>
                        </div>
                        <div class="flex items-center space-x-3 pt-4">
                            <button type="submit"
                                class="flex-1 bg-emerald-600 text-white px-4 py-2.5 rounded-xl font-medium hover:bg-emerald-700 transition shadow-sm flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Approve</span>
                            </button>
                            <button type="button"
                                onclick="document.getElementById('approve-order-modal').classList.add('hidden')"
                                class="flex-1 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center justify-center space-x-2">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Reject Order Modal -->
            <div id="reject-order-modal"
                class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
                @click.self="document.getElementById('reject-order-modal').classList.add('hidden')">
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-md w-full shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]"
                    @click.stop>
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Reject Restock Order</h2>
                        <button onclick="document.getElementById('reject-order-modal').classList.add('hidden')"
                            class="text-themeMuted hover:text-themeBody rounded-lg p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-themeBody mb-2"><span class="font-medium" id="reject-product-name"></span>
                    </p>
                    <p class="text-sm text-themeMuted mb-4">Order: <span id="reject-order-number"></span></p>
                    <form id="reject-order-form" method="POST" action="" class="space-y-4">
                        @csrf
                        <div>
                            <label for="reject_reason" class="block text-sm font-medium text-themeBody mb-2">Reason
                                (optional)</label>
                            <textarea name="rejection_reason" id="reject_reason" rows="3" maxlength="2000"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 font-medium text-themeHeading"
                                placeholder="e.g. order cancelled by dealership"></textarea>
                        </div>
                        <div class="flex items-center space-x-3 pt-4">
                            <button type="submit"
                                class="flex-1 bg-red-600 text-white px-4 py-2.5 rounded-xl font-medium hover:bg-red-700 transition shadow-sm flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span>Reject order</span>
                            </button>
                            <button type="button"
                                onclick="document.getElementById('reject-order-modal').classList.add('hidden')"
                                class="flex-1 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center justify-center space-x-2">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            @if (!$restockOrder->isRejected() || $batchOrders->isNotEmpty())
                <!-- Edit quantity modal (single order or batch lines) -->
                <div id="edit-quantity-modal"
                    class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
                    @click.self="document.getElementById('edit-quantity-modal').classList.add('hidden')">
                    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-md w-full shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]"
                        @click.stop>
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-primary tracking-tight">Edit quantity ordered</h2>
                            <button type="button"
                                onclick="document.getElementById('edit-quantity-modal').classList.add('hidden')"
                                class="text-themeMuted hover:text-themeBody rounded-lg p-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <p class="text-sm text-themeMuted mb-4" id="edit-quantity-order-label">Confirm your password to
                            change the quantity.</p>
                        <form id="edit-quantity-form" method="POST"
                            action="{{ route('stock-management.orders.update-quantity', $restockOrder) }}"
                            class="space-y-4">
                            @csrf
                            @method('PUT')
                            <div>
                                <label for="edit_quantity_ordered"
                                    class="block text-sm font-medium text-themeBody mb-2">Quantity ordered</label>
                                <input type="number" name="quantity_ordered" id="edit_quantity_ordered"
                                    min="{{ $restockOrder->quantity_received }}" max="99999"
                                    value="{{ $restockOrder->quantity_ordered }}" required
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                <p class="mt-1 text-xs text-themeMuted" id="edit-quantity-min-hint">Minimum:
                                    {{ $restockOrder->quantity_received }} (already received).</p>
                                @error('quantity_ordered')
                                    <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="edit_quantity_password"
                                    class="block text-sm font-medium text-themeBody mb-2">Your password</label>
                                <input type="password" name="password" id="edit_quantity_password" required
                                    autocomplete="current-password"
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                                    placeholder="Enter your password to confirm">
                                @error('password')
                                    <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex items-center space-x-3 pt-4">
                                <button type="submit"
                                    class="flex-1 bg-primary text-white px-4 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Update
                                    quantity</button>
                                <button type="button"
                                    onclick="document.getElementById('edit-quantity-modal').classList.add('hidden')"
                                    class="flex-1 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <script>
                function openReceiveModal(orderId, productName, orderNumber, maxQty) {
                    document.getElementById('receive-product-name').textContent = productName;
                    document.getElementById('receive-order-number').textContent = orderNumber;
                    document.getElementById('receive-max-qty').textContent = maxQty;
                    document.getElementById('receive_quantity').setAttribute('max', maxQty);
                    document.getElementById('receive_quantity').value = maxQty;
                    document.getElementById('receive-order-form').action = '{{ url('stock-management/orders') }}/' + orderId +
                        '/receive';
                    document.getElementById('receive-order-modal').classList.remove('hidden');
                }

                function openApproveModal(orderId, productName, orderNumber, quantity) {
                    document.getElementById('approve-product-name').textContent = productName;
                    document.getElementById('approve-order-number').textContent = orderNumber;
                    document.getElementById('approve-quantity-display').textContent = quantity;
                    document.getElementById('approve-order-form').action = '{{ url('stock-management/orders') }}/' + orderId +
                        '/approve';
                    document.getElementById('approve_imeis').value = '';
                    document.getElementById('approve-order-modal').classList.remove('hidden');
                }

                function openRejectModal(orderId, productName, orderNumber) {
                    document.getElementById('reject-product-name').textContent = productName;
                    document.getElementById('reject-order-number').textContent = orderNumber;
                    document.getElementById('reject-order-form').action = '{{ url('stock-management/orders') }}/' + orderId +
                        '/reject';
                    document.getElementById('reject_reason').value = '';
                    document.getElementById('reject-order-modal').classList.remove('hidden');
                }

                function openEditQuantityModal(orderId, orderNumber, quantityReceived, quantityOrdered) {
                    var baseUrl = '{{ url('stock-management/orders') }}';
                    document.getElementById('edit-quantity-form').action = baseUrl + '/' + orderId + '/quantity';
                    var label = document.getElementById('edit-quantity-order-label');
                    if (label) label.textContent = 'Confirm your password to change the quantity for order ' + orderNumber + '.';
                    var input = document.getElementById('edit_quantity_ordered');
                    if (input) {
                        input.setAttribute('min', quantityReceived);
                        input.value = quantityOrdered;
                    }
                    var hint = document.getElementById('edit-quantity-min-hint');
                    if (hint) hint.textContent = 'Minimum: ' + quantityReceived + ' (already received).';
                    document.getElementById('edit_quantity_password').value = '';
                    document.getElementById('edit-quantity-modal').classList.remove('hidden');
                }
            </script>
        @endif

        @if ($batchOrders->isNotEmpty())
            <!-- Batch: shared order info -->
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Order info</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Branch</dt>
                        <dd class="mt-1 text-sm font-medium text-themeHeading">{{ $restockOrder->branch->name }}</dd>
                    </div>
                    @if ($restockOrder->reference_number)
                        <div>
                            <dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Reference</dt>
                            <dd class="mt-1 text-sm font-medium text-themeHeading">{{ $restockOrder->reference_number }}
                            </dd>
                        </div>
                    @endif
                    @if ($restockOrder->dealership_display_name)
                        <div>
<dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Dealership</dt>
                                    <dd class="mt-1 text-sm font-medium text-themeHeading">{{ $restockOrder->dealership_display_name }}</dd>
                        </div>
                    @endif
                    @if ($restockOrder->expected_at)
                        <div>
                            <dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Expected</dt>
                            <dd class="mt-1 text-sm font-medium text-themeHeading">
                                {{ $restockOrder->expected_at->format('M d, Y') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        @else
            <!-- Order details (single order) -->
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Order details</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Branch</dt>
                        <dd class="mt-1 text-sm font-medium text-themeHeading">{{ $restockOrder->branch->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Product</dt>
                        <dd class="mt-1 text-sm font-medium text-themeHeading">{{ $restockOrder->product->name }}</dd>
                        @if ($restockOrder->product->sku)
                            <dd class="text-xs text-themeMuted">{{ $restockOrder->product->sku }}</dd>
                        @endif
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Quantity ordered</dt>
                        <dd class="mt-1 text-sm font-medium text-themeHeading flex items-center gap-2">
                            {{ $restockOrder->quantity_ordered }}
                            @if (!$restockOrder->isRejected() && (auth()->user()->isAdmin() || auth()->user()->isHeadBranchManager()))
                                <button type="button"
                                    onclick="document.getElementById('edit-quantity-modal').classList.remove('hidden')"
                                    class="text-xs font-medium text-primary hover:text-primary-dark hover:underline">Edit</button>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Quantity received</dt>
                        <dd class="mt-1 text-sm font-medium text-themeHeading">{{ $restockOrder->quantity_received }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Outstanding</dt>
                        <dd class="mt-1 text-sm font-medium text-themeHeading">{{ $restockOrder->quantity_outstanding }}
                        </dd>
                    </div>
                    @if ($restockOrder->reference_number)
                        <div>
                            <dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Reference</dt>
                            <dd class="mt-1 text-sm font-medium text-themeHeading">{{ $restockOrder->reference_number }}
                            </dd>
                        </div>
                    @endif
                    @if ($restockOrder->total_acquisition_cost !== null)
                        <div>
                            <dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Total acquisition cost
                            </dt>
                            <dd class="mt-1 text-sm font-medium text-themeHeading">
                                {{ number_format($restockOrder->total_acquisition_cost, 2) }}</dd>
                        </div>
                    @endif
                    @if ($restockOrder->dealership_display_name)
                        <div>
<dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Dealership</dt>
                                    <dd class="mt-1 text-sm font-medium text-themeHeading">{{ $restockOrder->dealership_display_name }}</dd>
                        </div>
                    @endif
                    @if ($restockOrder->expected_at)
                        <div>
                            <dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Expected</dt>
                            <dd class="mt-1 text-sm font-medium text-themeHeading">
                                {{ $restockOrder->expected_at->format('M d, Y') }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Ordered at</dt>
                        <dd class="mt-1 text-sm font-medium text-themeHeading">
                            {{ $restockOrder->ordered_at ? $restockOrder->ordered_at->format('M d, Y H:i') : '–' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Received at</dt>
                        <dd class="mt-1 text-sm font-medium text-themeHeading">
                            {{ $restockOrder->received_at ? $restockOrder->received_at->format('M d, Y H:i') : '–' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Created by</dt>
                        <dd class="mt-1 text-sm font-medium text-themeHeading">{{ $restockOrder->creator->name ?? '–' }}
                        </dd>
                    </div>
                    @if ($restockOrder->status === 'cancelled' && $restockOrder->rejection_reason)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-themeMuted uppercase tracking-wider">Rejection reason</dt>
                            <dd class="mt-1 text-sm font-medium text-themeHeading">{{ $restockOrder->rejection_reason }}
                            </dd>
                            @if ($restockOrder->rejectedBy)
                                <dd class="text-xs text-themeMuted">Rejected by {{ $restockOrder->rejectedBy->name }}</dd>
                            @endif
                        </div>
                    @endif
                </dl>
            </div>

        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (window.location.hash === '#transfer') {
                    var el = document.getElementById('transfer');
                    if (el) el.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        </script>
    @endpush
@endsection
