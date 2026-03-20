@extends('layouts.app')

@section('title', 'Edit Distributor Profile')

@section('content')
<div class="py-6 max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.distributor-portal.show', $profile) }}" class="text-sm hover:underline" style="color:#6b7280;">&larr; Back</a>
    </div>
    <h1 class="text-xl font-bold mb-6" style="color:#111827;">Edit: {{ $profile->customer?->name }}</h1>

    <form method="POST" action="{{ route('admin.distributor-portal.update', $profile) }}" class="space-y-5">
        @csrf @method('PUT')

        <div class="rounded-xl border shadow-sm p-5" style="background:#fff; border-color:#e5e7eb;">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Assigned Branch</label>
                    <select name="assigned_branch_id" class="w-full text-sm border rounded-lg px-3 py-2.5" style="border-color:#e5e7eb;">
                        <option value="">None</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" {{ $profile->assigned_branch_id === $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Credit Limit</label>
                    <input type="number" name="credit_limit" value="{{ old('credit_limit', $profile->credit_limit) }}" step="0.01" min="0"
                           class="w-full text-sm border rounded-lg px-3 py-2.5" style="border-color:#e5e7eb;">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Outstanding Balance</label>
                    <input type="number" name="outstanding_balance" value="{{ old('outstanding_balance', $profile->outstanding_balance) }}" step="0.01" min="0"
                           class="w-full text-sm border rounded-lg px-3 py-2.5" style="border-color:#e5e7eb;">
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active" {{ $profile->is_active ? 'checked' : '' }}
                           class="rounded">
                    <label for="is_active" class="text-sm font-medium" style="color:#374151;">Portal Active</label>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Notes</label>
                    <textarea name="notes" rows="3" class="w-full text-sm border rounded-lg px-3 py-2.5 resize-none" style="border-color:#e5e7eb;">{{ old('notes', $profile->notes) }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="text-sm px-5 py-2.5 rounded-lg font-medium text-white" style="background-color:#006F78;">Save Changes</button>
            <a href="{{ route('admin.distributor-portal.show', $profile) }}" class="text-sm px-5 py-2.5 rounded-lg font-medium border" style="border-color:#e5e7eb; color:#374151;">Cancel</a>
        </div>
    </form>
</div>
@endsection
