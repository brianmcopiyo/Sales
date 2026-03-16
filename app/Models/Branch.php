<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class Branch extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'email',
        'head_branch_id',
        'region_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function headBranch()
    {
        return $this->belongsTo(Branch::class, 'head_branch_id');
    }

    public function regionalBranches()
    {
        return $this->hasMany(Branch::class, 'head_branch_id');
    }

    /**
     * Branch IDs that are this branch plus all descendants (children, grandchildren, etc.).
     * Used to scope "this branch and branches below" for visibility.
     * Uses a single query and in-memory traversal so deep or circular trees cannot cause timeouts.
     */
    public static function selfAndDescendantIds(?string $branchId): array
    {
        if ($branchId === null) {
            return [];
        }
        $cacheKey = 'branch_self_and_descendant_pairs';
        $pairs = app()->bound($cacheKey) ? app($cacheKey) : null;
        if ($pairs === null) {
            $pairs = static::select('id', 'head_branch_id')->get();
            app()->instance($cacheKey, $pairs);
        }
        $childrenByParent = $pairs->groupBy('head_branch_id');
        $ids = [];
        $stack = [$branchId];
        $seen = [];
        while ($stack !== []) {
            $id = array_pop($stack);
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $ids[] = $id;
            $children = $childrenByParent->get($id, collect());
            foreach ($children as $row) {
                $cid = $row->id;
                if (!isset($seen[$cid])) {
                    $stack[] = $cid;
                }
            }
        }
        return array_values($ids);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function stockTransfersFrom()
    {
        return $this->hasMany(StockTransfer::class, 'from_branch_id');
    }

    public function stockTransfersTo()
    {
        return $this->hasMany(StockTransfer::class, 'to_branch_id');
    }

    public function stockRequestsRequesting()
    {
        return $this->hasMany(StockRequest::class, 'requesting_branch_id');
    }

    public function stockRequestsRequestedFrom()
    {
        return $this->hasMany(StockRequest::class, 'requested_from_branch_id');
    }

    public function branchStocks()
    {
        return $this->hasMany(BranchStock::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }


    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}
