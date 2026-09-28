<?php

namespace Tests\Feature;

use App\Models\Bug;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_home_and_project_list_show_published_and_closed_projects_but_not_drafts(): void
    {
        Project::factory()->published()->create(['name' => 'Published One']);
        Project::factory()->closed()->create(['name' => 'Closed One']);
        Project::factory()->draft()->create(['name' => 'Secret Draft']);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Home')
                ->has('projects', 2)
                ->where('projects.0.name', 'Published One')
                ->where('projects.1.name', 'Closed One'));

        $this->get('/projects')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Projects/Index')
                ->has('projects.data', 2))
            ->assertDontSee('Secret Draft');
    }

    public function test_projects_can_be_searched(): void
    {
        Project::factory()->create(['name' => 'Student Portfolio AI', 'project_code' => 'PORTFOLIO-AI']);
        Project::factory()->create(['name' => 'Library Booking', 'project_code' => 'LIBRARY']);

        $this->get('/projects?search=portfolio')
            ->assertInertia(fn (Assert $page) => $page
                ->has('projects.data', 1)
                ->where('projects.data.0.project_code', 'PORTFOLIO-AI')
                ->where('filters.search', 'portfolio'));
    }

    public function test_project_page_shows_details_stats_qr_code_and_bug_board(): void
    {
        $project = Project::factory()->published()->create([
            'project_code' => 'PORTFOLIO-AI',
            'name' => 'Student Portfolio AI',
            'demo_url' => 'https://demo.example.com',
            'repository_url' => 'https://github.com/example/repo',
            'technology_stack' => 'Laravel, React, PostgreSQL',
            'presentation_date' => '2026-09-28',
        ]);
        Bug::factory()->for($project)->create();
        Bug::factory()->for($project)->verified()->create();
        Bug::factory()->for($project)->verified()->create(['status' => 'fixed']);
        Bug::factory()->for($project)->verified()->create(['status' => 'closed']);
        Bug::factory()->create(); // belongs to another project

        $this->get('/project/PORTFOLIO-AI')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Projects/Show')
                ->where('project.name', 'Student Portfolio AI')
                ->where('project.demo_url', 'https://demo.example.com')
                ->where('project.repository_url', 'https://github.com/example/repo')
                ->where('project.technologies', ['Laravel', 'React', 'PostgreSQL'])
                ->where('project.presentation_date', '2026-09-28')
                ->where('project.public_url', 'http://localhost:8080/project/PORTFOLIO-AI')
                ->where('stats.total', 4)
                ->where('stats.verified', 3)
                ->where('stats.fixed', 2)
                ->has('bugs.data', 4)
                ->where('qrCodeUrl', fn (?string $url) => str_contains((string) $url, "/storage/qr-codes/project-{$project->id}.svg")));
    }

    public function test_project_codes_in_urls_are_case_insensitive(): void
    {
        Project::factory()->create(['project_code' => 'PORTFOLIO-AI']);

        $this->get('/project/portfolio-ai')->assertOk();
        $this->get('/project/Portfolio-Ai/report-bug')->assertOk();
    }

    public function test_draft_projects_are_hidden_from_the_public(): void
    {
        $project = Project::factory()->draft()->create(['project_code' => 'DRAFT-ONE']);
        $bug = Bug::factory()->for($project)->create();

        $this->get('/project/DRAFT-ONE')->assertNotFound();
        $this->get('/project/DRAFT-ONE/report-bug')->assertNotFound();
        $this->post('/project/DRAFT-ONE/report-bug', ['title' => 'x', 'description' => 'y'])->assertNotFound();
        $this->get("/bug/{$bug->bug_code}")->assertNotFound();
        $this->get('/project/DOES-NOT-EXIST')->assertNotFound();
    }

    public function test_admins_can_preview_draft_projects(): void
    {
        Project::factory()->draft()->create(['project_code' => 'DRAFT-ONE']);

        $this->actingAs($this->admin())
            ->get('/project/DRAFT-ONE')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('project.status', 'draft')
                ->where('qrCodeUrl', null));
    }

    public function test_closed_projects_stay_visible_but_do_not_accept_reports(): void
    {
        $project = Project::factory()->closed()->create(['project_code' => 'CLOSED-ONE']);
        $bug = Bug::factory()->for($project)->create();

        $this->get('/project/CLOSED-ONE')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('project.status', 'closed')->has('bugs.data', 1));
        $this->get("/bug/{$bug->bug_code}")->assertOk();

        $this->get('/project/CLOSED-ONE/report-bug')
            ->assertRedirect('/project/CLOSED-ONE')
            ->assertInertiaFlash('error');
    }

    public function test_public_bug_board_searches_by_bug_id_and_title(): void
    {
        $project = Project::factory()->create(['project_code' => 'BOARD']);
        $login = Bug::factory()->for($project)->create(['title' => 'Login button not working']);
        Bug::factory()->for($project)->create(['title' => 'Mobile layout broken', 'description' => 'login mentioned only here']);

        $this->get('/project/BOARD?search=login')
            ->assertInertia(fn (Assert $page) => $page
                ->has('bugs.data', 1)
                ->where('bugs.data.0.bug_code', $login->bug_code));

        $this->get('/project/BOARD?search='.strtolower($login->bug_code))
            ->assertInertia(fn (Assert $page) => $page
                ->has('bugs.data', 1)
                ->where('bugs.data.0.title', 'Login button not working'));
    }

    public function test_public_bug_board_filters_by_status_severity_and_verification(): void
    {
        $project = Project::factory()->create(['project_code' => 'BOARD']);
        Bug::factory()->for($project)->create();
        $match = Bug::factory()->for($project)->assessed()->create(['status' => 'in_progress', 'severity' => 'critical']);
        Bug::factory()->for($project)->assessed()->create(['status' => 'in_progress', 'severity' => 'low']);

        $this->get('/project/BOARD?status=in_progress&severity=critical&verification=verified')
            ->assertInertia(fn (Assert $page) => $page
                ->has('bugs.data', 1)
                ->where('bugs.data.0.bug_code', $match->bug_code)
                ->where('filters.status', 'in_progress')
                ->where('filters.severity', 'critical')
                ->where('filters.verification', 'verified'));

        $this->get('/project/BOARD?verification=pending')
            ->assertInertia(fn (Assert $page) => $page->has('bugs.data', 1));
    }

    public function test_invalid_filter_values_are_ignored(): void
    {
        $project = Project::factory()->create(['project_code' => 'BOARD']);
        Bug::factory()->count(2)->for($project)->create();

        $this->get('/project/BOARD?status=nonsense&severity[]=high&verification=')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('bugs.data', 2)
                ->where('filters.status', null)
                ->where('filters.severity', null));
    }

    public function test_bug_board_is_paginated_twenty_per_page(): void
    {
        $project = Project::factory()->create(['project_code' => 'BOARD']);
        Bug::factory()->count(25)->for($project)->create();

        $this->get('/project/BOARD')
            ->assertInertia(fn (Assert $page) => $page
                ->has('bugs.data', 20)
                ->where('bugs.meta.total', 25)
                ->where('bugs.meta.last_page', 2));

        $this->get('/project/BOARD?page=2')
            ->assertInertia(fn (Assert $page) => $page->has('bugs.data', 5));
    }

    public function test_public_bug_detail_page(): void
    {
        $project = Project::factory()->create(['project_code' => 'DETAIL', 'name' => 'Detail Project']);
        $bug = Bug::factory()->for($project)->assessed()->create([
            'title' => 'Login button not working',
            'steps_to_reproduce' => 'Tap login',
        ]);

        $this->get("/bug/{$bug->bug_code}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Bugs/Show')
                ->where('bug.bug_code', $bug->bug_code)
                ->where('bug.title', 'Login button not working')
                ->where('bug.severity', 'high')
                ->where('bug.priority', 'high')
                ->where('bug.score', 8)
                ->where('bug.status', 'open')
                ->where('bug.verification_status', 'verified')
                ->where('bug.steps_to_reproduce', 'Tap login')
                ->where('bug.project.name', 'Detail Project')
                ->has('bug.created_at')
                ->has('bug.updated_at'));

        $this->get('/bug/BUG-99999')->assertNotFound();
    }
}
