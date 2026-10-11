<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private function make(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function request(User $staff, ?User $tech = null, string $status = 'open'): MaintenanceRequest
    {
        $asset = Asset::create(['asset_tag' => 'A-'.uniqid(), 'name' => 'PC', 'status' => 'active']);

        return MaintenanceRequest::create([
            'asset_id' => $asset->id, 'requester_id' => $staff->id, 'technician_id' => $tech?->id,
            'title' => 'Broken', 'priority' => 'high', 'status' => $status,
        ]);
    }

    public function test_full_lifecycle_creates_record_and_logs(): void
    {
        [$admin, $tech, $staff] = [$this->make('admin'), $this->make('technician'), $this->make('staff')];
        $req = $this->request($staff);

        $this->actingAs($admin)->post("/maintenance/{$req->id}/transition", ['status' => 'assigned', 'technician_id' => $tech->id])->assertSessionHasNoErrors();
        $this->actingAs($tech)->post("/maintenance/{$req->id}/transition", ['status' => 'in_progress'])->assertSessionHasNoErrors();
        $this->assertEquals('under_maintenance', $req->asset->fresh()->status);
        $this->actingAs($tech)->post("/maintenance/{$req->id}/transition", ['status' => 'completed', 'work_summary' => 'Replaced PSU'])->assertSessionHasNoErrors();

        $this->assertEquals('completed', $req->fresh()->status);
        $this->assertEquals('active', $req->asset->fresh()->status);
        $this->assertDatabaseHas('maintenance_records', ['maintenance_request_id' => $req->id, 'performed_by' => $tech->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'maintenance.completed', 'outcome' => 'success']);
    }

    public function test_invalid_transition_is_rejected_and_logged(): void
    {
        $admin = $this->make('admin');
        $req = $this->request($this->make('staff'));

        $this->actingAs($admin)->post("/maintenance/{$req->id}/transition", ['status' => 'completed', 'work_summary' => 'x'])->assertSessionHasErrors('status');
        $this->assertEquals('open', $req->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'maintenance.completed', 'outcome' => 'denied']);
    }

    public function test_unassigned_technician_and_other_staff_cannot_touch_request(): void
    {
        $tech = $this->make('technician');
        $owner = $this->make('staff');
        $req = $this->request($owner, $this->make('technician'), 'assigned');

        $this->actingAs($tech)->post("/maintenance/{$req->id}/transition", ['status' => 'in_progress'])->assertForbidden();
        $this->actingAs($this->make('staff'))->get("/maintenance/{$req->id}")->assertForbidden();
        $this->actingAs($owner)->get("/maintenance/{$req->id}")->assertOk();
    }
}
