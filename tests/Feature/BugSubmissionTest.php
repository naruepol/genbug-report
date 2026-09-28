<?php

namespace Tests\Feature;

use App\Enums\BugStatus;
use App\Enums\VerificationStatus;
use App\Models\Bug;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BugSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->project = Project::factory()->published()->create(['project_code' => 'PORTFOLIO-AI']);
    }

    private function submit(array $data = [], string $ip = '127.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->post('/project/PORTFOLIO-AI/report-bug', [
            'title' => 'Login button not working',
            'description' => 'Tapping the button does nothing.',
            ...$data,
        ]);
    }

    public function test_report_form_is_shown_for_published_projects(): void
    {
        $this->get('/project/PORTFOLIO-AI/report-bug')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Bugs/Create')
                ->where('project.project_code', 'PORTFOLIO-AI'));
    }

    public function test_public_user_can_report_a_bug_without_signing_in(): void
    {
        $response = $this->submit([
            'category' => 'functional',
            'steps_to_reproduce' => "1. Open login\n2. Tap the button",
            'expected_result' => 'Signed in',
            'actual_result' => 'Nothing happens',
            'page_screen' => 'Login page',
            'browser' => 'Chrome 140',
            'operating_system' => 'Android 16',
            'device' => 'Mobile',
            'name' => 'Somchai',
            'email' => 'somchai@example.com',
        ], '203.0.113.9');

        $bug = Bug::firstOrFail();
        $response->assertRedirect("/bug/{$bug->bug_code}")->assertInertiaFlash('success');

        $this->assertGuest();
        $this->assertSame($this->project->id, $bug->project_id);
        $this->assertSame(Bug::codeFor($bug->id), $bug->bug_code);
        $this->assertMatchesRegularExpression('/^BUG-\d{3,}$/', $bug->bug_code);
        $this->assertSame(VerificationStatus::Pending, $bug->verification_status);
        $this->assertSame(BugStatus::Open, $bug->status);
        $this->assertSame('Login button not working', $bug->title);
        $this->assertSame('functional', $bug->category->value);
        $this->assertSame('Login page', $bug->page_screen);
        $this->assertSame('Somchai', $bug->reporter_name);
        $this->assertSame('somchai@example.com', $bug->reporter_email);
        $this->assertSame('203.0.113.9', $bug->reporter_ip);
        $this->assertNull($bug->severity);
        $this->assertNull($bug->priority);
        $this->assertNull($bug->score);
    }

    public function test_only_title_and_description_are_required(): void
    {
        $this->submit(['title' => '', 'description' => ''])
            ->assertSessionHasErrors(['title', 'description']);

        $this->submit()->assertSessionHasNoErrors();

        $this->assertSame(1, Bug::count());
    }

    public function test_bug_fields_are_validated(): void
    {
        $this->submit([
            'title' => str_repeat('a', 256),
            'category' => 'not-a-category',
            'email' => 'not-an-email',
        ])->assertSessionHasErrors(['title', 'category', 'email']);

        $this->assertSame(0, Bug::count());
    }

    public function test_public_users_cannot_set_admin_only_fields(): void
    {
        $this->submit([
            'severity' => 'critical',
            'priority' => 'high',
            'score' => 10,
            'status' => 'fixed',
            'verification_status' => 'verified',
            'admin_note' => 'I am the admin now',
            'bug_code' => 'BUG-999',
        ])->assertSessionHasNoErrors();

        $bug = Bug::firstOrFail();
        $this->assertNull($bug->severity);
        $this->assertNull($bug->priority);
        $this->assertNull($bug->score);
        $this->assertSame(BugStatus::Open, $bug->status);
        $this->assertSame(VerificationStatus::Pending, $bug->verification_status);
        $this->assertNull($bug->admin_note);
        $this->assertNotSame('BUG-999', $bug->bug_code);
    }

    public function test_bug_codes_are_sequential_and_unique(): void
    {
        $this->submit();
        $this->submit();
        $this->submit();

        $codes = Bug::orderBy('id')->pluck('bug_code')->all();
        $this->assertSame(array_map(fn (int $id) => Bug::codeFor($id), Bug::orderBy('id')->pluck('id')->all()), $codes);
        $this->assertCount(3, array_unique($codes));
    }

    public function test_closed_and_draft_projects_do_not_accept_reports(): void
    {
        Project::factory()->closed()->create(['project_code' => 'CLOSED-ONE']);
        Project::factory()->draft()->create(['project_code' => 'DRAFT-ONE']);

        $this->post('/project/CLOSED-ONE/report-bug', ['title' => 'A', 'description' => 'B'])
            ->assertRedirect('/project/CLOSED-ONE')
            ->assertInertiaFlash('error');

        $this->post('/project/DRAFT-ONE/report-bug', ['title' => 'A', 'description' => 'B'])
            ->assertNotFound();

        $this->assertSame(0, Bug::count());
    }

    public function test_honeypot_submissions_are_silently_discarded(): void
    {
        $this->submit(['website' => 'https://spam.example.com'])
            ->assertRedirect('/project/PORTFOLIO-AI')
            ->assertInertiaFlash('success');

        $this->assertSame(0, Bug::count());
    }

    public function test_reports_are_rate_limited_to_five_per_ten_minutes_per_ip(): void
    {
        foreach (range(1, 5) as $i) {
            $this->submit(['title' => "Report {$i}"])->assertSessionHasNoErrors();
        }

        $this->submit(['title' => 'Report 6'])->assertSessionHasErrors('form');
        $this->assertSame(5, Bug::count());

        // Another IP address is not affected.
        $this->submit(['title' => 'From another network'], '198.51.100.7')->assertSessionHasNoErrors();
        $this->assertSame(6, Bug::count());

        // The window resets after ten minutes.
        $this->travel(11)->minutes();
        $this->submit(['title' => 'Report 7'])->assertSessionHasNoErrors();
        $this->assertSame(7, Bug::count());
    }

    public function test_rate_limit_is_configurable(): void
    {
        config(['bug_reports.rate_limit.max_reports' => 2]);

        $this->submit()->assertSessionHasNoErrors();
        $this->submit()->assertSessionHasNoErrors();
        $this->submit()->assertSessionHasErrors('form');

        $this->assertSame(2, Bug::count());
    }

    public function test_rejected_validation_attempts_do_not_count_toward_the_rate_limit(): void
    {
        foreach (range(1, 6) as $i) {
            $this->submit(['title' => ''])->assertSessionHasErrors('title');
        }

        $this->submit()->assertSessionHasNoErrors();
        $this->assertSame(1, Bug::count());
    }

    public function test_rate_limit_returns_429_for_json_clients(): void
    {
        foreach (range(1, 5) as $i) {
            $this->submit();
        }

        $this->postJson('/project/PORTFOLIO-AI/report-bug', ['title' => 'x', 'description' => 'y'])
            ->assertStatus(429);
    }

    public function test_thai_text_is_stored_and_returned_intact(): void
    {
        $this->submit(['title' => 'ปุ่มบันทึกไม่ทำงาน', 'description' => 'กดแล้วไม่มีอะไรเกิดขึ้น']);

        $bug = Bug::firstOrFail();
        $this->assertSame('ปุ่มบันทึกไม่ทำงาน', $bug->title);

        $this->get("/bug/{$bug->bug_code}")
            ->assertInertia(fn (Assert $page) => $page->where('bug.title', 'ปุ่มบันทึกไม่ทำงาน'));
    }
}
