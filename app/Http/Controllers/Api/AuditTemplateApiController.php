<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditTemplate;

class AuditTemplateApiController extends Controller
{
    public function index()
    {
        $templates = AuditTemplate::query()
            ->where('is_active', true)
            ->with(['sections.questions'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'templates' => $templates->map(function (AuditTemplate $template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'sections' => $template->sections->map(function ($section) {
                        return [
                            'id' => $section->id,
                            'name' => $section->name,
                            'sort_order' => $section->sort_order,
                            'questions' => $section->questions->map(function ($question) {
                                return [
                                    'id' => $question->id,
                                    'question_text' => $question->question_text,
                                    'question_type' => $question->question_type,
                                    'sort_order' => $question->sort_order,
                                    'score_max' => $question->score_max,
                                ];
                            }),
                        ];
                    }),
                ];
            }),
        ]);
    }

    public function show(AuditTemplate $auditTemplate)
    {
        $auditTemplate->load(['sections.questions']);

        return response()->json([
            'template' => [
                'id' => $auditTemplate->id,
                'name' => $auditTemplate->name,
                'description' => $auditTemplate->description,
                'is_active' => (bool) $auditTemplate->is_active,
                'sections' => $auditTemplate->sections->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'name' => $section->name,
                        'sort_order' => $section->sort_order,
                        'questions' => $section->questions->map(function ($question) {
                            return [
                                'id' => $question->id,
                                'question_text' => $question->question_text,
                                'question_type' => $question->question_type,
                                'sort_order' => $question->sort_order,
                                'score_max' => $question->score_max,
                            ];
                        }),
                    ];
                }),
            ],
        ]);
    }
}
