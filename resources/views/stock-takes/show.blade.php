@extends('layouts.app')

@section('title', 'Stock Take Details')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Stock Take
                    #{{ $stockTake->stock_take_number }}</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $stockTake->branch->name }} •
                    {{ $stockTake->stock_take_date->format('M d, Y') }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('stock-takes.index') }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Back</span>
                </a>
                @if ($stockTake->canBeEdited() && auth()->user()?->hasPermission('stock-takes.update'))
                    <a href="{{ route('stock-takes.edit', $stockTake) }}"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        <span>Edit</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Status Badge -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            @php
                $statusColors = [
                    'draft' => 'bg-themeHover text-themeHeading',
                    'in_progress' => 'bg-sky-100 text-sky-800',
                    'completed' => 'bg-violet-100 text-violet-800',
                    'approved' => 'bg-emerald-100 text-emerald-800',
                    'cancelled' => 'bg-red-100 text-red-800',
                ];
            @endphp
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span
                        class="px-4 py-2 text-sm rounded-lg font-medium {{ $statusColors[$stockTake->status] ?? 'bg-themeHover text-themeHeading' }}">
                        {{ ucfirst(str_replace('_', ' ', $stockTake->status)) }}
                    </span>
                    @if ($stockTake->approved_by)
                        <span class="text-sm font-medium text-themeBody">
                            Approved by {{ $stockTake->approver->name }} on
                            {{ $stockTake->approved_at->format('M d, Y h:i A') }}
                        </span>
                    @endif
                </div>
                @if ($stockTake->canBeApproved() && auth()->user()?->hasPermission('stock-takes.approve'))
                    <button onclick="document.getElementById('approval-form').classList.toggle('hidden')"
                        class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-emerald-700 transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Approve & Apply Adjustments</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Total Items</div>
                <div class="text-2xl font-semibold text-primary tracking-tight">{{ $summary['total_items'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Counted</div>
                <div class="text-2xl font-semibold text-emerald-600 tracking-tight">{{ $summary['counted_items'] }}</div>
                @if ($summary['pending_items'] > 0)
                    <div class="text-xs font-medium text-amber-600 mt-1">{{ $summary['pending_items'] }} pending</div>
                @endif
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Variances</div>
                <div class="text-2xl font-semibold text-violet-600 tracking-tight">{{ $summary['items_with_variance'] }}
                </div>
                @if ($summary['items_with_variance'] > 0)
                    <div class="text-xs font-medium text-themeBody mt-1">
                        {{ $summary['overstock_count'] }} over / {{ $summary['shortage_count'] }} short
                    </div>
                @endif
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Total Variance</div>
                <div
                    class="text-2xl font-semibold {{ $summary['total_variance'] >= 0 ? 'text-emerald-600' : 'text-red-600' }} tracking-tight">
                    {{ $summary['total_variance'] >= 0 ? '+' : '' }}{{ $summary['total_variance'] }}
                </div>
            </div>
        </div>

        <!-- Stock Take Information -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Stock Take Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="text-sm font-medium text-themeMuted mb-1">Branch</div>
                    <div class="font-medium text-themeHeading">{{ $stockTake->branch->name }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-themeMuted mb-1">Stock Take Date</div>
                    <div class="font-medium text-themeHeading">{{ $stockTake->stock_take_date->format('M d, Y') }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-themeMuted mb-1">Created By</div>
                    <div class="font-medium text-themeHeading">{{ $stockTake->creator->name }}</div>
                    <div class="text-xs font-medium text-themeMuted">{{ $stockTake->created_at->format('M d, Y h:i A') }}
                    </div>
                </div>
                @if ($stockTake->completed_at)
                    <div>
                        <div class="text-sm font-medium text-themeMuted mb-1">Completed By</div>
                        <div class="font-medium text-themeHeading">
                            {{ $stockTake->completer ? $stockTake->completer->name : 'N/A' }}</div>
                        <div class="text-xs font-medium text-themeMuted">
                            {{ $stockTake->completed_at->format('M d, Y h:i A') }}</div>
                    </div>
                @endif
                @if ($stockTake->notes)
                    <div class="md:col-span-2">
                        <div class="text-sm font-medium text-themeMuted mb-1">Notes</div>
                        <div class="font-medium text-themeHeading whitespace-pre-wrap">{{ $stockTake->notes }}</div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Stock Take Items -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="px-6 py-4 border-b border-themeBorder flex justify-between items-center bg-themeInput/80">
                <h2 class="text-lg font-semibold text-primary tracking-tight">Items ({{ $stockTake->items->count() }})</h2>
                @if ($stockTake->canBeEdited() && auth()->user()?->hasPermission('stock-takes.update'))
                    <a href="{{ route('stock-takes.edit', $stockTake) }}"
                        class="text-sm font-medium text-primary hover:text-primary-dark transition">Add Products</a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Product</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Opening Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Closing Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Variance</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Counted By</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Counted At</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                IMEI numbers</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($stockTake->items as $item)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $item->product->name }}</div>
                                    <div class="text-xs font-medium text-themeMuted">{{ $item->product->sku }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $item->system_quantity }}</div>
                                    <div class="text-xs font-medium text-themeMuted">(System at start)</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($item->physical_quantity !== null)
                                        <div class="text-sm font-medium text-themeHeading">{{ $item->physical_quantity }}
                                        </div>
                                        <div class="text-xs font-medium text-themeMuted">(Actual counted)</div>
                                    @else
                                        <span class="text-sm font-medium text-amber-600">Not counted</span>
                                        <div class="text-xs font-medium text-themeMuted mt-1">Update in edit page</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($item->physical_quantity !== null && $item->variance > 0)
                                        <span class="text-sm font-medium text-emerald-600">+{{ $item->variance }}
                                            (Overstock)
                                        </span>
                                    @elseif ($item->physical_quantity !== null && $item->variance < 0)
                                        <span class="text-sm font-medium text-red-600">{{ $item->variance }}
                                            (Shortage)</span>
                                    @elseif ($item->physical_quantity !== null)
                                        <span class="text-sm font-medium text-themeBody">Match</span>
                                    @else
                                        <span class="text-sm font-medium text-themeMuted">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($item->counter)
                                        <div class="text-sm font-medium text-themeBody">{{ $item->counter->name }}</div>
                                    @else
                                        <span class="text-sm font-medium text-themeMuted">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($item->counted_at)
                                        <div class="text-sm font-medium text-themeBody">
                                            {{ $item->counted_at->format('M d, Y h:i A') }}</div>
                                    @else
                                        <span class="text-sm font-medium text-themeMuted">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if (!empty($item->submitted_imeis) && is_array($item->submitted_imeis))
                                        <div class="text-sm font-medium text-themeBody space-y-0.5 max-w-xs">
                                            @foreach ($item->submitted_imeis as $imei)
                                                <div class="font-mono text-xs">{{ $imei }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-sm font-medium text-themeMuted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-themeMuted font-medium">No items
                                    added yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Approval Form -->
        @if ($stockTake->canBeApproved() && auth()->user()?->hasPermission('stock-takes.approve'))
            <div id="approval-form"
                class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] hidden">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Approve Stock Take</h2>
                <form method="POST" action="{{ route('stock-takes.approve', $stockTake) }}"
                    onsubmit="return confirm('Are you sure you want to approve this stock take? This will apply all adjustments to branch stock.');">
                    @csrf
                    <div class="mb-4">
                        <label for="approval_notes" class="block text-sm font-medium text-themeBody mb-2">Approval Notes
                            (Optional)</label>
                        <textarea id="approval_notes" name="approval_notes" rows="3"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                            placeholder="Add any notes about this approval..."></textarea>
                    </div>
                    <div class="flex space-x-3">
                        <button type="submit"
                            class="bg-emerald-600 text-white px-6 py-2.5 rounded-xl font-medium hover:bg-emerald-700 transition shadow-sm flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span>Approve & Apply Adjustments</span>
                        </button>
                        <button type="button" onclick="document.getElementById('approval-form').classList.add('hidden')"
                            class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Action Buttons -->
        @if ($stockTake->canBeEdited() && auth()->user()?->hasPermission('stock-takes.update'))
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-primary tracking-tight mb-2">Actions</h3>
                        <p class="text-sm font-medium text-themeBody">Complete the stock take when all items have been
                            counted.</p>
                    </div>
                    <div class="flex space-x-2">
                        @if ($stockTake->isInProgress())
                            @php
                                $uncountedItems = $stockTake->items()->whereNull('physical_quantity')->get();
                            @endphp
                            @if ($uncountedItems->count() > 0)
                                <button type="button"
                                    onclick="document.getElementById('complete-modal').classList.remove('hidden')"
                                    class="bg-violet-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-violet-700 transition shadow-sm flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>Mark as Completed</span>
                                </button>
                            @else
                                <form method="POST" action="{{ route('stock-takes.complete', $stockTake) }}"
                                    class="inline" onsubmit="return confirm('Mark this stock take as completed?');">
                                    @csrf
                                    <button type="submit"
                                        class="bg-violet-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-violet-700 transition shadow-sm flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Mark as Completed</span>
                                    </button>
                                </form>
                            @endif
                        @endif
                        @if (auth()->user()?->hasPermission('stock-takes.cancel'))
                            <form method="POST" action="{{ route('stock-takes.cancel', $stockTake) }}" class="inline"
                                onsubmit="return confirm('Are you sure you want to cancel this stock take?');">
                                @csrf
                                <button type="submit"
                                    class="bg-red-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-red-700 transition shadow-sm flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    <span>Cancel</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Cancel (when completed but not yet approved) -->
        @if ($stockTake->isCompleted() && !$stockTake->isApproved() && !$stockTake->isCancelled() && auth()->user()?->hasPermission('stock-takes.cancel'))
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-themeHeading mb-1">Cancel this stock take</h3>
                        <p class="text-sm text-themeMuted">Found a mistake? You can cancel before it is approved. The stock take will be marked as cancelled and no adjustments will be applied.</p>
                    </div>
                    <form method="POST" action="{{ route('stock-takes.cancel', $stockTake) }}" class="inline"
                        onsubmit="return confirm('Are you sure you want to cancel this stock take? It will be marked as cancelled and you will need to start a new one if needed.');">
                        @csrf
                        <button type="submit"
                            class="bg-red-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-red-700 transition shadow-sm flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span>Cancel stock take</span>
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Complete Stock Take Modal -->
        @if ($stockTake->isInProgress() && $stockTake->canBeEdited() && auth()->user()?->hasPermission('stock-takes.update'))
            @php
                $uncountedItemsForModal = $stockTake->items()->whereNull('physical_quantity')->get();
                $countedItemsForModal = $stockTake->items()->whereNotNull('physical_quantity')->get();
            @endphp
            @if ($uncountedItemsForModal->count() > 0)
                <div id="complete-modal"
                    class="hidden fixed inset-0 overflow-y-auto h-full w-full z-50 bg-black/50 flex items-start justify-center p-4 pt-20"
                    onclick="if(event.target === this) document.getElementById('complete-modal').classList.add('hidden')">
                    <div class="relative w-full max-w-4xl rounded-2xl border border-themeBorder bg-themeCard p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]"
                        onclick="event.stopPropagation()">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between mb-4 pb-4 border-b border-themeBorder">
                            <h3 class="text-lg font-semibold text-primary tracking-tight">Complete Stock Take</h3>
                            <button type="button"
                                onclick="document.getElementById('complete-modal').classList.add('hidden')"
                                class="text-themeMuted hover:text-themeBody focus:outline-none rounded-lg p-1">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Modal Content -->
                        <form method="POST" action="{{ route('stock-takes.complete', $stockTake) }}" id="complete-form"
                            enctype="multipart/form-data">
                            @csrf

                            @if ($uncountedItemsForModal->count() > 0)
                                <div class="mb-6">
                                    <p class="text-sm font-medium text-themeBody mb-4">Enter the physical count (closing
                                        stock) for each item:</p>
                                    <div class="space-y-4 max-h-96 overflow-y-auto">
                                        @foreach ($uncountedItemsForModal as $item)
                                            <div class="border border-themeBorder rounded-xl p-4 bg-themeInput/50">
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                                    <div>
                                                        <div class="text-sm font-medium text-themeHeading">
                                                            {{ $item->product->name }}</div>
                                                        <div class="text-xs font-medium text-themeMuted">
                                                            {{ $item->product->sku }}</div>
                                                    </div>
                                                    <div>
                                                        <div class="text-xs font-medium text-themeMuted mb-1">Opening Stock
                                                            (System)
                                                        </div>
                                                        <div class="text-sm font-medium text-themeBody">
                                                            {{ $item->system_quantity }}</div>
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="block text-sm font-medium text-themeBody mb-2">Closing
                                                            Stock *</label>
                                                        <input type="number"
                                                            name="items[{{ $loop->index }}][physical_quantity]"
                                                            value="{{ old('items.' . $loop->index . '.physical_quantity', $item->system_quantity) }}"
                                                            min="0" step="1" required
                                                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                                        <input type="hidden" name="items[{{ $loop->index }}][item_id]"
                                                            value="{{ $item->id }}">
                                                        <p class="text-xs font-medium text-themeMuted mt-1">Actual counted
                                                            quantity</p>
                                                    </div>
                                                    <div class="md:col-span-3">
                                                        <label class="block text-sm font-medium text-themeBody mb-2">IMEI
                                                            numbers (optional)</label>
                                                        <textarea name="items[{{ $loop->index }}][imeis]" rows="3" maxlength="2000"
                                                            placeholder="One IMEI per line or comma-separated"
                                                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading text-sm"></textarea>
                                                        <div class="mt-2">
                                                            <label for="complete_imei_file_{{ $item->id }}"
                                                                class="block text-xs font-medium text-themeBody mb-1">Or
                                                                upload CSV/Excel</label>
                                                            <div class="flex items-center gap-2 flex-wrap">
                                                                <input type="file"
                                                                    name="items[{{ $loop->index }}][imei_file]"
                                                                    id="complete_imei_file_{{ $item->id }}"
                                                                    accept=".csv,.xlsx,.xls"
                                                                    class="block flex-1 min-w-0 text-sm text-themeBody file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-medium hover:file:bg-primary/20">
                                                                <button type="button"
                                                                    data-item-id="{{ $item->id }}"
                                                                    data-file-input-id="complete_imei_file_{{ $item->id }}"
                                                                    data-upload-url="{{ route('stock-takes.upload-imei-file', [$stockTake, $item]) }}"
                                                                    data-batch-url="{{ route('stock-takes.process-imei-batch', [$stockTake, $item]) }}"
                                                                    class="stock-take-upload-imei-btn bg-primary/10 text-primary px-3 py-2 rounded-lg text-sm font-medium hover:bg-primary/20 transition">
                                                                    Upload file
                                                                </button>
                                                            </div>
                                                            <div id="imei-progress-{{ $item->id }}" class="hidden mt-2">
                                                                <div class="flex items-center justify-between text-xs text-themeBody mb-1">
                                                                    <span>Processing…</span>
                                                                    <span class="imei-progress-text">0 / 0</span>
                                                                </div>
                                                                <div class="w-full bg-themeInput rounded-full h-2">
                                                                    <div class="imei-progress-bar h-2 rounded-full bg-primary transition-all duration-300" style="width: 0%"></div>
                                                                </div>
                                                            </div>
                                                            <div id="imei-report-{{ $item->id }}" class="hidden mt-2 p-3 rounded-xl bg-themeInput/80 border border-themeBorder text-sm">
                                                                <p class="font-medium text-themeHeading mb-1">Upload report</p>
                                                                <p class="imei-report-uploaded text-themeBody">Uploaded: 0</p>
                                                                <p class="imei-report-existing text-themeBody">Already existing: 0</p>
                                                                <p class="imei-report-never text-themeBody">Never recorded: 0</p>
                                                            </div>
                                                            <p class="mt-1 text-xs text-themeMuted">
                                                                <a href="{{ asset('sample_imei_upload.csv') }}" download
                                                                    class="text-primary hover:underline font-medium">Download
                                                                    sample CSV</a>
                                                                — one IMEI per row, header: <code
                                                                    class="text-themeBody">imei</code>.
                                                                Large files are processed in batches with a progress bar.
                                                            </p>
                                                        </div>
                                                        <p class="mt-1 text-xs text-themeMuted">Confirm/register devices at
                                                            this branch.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($countedItemsForModal->count() > 0)
                                <div class="mb-6">
                                    <p class="text-sm font-medium text-themeBody mb-4">Already counted items
                                        ({{ $countedItemsForModal->count() }}):</p>
                                    <div class="bg-themeInput/80 rounded-xl p-4 max-h-48 overflow-y-auto">
                                        <div class="space-y-2">
                                            @foreach ($countedItemsForModal as $item)
                                                <div
                                                    class="flex justify-between items-center text-sm font-medium text-themeBody">
                                                    <span>{{ $item->product->name }} ({{ $item->product->sku }})</span>
                                                    <span class="text-themeBody">Opening: {{ $item->system_quantity }} |
                                                        Closing: {{ $item->physical_quantity }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Modal Footer -->
                            <div class="flex justify-end space-x-3 pt-4 border-t border-themeBorder">
                                <button type="button"
                                    onclick="document.getElementById('complete-modal').classList.add('hidden')"
                                    class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="bg-violet-600 text-white px-6 py-2.5 rounded-xl font-medium hover:bg-violet-700 transition shadow-sm flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>Complete Stock Take</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <script>
                    // Close modal on Escape key
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') {
                            const modal = document.getElementById('complete-modal');
                            if (modal && !modal.classList.contains('hidden')) {
                                modal.classList.add('hidden');
                            }
                        }
                    });
                    // Stock take IMEI file upload: batches + progress bar + report (same as edit page)
                    document.querySelectorAll('.stock-take-upload-imei-btn').forEach(btn => {
                        btn.addEventListener('click', async function() {
                            const itemId = this.dataset.itemId;
                            const fileInputId = this.dataset.fileInputId || ('imei_file_' + itemId);
                            const fileInput = document.getElementById(fileInputId);
                            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                                alert('Please choose a CSV or Excel file first.');
                                return;
                            }
                            const uploadUrl = this.dataset.uploadUrl;
                            const batchUrl = this.dataset.batchUrl;
                            const progressEl = document.getElementById('imei-progress-' + itemId);
                            const reportEl = document.getElementById('imei-report-' + itemId);
                            const progressBar = progressEl && progressEl.querySelector('.imei-progress-bar');
                            const progressText = progressEl && progressEl.querySelector('.imei-progress-text');
                            const csrfToken = document.querySelector('input[name="_token"]')?.value || '';
                            const formData = new FormData();
                            formData.append('imei_file', fileInput.files[0]);
                            formData.append('_token', csrfToken);
                            try {
                                const uploadRes = await fetch(uploadUrl, { method: 'POST', body: formData });
                                const uploadData = await uploadRes.json();
                                if (!uploadRes.ok) {
                                    alert(uploadData.error || uploadData.message || 'Upload failed.');
                                    return;
                                }
                                const total = uploadData.total || 0;
                                if (total === 0) {
                                    alert('No valid IMEIs found in the file.');
                                    return;
                                }
                                const batchSize = uploadData.batch_size || 100;
                                const totalBatches = Math.ceil(total / batchSize);
                                let uploaded = 0, alreadyExisting = 0, neverRecorded = 0;
                                progressEl.classList.remove('hidden');
                                reportEl.classList.add('hidden');
                                for (let batchIndex = 0; batchIndex < totalBatches; batchIndex++) {
                                    const batchRes = await fetch(batchUrl, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': csrfToken,
                                            'Accept': 'application/json',
                                            'X-Requested-With': 'XMLHttpRequest'
                                        },
                                        body: JSON.stringify({
                                            upload_id: uploadData.upload_id,
                                            batch_index: batchIndex,
                                            _token: csrfToken
                                        })
                                    });
                                    const batchData = await batchRes.json();
                                    if (!batchRes.ok) {
                                        alert(batchData.error || 'Batch failed.');
                                        break;
                                    }
                                    uploaded += batchData.uploaded || 0;
                                    alreadyExisting += batchData.already_existing || 0;
                                    neverRecorded += batchData.never_recorded || 0;
                                    const processed = batchData.processed || (batchIndex + 1) * batchSize;
                                    const pct = Math.min(100, Math.round((processed / total) * 100));
                                    if (progressBar) progressBar.style.width = pct + '%';
                                    if (progressText) progressText.textContent = processed + ' / ' + total;
                                }
                                progressEl.classList.add('hidden');
                                reportEl.classList.remove('hidden');
                                reportEl.querySelector('.imei-report-uploaded').textContent = 'Uploaded: ' + uploaded;
                                reportEl.querySelector('.imei-report-existing').textContent = 'Already existing: ' + alreadyExisting;
                                reportEl.querySelector('.imei-report-never').textContent = 'Never recorded: ' + neverRecorded;
                            } catch (e) {
                                alert('Error: ' + (e.message || 'Network or server error.'));
                                if (progressEl) progressEl.classList.add('hidden');
                            }
                        });
                    });
                </script>
            @endif
        @endif
    </div>
@endsection
