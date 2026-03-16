@extends('layouts.app')

@section('title', $ticket->subject)

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">{{ $ticket->subject }}</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $ticket->ticket_number ?? 'Ticket' }}</p>
            </div>
            <a href="{{ route('tickets.index') }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                <span>Back</span>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Ticket Description Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Description</h2>
                    <div class="font-medium text-themeBody whitespace-pre-wrap">{{ $ticket->description }}</div>

                    @if ($ticket->tags->count() > 0)
                        <div class="mt-4 pt-4 border-t border-themeBorder">
                            <div class="text-sm text-themeMuted font-light mb-2">Tags</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($ticket->tags as $tag)
                                    <span class="px-3 py-1 text-sm rounded font-light"
                                        style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}; border: 1px solid {{ $tag->color }}40;">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($ticket->attachments->count() > 0)
                        <div class="mt-4 pt-4 border-t border-themeBorder">
                            <div class="text-sm text-themeMuted font-light mb-2">Attachments</div>
                            <div class="space-y-2">
                                @foreach ($ticket->attachments as $attachment)
                                    <div class="flex items-center justify-between p-2 bg-themeInput rounded">
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-5 h-5 text-themeMuted" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                </path>
                                            </svg>
                                            <span
                                                class="text-sm text-themeBody font-light">{{ $attachment->file_name }}</span>
                                            <span
                                                class="text-xs text-themeMuted font-light">({{ $attachment->file_size_human }})</span>
                                        </div>
                                        <a href="{{ route('tickets.attachments.download', $attachment) }}"
                                            class="text-primary hover:text-[#005a61] text-sm font-light">
                                            Download
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($ticket->sale)
                        <div class="mt-4 pt-4 border-t border-themeBorder">
                            <div class="text-sm text-themeMuted font-light mb-1">Related Sale</div>
                            <a href="{{ route('sales.show', $ticket->sale) }}"
                                class="text-primary hover:text-[#005a61] font-light">
                                Sale #{{ $ticket->sale->sale_number ?? $ticket->sale->id }} - TSh
                                {{ number_format($ticket->sale->total, 2) }}
                            </a>
                        </div>
                    @endif

                    @if ($ticket->device)
                        <div class="mt-4 pt-4 border-t border-themeBorder">
                            <div class="text-sm text-themeMuted font-light mb-1">Related Device</div>
                            <a href="{{ route('devices.show', $ticket->device) }}"
                                class="text-primary hover:text-[#005a61] font-light">
                                IMEI: {{ $ticket->device->imei }} -
                                {{ $ticket->device->product->name ?? 'Unknown Product' }}
                            </a>
                        </div>
                    @endif

                    @if ($ticket->product)
                        <div class="mt-4 pt-4 border-t border-themeBorder">
                            <div class="text-sm text-themeMuted font-light mb-1">Related Product</div>
                            <a href="{{ route('products.show', $ticket->product) }}"
                                class="text-primary hover:text-[#005a61] font-light">
                                {{ $ticket->product->name }} ({{ $ticket->product->sku }})
                            </a>
                        </div>
                    @endif

                    @if ($ticket->branch)
                        <div class="mt-4 pt-4 border-t border-themeBorder">
                            <div class="text-sm text-themeMuted font-light mb-1">Related Branch</div>
                            <a href="{{ route('branches.show', $ticket->branch) }}"
                                class="text-primary hover:text-[#005a61] font-light">
                                {{ $ticket->branch->name }} ({{ $ticket->branch->code }})
                            </a>
                        </div>
                    @endif

                    @if ($ticket->disbursement)
                        <div class="mt-4 pt-4 border-t border-themeBorder">
                            <div class="text-sm text-themeMuted font-light mb-1">Related Disbursement</div>
                            <a href="{{ route('customer-disbursements.show', $ticket->disbursement) }}"
                                class="text-primary hover:text-[#005a61] font-light">
                                TSh {{ number_format($ticket->disbursement->amount, 2) }} -
                                {{ $ticket->disbursement->created_at->format('M d, Y') }}
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Ticket Details Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-themeMuted font-light mb-1">Status</div>
                            @php
                                $statusColors = [
                                    'open' => 'bg-blue-100 text-blue-800',
                                    'in_progress' => 'bg-yellow-100 text-yellow-800',
                                    'resolved' => 'bg-green-100 text-green-800',
                                    'closed' => 'bg-themeHover text-themeHeading',
                                ];
                            @endphp
                            <span
                                class="px-3 py-1 text-sm rounded font-light {{ $statusColors[$ticket->status] ?? 'bg-themeHover text-themeHeading' }}">
                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                            </span>
                        </div>

                        <div>
                            <div class="text-sm text-themeMuted font-light mb-1">Priority</div>
                            @php
                                $priorityColors = [
                                    'low' => 'bg-blue-100 text-blue-800',
                                    'medium' => 'bg-yellow-100 text-yellow-800',
                                    'high' => 'bg-orange-100 text-orange-800',
                                    'urgent' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span
                                class="px-3 py-1 text-sm rounded font-light {{ $priorityColors[$ticket->priority] ?? 'bg-themeHover text-themeHeading' }}">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </div>

                        <div>
                            <div class="text-sm text-themeMuted font-light mb-1">Category</div>
                            <div class="text-themeHeading font-light">{{ ucfirst($ticket->category) }}</div>
                        </div>

                        <div>
                            <div class="text-sm text-themeMuted font-light mb-1">Customer</div>
                            <div class="text-themeHeading font-light">{{ $ticket->customer->name }}</div>
                        </div>

                        <div>
                            <div class="text-sm text-themeMuted font-light mb-1">Assigned To</div>
                            <div class="text-themeHeading font-light">{{ $ticket->assignedTo->name ?? 'Unassigned' }}</div>
                        </div>

                        <div>
                            <div class="text-sm text-themeMuted font-light mb-1">Created</div>
                            <div class="text-themeHeading font-light">{{ $ticket->created_at->format('M d, Y H:i') }}</div>
                        </div>

                        @if ($ticket->due_date)
                            <div>
                                <div class="text-sm text-themeMuted font-light mb-1">Due Date</div>
                                <div
                                    class="text-themeHeading font-light {{ $ticket->isOverdue() ? 'text-red-600 font-medium' : '' }}">
                                    {{ $ticket->due_date->format('M d, Y H:i') }}
                                    @if ($ticket->isOverdue())
                                        <span class="text-xs text-red-600">(Overdue)</span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if ($ticket->first_response_at)
                            <div>
                                <div class="text-sm text-themeMuted font-light mb-1">First Response</div>
                                <div class="text-themeHeading font-light">
                                    {{ $ticket->first_response_at->format('M d, Y H:i') }}</div>
                                <div class="text-xs text-themeMuted font-light">
                                    {{ $ticket->created_at->diffForHumans($ticket->first_response_at, true) }} after
                                    creation
                                </div>
                            </div>
                        @endif

                        @if ($ticket->last_response_at)
                            <div>
                                <div class="text-sm text-themeMuted font-light mb-1">Last Response</div>
                                <div class="text-themeHeading font-light">
                                    {{ $ticket->last_response_at->format('M d, Y H:i') }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Assignment History Card -->
                @if ($ticket->assignments->count() > 0)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Assignment History</h2>
                        <div class="space-y-4">
                            @foreach ($ticket->assignments->sortByDesc('assigned_at') as $assignment)
                                <div
                                    class="border-l-4 {{ $assignment->is_current ? 'border-primary' : 'border-themeBorder' }} pl-4 py-2">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-center gap-3">
                                            <x-profile-picture :user="$assignment->assignedTo" size="sm" />
                                            <div>
                                                <div class="text-themeHeading font-medium">
                                                    {{ $assignment->assignedTo->name }}
                                                    @if ($assignment->is_current)
                                                        <span class="text-xs text-primary font-light">(Current)</span>
                                                    @endif
                                                </div>
                                                <div class="text-sm text-themeMuted font-light">
                                                    Assigned by {{ $assignment->assignedBy->name }}
                                                    on {{ $assignment->assigned_at->format('M d, Y H:i') }}
                                                </div>
                                                @if ($assignment->unassigned_at)
                                                    <div class="text-sm text-themeMuted font-light">
                                                        Unassigned on
                                                        {{ $assignment->unassigned_at->format('M d, Y H:i') }}
                                                        @if ($assignment->assigned_at && $assignment->unassigned_at)
                                                            ({{ $assignment->assigned_at->diffForHumans($assignment->unassigned_at, true) }})
                                                        @endif
                                                    </div>
                                                @endif
                                                @if ($assignment->activity_summary)
                                                    <div
                                                        class="mt-2 text-sm text-themeBody font-light bg-themeInput p-2 rounded">
                                                        {{ $assignment->activity_summary }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Replies Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Replies</h2>
                    <div class="space-y-4">
                        @foreach ($ticket->replies as $reply)
                            <div class="border-b border-themeBorder pb-4 last:border-0">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center gap-3">
                                        <x-profile-picture :user="$reply->user" size="sm" />
                                        <div>
                                            <div class="text-themeHeading font-light">{{ $reply->user->name }}</div>
                                            <div class="text-sm text-themeMuted font-light">
                                                {{ $reply->created_at->format('M d, Y H:i') }}</div>
                                        </div>
                                    </div>
                                    @if ($reply->is_internal)
                                        <span
                                            class="px-2 py-1 text-xs rounded font-light bg-[#E48A22] text-white">Internal</span>
                                    @endif
                                </div>
                                <div class="text-themeBody font-light whitespace-pre-wrap ml-12">{{ $reply->message }}
                                </div>

                                @if ($reply->attachments->count() > 0)
                                    <div class="mt-3 space-y-2">
                                        @foreach ($reply->attachments as $attachment)
                                            <div class="flex items-center justify-between p-2 bg-themeInput rounded">
                                                <div class="flex items-center space-x-2">
                                                    <svg class="w-4 h-4 text-themeMuted" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                        </path>
                                                    </svg>
                                                    <span
                                                        class="text-sm text-themeBody font-light">{{ $attachment->file_name }}</span>
                                                    <span
                                                        class="text-xs text-themeMuted font-light">({{ $attachment->file_size_human }})</span>
                                                </div>
                                                <a href="{{ route('tickets.attachments.download', $attachment) }}"
                                                    class="text-primary hover:text-[#005a61] text-sm font-light">
                                                    Download
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 pt-6 border-t border-themeBorder">
                        <form method="POST" action="{{ route('tickets.reply', $ticket) }}"
                            enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <div>
                                <label for="message" class="block text-themeBody font-light mb-2">Reply *</label>
                                <textarea id="message" name="message" rows="4" required
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"></textarea>
                            </div>
                            <div>
                                <label for="reply_attachments"
                                    class="block text-themeBody font-light mb-2">Attachments</label>
                                <input type="file" id="reply_attachments" name="attachments[]" multiple
                                    accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                <p class="text-xs text-themeMuted font-light mt-1">Max 10MB per file</p>
                            </div>
                            @if (!auth()->user()->isCustomer())
                                <div class="flex items-center">
                                    <input type="checkbox" id="is_internal" name="is_internal" value="1"
                                        class="rounded border-themeBorder text-primary focus:ring-primary">
                                    <label for="is_internal" class="ml-2 text-themeBody font-light">Internal Note</label>
                                </div>
                            @endif
                            <button type="submit"
                                class="bg-primary text-white px-6 py-2 rounded hover:bg-[#005a61] transition font-light flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Add Reply</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Escalation Card -->
                @if (!auth()->user()->isCustomer() && $ticket->assigned_to === auth()->id())
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Request Escalation</h2>
                        <form method="POST" action="{{ route('tickets.escalate', $ticket) }}" class="space-y-4">
                            @csrf
                            <div>
                                <label for="escalate_to" class="block text-themeBody font-light mb-2">Escalate To
                                    *</label>
                                <select id="escalate_to" name="requested_to" required
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                    <option value="">Select User</option>
                                    @foreach ($staff as $user)
                                        @if ($user->id !== auth()->id())
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="escalation_reason" class="block text-themeBody font-light mb-2">Reason
                                    *</label>
                                <textarea id="escalation_reason" name="reason" rows="3" required
                                    placeholder="Explain why you need to escalate this ticket..."
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"></textarea>
                            </div>
                            <button type="submit"
                                class="w-full bg-[#E48A22] text-white px-4 py-2.5 rounded-xl hover:bg-[#d17a1a] transition font-medium">
                                Request Escalation
                            </button>
                        </form>
                    </div>
                @endif

                <!-- Pending Escalations Card -->
                @if ($ticket->pendingEscalations->count() > 0)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Pending Escalations</h2>
                        <div class="space-y-4">
                            @foreach ($ticket->pendingEscalations as $escalation)
                                <div class="border border-themeBorder rounded-xl p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <x-profile-picture :user="$escalation->requestedBy" size="xs" />
                                        <div class="text-sm text-themeMuted font-light">
                                            Requested by {{ $escalation->requestedBy->name }}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <x-profile-picture :user="$escalation->requestedTo" size="xs" />
                                        <div class="text-themeHeading font-medium">
                                            To: {{ $escalation->requestedTo->name }}
                                        </div>
                                    </div>
                                    <div class="text-sm text-themeBody font-light mb-3">
                                        {{ $escalation->reason }}
                                    </div>
                                    @if ($escalation->requested_to === auth()->id())
                                        <div class="flex space-x-2">
                                            <form method="POST"
                                                action="{{ route('tickets.escalations.accept', [$ticket, $escalation]) }}"
                                                class="flex-1">
                                                @csrf
                                                <button type="submit"
                                                    class="w-full bg-primary text-white px-3 py-1.5 rounded-lg hover:bg-[#005a61] transition text-sm font-medium">
                                                    Accept
                                                </button>
                                            </form>
                                            <button type="button" onclick="showRejectModal('{{ $escalation->id }}')"
                                                class="flex-1 bg-red-500 text-white px-3 py-1.5 rounded-lg hover:bg-red-600 transition text-sm font-medium">
                                                Reject
                                            </button>
                                        </div>
                                        <!-- Reject Modal -->
                                        <div id="reject-modal-{{ $escalation->id }}" class="hidden mt-3">
                                            <form method="POST"
                                                action="{{ route('tickets.escalations.reject', [$ticket, $escalation]) }}">
                                                @csrf
                                                <textarea name="response_notes" rows="2" placeholder="Optional rejection reason..."
                                                    class="w-full px-3 py-2 border border-themeBorder rounded-lg text-sm mb-2"></textarea>
                                                <div class="flex space-x-2">
                                                    <button type="submit"
                                                        class="flex-1 bg-red-500 text-white px-3 py-1.5 rounded-lg hover:bg-red-600 transition text-sm font-medium">
                                                        Confirm Reject
                                                    </button>
                                                    <button type="button"
                                                        onclick="hideRejectModal('{{ $escalation->id }}')"
                                                        class="flex-1 bg-themeHover text-themeBody px-3 py-1.5 rounded-lg hover:bg-themeBorder transition text-sm font-medium">
                                                        Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    @elseif ($escalation->requested_by === auth()->id())
                                        <form method="POST"
                                            action="{{ route('tickets.escalations.cancel', [$ticket, $escalation]) }}">
                                            @csrf
                                            <button type="submit"
                                                class="w-full bg-themeHover text-themeBody px-3 py-1.5 rounded-lg hover:bg-themeBorder transition text-sm font-medium">
                                                Cancel Request
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Activity Summary Card (for assigned user) -->
                @if (!auth()->user()->isCustomer() && $ticket->assigned_to === auth()->id() && $ticket->currentAssignment)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Update Activity</h2>
                        <form method="POST" action="{{ route('tickets.assignment-activity', $ticket) }}"
                            class="space-y-4">
                            @csrf
                            <div>
                                <label for="activity_summary" class="block text-themeBody font-light mb-2">Activity
                                    Summary</label>
                                <textarea id="activity_summary" name="activity_summary" rows="4"
                                    placeholder="Describe what you've done on this ticket..."
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ $ticket->currentAssignment->activity_summary }}</textarea>
                            </div>
                            <button type="submit"
                                class="w-full bg-primary text-white px-4 py-2.5 rounded-xl hover:bg-[#005a61] transition font-medium">
                                Update Activity
                            </button>
                        </form>
                    </div>
                @endif

                <!-- Quick Links Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Quick Links</h2>
                    <div class="space-y-2">
                        @if ($ticket->sale)
                            <a href="{{ route('sales.show', $ticket->sale) }}"
                                class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeHover transition flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                                <span>View Related Sale</span>
                            </a>
                        @endif
                        @if ($ticket->customer)
                            <a href="{{ route('customers.show', $ticket->customer) }}"
                                class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeHover transition flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>View Customer</span>
                            </a>
                        @endif

                        @if (!$ticket->disbursement && !auth()->user()->isCustomer())
                            <button onclick="document.getElementById('disbursement-form').classList.toggle('hidden')"
                                class="block w-full bg-[#E48A22] text-white px-4 py-2 rounded hover:bg-[#d17a1a] transition font-light flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10V6m0 12v2m-7-6a7 7 0 1114 0 7 7 0 01-14 0z">
                                    </path>
                                </svg>
                                <span>Create Disbursement</span>
                            </button>
                        @endif
                        <a href="{{ route('tickets.index') }}?status={{ $ticket->status }}"
                            class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeHover transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            <span>View {{ ucfirst($ticket->status) }} Tickets</span>
                        </a>
                        <a href="{{ route('tickets.index') }}?priority={{ $ticket->priority }}"
                            class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeHover transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span>View {{ ucfirst($ticket->priority) }} Priority</span>
                        </a>
                    </div>
                </div>

                <!-- Stats Card -->
                <div class="bg-themeCard rounded-lg border border-themeBorder p-6">
                    <h2 class="text-xl text-primary font-light mb-4">Statistics</h2>
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm text-themeMuted font-light mb-1">Total Replies</div>
                            <div class="text-2xl text-primary font-light">{{ $ticket->replies->count() }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-themeMuted font-light mb-1">Days Open</div>
                            <div class="text-2xl text-primary font-light">
                                @php
                                    $daysOpen = floor($ticket->created_at->diffInDays(now()));
                                    if ($daysOpen == 0) {
                                        $hoursOpen = $ticket->created_at->diffInHours(now());
                                        echo $hoursOpen < 1
                                            ? 'Just now'
                                            : ($hoursOpen == 1
                                                ? '1 hour'
                                                : $hoursOpen . ' hours');
                                    } else {
                                        echo $daysOpen . ($daysOpen == 1 ? ' day' : ' days');
                                    }
                                @endphp
                            </div>
                        </div>
                        @if ($ticket->resolved_at)
                            <div>
                                <div class="text-sm text-themeMuted font-light mb-1">Resolution Time</div>
                                <div class="text-2xl text-[#E48A22] font-light">
                                    @php
                                        $resolutionDays = $ticket->created_at->diffInDays($ticket->resolved_at);
                                        echo $resolutionDays . ($resolutionDays == 1 ? ' day' : ' days');
                                    @endphp
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Customer Context Card -->
                @if (!auth()->user()->isCustomer())
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Customer Context</h2>

                        @if ($customerDevices->count() > 0)
                            <div class="mb-4">
                                <div class="text-sm text-themeMuted font-light mb-2">Devices
                                    ({{ $customerDevices->count() }})</div>
                                <div class="space-y-2 max-h-32 overflow-y-auto">
                                    @foreach ($customerDevices->take(3) as $device)
                                        <div class="text-sm text-themeBody font-light">
                                            <a href="{{ route('devices.show', $device) }}"
                                                class="text-primary hover:text-[#005a61]">
                                                {{ $device->imei }} - {{ $device->product->name ?? 'Unknown' }}
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($customerSales->count() > 0)
                            <div class="mb-4">
                                <div class="text-sm text-themeMuted font-light mb-2">Recent Sales
                                    ({{ $customerSales->count() }})</div>
                                <div class="space-y-2 max-h-32 overflow-y-auto">
                                    @foreach ($customerSales->take(3) as $sale)
                                        <div class="text-sm text-themeBody font-light">
                                            <a href="{{ route('sales.show', $sale) }}"
                                                class="text-primary hover:text-[#005a61]">
                                                {{ $sale->sale_number ?? 'Sale #' . $sale->id }} - TSh
                                                {{ number_format($sale->total, 2) }}
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($customerTickets->count() > 0)
                            <div class="mb-4">
                                <div class="text-sm text-themeMuted font-light mb-2">Other Tickets
                                    ({{ $customerTickets->count() }})</div>
                                <div class="space-y-2 max-h-32 overflow-y-auto">
                                    @foreach ($customerTickets->take(3) as $otherTicket)
                                        <div class="text-sm text-themeBody font-light">
                                            <a href="{{ route('tickets.show', $otherTicket) }}"
                                                class="text-primary hover:text-[#005a61]">
                                                {{ $otherTicket->ticket_number }} -
                                                {{ \Illuminate\Support\Str::limit($otherTicket->subject, 30) }}
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($customerDisbursements->count() > 0)
                            <div>
                                <div class="text-sm text-themeMuted font-light mb-2">Recent Disbursements
                                    ({{ $customerDisbursements->count() }})</div>
                                <div class="space-y-2 max-h-32 overflow-y-auto">
                                    @foreach ($customerDisbursements->take(3) as $disbursement)
                                        <div class="text-sm text-themeBody font-light">
                                            <a href="{{ route('customer-disbursements.show', $disbursement) }}"
                                                class="text-primary hover:text-[#005a61]">
                                                TSh {{ number_format($disbursement->amount, 2) }} -
                                                {{ $disbursement->created_at->format('M d, Y') }}
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                @if (!auth()->user()->isCustomer())
                    <!-- Create Disbursement Form -->
                    <div id="disbursement-form"
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] {{ $ticket->disbursement ? 'hidden' : '' }}">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Create Customer Disbursement
                        </h2>
                        <form method="POST" action="{{ route('tickets.create-disbursement', $ticket) }}"
                            class="space-y-4">
                            @csrf
                            <div>
                                <label for="device_id" class="block text-themeBody font-light mb-2">Device (IMEI)
                                    *</label>
                                <select id="device_id" name="device_id" required
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                    <option value="">Select Device</option>
                                    @foreach ($availableDevices as $device)
                                        <option value="{{ $device->id }}"
                                            {{ old('device_id') == $device->id || $ticket->device_id == $device->id ? 'selected' : '' }}>
                                            {{ $device->imei }} - {{ $device->product->name ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-themeMuted font-light mt-1">Select the device (IMEI) that will
                                    receive
                                    this disbursement.</p>
                                @error('device_id')
                                    <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="amount" class="block text-themeBody font-light mb-2">Amount *</label>
                                <input type="number" id="amount" name="amount" step="0.01" min="0.01"
                                    required
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            </div>
                            <div>
                                <label for="disbursement_phone" class="block text-themeBody font-light mb-2">Phone Number
                                    *</label>
                                <input type="text" id="disbursement_phone" name="disbursement_phone"
                                    value="{{ $ticket->customer->phone ?? '' }}" required
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            </div>
                            <div>
                                <label for="notes" class="block text-themeBody font-light mb-2">Notes</label>
                                <textarea id="notes" name="notes" rows="3"
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"></textarea>
                            </div>
                            <button type="submit"
                                class="w-full bg-[#E48A22] text-white px-6 py-2 rounded hover:bg-[#d17a1a] transition font-light flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Create Disbursement</span>
                            </button>
                        </form>
                    </div>

                    <!-- Update Ticket Card -->
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Update Ticket</h2>
                        <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label for="assigned_to" class="block text-themeBody font-light mb-2">Assign To</label>
                                <select id="assigned_to" name="assigned_to"
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                    <option value="">Unassigned</option>
                                    @foreach ($staff as $user)
                                        <option value="{{ $user->id }}"
                                            {{ old('assigned_to', $ticket->assigned_to) == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="status" class="block text-themeBody font-light mb-2">Status *</label>
                                <select id="status" name="status" required
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                    <option value="open"
                                        {{ old('status', $ticket->status) === 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="in_progress"
                                        {{ old('status', $ticket->status) === 'in_progress' ? 'selected' : '' }}>In
                                        Progress</option>
                                    <option value="resolved"
                                        {{ old('status', $ticket->status) === 'resolved' ? 'selected' : '' }}>Resolved
                                    </option>
                                    <option value="closed"
                                        {{ old('status', $ticket->status) === 'closed' ? 'selected' : '' }}>Closed
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label for="priority" class="block text-themeBody font-light mb-2">Priority *</label>
                                <select id="priority" name="priority" required
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                    <option value="low"
                                        {{ old('priority', $ticket->priority) === 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium"
                                        {{ old('priority', $ticket->priority) === 'medium' ? 'selected' : '' }}>Medium
                                    </option>
                                    <option value="high"
                                        {{ old('priority', $ticket->priority) === 'high' ? 'selected' : '' }}>High
                                    </option>
                                    <option value="urgent"
                                        {{ old('priority', $ticket->priority) === 'urgent' ? 'selected' : '' }}>Urgent
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label for="due_date" class="block text-themeBody font-light mb-2">Due Date</label>
                                <input type="datetime-local" id="due_date" name="due_date"
                                    value="{{ old('due_date', $ticket->due_date ? $ticket->due_date->format('Y-m-d\TH:i') : '') }}"
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            </div>

                            <div>
                                <label for="tags" class="block text-themeBody font-light mb-2">Tags</label>
                                <select id="tags" name="tags[]" multiple
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                    @foreach ($tags as $tag)
                                        <option value="{{ $tag->id }}"
                                            {{ $ticket->tags->contains($tag->id) ? 'selected' : '' }}>
                                            {{ $tag->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-themeMuted font-light mt-1">Hold Ctrl/Cmd to select multiple tags
                                </p>
                            </div>

                            <button type="submit"
                                class="w-full bg-primary text-white px-6 py-2 rounded hover:bg-[#005a61] transition font-light flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Update Ticket</span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function showRejectModal(escalationId) {
            document.getElementById('reject-modal-' + escalationId).classList.remove('hidden');
        }

        function hideRejectModal(escalationId) {
            document.getElementById('reject-modal-' + escalationId).classList.add('hidden');
        }
    </script>
@endpush
