<?php

namespace App\Http\Controllers;

use App\Models\ChecklistTemplate;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChecklistController extends Controller
{
    public function index() { return view('checklists.index', ['templates' => ChecklistTemplate::withCount('items')->orderBy('name')->get()]); }

    public function create() { return view('checklists.create'); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:checklist_templates,name',
            'description' => 'nullable|string|max:2000',
            'questions' => 'required|string|max:20000',
        ]);

        // One question per line; blank lines ignored.
        $questions = array_values(array_filter(array_map('trim', preg_split('/\R/', $data['questions']))));
        if (count($questions) < 1 || count($questions) > 100 || collect($questions)->contains(fn ($q) => mb_strlen($q) > 255)) {
            throw ValidationException::withMessages(['questions' => 'Enter 1–100 questions, one per line, each under 255 characters.']);
        }

        $tpl = DB::transaction(function () use ($data, $questions, $request) {
            $tpl = ChecklistTemplate::create(['name' => $data['name'], 'description' => $data['description'] ?? null, 'created_by' => $request->user()->id]);
            foreach ($questions as $i => $q) {
                $tpl->items()->create(['question' => $q, 'position' => $i]);
            }
            ActivityLogService::record('checklist.created', $tpl);

            return $tpl;
        });

        return redirect()->route('checklists.index')->with('ok', "Template \"{$tpl->name}\" created.");
    }
}
