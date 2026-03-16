<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\TicketAttachment;
use App\Models\TicketTag;
use App\Models\TicketAssignment;
use App\Models\TicketEscalation;
use App\Models\User;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Device;
use App\Models\Product;
use App\Models\Branch;
use App\Models\CustomerDisbursement;
use App\Models\ActivityLog;
use App\Exports\TicketsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Base query
        if ($user->isCustomer()) {
            $customer = Customer::where('email', $user->email)
                ->orWhere('phone', $user->phone)
                ->first();

            if ($customer) {
                $query = Ticket::with(['customer', 'assignedTo', 'sale', 'device.product', 'product', 'branch', 'tags'])
                    ->where('customer_id', $customer->id);
            } else {
                $query = Ticket::whereRaw('1 = 0');
            }
        } else {
            $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
            $query = Ticket::with(['customer', 'assignedTo', 'sale', 'device.product', 'product', 'branch', 'tags']);
            if ($isFieldAgent) {
                $query->where('assigned_to', $user->id);
            } else {
                $query->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id));
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('ticket_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('ticket_tags.id', $request->tag);
            });
        }

        if ($request->filled('overdue')) {
            $query->where('due_date', '<', now())
                ->whereNotIn('status', ['resolved', 'closed']);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $tickets = $query->paginate(15)->withQueryString();

        // Stats (same scope as list: customer sees own tickets; field agent sees assigned/own; staff see branch tree)
        if ($user->isCustomer()) {
            $baseQuery = isset($customer) && $customer ? Ticket::where('customer_id', $customer->id) : Ticket::whereRaw('1 = 0');
        } elseif (isset($isFieldAgent) && $isFieldAgent) {
            $baseQuery = Ticket::query()->where('assigned_to', $user->id);
        } else {
            $baseQuery = Ticket::query()->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id));
        }

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'open' => (clone $baseQuery)->where('status', 'open')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'resolved' => (clone $baseQuery)->where('status', 'resolved')->count(),
            'overdue' => (clone $baseQuery)->where('due_date', '<', now())
                ->whereNotIn('status', ['resolved', 'closed'])->count(),
        ];

        // Get filter options: users who can be assigned tickets (permission-based)
        $staff = User::assignableToTickets($request->user())->orderBy('name')->get();
        $tags = TicketTag::orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('tickets.index', compact('tickets', 'stats', 'staff', 'tags', 'products', 'branches'));
    }

    public function export(Request $request)
    {
        $filename = 'tickets-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new TicketsExport($request), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $customerId = $request->query('customer_id');
        $customer = null;
        $sales = collect();
        $devices = collect();
        $products = collect();
        $customers = collect();

        if ($customerId) {
            // Staff/admin creating ticket for a customer
            $customer = Customer::findOrFail($customerId);
            $sales = $customer->sales()->latest()->get();
            $devices = $customer->devices()->with('product')->latest()->get();
        } elseif ($user->isCustomer()) {
            // Customer creating their own ticket
            $customer = Customer::where('email', $user->email)
                ->orWhere('phone', $user->phone)
                ->first();
            if ($customer) {
                $sales = $customer->sales()->latest()->get();
                $devices = $customer->devices()->with('product')->latest()->get();
            }
        }

        if (!$user->isCustomer()) {
            $customers = Customer::where('is_active', true)->orderBy('name')->get();
            $products = Product::where('is_active', true)->orderBy('name')->get();
        }

        $tags = TicketTag::orderBy('name')->get();
        $assignableUsers = $user->isCustomer() ? collect() : User::assignableToTickets($user)->orderBy('name')->get();

        return view('tickets.create', compact('sales', 'customer', 'tags', 'devices', 'products', 'customers', 'assignableUsers'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Check if creating a new customer
        $createNewCustomer = $request->boolean('create_new_customer') || $request->input('customer_id') === 'new';

        // Validate ticket fields
        $ticketRules = [
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'category' => 'required|in:technical,billing,sales,general,order,promise,complaint,unsuccessful,credit',
            'sale_id' => 'nullable|exists:sales,id',
            'device_id' => 'nullable|exists:devices,id',
            'product_id' => 'nullable|exists:products,id',
            'assigned_to' => 'nullable|exists:users,id',
            'customer_id' => $createNewCustomer ? 'nullable' : 'nullable|exists:customers,id',
            'due_date' => 'nullable|date|after:now',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:ticket_tags,id',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt',
        ];

        // Add customer creation validation if creating new customer
        if ($createNewCustomer) {
            $ticketRules['new_customer_name'] = 'required|string|max:255';
            $ticketRules['new_customer_email'] = 'nullable|string|email|max:255|unique:customers,email';
            $ticketRules['new_customer_phone'] = 'nullable|string|max:255|unique:customers,phone';
            $ticketRules['new_customer_address'] = 'nullable|string';
            $ticketRules['new_customer_id_number'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($ticketRules);

        // Handle customer creation or selection
        if ($user->isCustomer()) {
            // Customer creating their own ticket - find their customer record
            $customer = Customer::where('email', $user->email)
                ->orWhere('phone', $user->phone)
                ->first();
            if (!$customer) {
                return redirect()->back()->withErrors(['customer_id' => 'Customer record not found.'])->withInput();
            }
            $customerId = $customer->id;
            // Don't auto-assign tickets created by customers
        } else {
            // Staff/admin creating ticket
            if ($createNewCustomer || $request->input('customer_id') === 'new') {
                // Create new customer first
                $customer = Customer::create([
                    'name' => $validated['new_customer_name'],
                    'email' => trim($validated['new_customer_email'] ?? '') ?: null,
                    'phone' => trim($validated['new_customer_phone'] ?? '') ?: null,
                    'address' => $validated['new_customer_address'] ?? null,
                    'id_number' => $validated['new_customer_id_number'] ?? null,
                    'is_active' => true,
                ]);
                $customerId = $customer->id;

                // Log customer creation
                ActivityLog::log(
                    Auth::id(),
                    'customer_created',
                    "Created customer: {$customer->name}",
                    Customer::class,
                    $customer->id,
                    ['customer_name' => $customer->name, 'customer_email' => $customer->email]
                );
            } else {
                // Use existing customer
                if (empty($validated['customer_id']) || $validated['customer_id'] === 'new') {
                    return redirect()->back()->withErrors(['customer_id' => 'Customer is required when creating a ticket as staff.'])->withInput();
                }
                $customerId = $validated['customer_id'];
            }
            // Assign: use request value if provided and assignable, else default to current user
            $assignableIds = User::assignableToTickets($user)->pluck('id');
            if ($request->filled('assigned_to') && $assignableIds->contains($request->assigned_to)) {
                $validated['assigned_to'] = $request->assigned_to;
            } else {
                $validated['assigned_to'] = $user->id;
            }

            // Automatically set branch_id from current user's branch
            if ($user->branch_id) {
                $validated['branch_id'] = $user->branch_id;
            }
        }

        $validated['customer_id'] = $customerId;
        $validated['status'] = 'open';

        $ticket = Ticket::create($validated);

        // Attach tags
        if ($request->filled('tags')) {
            $ticket->tags()->sync($request->tags);
        }

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket-attachments', 'public');
                TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }

        // Log activity
        ActivityLog::log(
            Auth::id(),
            'ticket_created',
            "Created ticket #{$ticket->ticket_number}: {$ticket->subject}",
            Ticket::class,
            $ticket->id,
            ['ticket_number' => $ticket->ticket_number, 'subject' => $ticket->subject]
        );

        return redirect()->route('tickets.index')->with('success', 'Ticket created successfully.');
    }

    public function show(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        if (!$user->isCustomer()) {
            if ($user->fieldAgentProfile && $user->branch_id) {
                $allowed = (string) $ticket->assigned_to === (string) $user->id;
                if (!$allowed) {
                    abort(403, 'You do not have access to this ticket.');
                }
            } elseif ($user->branch_id && $ticket->branch_id !== $user->branch_id) {
                abort(403, 'You do not have access to this ticket. It belongs to another branch.');
            }
        } elseif ($user->isCustomer()) {
            $customer = Customer::where('email', $user->email)->orWhere('phone', $user->phone)->first();
            if (!$customer || (string) $ticket->customer_id !== (string) $customer->id) {
                abort(403, 'You do not have access to this ticket.');
            }
        }
        $ticket->load([
            'customer',
            'assignedTo',
            'sale.items.product',
            'sale.items.device',
            'device.product',
            'device.branch',
            'product.brand',
            'branch',
            'disbursement',
            'replies.user',
            'replies.attachments',
            'attachments.uploadedBy',
            'tags',
            'assignments.assignedTo',
            'assignments.assignedBy',
            'currentAssignment.assignedTo',
            'escalations.requestedBy',
            'escalations.requestedTo',
            'escalations.respondedBy',
            'pendingEscalations.requestedBy',
            'pendingEscalations.requestedTo'
        ]);

        $staff = User::assignableToTickets($request->user())->orderBy('name')->get();
        $tags = TicketTag::orderBy('name')->get();

        // Get customer's related data for context
        $customerDevices = $ticket->customer->devices()->with('product')->latest()->get();
        $customerSales = $ticket->customer->sales()->latest()->take(10)->get();
        $customerTickets = $ticket->customer->tickets()->where('id', '!=', $ticket->id)->latest()->take(5)->get();
        $customerDisbursements = $ticket->customer->disbursements()->latest()->take(5)->get();

        // Get devices for disbursement (that haven't received disbursement)
        $availableDevices = Device::where('customer_id', $ticket->customer_id)
            ->where('has_received_disbursement', false)
            ->with('product')
            ->orderBy('imei')
            ->get();

        return view('tickets.show', compact('ticket', 'staff', 'tags', 'customerDevices', 'customerSales', 'customerTickets', 'customerDisbursements', 'availableDevices'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        if (!$user->isCustomer()) {
            if ($user->fieldAgentProfile && $user->branch_id) {
                $allowed = (string) $ticket->assigned_to === (string) $user->id;
                if (!$allowed) {
                    abort(403, 'You do not have access to this ticket.');
                }
            } elseif ($user->branch_id && $ticket->branch_id !== $user->branch_id) {
                abort(403, 'You do not have access to this ticket. It belongs to another branch.');
            }
        } else {
            $customer = Customer::where('email', $user->email)->orWhere('phone', $user->phone)->first();
            if (!$customer || (string) $ticket->customer_id !== (string) $customer->id) {
                abort(403, 'You do not have access to this ticket.');
            }
        }
        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'required|in:open,in_progress,resolved,closed',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:ticket_tags,id',
        ]);

        if (!empty($validated['assigned_to']) && !User::visibleTo($request->user())->where('id', $validated['assigned_to'])->exists()) {
            return back()->withErrors(['assigned_to' => 'You can only assign to users in your branch or branches below.']);
        }

        if ($validated['status'] === 'resolved' && !$ticket->resolved_at) {
            $validated['resolved_at'] = now();
        }

        $oldAssignedTo = $ticket->assigned_to;
        $newAssignedTo = $validated['assigned_to'] ?? null;
        $wasAssigned = $oldAssignedTo != $newAssignedTo && $newAssignedTo !== null;

        $oldStatus = $ticket->status;
        $oldPriority = $ticket->priority;

        $ticket->update($validated);

        // Update tags
        if ($request->has('tags')) {
            $ticket->tags()->sync($request->tags ?? []);
        }

        // Log activity for status change
        if ($oldStatus !== $validated['status']) {
            ActivityLog::log(
                Auth::id(),
                'ticket_status_changed',
                "Changed ticket #{$ticket->ticket_number} status from " . ucfirst(str_replace('_', ' ', $oldStatus)) . " to " . ucfirst(str_replace('_', ' ', $validated['status'])),
                Ticket::class,
                $ticket->id,
                ['ticket_number' => $ticket->ticket_number, 'old_status' => $oldStatus, 'new_status' => $validated['status']]
            );
        }

        // Log activity for priority change
        if ($oldPriority !== $validated['priority']) {
            ActivityLog::log(
                Auth::id(),
                'ticket_priority_changed',
                "Changed ticket #{$ticket->ticket_number} priority from " . ucfirst($oldPriority) . " to " . ucfirst($validated['priority']),
                Ticket::class,
                $ticket->id,
                ['ticket_number' => $ticket->ticket_number]
            );
        }

        // Log activity for assignment
        if ($wasAssigned) {
            $ticket->refresh();
            ActivityLog::log(
                Auth::id(),
                'ticket_assigned',
                "Assigned ticket #{$ticket->ticket_number} to " . ($ticket->assignedTo->name ?? 'user'),
                Ticket::class,
                $ticket->id,
                ['ticket_number' => $ticket->ticket_number]
            );
        }

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket updated successfully.');
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'is_internal' => 'boolean',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['is_internal'] = $request->boolean('is_internal', false);

        $reply = $ticket->replies()->create($validated);

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ticket-attachments', 'public');
                TicketAttachment::create([
                    'ticket_reply_id' => $reply->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }

        // Update ticket status and SLA tracking
        if ($ticket->status === 'open' && !Auth::user()->isCustomer()) {
            $ticket->update(['status' => 'in_progress']);
        }

        // Mark first response if this is the first staff reply
        if (!Auth::user()->isCustomer() && !$ticket->first_response_at) {
            $ticket->markFirstResponse();
        }

        // Update last response time
        $ticket->updateLastResponse();

        // Log activity
        ActivityLog::log(
            Auth::id(),
            'ticket_replied',
            "Replied to ticket #{$ticket->ticket_number}",
            Ticket::class,
            $ticket->id,
            ['ticket_number' => $ticket->ticket_number]
        );

        return redirect()->route('tickets.show', $ticket)->with('success', 'Reply added successfully.');
    }

    public function downloadAttachment(TicketAttachment $attachment)
    {
        $filePath = storage_path('app/public/' . $attachment->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath, $attachment->file_name);
    }

    public function deleteAttachment(TicketAttachment $attachment)
    {
        // Only allow deletion by uploader or admin
        if ($attachment->uploaded_by !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return redirect()->back()->with('success', 'Attachment deleted successfully.');
    }

    public function createDisbursement(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'device_id' => 'required|exists:devices,id',
            'amount' => 'required|numeric|min:0.01',
            'disbursement_phone' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Validate device belongs to ticket's customer
        $device = Device::findOrFail($validated['device_id']);
        if ($device->customer_id !== $ticket->customer_id) {
            return back()->withErrors(['device_id' => 'Selected device does not belong to the ticket customer.'])->withInput();
        }

        // Every disbursement must have a sale (use ticket's sale or device's sale)
        $saleId = $ticket->sale_id ?? $device->sale_id;
        if ($saleId === null) {
            return back()->withErrors(['device_id' => 'This device is not linked to a sale. Link the ticket to a sale first, or choose a device that was sold in a sale.'])->withInput();
        }

        // One disbursement per device (DB unique); update existing or create
        $existing = CustomerDisbursement::where('device_id', $validated['device_id'])->first();
        if ($existing && ($existing->isApproved() || $existing->isRejected())) {
            return back()->withErrors(['device_id' => 'This device already has an approved or rejected disbursement.'])->withInput();
        }
        if (!$existing && $device->has_received_disbursement) {
            return back()->withErrors(['device_id' => 'This device has already received a disbursement.'])->withInput();
        }

        $notes = ($validated['notes'] ?? '') . "\n\nCreated from ticket: {$ticket->ticket_number}";
        $data = [
            'customer_id' => $ticket->customer_id,
            'sale_id' => $saleId,
            'device_id' => $validated['device_id'],
            'amount' => $validated['amount'],
            'disbursement_phone' => $validated['disbursement_phone'],
            'notes' => $notes,
            'disbursed_by' => Auth::id(),
            'status' => CustomerDisbursement::STATUS_PENDING,
        ];

        DB::transaction(function () use ($data, $ticket, $existing) {
            $disbursement = $existing
                ? tap($existing)->update($data)
                : CustomerDisbursement::create($data);

            $ticket->update(['disbursement_id' => $disbursement->id]);

            ActivityLog::log(
                Auth::id(),
                $existing ? 'ticket_disbursement_updated' : 'ticket_disbursement_created',
                ($existing ? 'Updated' : 'Created') . " disbursement of {$disbursement->amount} from ticket #{$ticket->ticket_number} (pending approval)",
                Ticket::class,
                $ticket->id,
                ['ticket_number' => $ticket->ticket_number, 'disbursement_id' => $disbursement->id, 'amount' => $disbursement->amount]
            );
        });

        $message = $existing ? 'Disbursement updated. It requires approval before it is applied.' : 'Customer disbursement created. It requires approval before it is applied.';
        return redirect()->route('tickets.show', $ticket)->with('success', $message);
    }

    /**
     * Request escalation of a ticket to another user
     */
    public function requestEscalation(Request $request, Ticket $ticket)
    {
        // Only the currently assigned user can request escalation
        if ($ticket->assigned_to !== Auth::id()) {
            return back()->withErrors(['error' => 'Only the assigned user can request escalation.']);
        }

        $validated = $request->validate([
            'requested_to' => 'required|exists:users,id',
            'reason' => 'required|string|max:1000',
        ]);

        // Check if user is visible to current user
        if (!User::visibleTo($request->user())->where('id', $validated['requested_to'])->exists()) {
            return back()->withErrors(['requested_to' => 'You can only escalate to users in your branch or branches below.']);
        }

        // Check if there's already a pending escalation
        $pendingEscalation = $ticket->pendingEscalations()
            ->where('requested_to', $validated['requested_to'])
            ->first();

        if ($pendingEscalation) {
            return back()->withErrors(['error' => 'There is already a pending escalation request to this user.']);
        }

        $escalation = TicketEscalation::create([
            'ticket_id' => $ticket->id,
            'requested_by' => Auth::id(),
            'requested_to' => $validated['requested_to'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        // Log activity
        ActivityLog::log(
            Auth::id(),
            'ticket_escalation_requested',
            "Requested escalation of ticket #{$ticket->ticket_number} to " . User::find($validated['requested_to'])->name,
            Ticket::class,
            $ticket->id,
            ['ticket_number' => $ticket->ticket_number, 'escalation_id' => $escalation->id]
        );

        return redirect()->route('tickets.show', $ticket)->with('success', 'Escalation request sent successfully.');
    }

    /**
     * Accept an escalation request
     */
    public function acceptEscalation(Request $request, Ticket $ticket, TicketEscalation $escalation)
    {
        // Only the requested user can accept
        if ($escalation->requested_to !== Auth::id()) {
            return back()->withErrors(['error' => 'Only the requested user can accept this escalation.']);
        }

        // Only pending escalations can be accepted
        if (!$escalation->isPending()) {
            return back()->withErrors(['error' => 'This escalation request is no longer pending.']);
        }

        $validated = $request->validate([
            'response_notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($ticket, $escalation, $validated) {
            // Accept the escalation
            $escalation->accept($validated['response_notes'] ?? null);

            // Assign ticket to the user who accepted
            $oldAssignedTo = $ticket->assigned_to;
            $ticket->update(['assigned_to' => Auth::id()]);

            // Update assignment history (handled by model boot, but we can add activity summary)
            $currentAssignment = $ticket->currentAssignment;
            if ($currentAssignment) {
                $currentAssignment->update([
                    'activity_summary' => "Accepted escalation request. " . ($validated['response_notes'] ?? ''),
                ]);
            }

            // Cancel any other pending escalations for this ticket
            $ticket->pendingEscalations()
                ->where('id', '!=', $escalation->id)
                ->get()
                ->each(function ($otherEscalation) {
                    $otherEscalation->cancel();
                });

            // Log activity
            ActivityLog::log(
                Auth::id(),
                'ticket_escalation_accepted',
                "Accepted escalation request for ticket #{$ticket->ticket_number}",
                Ticket::class,
                $ticket->id,
                ['ticket_number' => $ticket->ticket_number, 'escalation_id' => $escalation->id, 'previous_assigned_to' => $oldAssignedTo]
            );
        });

        return redirect()->route('tickets.show', $ticket)->with('success', 'Escalation accepted. Ticket has been assigned to you.');
    }

    /**
     * Reject an escalation request
     */
    public function rejectEscalation(Request $request, Ticket $ticket, TicketEscalation $escalation)
    {
        // Only the requested user can reject
        if ($escalation->requested_to !== Auth::id()) {
            return back()->withErrors(['error' => 'Only the requested user can reject this escalation.']);
        }

        // Only pending escalations can be rejected
        if (!$escalation->isPending()) {
            return back()->withErrors(['error' => 'This escalation request is no longer pending.']);
        }

        $validated = $request->validate([
            'response_notes' => 'nullable|string|max:1000',
        ]);

        $escalation->reject($validated['response_notes'] ?? null);

        // Log activity
        ActivityLog::log(
            Auth::id(),
            'ticket_escalation_rejected',
            "Rejected escalation request for ticket #{$ticket->ticket_number}",
            Ticket::class,
            $ticket->id,
            ['ticket_number' => $ticket->ticket_number, 'escalation_id' => $escalation->id]
        );

        return redirect()->route('tickets.show', $ticket)->with('success', 'Escalation request rejected.');
    }

    /**
     * Cancel an escalation request
     */
    public function cancelEscalation(Request $request, Ticket $ticket, TicketEscalation $escalation)
    {
        // Only the requester can cancel
        if ($escalation->requested_by !== Auth::id()) {
            return back()->withErrors(['error' => 'Only the requester can cancel this escalation.']);
        }

        // Only pending escalations can be cancelled
        if (!$escalation->isPending()) {
            return back()->withErrors(['error' => 'This escalation request is no longer pending.']);
        }

        $escalation->cancel();

        // Log activity
        ActivityLog::log(
            Auth::id(),
            'ticket_escalation_cancelled',
            "Cancelled escalation request for ticket #{$ticket->ticket_number}",
            Ticket::class,
            $ticket->id,
            ['ticket_number' => $ticket->ticket_number, 'escalation_id' => $escalation->id]
        );

        return redirect()->route('tickets.show', $ticket)->with('success', 'Escalation request cancelled.');
    }

    /**
     * Update activity summary for current assignment
     */
    public function updateAssignmentActivity(Request $request, Ticket $ticket)
    {
        // Only the assigned user can update their activity
        if ($ticket->assigned_to !== Auth::id()) {
            return back()->withErrors(['error' => 'Only the assigned user can update activity.']);
        }

        $validated = $request->validate([
            'activity_summary' => 'required|string|max:2000',
        ]);

        $currentAssignment = $ticket->currentAssignment;
        if ($currentAssignment) {
            $currentAssignment->update([
                'activity_summary' => $validated['activity_summary'],
            ]);

            // Log activity
            ActivityLog::log(
                Auth::id(),
                'ticket_assignment_activity_updated',
                "Updated activity summary for ticket #{$ticket->ticket_number}",
                Ticket::class,
                $ticket->id,
                ['ticket_number' => $ticket->ticket_number]
            );

            return redirect()->route('tickets.show', $ticket)->with('success', 'Activity summary updated successfully.');
        }

        return back()->withErrors(['error' => 'No current assignment found.']);
    }
}
