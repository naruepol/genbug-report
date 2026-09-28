<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Bug;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The admin can turn public bug reporting on or off for each project.
 * A project accepts new reports only while it is Published AND reporting is on.
 */
class BugReportingToggleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = $this->admin();
        $this->project = Project::factory()->published()->create(['project_code' => 'TOGGLE']);
    }

    private function toggle(mixed $enabled)
    {
        return $this->actingAs($this->admin)
            ->from("/admin/projects/{$this->project->id}")
            ->patch("/admin/projects/{$this->project->id}/bug-reporting", ['enabled' => $enabled]);
    }

    private function report()
    {
        return $this->post('/project/TOGGLE/report-bug', ['title' => 'Broken', 'description' => 'It broke']);
    }

    public function test_bug_reporting_is_on_by_default(): void
    {
        $project = Project::create([
            'project_code' => 'NEW-ONE',
            'name' => 'New',
            'description' => 'New project',
            'status' => 'published',
        ]);

        $this->assertTrue($project->bug_reporting_enabled);
        $this->assertTrue($project->fresh()->bug_reporting_enabled);
        $this->assertTrue($project->acceptsBugReports());
    }

    public function test_admin_can_turn_bug_reporting_off_and_back_on(): void
    {
        $this->toggle(false)
            ->assertRedirect("/admin/projects/{$this->project->id}")
            ->assertInertiaFlash('success', 'Bug reporting is off. The project stays visible, but new reports are not accepted.');

        $this->assertFalse($this->project->fresh()->bug_reporting_enabled);
        $this->assertFalse($this->project->fresh()->acceptsBugReports());
        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'project.bug_reporting_disabled',
            'entity_id' => $this->project->id,
        ]);

        $this->toggle(true)->assertInertiaFlash('success', 'Bug reporting is on. Visitors can report bugs.');

        $this->assertTrue($this->project->fresh()->bug_reporting_enabled);
        $this->assertDatabaseHas('audit_logs', ['action' => 'project.bug_reporting_enabled', 'entity_id' => $this->project->id]);
    }

    public function test_turning_on_reporting_for_a_draft_explains_when_it_takes_effect(): void
    {
        $this->project->update(['status' => 'draft', 'bug_reporting_enabled' => false]);

        $this->toggle(true)->assertInertiaFlash('success', 'Bug reporting is on. It takes effect while the project is published.');
    }

    public function test_setting_the_same_value_again_does_not_add_an_audit_entry(): void
    {
        $this->toggle(true)->assertSessionHasNoErrors();

        $this->assertSame(0, AuditLog::count());
    }

    public function test_enabled_must_be_a_boolean(): void
    {
        $this->actingAs($this->admin)
            ->patch("/admin/projects/{$this->project->id}/bug-reporting", [])
            ->assertSessionHasErrors('enabled');

        $this->toggle('maybe')->assertSessionHasErrors('enabled');

        $this->assertTrue($this->project->fresh()->bug_reporting_enabled);
    }

    public function test_guests_and_non_admins_cannot_change_bug_reporting(): void
    {
        $this->patch("/admin/projects/{$this->project->id}/bug-reporting", ['enabled' => false])
            ->assertRedirect('/login');

        $user = User::factory()->create(['email' => 'visitor@example.com']);
        $this->actingAs($user)
            ->patch("/admin/projects/{$this->project->id}/bug-reporting", ['enabled' => false])
            ->assertForbidden();

        $this->assertTrue($this->project->fresh()->bug_reporting_enabled);
    }

    public function test_report_form_and_submissions_are_refused_while_reporting_is_off(): void
    {
        $this->project->update(['bug_reporting_enabled' => false]);

        $this->get('/project/TOGGLE/report-bug')
            ->assertRedirect('/project/TOGGLE')
            ->assertInertiaFlash('error', 'Bug reporting is turned off for this project at the moment.');

        $this->report()
            ->assertRedirect('/project/TOGGLE')
            ->assertInertiaFlash('error', 'Bug reporting is turned off for this project at the moment.');

        $this->assertSame(0, Bug::count());
    }

    public function test_project_and_its_bugs_stay_visible_while_reporting_is_off(): void
    {
        $bug = Bug::factory()->for($this->project)->create();
        $this->project->update(['bug_reporting_enabled' => false]);

        $this->get('/project/TOGGLE')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('project.bug_reporting_enabled', false)
                ->where('project.accepts_bug_reports', false)
                ->has('bugs.data', 1));

        $this->get("/bug/{$bug->bug_code}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('bug.project.accepts_bug_reports', false));

        $this->get('/projects')
            ->assertInertia(fn (Assert $page) => $page
                ->where('projects.data.0.project_code', 'TOGGLE')
                ->where('projects.data.0.accepts_bug_reports', false));
    }

    public function test_reports_are_accepted_again_after_turning_reporting_back_on(): void
    {
        $this->toggle(false);
        $this->report();
        $this->assertSame(0, Bug::count());

        $this->toggle(true);
        $this->report()->assertSessionHasNoErrors();

        $this->assertSame(1, Bug::count());
        $this->get('/project/TOGGLE')
            ->assertInertia(fn (Assert $page) => $page->where('project.accepts_bug_reports', true));
    }

    public function test_closed_projects_refuse_reports_even_when_reporting_is_on(): void
    {
        $this->project->update(['status' => 'closed', 'bug_reporting_enabled' => true]);

        $this->assertFalse($this->project->fresh()->acceptsBugReports());
        $this->report()
            ->assertRedirect('/project/TOGGLE')
            ->assertInertiaFlash('error', 'This project is closed and no longer accepts new bug reports.');
        $this->assertSame(0, Bug::count());
    }

    public function test_admin_can_set_bug_reporting_in_the_project_form(): void
    {
        $data = [
            'name' => 'Form Project',
            'project_code' => 'FORM-PROJECT',
            'description' => 'Created from the form',
            'status' => 'published',
            'bug_reporting_enabled' => false,
        ];

        $this->actingAs($this->admin)->post('/admin/projects', $data)->assertSessionHasNoErrors();

        $project = Project::where('project_code', 'FORM-PROJECT')->firstOrFail();
        $this->assertFalse($project->bug_reporting_enabled);

        $this->actingAs($this->admin)
            ->put("/admin/projects/{$project->id}", [...$data, 'bug_reporting_enabled' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($project->fresh()->bug_reporting_enabled);
        $log = AuditLog::where('action', 'project.updated')->latest('id')->firstOrFail();
        $this->assertContains('bug_reporting_enabled', $log->metadata['fields']);

        $this->actingAs($this->admin)
            ->put("/admin/projects/{$project->id}", [...$data, 'bug_reporting_enabled' => 'sometimes'])
            ->assertSessionHasErrors('bug_reporting_enabled');
    }

    public function test_saving_the_form_without_the_field_keeps_the_current_setting(): void
    {
        $this->project->update(['bug_reporting_enabled' => false]);

        $this->actingAs($this->admin)->put("/admin/projects/{$this->project->id}", [
            'name' => 'Renamed',
            'project_code' => 'TOGGLE',
            'description' => 'Still paused',
            'status' => 'published',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($this->project->fresh()->bug_reporting_enabled);
    }

    public function test_admin_pages_show_the_current_setting(): void
    {
        $this->project->update(['bug_reporting_enabled' => false]);

        $this->actingAs($this->admin)
            ->get("/admin/projects/{$this->project->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('project.bug_reporting_enabled', false)
                ->where('project.accepts_bug_reports', false));

        $this->actingAs($this->admin)
            ->get('/admin/projects')
            ->assertInertia(fn (Assert $page) => $page->where('projects.data.0.bug_reporting_enabled', false));
    }
}
