<?php

namespace Tests\Feature;

use App\Enums\BugStatus;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Bug;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BugStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = $this->admin();
    }

    private function setStatus(Bug $bug, string $status)
    {
        return $this->actingAs($this->admin)->patch("/admin/bugs/{$bug->id}", ['status' => $status]);
    }

    public function test_new_reports_start_as_pending_and_open(): void
    {
        $bug = Bug::factory()->create();

        $this->assertSame(VerificationStatus::Pending, $bug->verification_status);
        $this->assertSame(BugStatus::Open, $bug->status);
    }

    public function test_admin_moves_a_bug_through_the_workflow(): void
    {
        $bug = Bug::factory()->verified()->create();

        foreach (['in_progress', 'fixed', 'closed'] as $status) {
            $this->setStatus($bug, $status)->assertSessionHasNoErrors();
            $this->assertSame($status, $bug->fresh()->status->value);
        }

        $this->assertSame(VerificationStatus::Verified, $bug->fresh()->verification_status);
        $this->assertSame(3, AuditLog::where('action', 'bug.updated')->count());
        $this->assertSame(
            ['fixed', 'closed'],
            AuditLog::latest('id')->firstOrFail()->metadata['changes']['status'],
        );
    }

    public function test_status_change_is_visible_on_the_public_board(): void
    {
        $bug = Bug::factory()->verified()->create();

        $this->setStatus($bug, 'fixed');

        $this->get("/project/{$bug->project->project_code}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('bugs.data.0.status', 'fixed')
                ->where('stats.fixed', 1));
    }

    public function test_setting_rejected_or_duplicate_status_updates_verification(): void
    {
        $rejected = Bug::factory()->create();
        $duplicate = Bug::factory()->create();

        $this->setStatus($rejected, 'rejected');
        $this->setStatus($duplicate, 'duplicate');

        $this->assertSame(VerificationStatus::Rejected, $rejected->fresh()->verification_status);
        $this->assertSame(VerificationStatus::Duplicate, $duplicate->fresh()->verification_status);
    }

    public function test_reopening_a_rejected_bug_sends_it_back_to_pending_review(): void
    {
        $bug = Bug::factory()->create(['verification_status' => 'rejected', 'status' => 'rejected']);

        $this->setStatus($bug, 'open');

        $bug->refresh();
        $this->assertSame(BugStatus::Open, $bug->status);
        $this->assertSame(VerificationStatus::Pending, $bug->verification_status);
    }

    public function test_unknown_status_is_rejected(): void
    {
        $bug = Bug::factory()->create();

        $this->setStatus($bug, 'done')->assertSessionHasErrors('status');
        $this->setStatus($bug, '')->assertSessionHasErrors('status');

        $this->assertSame(BugStatus::Open, $bug->fresh()->status);
    }

    public function test_public_users_cannot_change_a_bug_status(): void
    {
        $bug = Bug::factory()->create();

        $this->patch("/admin/bugs/{$bug->id}", ['status' => 'fixed'])->assertRedirect('/login');
        $this->patch("/bug/{$bug->bug_code}", ['status' => 'fixed'])->assertMethodNotAllowed();
        $this->put("/bug/{$bug->bug_code}", ['status' => 'fixed'])->assertMethodNotAllowed();
        $this->delete("/bug/{$bug->bug_code}")->assertMethodNotAllowed();

        $this->assertSame(BugStatus::Open, $bug->fresh()->status);
    }

    public function test_admin_can_delete_a_spam_bug_with_its_screenshot(): void
    {
        $bug = Bug::factory()->create();
        $path = UploadedFile::fake()->image('spam.png')->store("bugs/{$bug->id}", 'public');
        $bug->attachments()->create(['file_name' => 'spam.png', 'file_path' => $path, 'mime_type' => 'image/png', 'file_size' => 100]);

        $this->actingAs($this->admin)
            ->delete("/admin/bugs/{$bug->id}")
            ->assertRedirect('/admin/bugs')
            ->assertInertiaFlash('success');

        $this->assertModelMissing($bug);
        $this->assertDatabaseCount('bug_attachments', 0);
        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'bug.deleted', 'entity_type' => 'Bug', 'entity_id' => $bug->id]);
    }

    public function test_guests_cannot_delete_bugs(): void
    {
        $bug = Bug::factory()->create();

        $this->delete("/admin/bugs/{$bug->id}")->assertRedirect('/login');

        $this->assertModelExists($bug);
    }
}
