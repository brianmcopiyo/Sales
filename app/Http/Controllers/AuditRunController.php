<?php

namespace App\Http\Controllers;

use App\Models\AuditRun;
use App\Models\AuditTemplate;
use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuditRunController extends Controller
{
    public function create(Request $request)
    {
        $checkInId = $request->get('check_in_id');
        if (!$checkInId) {
            return redirect()->route('check-ins.index')->with('error', 'Select a check-in to start an audit.');
        }
        $checkIn = CheckIn::with('outlet')->findOrFail($checkInId);
        if (AuditRun::where('check_in_id', $checkIn->id)->exists()) {
            return redirect()->route('check-ins.index')->with('error', 'This visit already has an audit.');
        }
        $templates = AuditTemplate::where('is_active', true)->orderBy('name')->get();
        if ($templates->isEmpty()) {
            return redirect()->route('check-ins.index')->with('error', 'No active audit templates. Create one first.');
        }
        return view('audit-runs.create', compact('checkIn', 'templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'check_in_id' => 'required|exists:check_ins,id',
            'audit_template_id' => 'required|exists:audit_templates,id',
        ]);
        if (AuditRun::where('check_in_id', $validated['check_in_id'])->exists()) {
            return redirect()->route('check-ins.index')->with('error', 'This visit already has an audit.');
        }
        $run = AuditRun::create($validated);
        return redirect()->route('audit-runs.fill', $run)->with('success', 'Audit started. Fill in the checklist.');
    }

    public function fill(AuditRun $auditRun)
    {
        $auditRun->load(['template.sections.questions', 'checkIn.outlet']);
        if ($auditRun->completed_at) {
            return redirect()->route('audit-runs.show', $auditRun)->with('info', 'This audit is already completed.');
        }
        return view('audit-runs.fill', compact('auditRun'));
    }

    public function submit(Request $request, AuditRun $auditRun)
    {
        if ($auditRun->completed_at) {
            return redirect()->route('audit-runs.show', $auditRun)->with('error', 'Audit already completed.');
        }
        $auditRun->load(['template.sections.questions']);
        $rules = [];
        foreach ($auditRun->template->sections as $section) {
            foreach ($section->questions as $q) {
                $key = "answers.{$q->id}";
                if ($q->question_type === 'yes_no') {
                    $rules[$key] = 'required|in:yes,no';
                } elseif ($q->question_type === 'score') {
                    $rules[$key] = 'required|integer|min:0|max:' . ($q->score_max ?? 5);
                } elseif ($q->question_type === 'photo') {
                    $rules["answers.{$q->id}"] = 'nullable';
                    $rules["photo.{$q->id}"] = 'nullable|image|max:5120';
                }
            }
        }
        $validated = $request->validate($rules);

        $answers = $request->input('answers', []);
        $photos = $request->file('photo', []);

        foreach ($auditRun->template->sections as $section) {
            foreach ($section->questions as $q) {
                $answerValue = $answers[$q->id] ?? null;
                $photoPath = null;
                if ($q->question_type === 'photo' && !empty($photos[$q->id])) {
                    $photoPath = $photos[$q->id]->store('audit-run-photos', 'public');
                }
                $auditRun->answers()->updateOrCreate(
                    ['audit_question_id' => $q->id],
                    ['answer_value' => $answerValue, 'photo_path' => $photoPath]
                );
            }
        }

        $auditRun->load('answers.question');
        $auditRun->compliance_score = $auditRun->computeComplianceScore();
        $auditRun->completed_at = now();
        $auditRun->save();

        return redirect()->route('audit-runs.show', $auditRun)->with('success', 'Audit submitted. Compliance score: ' . ($auditRun->compliance_score ?? 'N/A') . '%');
    }

    public function show(AuditRun $auditRun)
    {
        $auditRun->load(['template.sections.questions', 'checkIn.outlet', 'checkIn.user', 'answers.question']);
        return view('audit-runs.show', compact('auditRun'));
    }
}
