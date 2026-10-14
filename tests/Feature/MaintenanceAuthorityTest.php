<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceAuthorityTest extends TestCase
{
    use RefreshDatabase;

    private function make(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function req(User $requester, ?User $tech, string $status, ?Asset $asset = null): MaintenanceRequest
    {
        $asset ??= Asset::create(['asset_tag' => 'A-'.uniqid(), 'name' => 'PC', 'status' => 'active']);

        return MaintenanceRequest::create(['asset_id' => $asset->id, 'requester_id' => $requester->id, 'technician_id' => $tech?->id, 'title' => 'T-'.uniqid(), 'priority' => 'low', 'status' => $status]);
    }

    public function test_technician_can_open_the_request_they_just_submitted(): void
    {
        $tech = $this->make('technician');
        $asset = Asset::create(['asset_tag' => 'A-1', 'name' => 'PC', 'status' => 'active']);

        $res = $this->actingAs($tech)->post('/maintenance', ['asset_id' => $asset->id, 'title' => 'Fan noise', 'priority' => 'low']);
        $res->assertRedirect();
        $this->followRedirects($res)->assertOk()->assertSee('Fan noise');
    }

    public function test_technician_sees_assigned_and_own_requests_only(): void
    {
        [$tech, $other, $staff] = [$this->make('technician'), $this->make('technician'), $this->make('staff')];
        $mine = $this->req($staff, $tech, 'assigned');
        $own = $this->req($tech, null, 'open');
        $notMine = $this->req($staff, $other, 'assigned');

        $this->actingAs($tech)->get('/maintenance')->assertSee($mine->title)->assertSee($own->title)->assertDontSee($notMine->title);
        $this->actingAs($tech)->get("/maintenance/{$notMine->id}")->assertForbidden();
    }

    public function test_auditor_has_no_maintenance_access(): void
    {
        $r = $this->req($this->make('staff'), null, 'open');
        $this->actingAs($this->make('auditor'))->get("/maintenance/{$r->id}")->assertForbidden();
        $this->actingAs($this->make('auditor'))->get('/maintenance')->assertForbidden();
    }

    public function test_staff_can_cancel_own_open_request_but_not_after_assignment(): void
    {
        [$staff, $tech] = [$this->make('staff'), $this->make('technician')];
        $open = $this->req($staff, null, 'open');
        $assigned = $this->req($staff, $tech, 'assigned');

        $this->actingAs($staff)->post("/maintenance/{$assigned->id}/transition", ['status' => 'cancelled'])->assertForbidden();
        $this->actingAs($staff)->post("/maintenance/{$open->id}/transition", ['status' => 'cancelled'])->assertSessionHasNoErrors();
        $this->assertEquals('cancelled', $open->fresh()->status);
    }

    public function test_only_admin_can_assign(): void
    {
        [$staff, $tech] = [$this->make('staff'), $this->make('technician')];
        $r = $this->req($staff, null, 'open');

        $this->actingAs($tech)->post("/maintenance/{$r->id}/transition", ['status' => 'assigned', 'technician_id' => $tech->id])->assertForbidden();
        $this->actingAs($staff)->post("/maintenance/{$r->id}/transition", ['status' => 'assigned', 'technician_id' => $tech->id])->assertForbidden();
        $this->assertEquals('open', $r->fresh()->status);
    }

    public function test_completing_work_does_not_reactivate_a_retired_asset(): void
    {
        $tech = $this->make('technician');
        $asset = Asset::create(['asset_tag' => 'R-1', 'name' => 'Old', 'status' => 'under_maintenance']);
        $r = $this->req($this->make('staff'), $tech, 'in_progress', $asset);
        $asset->update(['status' => 'retired']); // retired by an admin while work was under way

        $this->actingAs($tech)->post("/maintenance/{$r->id}/transition", ['status' => 'completed', 'work_summary' => 'done'])->assertSessionHasNoErrors();

        $this->assertEquals('retired', $asset->fresh()->status);
    }

    public function test_asset_stays_under_maintenance_while_another_request_is_in_progress(): void
    {
        [$t1, $t2, $staff] = [$this->make('technician'), $this->make('technician'), $this->make('staff')];
        $asset = Asset::create(['asset_tag' => 'S-1', 'name' => 'Srv', 'status' => 'under_maintenance']);
        $a = $this->req($staff, $t1, 'in_progress', $asset);
        $this->req($staff, $t2, 'in_progress', $asset);

        $this->actingAs($t1)->post("/maintenance/{$a->id}/transition", ['status' => 'completed', 'work_summary' => 'ok'])->assertSessionHasNoErrors();

        $this->assertEquals('under_maintenance', $asset->fresh()->status);
    }

    public function test_asset_page_lists_only_maintenance_the_viewer_can_open(): void
    {
        [$tech, $other, $staff] = [$this->make('technician'), $this->make('technician'), $this->make('staff')];
        $asset = Asset::create(['asset_tag' => 'H-1', 'name' => 'PC', 'status' => 'active']);
        $mine = $this->req($staff, $tech, 'assigned', $asset);
        $hidden = $this->req($staff, $other, 'assigned', $asset);

        $this->actingAs($tech)->get("/assets/{$asset->id}")->assertOk()->assertSee($mine->title)->assertDontSee($hidden->title);
    }
}
