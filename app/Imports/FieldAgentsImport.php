<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\FieldAgent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FieldAgentsImport implements ToCollection, WithHeadingRow
{
    protected int $imported = 0;

    protected array $errors = [];

    /** Default branch when row has no branch. */
    protected ?string $defaultBranchId = null;

    /** Allowed branch IDs (null = any branch). */
    protected ?array $allowedBranchIds = null;

    public function __construct(?string $defaultBranchId, ?array $allowedBranchIds = null)
    {
        $this->defaultBranchId = $defaultBranchId;
        $this->allowedBranchIds = $allowedBranchIds;
    }

    public function collection(Collection $rows): void
    {
        $staffRole = Role::where('slug', 'staff')->first();
        if (!$staffRole) {
            $this->errors[] = 'Staff role not found. Please ensure the staff role exists.';
            return;
        }

        foreach ($rows as $index => $row) {
            $rowNum = (int) $index + 2;
            $email = $this->normalize($row, 'email');
            $phone = $this->normalize($row, 'phone');
            $email = trim((string) $email) === '' ? null : trim($email);
            $phone = trim((string) $phone) === '' ? null : trim($phone);

            if (!$email && !$phone) {
                $this->errors[] = "Row {$rowNum}: Either email or phone is required.";
                continue;
            }

            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->errors[] = "Row {$rowNum}: Invalid email \"{$email}\".";
                continue;
            }

            if ($email && User::where('email', $email)->exists()) {
                $this->errors[] = "Row {$rowNum}: Email {$email} already exists.";
                continue;
            }

            $name = $this->normalize($row, 'name');
            if (!$name) {
                $this->errors[] = "Row {$rowNum}: Name is required.";
                continue;
            }

            $branchId = $this->resolveBranch($row, $rowNum);
            if ($branchId === false) {
                continue;
            }

            $isActive = $this->resolveIsActive($row, $rowNum);
            if ($isActive === null) {
                continue;
            }

            $password = Str::random(12);

            try {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'role_id' => $staffRole->id,
                    'role' => 'staff',
                    'branch_id' => $branchId,
                    'phone' => $phone,
                ]);

                FieldAgent::create([
                    'user_id' => $user->id,
                    'is_active' => $isActive,
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
    protected function resolveBranch(Collection $row, int $rowNum)
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

    protected function resolveIsActive(Collection $row, int $rowNum): ?bool
    {
        $val = $this->normalize($row, 'is_active');
        if ($val === null || $val === '') {
            return true;
        }
        $v = strtolower($val);
        if (in_array($v, ['1', 'true', 'yes', 'active'], true)) {
            return true;
        }
        if (in_array($v, ['0', 'false', 'no', 'inactive'], true)) {
            return false;
        }
        $this->errors[] = "Row {$rowNum}: is_active must be 1/0, true/false, yes/no, or active/inactive.";
        return null;
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
