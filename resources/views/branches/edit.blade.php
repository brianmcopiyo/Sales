@extends('layouts.app')

@section('title', 'Edit Branch')

@section('content')
    <div class="w-full">
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Edit Branch</h1>
            <a href="{{ route('branches.index') }}"
                class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                <span>Back</span>
            </a>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="POST" action="{{ route('branches.update', $branch) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-themeBody font-light mb-2">Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $branch->name) }}"
                            required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    </div>

                    <div>
                        <label class="block text-themeBody font-light mb-2">Code</label>
                        <div class="w-full px-4 py-2 border border-themeBorder rounded bg-themeHover text-themeBody font-light">
                            {{ $branch->code }}
                        </div>
                        <p class="mt-1 text-xs text-themeMuted font-light">Branch code is auto-generated and cannot be changed
                        </p>
                    </div>

                    <div>
                        <label for="region_id" class="block text-themeBody font-light mb-2">Region *</label>
                        <select id="region_id" name="region_id" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">Select a region</option>
                            @foreach ($regions as $region)
                                <option value="{{ $region->id }}"
                                    {{ old('region_id', $branch->region_id) == $region->id ? 'selected' : '' }}>
                                    {{ $region->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="head_branch_id" class="block text-sm font-medium text-themeBody mb-2">Head Branch</label>
                        <select id="head_branch_id" name="head_branch_id" disabled
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl bg-themeInput cursor-not-allowed text-themeMuted font-medium focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            @php
                                $currentHeadBranch = $branch->headBranch;
                            @endphp
                            @if ($currentHeadBranch)
                                <option value="{{ $currentHeadBranch->id }}" selected>{{ $currentHeadBranch->name }}
                                </option>
                            @elseif(isset($userBranchId) && $userBranchId)
                                @php
                                    $userBranch = \App\Models\Branch::find($userBranchId);
                                @endphp
                                @if ($userBranch)
                                    <option value="{{ $userBranch->id }}" selected>{{ $userBranch->name }}</option>
                                @else
                                    <option value="">No branch assigned</option>
                                @endif
                            @else
                                <option value="">No branch assigned</option>
                            @endif
                        </select>
                        @if ($branch->head_branch_id)
                            <input type="hidden" name="head_branch_id" value="{{ $branch->head_branch_id }}">
                        @elseif(isset($userBranchId) && $userBranchId)
                            <input type="hidden" name="head_branch_id" value="{{ $userBranchId }}">
                        @endif
                        <p class="mt-1 text-xs font-medium text-themeMuted">This branch belongs to the displayed head branch
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-themeBody mb-1">Address</label>
                        <textarea id="address" name="address" rows="2" placeholder="Optional"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition">{{ old('address', $branch->address) }}</textarea>
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-themeBody mb-1">Phone</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $branch->phone) }}"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-themeBody mb-1">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $branch->email) }}"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition">
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                            {{ old('is_active', $branch->is_active) ? 'checked' : '' }}
                            class="rounded border-themeBorder text-primary focus:ring-primary/20">
                        <label for="is_active" class="ml-2 text-sm font-medium text-themeBody">Active</label>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit"
                        class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Update Branch</span>
                    </button>
                    <a href="{{ route('branches.index') }}"
                        class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                        <span>Cancel</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

@endsection

