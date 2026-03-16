<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToCollection, WithHeadingRow
{
    protected int $imported = 0;

    protected array $errors = [];

    /** Default branch when row has no branch. */
    protected ?string $defaultBranchId = null;

    /** Allowed branch IDs (null = any branch). */
    protected ?array $allowedBranchIds = null;

    /** Whether the current user can assign admin role. */
    protected bool $canAssignAdmin = false;

    public function __construct(?string $defaultBranchId, ?array $allowedBranchIds, bool $canAssignAdmin = false)
    {
        $this->defaultBranchId = $defaultBranchId;
        $this->allowedBranchIds = $allowedBranchIds;
        $this->canAssignAdmin = $canAssignAdmin;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = (int) $index + 2;
            $email = $this->normalize($row, 'email');
            if (trim((string) $email) === '') {
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->errors[] = "Row {$rowNum}: Invalid email \"{$email}\".";
                continue;
            }

            if (User::where('email', $email)->exists()) {
                $this->errors[] = "Row {$rowNum}: Email {$email} already exists.";
                continue;
            }

            $name = $this->normalize($row, 'name');
            if (!$name) {
                $this->errors[] = "Row {$rowNum}: Name is required.";
                continue;
            }

            $role = $this->resolveRole($row, $rowNum);
            if ($role === null) {
                continue;
            }

            if ($role->slug === 'admin' && !$this->canAssignAdmin) {
                $this->errors[] = "Row {$rowNum}: You do not have permission to assign the admin role.";
                continue;
            }

            $branchId = $this->resolveBranch($row, $rowNum);
            if ($branchId === false) {
                continue;
            }

            $phone = $this->normalize($row, 'phone');
            $password = $this->generateRandomPassword();

            try {
                User::$plainPasswordForNewUser = $password;
                User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'role_id' => $role->id,
                    'role' => $role->slug,
                    'branch_id' => $branchId,
                    'phone' => $phone ?: null,
                ]);
                $this->imported++;
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$rowNum}: " . $e->getMessage();
            }
        }
    }

    /**
     * Resolve branch from row. Returns branch id, or false on error (message added to errors).
     */
    protected function resolveBranch(Collection $row, int $rowNum): ?string
    {
        $branchRef = $this->normalize($row, 'branch');
        if ($branchRef === null || $branchRef === '') {
            return $this->defaultBranchId;
        }

        $query = Branch::where('is_active', true)
            ->where(function ($q) use ($branchRef) {
                $q->where('code', $branchRef)
                    ->orWhere('name', 'like', "%{$branchRef}%");
            });
        if ($this->allowedBranchIds !== null && count($this->allowedBranchIds) > 0) {
            $query->whereIn('id', $this->allowedBranchIds);
        }
        $branch = $query->first();

        if (!$branch) {
            $this->errors[] = "Row {$rowNum}: Branch \"{$branchRef}\" not found or not allowed.";
            return false;
        }
        return $branch->id;
    }

    protected function normalize(Collection $row, string $key): ?string
    {
        $value = $row->get(str_replace(' ', '_', strtolower($key)));
        if ($value === null) {
            $value = $row->get($key);
        }
        return $value !== null ? trim((string) $value) : null;
    }

    protected function resolveRole(Collection $row, int $rowNum): ?Role
    {
        $roleRef = $this->normalize($row, 'role');
        if ($roleRef === null || $roleRef === '') {
            $role = Role::where('is_active', true)->where('slug', 'staff')->first();
            if ($role) {
                return $role;
            }
            $this->errors[] = "Row {$rowNum}: Role is required (or default staff role must exist).";
            return null;
        }

        $slug = strtolower(preg_replace('/\s+/', '_', $roleRef));
        $role = Role::where('is_active', true)
            ->where(function ($q) use ($roleRef, $slug) {
                $q->where('slug', $slug)
                    ->orWhere('name', 'like', $roleRef);
            })
            ->first();

        if (!$role) {
            $this->errors[] = "Row {$rowNum}: Unknown role \"{$roleRef}\". Use role slug or name (e.g. staff, admin, head_branch_manager).";
            return null;
        }

        if ($role->slug === 'customer') {
            $this->errors[] = "Row {$rowNum}: Cannot import users with customer role via this import.";
            return null;
        }

        return $role;
    }

    protected function generateRandomPassword(int $length = 12): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $max = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $max)];
        }
        return $password;
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
