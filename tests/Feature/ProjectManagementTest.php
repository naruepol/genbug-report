<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\AuditLog;
use App\Models\Bug;
use App\Models\Project;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = $this->admin();
    }

    /**
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return [
            'name' => 'Student Portfolio AI',
            'project_code' => 'portfolio-ai',
            'description' => 'Build a portfolio with AI.',
            'demo_url' => 'https://demo.example.com',
            'repository_url' => 'https://github.com/example/portfolio-ai',
            'technology_stack' => 'Laravel, React',
            'presentation_date' => '2026-10-01',
            'status' => 'draft',
            ...$overrides,
        ];
    }

    public function test_guests_and_non_admins_cannot_manage_projects(): void
    {
        $project = Project::factory()->create();

        $this->post('/admin/projects', $this->validData())->assertRedirect('/login');
        $this->put("/admin/projects/{$project->id}", $this->validData())->assertRedirect('/login');
        $this->delete("/admin/projects/{$project->id}")->assertRedirect('/login');

        $user = User::factory()->create(['email' => 'someone@example.com']);
        $this->actingAs($user)->post('/admin/projects', $this->validData())->assertForbidden();
        $this->actingAs($user)->post("/admin/projects/{$project->id}/publish")->assertForbidden();

        $this->assertSame(1, Project::count());
    }

    public function test_admin_sees_the_project_list_with_bug_counts(): void
    {
        $project = Project::factory()->create(['name' => 'Alpha']);
        Bug::factory()->count(3)->for($project)->create();
        Project::factory()->draft()->create(['name' => 'Beta']);

        $this->actingAs($this->admin)
            ->get('/admin/projects')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Projects/Index')
                ->has('projects.data', 2)
                ->where('projects.data.1.name', 'Alpha')
                ->where('projects.data.1.bugs_count', 3)
                ->where('projects.data.1.pending_bugs_count', 3));
    }

    public function test_admin_can_create_a_project_with_an_image(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/projects', $this->validData([
            'image' => UploadedFile::fake()->image('cover.png', 1200, 675),
        ]));

        $project = Project::firstOrFail();
        $response->assertRedirect("/admin/projects/{$project->id}");

        $this->assertSame('PORTFOLIO-AI', $project->project_code, 'Project codes are stored upper-case');
        $this->assertSame(ProjectStatus::Draft, $project->status);
        $this->assertSame('https://demo.example.com', $project->demo_url);
        $this->assertSame('2026-10-01', $project->presentation_date->format('Y-m-d'));
        $this->assertNotNull($project->image_url);
        Storage::disk('public')->assertExists($project->image_url);
        $this->assertDatabaseHas('audit_logs', ['action' => 'project.created', 'entity_type' => 'Project', 'entity_id' => $project->id]);

        // Drafts get no QR code until they are published.
        $this->assertFalse(app(QrCodeService::class)->exists($project));
    }

    public function test_creating_a_published_project_generates_its_qr_code(): void
    {
        $this->actingAs($this->admin)->post('/admin/projects', $this->validData(['status' => 'published']));

        $project = Project::firstOrFail();
        $qrCodes = app(QrCodeService::class);

        Storage::disk('public')->assertExists($qrCodes->path($project, 'png'));
        Storage::disk('public')->assertExists($qrCodes->path($project, 'svg'));
        $this->assertStringContainsString('<svg', Storage::disk('public')->get($qrCodes->path($project, 'svg')));
        $this->assertSame('http://localhost:8080/project/PORTFOLIO-AI', $project->publicUrl());
    }

    public function test_project_validation_rules(): void
    {
        Project::factory()->create(['project_code' => 'TAKEN']);

        $this->actingAs($this->admin)
            ->post('/admin/projects', [])
            ->assertSessionHasErrors(['name', 'project_code', 'description', 'status']);

        $this->actingAs($this->admin)
            ->post('/admin/projects', $this->validData([
                'project_code' => 'taken',
                'demo_url' => 'javascript:alert(1)',
                'repository_url' => 'not a url',
                'presentation_date' => 'soon',
                'status' => 'archived',
            ]))
            ->assertSessionHasErrors(['project_code', 'demo_url', 'repository_url', 'presentation_date', 'status']);

        $this->actingAs($this->admin)
            ->post('/admin/projects', $this->validData(['project_code' => 'has spaces!']))
            ->assertSessionHasErrors(['project_code']);

        $this->assertSame(1, Project::count());
    }

    public function test_admin_can_update_a_project(): void
    {
        $project = Project::factory()->draft()->create(['project_code' => 'OLD-CODE']);

        $this->actingAs($this->admin)
            ->put("/admin/projects/{$project->id}", $this->validData([
                'project_code' => 'OLD-CODE',
                'name' => 'Renamed project',
                'demo_url' => '',
            ]))
            ->assertRedirect("/admin/projects/{$project->id}");

        $project->refresh();
        $this->assertSame('Renamed project', $project->name);
        $this->assertNull($project->demo_url);
        $this->assertDatabaseHas('audit_logs', ['action' => 'project.updated', 'entity_id' => $project->id]);
    }

    public function test_project_code_must_stay_unique_when_updating(): void
    {
        Project::factory()->create(['project_code' => 'FIRST']);
        $second = Project::factory()->create(['project_code' => 'SECOND']);

        $this->actingAs($this->admin)
            ->put("/admin/projects/{$second->id}", $this->validData(['project_code' => 'first']))
            ->assertSessionHasErrors('project_code');

        $this->assertSame('SECOND', $second->fresh()->project_code);
    }

    public function test_admin_can_replace_and_remove_the_project_image(): void
    {
        $this->actingAs($this->admin)->post('/admin/projects', $this->validData([
            'image' => UploadedFile::fake()->image('first.png'),
        ]));
        $project = Project::firstOrFail();
        $firstImage = $project->image_url;

        $this->actingAs($this->admin)->post("/admin/projects/{$project->id}", $this->validData([
            '_method' => 'put',
            'image' => UploadedFile::fake()->image('second.jpg'),
        ]))->assertRedirect();

        $secondImage = $project->fresh()->image_url;
        $this->assertNotSame($firstImage, $secondImage);
        Storage::disk('public')->assertMissing($firstImage);
        Storage::disk('public')->assertExists($secondImage);

        $this->actingAs($this->admin)->put("/admin/projects/{$project->id}", $this->validData(['remove_image' => true]));

        $this->assertNull($project->fresh()->image_url);
        Storage::disk('public')->assertMissing($secondImage);
    }

    public function test_admin_can_publish_a_project_and_its_qr_code_is_generated(): void
    {
        $project = Project::factory()->draft()->create();

        $this->actingAs($this->admin)
            ->from("/admin/projects/{$project->id}")
            ->post("/admin/projects/{$project->id}/publish")
            ->assertRedirect("/admin/projects/{$project->id}");

        $this->assertSame(ProjectStatus::Published, $project->fresh()->status);
        $this->assertTrue(app(QrCodeService::class)->exists($project, 'png'));
        $this->assertTrue(app(QrCodeService::class)->exists($project, 'svg'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'project.published', 'entity_id' => $project->id]);
    }

    public function test_admin_can_close_a_project(): void
    {
        $project = Project::factory()->published()->create();

        $this->actingAs($this->admin)->post("/admin/projects/{$project->id}/close")->assertRedirect();

        $this->assertSame(ProjectStatus::Closed, $project->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'project.closed', 'entity_id' => $project->id]);
    }

    public function test_admin_can_download_the_qr_code(): void
    {
        $project = Project::factory()->create(['project_code' => 'DEMO-QR']);

        $png = $this->actingAs($this->admin)->get("/admin/projects/{$project->id}/qr-code.png");
        $png->assertOk()->assertDownload('DEMO-QR-qr-code.png');
        $this->assertSame('image/png', $png->headers->get('Content-Type'));

        $this->actingAs($this->admin)
            ->get("/admin/projects/{$project->id}/qr-code.svg")
            ->assertOk()
            ->assertDownload('DEMO-QR-qr-code.svg');

        $this->actingAs($this->admin)->get("/admin/projects/{$project->id}/qr-code.gif")->assertNotFound();
    }

    public function test_admin_can_regenerate_the_qr_code(): void
    {
        $project = Project::factory()->draft()->create();

        $this->actingAs($this->admin)->post("/admin/projects/{$project->id}/qr-code")->assertRedirect();

        $this->assertTrue(app(QrCodeService::class)->exists($project));
        $this->assertDatabaseHas('audit_logs', ['action' => 'project.qr_generated', 'entity_id' => $project->id]);
    }

    public function test_project_dashboard_shows_summary_and_charts(): void
    {
        $project = Project::factory()->create();
        Bug::factory()->for($project)->create(['category' => 'functional']);
        Bug::factory()->for($project)->assessed()->create(['status' => 'in_progress', 'category' => 'ui_ux']);
        Bug::factory()->for($project)->assessed()->create(['status' => 'fixed', 'severity' => 'low', 'category' => 'ui_ux']);
        Bug::factory()->create(); // another project

        $this->actingAs($this->admin)
            ->get("/admin/projects/{$project->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Projects/Show')
                ->where('stats.total', 3)
                ->where('stats.verification.pending', 1)
                ->where('stats.verification.verified', 2)
                ->where('stats.status.open', 1)
                ->where('stats.status.in_progress', 1)
                ->where('stats.status.fixed', 1)
                ->where('stats.status.closed', 0)
                ->has('charts.status', 6)
                ->where('charts.severity.1.label', 'High')
                ->where('charts.severity.1.count', 1)
                ->where('charts.severity.4.label', 'Not assessed')
                ->where('charts.severity.4.count', 1)
                ->where('charts.category.1.value', 'ui_ux')
                ->where('charts.category.1.count', 2)
                ->has('recentBugs', 3)
                ->where('qrCode.target', $project->publicUrl()));
    }

    public function test_deleting_a_project_removes_its_bugs_and_files(): void
    {
        $this->actingAs($this->admin)->post('/admin/projects', $this->validData([
            'status' => 'published',
            'image' => UploadedFile::fake()->image('cover.png'),
        ]));
        $project = Project::firstOrFail();

        $this->post("/project/{$project->project_code}/report-bug", [
            'title' => 'Broken button',
            'description' => 'Nothing happens',
            'screenshot' => UploadedFile::fake()->image('shot.png'),
        ]);
        $bug = Bug::firstOrFail();
        $screenshot = $bug->attachments()->firstOrFail()->file_path;
        $qrPath = app(QrCodeService::class)->path($project);

        Storage::disk('public')->assertExists([$screenshot, $project->image_url, $qrPath]);

        $this->actingAs($this->admin)
            ->delete("/admin/projects/{$project->id}")
            ->assertRedirect('/admin/projects');

        $this->assertModelMissing($project);
        $this->assertModelMissing($bug);
        $this->assertDatabaseCount('bug_attachments', 0);
        Storage::disk('public')->assertMissing([$screenshot, $project->image_url, $qrPath]);
        $this->assertSame(1, AuditLog::where('action', 'project.deleted')->where('entity_id', $project->id)->count());
    }
}
