<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class ProductsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(protected Request $request) {}

    public function query()
    {
        $regionId = auth()->user()?->branch?->region_id;
        $query = Product::with([
            'brand',
            'regionPrices' => fn($q) => $regionId ? $q->where('region_id', $regionId) : $q->whereRaw('1=0'),
        ]);

        if ($this->request->filled('search')) {
            $term = $this->request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('model', 'like', "%{$term}%");
            });
        }
        if ($this->request->filled('brand_id')) {
            $query->where('brand_id', $this->request->get('brand_id'));
        }
        if ($this->request->filled('status')) {
            if ($this->request->get('status') === 'active') {
                $query->where('is_active', true);
            }
            if ($this->request->get('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return ['Name', 'SKU', 'Brand', 'Model', 'Selling Price (Region)', 'Status', 'Created'];
    }

    public function map($product): array
    {
        $regionPrice = $product->regionPrices->first();
        $price = $regionPrice ? number_format((float) $regionPrice->unit_price, 2) : '—';

        return [
            $product->name ?? '',
            $product->sku ?? '',
            $product->brand?->name ?? '',
            $product->model ?? '',
            $price,
            $product->is_active ? 'Active' : 'Inactive',
            $product->created_at?->format('M d, Y') ?? '',
        ];
    }
}
