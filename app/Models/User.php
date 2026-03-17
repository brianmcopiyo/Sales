<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasUuid;
use App\Mail\LoginCredentialsMail;
use App\Helpers\SmsHelper;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuid, SoftDeletes, HasApiTokens;

    /**
     * When set before creating a user, the plain password will be emailed to the new user
     * (non-customers only) after the model is created. Clear after use.
     */
    public static ?string $plainPasswordForNewUser = null;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'role_id',
        'branch_id',
        'phone',
        'profile_picture',
        'dashboard_background_type',
        'dashboard_background_value',
        'dashboard_background_custom_path',
        'theme',
        'suspended_at',
        'total_commission_earned',
        'commission_available_balance',
    ];

    /**
     * Theme key for the user (default if not set).
     */
    public function getThemeKey(): string
    {
        $key = $this->theme ?? 'default';
        $themes = config('themes', []);
        return isset($themes[$key]) ? $key : 'default';
    }

    /**
     * Primary color for the current theme (for inline styles if needed).
     */
    public function getThemePrimaryColor(): string
    {
        $themes = config('themes', []);
        $theme = $themes[$this->getThemeKey()] ?? $themes['default'] ?? [];
        return $theme['primary'] ?? '#006F78';
    }

    /**
     * Get the profile picture URL
     */
    public function getProfilePictureUrlAttribute(): ?string
    {
        if (!$this->profile_picture) {
            return null;
        }
        return \Illuminate\Support\Facades\Storage::url($this->profile_picture);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
            'total_commission_earned' => 'decimal:2',
            'commission_available_balance' => 'decimal:2',
        ];
    }

    /** Whether this user is suspended (cannot log in). */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            if (static::$plainPasswordForNewUser === null) {
                return;
            }
            if ($user->isCustomer()) {
                static::$plainPasswordForNewUser = null;
                return;
            }
            try {
                if (!empty($user->email)) {
                    Mail::to($user->email)->send(new LoginCredentialsMail($user, static::$plainPasswordForNewUser));
                }
                if (!empty($user->phone)) {
                    $loginId = $user->email ?: $user->phone;
                    $msg = "Stock Management - Your login: {$loginId}, password: " . static::$plainPasswordForNewUser . ". Change password after first login.";
                    SmsHelper::send($user->phone, $msg);
                }
            } finally {
                static::$plainPasswordForNewUser = null;
            }
        });
    }

    /**
     * Send login credentials email to this user (for non-customers).
     * Call this when you have the plain password after creating the user.
     */
    public function sendLoginCredentialsEmail(string $plainPassword): void
    {
        if ($this->isCustomer() || empty($this->email)) {
            return;
        }
        Mail::to($this->email)->send(new LoginCredentialsMail($this, $plainPassword));
    }

    /**
     * Return CSS style string for the user's dashboard background (for use on dashboard only).
     * Returns empty string if no preference set.
     */
    public function getDashboardBackgroundCss(): string
    {
        $type = $this->dashboard_background_type;
        $value = $this->dashboard_background_value;
        if (!$type || !$value) {
            return '';
        }
        $presets = config('dashboard_backgrounds', []);
        if ($type === 'color' && isset($presets['color'][$value])) {
            return $presets['color'][$value]['css'] ?? '';
        }
        if ($type === 'pattern' && isset($presets['pattern'][$value])) {
            return $presets['pattern'][$value]['css'] ?? '';
        }
        if ($type === 'image') {
            $url = null;
            if ($value === 'custom' && $this->dashboard_background_custom_path) {
                $url = \Illuminate\Support\Facades\Storage::url($this->dashboard_background_custom_path);
            } elseif (isset($presets['image'][$value])) {
                $url = $presets['image'][$value]['url'] ?? '';
            }
            if ($url) {
                $css = 'background-image: url(%s); background-size: cover; background-position: center; background-attachment: fixed;';
                return sprintf($css, e($url));
            }
        }
        return '';
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function roleModel()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function createdStockTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'created_by');
    }

    public function receivedStockTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'received_by');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'sold_by');
    }

    public function customerSales()
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'customer_id');
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function ticketReplies()
    {
        return $this->hasMany(TicketReply::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class)->latest();
    }

    public function fieldAgentProfile()
    {
        return $this->hasOne(FieldAgent::class, 'user_id');
    }

    public function commissionDisbursements()
    {
        return $this->hasMany(CommissionDisbursement::class, 'user_id');
    }

    /** Stock allocated to this user when they are a field agent (field_agent_id = user id). */
    public function fieldAgentStock()
    {
        return $this->hasMany(FieldAgentStock::class, 'field_agent_id');
    }

    /** Agent stock requests made by this user when they are a field agent. */
    public function agentStockRequests()
    {
        return $this->hasMany(AgentStockRequest::class, 'field_agent_id');
    }

    public function plannedVisits()
    {
        return $this->hasMany(PlannedVisit::class);
    }

    public function checkIns()
    {
        return $this->hasMany(CheckIn::class);
    }

    /**
     * Scope: only users in the current user's branch and branches below (when current user has a branch).
     * Users with no branch (e.g. global admin) see all.
     */
    public function scopeVisibleTo($query, ?User $currentUser)
    {
        if (!$currentUser || !$currentUser->branch_id) {
            return $query;
        }
        $branchIds = Branch::selfAndDescendantIds($currentUser->branch_id);

        return $query->whereIn('branch_id', $branchIds);
    }

    /**
     * Scope: users who can be assigned tickets (have permission tickets.can-be-assigned), visible to current user.
     */
    public function scopeAssignableToTickets($query, ?User $currentUser)
    {
        return $query->visibleTo($currentUser)
            ->whereHas('roleModel', fn($q) => $q->whereHas('permissions', fn($p) => $p->where('slug', 'tickets.can-be-assigned')));
    }

    /** Whether the current user can see/touch this user (same user, or target in current user's branch tree). */
    public static function visibleToUser(?User $target, User $currentUser): bool
    {
        if ($target->id === $currentUser->id) {
            return true;
        }
        if (!$currentUser->branch_id) {
            return true;
        }
        $branchIds = Branch::selfAndDescendantIds($currentUser->branch_id);

        return $target->branch_id && in_array($target->branch_id, $branchIds, true);
    }

    public function isAdmin(): bool
    {
        // Check roleModel first (preferred): admin or super_admin have full access
        if ($this->roleModel && in_array($this->roleModel->slug, ['admin', 'super_admin'], true)) {
            return true;
        }

        // Fallback to role enum for backward compatibility
        return $this->role === 'admin';
    }

    public function isHeadBranchManager(): bool
    {
        return $this->roleModel && $this->roleModel->slug === 'head_branch_manager';
    }

    public function isRegionalBranchManager(): bool
    {
        return $this->roleModel && $this->roleModel->slug === 'regional_branch_manager';
    }

    public function isStaff(): bool
    {
        return $this->roleModel && $this->roleModel->slug === 'staff';
    }

    public function isCustomer(): bool
    {
        return $this->roleModel && $this->roleModel->slug === 'customer';
    }

    /**
     * Check if user has a specific permission
     * Admin users automatically have all permissions
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // Admin users have all permissions
        if ($this->isAdmin()) {
            return true;
        }

        if (!$this->roleModel) {
            return false;
        }

        return $this->roleModel->hasPermission($permissionSlug);
    }

    /**
     * Check if user has any of the given permissions
     * Admin users automatically have all permissions
     */
    public function hasAnyPermission(array $permissionSlugs): bool
    {
        // Admin users have all permissions
        if ($this->isAdmin()) {
            return true;
        }

        if (!$this->roleModel) {
            return false;
        }

        foreach ($permissionSlugs as $slug) {
            if ($this->roleModel->hasPermission($slug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get users to notify for stock-related activity in the given branch(es).
     * Returns all users in the branch who have stock-related permissions; if none, returns all users in the branch.
     *
     * @param  array<string>  $branchIds
     * @return \Illuminate\Support\Collection<int, User>
     */
    public static function usersForStockNotifications(array $branchIds): \Illuminate\Support\Collection
    {
        $branchIds = array_filter(array_unique($branchIds));
        if (empty($branchIds)) {
            return collect();
        }

        $permissionSlugs = ['stock-transfers.receive-notifications', 'stock-management.view', 'stock-transfers.view', 'stock-transfers.create'];

        $users = static::whereIn('branch_id', $branchIds)
            ->whereHas('roleModel', function ($q) use ($permissionSlugs) {
                $q->whereHas('permissions', function ($p) use ($permissionSlugs) {
                    $p->whereIn('slug', $permissionSlugs);
                });
            })
            ->get();

        if ($users->isEmpty()) {
            $users = static::whereIn('branch_id', $branchIds)->get();
        }

        return $users->unique('id')->filter(fn($u) => $u->email)->values();
    }

    /**
     * Get users in the given branch(es) who have stock management permissions (view, approve, or restock).
     * Used for low-stock alerts and other stock management notifications.
     *
     * @param  array<string>  $branchIds
     * @return \Illuminate\Support\Collection<int, User>
     */
    public static function usersWithStockManagementPermission(array $branchIds): \Illuminate\Support\Collection
    {
        $branchIds = array_filter(array_unique($branchIds));
        if (empty($branchIds)) {
            return collect();
        }

        $permissionSlugs = ['stock-management.view', 'stock-management.approve', 'stock-management.restock'];

        return static::whereIn('branch_id', $branchIds)
            ->whereHas('roleModel', function ($q) use ($permissionSlugs) {
                $q->whereHas('permissions', function ($p) use ($permissionSlugs) {
                    $p->whereIn('slug', $permissionSlugs);
                });
            })
            ->get()
            ->unique('id')
            ->filter(fn($u) => $u->email)
            ->values();
    }

    /**
     * Get users in the given branch(es) who have stock-requests.view (receive stock request notifications).
     *
     * @param  array<string>  $branchIds
     * @return \Illuminate\Support\Collection<int, User>
     */
    public static function usersWithStockRequestPermission(array $branchIds): \Illuminate\Support\Collection
    {
        $branchIds = array_filter(array_unique($branchIds));
        if (empty($branchIds)) {
            return collect();
        }

        return static::whereIn('branch_id', $branchIds)
            ->whereHas('roleModel', function ($q) {
                $q->whereHas('permissions', function ($p) {
                    $p->where('slug', 'stock-requests.view');
                });
            })
            ->get()
            ->unique('id')
            ->filter(fn($u) => $u->email)
            ->values();
    }

    /**
     * Get users in the given branch(es) who have agent-stock-requests.view (receive agent stock request notifications).
     *
     * @param  array<string>  $branchIds
     * @return \Illuminate\Support\Collection<int, User>
     */
    public static function usersWithAgentStockRequestPermission(array $branchIds): \Illuminate\Support\Collection
    {
        $branchIds = array_filter(array_unique($branchIds));
        if (empty($branchIds)) {
            return collect();
        }

        return static::whereIn('branch_id', $branchIds)
            ->whereHas('roleModel', function ($q) {
                $q->whereHas('permissions', function ($p) {
                    $p->where('slug', 'agent-stock-requests.view');
                });
            })
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * Get users in the given branch(es) who can view device requests (stock-requests.view or sales.view).
     * Used to notify host branch when a device is requested from them.
     *
     * @param  array<string>  $branchIds
     * @return \Illuminate\Support\Collection<int, User>
     */
    public static function usersWhoCanViewDeviceRequests(array $branchIds): \Illuminate\Support\Collection
    {
        $branchIds = array_filter(array_unique($branchIds));
        if (empty($branchIds)) {
            return collect();
        }

        return static::whereIn('branch_id', $branchIds)
            ->whereHas('roleModel', function ($q) {
                $q->whereHas('permissions', function ($p) {
                    $p->whereIn('slug', ['stock-requests.view', 'sales.view']);
                });
            })
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * Get users who have bills.approve (notify when a new bill is submitted for approval).
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public static function usersWithBillsApprovePermission(): \Illuminate\Support\Collection
    {
        return static::whereHas('roleModel', function ($q) {
            $q->whereHas('permissions', function ($p) {
                $p->where('slug', 'bills.approve');
            });
        })
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * Get users who have petty-cash.approve or petty-cash.custodian and can access the given branch.
     * Used to notify approvers and custodians when a new petty cash request is created.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public static function usersWithPettyCashApproveOrCustodianPermission(string $fundBranchId): \Illuminate\Support\Collection
    {
        $allowedIds = Branch::selfAndDescendantIds($fundBranchId);
        if ($allowedIds === []) {
            $allowedIds = [$fundBranchId];
        }

        return static::whereHas('roleModel', function ($q) {
            $q->whereHas('permissions', function ($p) {
                $p->whereIn('slug', ['petty-cash.approve', 'petty-cash.custodian']);
            });
        })
            ->where(function ($q) use ($allowedIds) {
                $q->whereNull('branch_id')
                    ->orWhereIn('branch_id', $allowedIds);
            })
            ->get()
            ->unique('id')
            ->values();
    }
}
