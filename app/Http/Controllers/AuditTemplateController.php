<?php

namespace App\Http\Controllers;

use App\Models\AuditTemplate;
use App\Models\AuditSection;
use App\Models\AuditQuestion;
use Illuminate\Http\Request;

class AuditTemplateController extends Controller
{
    public function index()
    {
        $templates = AuditTemplate::withCount('sections')->latest()->paginate(15);
        return view('audit-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('audit-templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        AuditTemplate::create($validated);
        return redirect()->route('audit-templates.index')->with('success', 'Audit template created.');
    }

    public function show(AuditTemplate $auditTemplate)
    {
        $auditTemplate->load(['sections.questions']);
        return view('audit-templates.show', compact('auditTemplate'));
    }

    public function edit(AuditTemplate $auditTemplate)
    {
        $auditTemplate->load(['sections.questions']);
        return view('audit-templates.edit', compact('auditTemplate'));
    }

    public function update(Request $request, AuditTemplate $auditTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'is_active' => 'boolean',
        ]);
        $auditTemplate->update($validated);
        return redirect()->route('audit-templates.edit', $auditTemplate)->with('success', 'Template updated.');
    }

    public function destroy(AuditTemplate $auditTemplate)
    {
        $auditTemplate->delete();
        return redirect()->route('audit-templates.index')->with('success', 'Audit template deleted.');
    }

    public function storeSection(Request $request, AuditTemplate $auditTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? $auditTemplate->sections()->max('sort_order') + 1);
        $auditTemplate->sections()->create($validated);
        return redirect()->route('audit-templates.edit', $auditTemplate)->with('success', 'Section added.');
    }

    public function updateSection(Request $request, AuditSection $auditSection)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $auditSection->update($validated);
        return redirect()->route('audit-templates.edit', $auditSection->template)->with('success', 'Section updated.');
    }

    public function destroySection(AuditSection $auditSection)
    {
        $template = $auditSection->template;
        $auditSection->delete();
        return redirect()->route('audit-templates.edit', $template)->with('success', 'Section removed.');
    }

    public function storeQuestion(Request $request, AuditSection $auditSection)
    {
        $validated = $request->validate([
            'question_text' => 'required|string|max:1000',
            'question_type' => 'required|string|in:yes_no,score,photo',
            'sort_order' => 'nullable|integer|min:0',
            'score_max' => 'nullable|integer|min:1|max:10',
        ]);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? $auditSection->questions()->max('sort_order') + 1);
        if ($validated['question_type'] !== 'score') {
            $validated['score_max'] = null;
        }
        $auditSection->questions()->create($validated);
        return redirect()->route('audit-templates.edit', $auditSection->template)->with('success', 'Question added.');
    }

    public function updateQuestion(Request $request, AuditQuestion $auditQuestion)
    {
        $validated = $request->validate([
            'question_text' => 'required|string|max:1000',
            'question_type' => 'required|string|in:yes_no,score,photo',
            'sort_order' => 'nullable|integer|min:0',
            'score_max' => 'nullable|integer|min:1|max:10',
        ]);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? $auditQuestion->sort_order);
        if ($validated['question_type'] !== 'score') {
            $validated['score_max'] = null;
        }
        $auditQuestion->update($validated);
        return redirect()->route('audit-templates.edit', $auditQuestion->section->template)->with('success', 'Question updated.');
    }

    public function destroyQuestion(AuditQuestion $auditQuestion)
    {
        $template = $auditQuestion->section->template;
        $auditQuestion->delete();
        return redirect()->route('audit-templates.edit', $template)->with('success', 'Question removed.');
    }
}
