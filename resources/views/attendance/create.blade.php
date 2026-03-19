@extends('layouts.app')

@section('title', 'Log Attendance')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <h1 class="text-3xl font-semibold text-primary tracking-tight">Log Attendance</h1>

    <form method="POST" action="{{ route('attendance.store') }}" class="bg-themeCard border border-themeBorder rounded-2xl p-6 space-y-4 shadow-sm">
        @csrf
        <div>
            <label class="block text-sm font-medium text-themeBody mb-2">Date</label>
            <input type="date" name="attendance_date" value="{{ old('attendance_date', now()->toDateString()) }}" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading" required>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-themeBody mb-2">Clock In</label>
                <input type="datetime-local" name="clock_in_at" value="{{ old('clock_in_at') }}" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading">
            </div>
            <div>
                <label class="block text-sm font-medium text-themeBody mb-2">Clock Out</label>
                <input type="datetime-local" name="clock_out_at" value="{{ old('clock_out_at') }}" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-themeBody mb-2">Status</label>
            <select name="status" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading">
                <option value="present">Present</option>
                <option value="half_day">Half day</option>
                <option value="leave">Leave</option>
                <option value="absent">Absent</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-themeBody mb-2">Notes</label>
            <textarea name="notes" rows="3" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading">{{ old('notes') }}</textarea>
        </div>
        <div class="flex justify-end gap-2">
            <a href="{{ route('attendance.index') }}" class="px-4 py-2 rounded-xl border border-themeBorder text-themeBody">Cancel</a>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary text-white hover:bg-primary-dark transition">Save</button>
        </div>
    </form>
</div>
@endsection
