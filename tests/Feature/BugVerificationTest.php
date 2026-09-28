<?php

namespace Tests\Feature;

use App\Enums\BugPriority;
use App\Enums\BugSeverity;
use App\Enums\BugStatus;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Bug;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BugVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Bug $bug;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->admin();
        $this->bug = Bug::factory()->create();
    }

    private function review(array $data)
    {
        return $this->actingAs($this->admin)
            ->from("/admin/bugs/{$this->bug->id}")
            ->patch("/admin/bugs/{$this->bug->id}", $data);
    }

    public function test_admin_sees_the_bug_list_and_detail(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/bugs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Bugs/Index')
                ->has('bugs.data', 1)
                ->has('projects', 1));

        $this->actingAs($this->admin)
            ->get("/admin/bugs/{$this->bug->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Bugs/Show')
                ->where('bug.bug_code', $this->bug->bug_code)
                ->where('bug.verification_status', 'pending'));
    }

    public function test_admin_can_verify_a_bug(): void
    {
        $this->review(['verification_status' => 'verified'])
            ->assertRedirect("/admin/bugs/{$this->bug->id}")
            ->assertInertiaFlash('success');

        $this->bug->refresh();
        $this->assertSame(VerificationStatus::Verified, $this->bug->verification_status);
        $this->assertSame(BugStatus::Open, $this->bug->status);

        $log = AuditLog::where('action', 'bug.updated')->firstOrFail();
        $this->assertSame($this->admin->id, $log->admin_id);
        $this->assertSame('Bug', $log->entity_type);
        $this->assertSame($this->bug->id, $log->entity_id);
        $this->assertSame(['pending', 'verified'], $log->metadata['changes']['verification_status']);
    }

    public function test_rejecting_a_bug_also_sets_the_rejected_status(): void
    {
        $this->review(['verification_status' => 'rejected'])->assertSessionHasNoErrors();

        $this->bug->refresh();
        $this->assertSame(VerificationStatus::Rejected, $this->bug->verification_status);
        $this->assertSame(BugStatus::Rejected, $this->bug->status);
    }

    public function test_marking_a_duplicate_also_sets_the_duplicate_status(): void
    {
        $this->review(['verification_status' => 'duplicate'])->assertSessionHasNoErrors();

        $this->bug->refresh();
        $this->assertSame(VerificationStatus::Duplicate, $this->bug->verification_status);
        $this->assertSame(BugStatus::Duplicate, $this->bug->status);
    }

    public function test_verifying_a_rejected_bug_reopens_it(): void
    {
        $this->review(['verification_status' => 'rejected']);
        $this->review(['verification_status' => 'verified']);

        $this->bug->refresh();
        $this->assertSame(VerificationStatus::Verified, $this->bug->verification_status);
        $this->assertSame(BugStatus::Open, $this->bug->status);
    }

    public function test_admin_can_set_severity_priority_score_and_note(): void
    {
        $this->review([
            'severity' => 'high',
            'priority' => 'medium',
            'score' => 8,
            'admin_note' => 'Reproduced on Android.',
        ])->assertSessionHasNoErrors();

        $this->bug->refresh();
        $this->assertSame(BugSeverity::High, $this->bug->severity);
        $this->assertSame(BugPriority::Medium, $this->bug->priority);
        $this->assertSame(8, $this->bug->score);
        $this->assertSame('Reproduced on Android.', $this->bug->admin_note);

        $changes = AuditLog::latest('id')->firstOrFail()->metadata['changes'];
        $this->assertSame([null, 'high'], $changes['severity']);
        $this->assertSame([null, 8], $changes['score']);
        $this->assertSame('updated', $changes['admin_note'], 'The note text is not copied into the audit log');
    }

    public function test_assessment_can_be_cleared(): void
    {
        $this->bug->update(['severity' => 'high', 'priority' => 'high', 'score' => 7]);

        $this->review(['severity' => null, 'priority' => null, 'score' => null])->assertSessionHasNoErrors();

        $this->bug->refresh();
        $this->assertNull($this->bug->severity);
        $this->assertNull($this->bug->priority);
        $this->assertNull($this->bug->score);
    }

    public function test_score_must_be_between_one_and_ten(): void
    {
        foreach ([0, 11, -1, 'ten', 5.5] as $invalid) {
            $this->review(['score' => $invalid])->assertSessionHasErrors('score');
        }

        foreach ([1, 10] as $valid) {
            $this->review(['score' => $valid])->assertSessionHasNoErrors();
        }

        $this->assertSame(10, $this->bug->fresh()->score);
    }

    public function test_severity_priority_and_verification_must_be_known_values(): void
    {
        $this->review([
            'severity' => 'apocalyptic',
            'priority' => 'urgent',
            'verification_status' => 'maybe',
        ])->assertSessionHasErrors(['severity', 'priority', 'verification_status']);

        $this->assertSame(VerificationStatus::Pending, $this->bug->fresh()->verification_status);
    }

    public function test_saving_without_changes_does_not_write_an_audit_entry(): void
    {
        $this->review(['verification_status' => 'pending', 'status' => 'open'])->assertInertiaFlash('success', 'No changes to save.');

        $this->assertSame(0, AuditLog::count());
    }

    public function test_guests_and_non_admins_cannot_review_bugs(): void
    {
        $this->patch("/admin/bugs/{$this->bug->id}", ['verification_status' => 'verified'])->assertRedirect('/login');

        $user = User::factory()->create(['email' => 'random@example.com']);
        $this->actingAs($user)
            ->patch("/admin/bugs/{$this->bug->id}", ['verification_status' => 'verified'])
            ->assertForbidden();

        $this->assertSame(VerificationStatus::Pending, $this->bug->fresh()->verification_status);
    }

    public function test_admin_bug_list_can_be_filtered_and_searched(): void
    {
        $other = Bug::factory()->assessed(BugSeverity::Critical, BugPriority::High, 9)->create([
            'title' => 'Payment page crashes',
            'reporter_email' => 'finder@example.com',
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->admin)
            ->get("/admin/bugs?project={$other->project_id}&severity=critical&priority=high&verification=verified&status=in_progress")
            ->assertInertia(fn (Assert $page) => $page
                ->has('bugs.data', 1)
                ->where('bugs.data.0.bug_code', $other->bug_code));

        // Admin search also covers description and reporter email.
        $this->actingAs($this->admin)
            ->get('/admin/bugs?search=finder@example')
            ->assertInertia(fn (Assert $page) => $page->has('bugs.data', 1)->where('bugs.data.0.id', $other->id));

        $today = now()->toDateString();
        $this->actingAs($this->admin)
            ->get("/admin/bugs?from={$today}&to={$today}")
            ->assertInertia(fn (Assert $page) => $page->has('bugs.data', 2));

        $this->actingAs($this->admin)
            ->get('/admin/bugs?to=2000-01-01')
            ->assertInertia(fn (Assert $page) => $page->has('bugs.data', 0));
    }

    public function test_admin_dashboard_shows_totals(): void
    {
        Bug::factory()->verified()->create(['status' => 'fixed']);
        Bug::factory()->verified()->create(['status' => 'closed']);

        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('stats.projects.total', 3)
                ->where('stats.projects.published', 3)
                ->where('stats.bugs.total', 3)
                ->where('stats.bugs.verification.pending', 1)
                ->where('stats.bugs.verification.verified', 2)
                ->where('stats.bugs.status.open', 1)
                ->where('stats.bugs.status.in_progress', 0)
                ->where('stats.bugs.status.fixed', 1)
                ->where('stats.bugs.status.closed', 1)
                ->has('recentBugs', 3));
    }
}
