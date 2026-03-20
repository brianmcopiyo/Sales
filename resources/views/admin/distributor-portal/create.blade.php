@extends('layouts.app')

@section('title', 'Create Distributor Portal Account')

@section('content')
<div class="py-6 max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.distributor-portal.index') }}" class="text-sm hover:underline" style="color:#6b7280;">&larr; Back</a>
    </div>
    <h1 class="text-xl font-bold mb-6" style="color:#111827;">Create Distributor Portal Account</h1>

    <form method="POST" action="{{ route('admin.distributor-portal.store') }}" class="space-y-5">
        @csrf

        <div class="rounded-xl border shadow-sm p-5" style="background:#fff; border-color:#e5e7eb;">
            <h2 class="text-sm font-semibold mb-4" style="color:#374151;">Link to Customer</h2>
            <div>
                <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Customer <span class="text-red-500">*</span></label>
                <select name="customer_id" required class="w-full text-sm border rounded-lg px-3 py-2.5 focus:outline-none @error('customer_id') border-red-400 @enderror" style="border-color:#e5e7eb;">
                    <option value="">Select customer...</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}" {{ old('customer_id') === $c->id ? 'selected' : '' }}>
                            {{ $c->name }} @if($c->phone) ({{ $c->phone }}) @endif
                        </option>
                    @endforeach
                </select>
                @error('customer_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="rounded-xl border shadow-sm p-5" style="background:#fff; border-color:#e5e7eb;">
            <h2 class="text-sm font-semibold mb-4" style="color:#374151;">Portal Login Account</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="user_name" value="{{ old('user_name') }}" required
                           class="w-full text-sm border rounded-lg px-3 py-2.5 @error('user_name') border-red-400 @enderror" style="border-color:#e5e7eb;">
                    @error('user_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Email</label>
                    <input type="email" name="user_email" value="{{ old('user_email') }}"
                           class="w-full text-sm border rounded-lg px-3 py-2.5 @error('user_email') border-red-400 @enderror" style="border-color:#e5e7eb;">
                    @error('user_email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Phone</label>
                    <input type="text" name="user_phone" value="{{ old('user_phone') }}"
                           class="w-full text-sm border rounded-lg px-3 py-2.5 @error('user_phone') border-red-400 @enderror" style="border-color:#e5e7eb;">
                    @error('user_phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Password <span class="text-red-500">*</span></label>
                    <input type="text" name="user_password" value="{{ old('user_password') }}" required
                           class="w-full text-sm border rounded-lg px-3 py-2.5 @error('user_password') border-red-400 @enderror" style="border-color:#e5e7eb;"
                           placeholder="Will be sent via email/SMS">
                    @error('user_password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="rounded-xl border shadow-sm p-5" style="background:#fff; border-color:#e5e7eb;">
            <h2 class="text-sm font-semibold mb-4" style="color:#374151;">Portal Settings</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Assigned Branch</label>
                    <select name="assigned_branch_id" class="w-full text-sm border rounded-lg px-3 py-2.5" style="border-color:#e5e7eb;">
                        <option value="">None</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" {{ old('assigned_branch_id') === $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Credit Limit</label>
                    <input type="number" name="credit_limit" value="{{ old('credit_limit') }}" step="0.01" min="0"
                           class="w-full text-sm border rounded-lg px-3 py-2.5" style="border-color:#e5e7eb;">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Notes</label>
                    <textarea name="notes" rows="3" class="w-full text-sm border rounded-lg px-3 py-2.5 resize-none" style="border-color:#e5e7eb;">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="text-sm px-5 py-2.5 rounded-lg font-medium text-white" style="background-color:#006F78;">
                Create Account
            </button>
            <a href="{{ route('admin.distributor-portal.index') }}" class="text-sm px-5 py-2.5 rounded-lg font-medium border" style="border-color:#e5e7eb; color:#374151;">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
