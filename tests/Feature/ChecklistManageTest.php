<?php

namespace Tests\Feature;

use App\Models\ChecklistTemplate;
use App\Models\SecurityAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistManageTest extends TestCase
{
    use RefreshDatabase;

    private function make(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function template(User $by, string $name = 'Base'): ChecklistTemplate
    {
        $t = ChecklistTemplate::create(['name' => $name, 'created_by' => $by->id]);
        $t->items()->create(['question' => 'Q1', 'position' => 0]);
        $t->items()->create(['question' => 'Q2', 'position' => 1]);

        return $t;
    }

    public function test_creator_can_edit_questions_and_existing_audits_keep_their_copy(): void
    {
        $a = $this->make('auditor');
        $t = $this->template($a);
        $this->actingAs($a)->post('/audits', ['title' => 'Old', 'checklist_template_id' => $t->id]);

        $this->actingAs($a)->get("/checklists/{$t->id}/edit")->assertOk()->assertSee('Q1');
        $this->actingAs($a)->put("/checklists/{$t->id}", ['name' => 'Base v2', 'description' => 'd', 'questions' => "New A\n\nNew B\nNew C"])->assertSessionHasNoErrors();

        $this->assertEquals(['New A', 'New B', 'New C'], $t->fresh()->items->pluck('question')->all());
        $this->assertEquals('Base v2', $t->fresh()->name);
        $this->assertEquals(['Q1', 'Q2'], SecurityAudit::firstOrFail()->checks->pluck('question')->all());
        $this->assertDatabaseHas('activity_logs', ['action' => 'checklist.updated']);
    }

    public function test_other_auditor_and_non_auditors_cannot_edit_or_delete(): void
    {
        $t = $this->template($this->make('auditor'));
        foreach ([$this->make('auditor'), $this->make('staff'), $this->make('technician')] as $u) {
            $this->actingAs($u)->get("/checklists/{$t->id}/edit")->assertForbidden();
            $this->actingAs($u)->put("/checklists/{$t->id}", ['name' => 'x', 'questions' => 'q'])->assertForbidden();
            $this->actingAs($u)->delete("/checklists/{$t->id}")->assertForbidden();
        }
        $this->assertDatabaseHas('checklist_templates', ['id' => $t->id, 'name' => 'Base']);
    }

    public function test_admin_can_edit_a_template_without_creator(): void
    {
        $t = ChecklistTemplate::create(['name' => 'Seeded']);
        $t->items()->create(['question' => 'Q', 'position' => 0]);

        $this->actingAs($this->make('auditor'))->delete("/checklists/{$t->id}")->assertForbidden();
        $this->actingAs($this->make('admin'))->put("/checklists/{$t->id}", ['name' => 'Seeded', 'questions' => 'Only one'])->assertSessionHasNoErrors();
        $this->assertEquals(['Only one'], $t->fresh()->items->pluck('question')->all());
    }

    public function test_edit_validates_name_uniqueness_and_question_limits(): void
    {
        $a = $this->make('auditor');
        $t = $this->template($a, 'One');
        $this->template($a, 'Two');

        $this->actingAs($a)->put("/checklists/{$t->id}", ['name' => 'Two', 'questions' => 'q'])->assertSessionHasErrors('name');
        $this->actingAs($a)->put("/checklists/{$t->id}", ['name' => 'One', 'questions' => "\n  \n"])->assertSessionHasErrors('questions');
        $this->actingAs($a)->put("/checklists/{$t->id}", ['name' => 'One', 'questions' => str_repeat('x', 256)])->assertSessionHasErrors('questions');
        $this->actingAs($a)->put("/checklists/{$t->id}", ['name' => 'One', 'questions' => 'Keep name ok'])->assertSessionHasNoErrors(); // same name as itself is fine
        $this->assertEquals(['Keep name ok'], $t->fresh()->items->pluck('question')->all());
    }

    public function test_delete_removes_template_but_not_audits(): void
    {
        $a = $this->make('auditor');
        $t = $this->template($a);
        $this->actingAs($a)->post('/audits', ['title' => 'Keep me', 'checklist_template_id' => $t->id]);

        $this->actingAs($a)->delete("/checklists/{$t->id}")->assertRedirect('/checklists');

        $this->assertDatabaseMissing('checklist_templates', ['id' => $t->id]);
        $this->assertDatabaseCount('checklist_items', 0);
        $this->assertCount(2, SecurityAudit::firstOrFail()->checks);
        $this->assertDatabaseHas('activity_logs', ['action' => 'checklist.deleted', 'subject_id' => $t->id]);
    }

    public function test_index_shows_edit_only_to_those_who_may_edit(): void
    {
        $owner = $this->make('auditor');
        $this->template($owner);

        $this->actingAs($owner)->get('/checklists')->assertSee('Edit')->assertSee('Delete');
        $this->actingAs($this->make('auditor'))->get('/checklists')->assertDontSee('Delete');
    }
}
