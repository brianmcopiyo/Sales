<?php

namespace App\Imports;

use App\Models\Device;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Brand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DevicesImport implements ToCollection, WithHeadingRow
{
    protected int $imported = 0;

    protected array $errors = [];

    /** IMEIs that already existed: [ ['imei' => x, 'branch' => name], ... ] */
    protected array $alreadyExisted = [];

    /** IMEIs that were added: [ ['imei' => x, 'branch_id' => y], ... ] */
    protected array $added = [];

    /** Default product when row has no product_sku/product_name. */
    protected ?string $defaultProductId = null;

    /** Default branch when row has no branch. */
    protected ?string $defaultBranchId = null;

    public function __construct(
        ?string $branchId,
        ?string $defaultProductId = null
    ) {
        $this->defaultBranchId = $branchId;
        $this->defaultProductId = $defaultProductId;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = (int) $index + 2;
            $imei = $this->normalize($row, 'imei');
            if (trim((string) $imei) === '') {
                continue;
            }

            $imei = preg_replace('/\D/', '', (string) $imei);
            if (strlen($imei) !== 15) {
                $this->errors[] = "Row {$rowNum}: IMEI must be exactly 15 digits (got " . strlen($imei) . ").";
                continue;
            }
            $existingDevice = Device::where('imei', $imei)->with('branch')->first();
            if ($existingDevice) {
                $branchName = $existingDevice->branch ? $existingDevice->branch->name : 'Unknown';
                $this->alreadyExisted[] = ['imei' => $imei, 'branch' => $branchName];
                $this->errors[] = "Row {$rowNum}: IMEI {$imei} already exists.";
                continue;
            }

            $product = $this->resolveProduct($row, $rowNum);
            if ($product === null) {
                continue;
            }

            $branchId = $this->resolveBranch($row, $rowNum);
            if ($branchId === null) {
                continue;
            }

            $status = $this->resolveStatus($row, $rowNum);
            if ($status === null) {
                continue;
            }

            $customerId = $this->resolveCustomer($row, $rowNum);
            if ($customerId === false) {
                continue;
            }

            // A device cannot be attached to a customer and still available: enforce consistent state.
            // Do not override 'sold' — only reconcile available/assigned vs customer.
            if ($status !== 'sold') {
                if ($status === 'available') {
                    $customerId = null;
                } elseif ($customerId !== null) {
                    $status = 'assigned';
                }
            }

            $notes = $this->normalize($row, 'notes');

            try {
                Device::create([
                    'imei' => $imei,
                    'product_id' => $product->id,
                    'branch_id' => $branchId,
                    'customer_id' => $customerId,
                    'status' => $status,
                    'notes' => trim((string) $notes) ?: null,
                ]);
                $this->imported++;
                $this->added[] = ['imei' => $imei, 'branch_id' => $branchId];
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$rowNum}: " . $e->getMessage();
            }
        }
    }

    protected function normalize(Collection $row, string $key): ?string
    {
        $value = $row->get(str_replace(' ', '_', strtolower($key)));
        if ($value === null) {
            $value = $row->get($key);
        }
        return $value !== null ? trim((string) $value) : null;
    }

    protected function resolveProduct(Collection $row, int $rowNum): ?Product
    {
        $productSku = $this->normalize($row, 'product_sku');
        $productName = $this->normalize($row, 'product_name');
        $productRef = $productSku ?: $productName;
        $brandRef = $this->normalize($row, 'brand');

        if ($productRef !== null && $productRef !== '') {
            $query = Product::where('is_active', true)
                ->where(function ($q) use ($productRef) {
                    $q->where('sku', $productRef)->orWhere('name', $productRef);
                });
            if ($brandRef !== null && $brandRef !== '') {
                $query->whereHas('brand', function ($q) use ($brandRef) {
                    $q->where('name', 'like', $brandRef)->orWhere('id', $brandRef);
                });
            }
            $product = $query->first();
            if (!$product) {
                $product = $this->createProductIfMissing($productSku, $productName, $brandRef, $rowNum);
                if (!$product) {
                    return null;
                }
            }
            return $product;
        }

        if ($this->defaultProductId !== null) {
            $product = Product::where('is_active', true)->find($this->defaultProductId);
            if ($product) {
                return $product;
            }
        }

        $this->errors[] = "Row {$rowNum}: Product (product_sku or product_name) is required, or set a default product.";
        return null;
    }

    protected function createProductIfMissing(?string $productSku, ?string $productName, ?string $brandRef, int $rowNum): ?Product
    {
        $name = $productName ?: $productSku ?: 'Unknown';
        $brandId = $this->resolveOrCreateBrandId($brandRef);

        $sku = $productSku ?: Str::slug($name);
        if (strlen($sku) > 50) {
            $sku = Str::limit($sku, 47, '') . substr(uniqid(), -3);
        }
        $existing = Product::where('sku', $sku)->first();
        if ($existing) {
            $sku = $sku . '-' . substr(uniqid(), -4);
        }

        try {
            return Product::create([
                'name' => $name,
                'sku' => $sku,
                'brand_id' => $brandId,
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            $this->errors[] = "Row {$rowNum}: Could not create product \"{$name}\": " . $e->getMessage();
            return null;
        }
    }

    /** Resolve brand by ref or create; if no ref, return default "Unbranded" brand id (products.brand_id is required). */
    protected function resolveOrCreateBrandId(?string $brandRef): string
    {
        if ($brandRef !== null && $brandRef !== '') {
            $brand = Brand::where('name', 'like', $brandRef)->orWhere('id', $brandRef)->first();
            if (!$brand) {
                $brand = Brand::create(['name' => $brandRef, 'is_active' => true]);
            }
            return $brand->id;
        }
        $default = Brand::firstOrCreate(
            ['name' => 'Unbranded'],
            ['is_active' => true]
        );
        return $default->id;
    }

    protected function resolveBranch(Collection $row, int $rowNum): ?string
    {
        $branchRef = $this->normalize($row, 'branch');
        if ($branchRef !== null && $branchRef !== '') {
            $branch = Branch::where('is_active', true)
                ->where(function ($q) use ($branchRef) {
                    $q->where('code', $branchRef)->orWhere('name', 'like', "%{$branchRef}%");
                })
                ->first();
            if (!$branch) {
                $branch = $this->createBranchIfMissing($branchRef, $rowNum);
                if (!$branch) {
                    return null;
                }
            }
            return $branch->id;
        }
        return $this->defaultBranchId;
    }

    protected function createBranchIfMissing(string $branchRef, int $rowNum): ?Branch
    {
        $code = Str::upper(Str::limit(preg_replace('/[^a-zA-Z0-9]/', '', $branchRef), 20, ''));
        if ($code === '') {
            $code = 'BR-' . substr(uniqid(), -6);
        }
        if (Branch::where('code', $code)->exists()) {
            $code = $code . '-' . substr(uniqid(), -4);
        }
        try {
            return Branch::create([
                'name' => $branchRef,
                'code' => $code,
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            $this->errors[] = "Row {$rowNum}: Could not create branch \"{$branchRef}\": " . $e->getMessage();
            return null;
        }
    }

    protected function resolveStatus(Collection $row, int $rowNum): ?string
    {
        $status = $this->normalize($row, 'status');
        if ($status === null || $status === '') {
            return 'available';
        }
        // Normalize to lowercase so Sold, SOLD, sold etc. all work
        $status = strtolower(trim((string) $status));
        if (in_array($status, ['available', 'assigned', 'sold'], true)) {
            return $status;
        }
        $this->errors[] = "Row {$rowNum}: Invalid status \"{$status}\". Use available, assigned, or sold.";
        return null;
    }

    /** @return string|null|false — null = no customer, false = error (message already added) */
    protected function resolveCustomer(Collection $row, int $rowNum): ?string
    {
        $customerRef = $this->normalize($row, 'customer');
        if ($customerRef === null || $customerRef === '') {
            return null;
        }
        $customer = Customer::where('is_active', true)
            ->where(function ($q) use ($customerRef) {
                $q->where('name', 'like', "%{$customerRef}%")
                    ->orWhere('email', 'like', "%{$customerRef}%")
                    ->orWhere('phone', 'like', "%{$customerRef}%");
            })
            ->first();
        if (!$customer) {
            $customer = $this->createCustomerIfMissing($customerRef, $rowNum);
            if (!$customer) {
                return false;
            }
        }
        return $customer->id;
    }

    protected function createCustomerIfMissing(string $customerRef, int $rowNum): ?Customer
    {
        try {
            return Customer::create([
                'name' => $customerRef,
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            $this->errors[] = "Row {$rowNum}: Could not create customer \"{$customerRef}\": " . $e->getMessage();
            return null;
        }
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /** @return array<int, array{imei: string, branch: string}> */
    public function getAlreadyExisted(): array
    {
        return $this->alreadyExisted;
    }

    /** @return array<int, array{imei: string, branch_id: string}> */
    public function getAdded(): array
    {
        return $this->added;
    }
}
