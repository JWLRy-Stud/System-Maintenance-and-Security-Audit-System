<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChecklistRequest;
use App\Models\ChecklistTemplate;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ChecklistController extends Controller
{
    public function index() { return view('checklists.index', ['templates' => ChecklistTemplate::withCount('items')->orderBy('name')->get()]); }

    public function create() { return view('checklists.form', ['template' => new ChecklistTemplate, 'questions' => '']); }

    public function store(ChecklistRequest $request)
    {
        $tpl = DB::transaction(function () use ($request) {
            $tpl = ChecklistTemplate::create(['name' => $request->input('name'), 'description' => $request->input('description'), 'created_by' => $request->user()->id]);
            $this->writeItems($tpl, $request->questions());
            ActivityLogService::record('checklist.created', $tpl);

            return $tpl;
        });

        return redirect()->route('checklists.index')->with('ok', "Template \"{$tpl->name}\" created.");
    }

    public function edit(ChecklistTemplate $template)
    {
        Gate::authorize('update', $template);

        return view('checklists.form', ['template' => $template, 'questions' => $template->items->pluck('question')->implode("\n")]);
    }

    /**
     * Audits copy the questions when they start, so editing a template never changes audits that already exist.
     */
    public function update(ChecklistRequest $request, ChecklistTemplate $template)
    {
        Gate::authorize('update', $template);

        DB::transaction(function () use ($request, $template) {
            $template->update(['name' => $request->input('name'), 'description' => $request->input('description')]);
            $template->items()->delete();
            $this->writeItems($template, $request->questions());
            ActivityLogService::record('checklist.updated', $template);
        });

        return redirect()->route('checklists.index')->with('ok', "Template \"{$template->name}\" updated.");
    }

    public function destroy(ChecklistTemplate $template)
    {
        Gate::authorize('delete', $template);

        $name = $template->name;
        DB::transaction(function () use ($template) {
            ActivityLogService::record('checklist.deleted', $template); // log first: the id is still valid afterwards in the log
            $template->delete(); // items cascade; existing audits keep their own copy of the questions
        });

        return redirect()->route('checklists.index')->with('ok', "Template \"{$name}\" deleted. Existing audits are not affected.");
    }

    private function writeItems(ChecklistTemplate $template, array $questions): void
    {
        foreach ($questions as $i => $q) {
            $template->items()->create(['question' => $q, 'position' => $i]);
        }
    }
}
