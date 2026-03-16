@extends('layouts.app')

@section('title', 'Edit Customer')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Edit Customer</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $customer->name }}</p>
            </div>
            <a href="{{ route('customers.index') }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                <span>Back</span>
            </a>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-themeBody mb-2">Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $customer->name) }}"
                            required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-themeBody mb-2">Email (optional)</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $customer->email) }}"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-themeBody mb-2">Phone</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    </div>

                    <div>
                        <label for="id_number" class="block text-sm font-medium text-themeBody mb-2">ID Number</label>
                        <input type="text" id="id_number" name="id_number"
                            value="{{ old('id_number', $customer->id_number) }}"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    </div>

                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-themeBody mb-2">Address</label>
                        <textarea id="address" name="address" rows="3"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('address', $customer->address) }}</textarea>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                            {{ old('is_active', $customer->is_active) ? 'checked' : '' }}
                            class="rounded border-themeBorder text-primary focus:ring-primary/20">
                        <label for="is_active" class="ml-2 text-sm font-medium text-themeBody">Active</label>
                    </div>
                </div>

                <div class="flex space-x-3">
                    <button type="submit"
                        class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Update Customer</span>
                    </button>
                    <a href="{{ route('customers.index') }}"
                        class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
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

