<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditRun;
use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuditRunApiController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'check_in_id' => 'required|exists:check_ins,id',
            'audit_template_id' => 'required|exists:audit_templates,id',
        ]);

        $checkIn = CheckIn::query()
            ->where('id', $validated['check_in_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $existing = AuditRun::query()->where('check_in_id', $checkIn->id)->first();
        if ($existing) {
            return response()->json([
                'message' => 'This visit already has an audit.',
                'audit_run_id' => $existing->id,
            ], 422);
        }

        $run = AuditRun::create($validated);

        return response()->json([
            'message' => 'Audit run started.',
            'audit_run' => [
                'id' => $run->id,
                'check_in_id' => $run->check_in_id,
                'audit_template_id' => $run->audit_template_id,
                'completed_at' => null,
                'compliance_score' => null,
            ],
        ], 201);
    }

    public function submit(Request $request, AuditRun $auditRun)
    {
        if ($auditRun->completed_at) {
            return response()->json(['message' => 'Audit already completed.'], 422);
        }

        $auditRun->load(['checkIn', 'template.sections.questions', 'answers']);
        if ($auditRun->checkIn?->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not allowed for this audit run.'], 403);
        }

        $rules = [];
        foreach ($auditRun->template->sections as $section) {
            foreach ($section->questions as $q) {
                $key = "answers.{$q->id}";
                if ($q->question_type === 'yes_no') {
                    $rules[$key] = 'required|in:yes,no';
                } elseif ($q->question_type === 'score') {
                    $rules[$key] = 'required|integer|min:0|max:' . ($q->score_max ?? 5);
                } elseif ($q->question_type === 'photo') {
                    $rules[$key] = 'nullable';
                    $rules["photo.{$q->id}"] = 'nullable|string';
                }
            }
        }
        $request->validate($rules);

        $answers = $request->input('answers', []);
        $photos = $request->input('photo', []);

        foreach ($auditRun->template->sections as $section) {
            foreach ($section->questions as $q) {
                $answerValue = $answers[$q->id] ?? null;
                $photoPath = null;
                if ($q->question_type === 'photo' && !empty($photos[$q->id])) {
                    $decoded = base64_decode((string) $photos[$q->id], true);
                    if ($decoded !== false && strlen($decoded) < 6 * 1024 * 1024) {
                        $filename = 'audit-run-photos/' . uniqid('api_', true) . '.jpg';
                        Storage::disk('public')->put($filename, $decoded);
                        $photoPath = $filename;
                    }
                }

                $auditRun->answers()->updateOrCreate(
                    ['audit_question_id' => $q->id],
                    ['answer_value' => $answerValue, 'photo_path' => $photoPath]
                );
            }
        }

        $auditRun->load(['answers.question', 'template.sections.questions']);
        $auditRun->compliance_score = $auditRun->computeComplianceScore();
        $auditRun->completed_at = now();
        $auditRun->save();

        return response()->json([
            'message' => 'Audit submitted.',
            'audit_run' => [
                'id' => $auditRun->id,
                'check_in_id' => $auditRun->check_in_id,
                'audit_template_id' => $auditRun->audit_template_id,
                'completed_at' => $auditRun->completed_at?->toIso8601String(),
                'compliance_score' => $auditRun->compliance_score,
            ],
        ]);
    }

    public function show(Request $request, AuditRun $auditRun)
    {
        $auditRun->load(['checkIn', 'checkIn.outlet', 'template', 'answers.question']);
        if ($auditRun->checkIn?->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not allowed for this audit run.'], 403);
        }

        return response()->json([
            'audit_run' => [
                'id' => $auditRun->id,
                'check_in_id' => $auditRun->check_in_id,
                'outlet' => $auditRun->checkIn?->outlet ? [
                    'id' => $auditRun->checkIn->outlet->id,
                    'name' => $auditRun->checkIn->outlet->name,
                ] : null,
                'audit_template_id' => $auditRun->audit_template_id,
                'template_name' => $auditRun->template?->name,
                'completed_at' => $auditRun->completed_at?->toIso8601String(),
                'compliance_score' => $auditRun->compliance_score,
                'answers' => $auditRun->answers->map(function ($answer) {
                    return [
                        'question_id' => $answer->audit_question_id,
                        'question_text' => $answer->question?->question_text,
                        'answer_value' => $answer->answer_value,
                        'photo_path' => $answer->photo_path,
                    ];
                }),
            ],
        ]);
    }
}
