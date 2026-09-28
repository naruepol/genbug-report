<?php

namespace Database\Seeders;

use App\Enums\BugCategory;
use App\Enums\BugPriority;
use App\Enums\BugSeverity;
use App\Enums\BugStatus;
use App\Enums\ProjectStatus;
use App\Enums\VerificationStatus;
use App\Models\Project;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Development data: an admin user, sample projects and sample bugs.
 * Safe to run more than once; existing records are updated, bugs are only added to empty projects.
 */
class DatabaseSeeder extends Seeder
{
    public function run(QrCodeService $qrCodes): void
    {
        $adminEmail = config('admin.emails.0', 'admin@example.com');

        User::updateOrCreate(
            ['email' => $adminEmail],
            ['name' => Str::headline(Str::before($adminEmail, '@'))],
        );

        $portfolio = $this->project([
            'project_code' => 'PORTFOLIO-AI',
            'name' => 'Student Portfolio AI',
            'description' => "An AI assistant that helps students build a professional portfolio from their coursework and projects.\nTry creating a portfolio, editing sections and exporting it as PDF.",
            'demo_url' => 'https://example.com/portfolio-ai',
            'repository_url' => 'https://example.com/git/portfolio-ai',
            'technology_stack' => 'Laravel, React, PostgreSQL, OpenAI API',
            'presentation_date' => now()->toDateString(),
            'status' => ProjectStatus::Published,
        ]);

        $library = $this->project([
            'project_code' => 'LIBRARY-BOOKING',
            'name' => 'Smart Library Room Booking',
            'description' => 'Book study rooms in the university library, check availability in real time and get reminders before your booking.',
            'demo_url' => 'https://example.com/library-booking',
            'repository_url' => null,
            'technology_stack' => 'Laravel, Inertia.js, Vue, MySQL',
            'presentation_date' => now()->subDays(2)->toDateString(),
            'status' => ProjectStatus::Published,
        ]);

        $canteen = $this->project([
            'project_code' => 'CANTEEN-QUEUE',
            'name' => 'Canteen Queue Tracker',
            'description' => 'Shows how long the queue is at each canteen shop so students can pick the fastest one.',
            'demo_url' => 'https://example.com/canteen-queue',
            'repository_url' => 'https://example.com/git/canteen-queue',
            'technology_stack' => 'Flutter, Firebase',
            'presentation_date' => now()->subWeeks(3)->toDateString(),
            'status' => ProjectStatus::Closed,
        ]);

        $this->project([
            'project_code' => 'EVENT-HUB',
            'name' => 'Campus Event Hub',
            'description' => 'One place for every club and faculty event, with registration and QR check-in.',
            'demo_url' => null,
            'repository_url' => null,
            'technology_stack' => 'Next.js, Supabase',
            'presentation_date' => now()->addWeek()->toDateString(),
            'status' => ProjectStatus::Draft,
        ]);

        $this->bugs($portfolio, [
            ['Login button not working', BugCategory::Functional, BugSeverity::High, BugPriority::High, 8, VerificationStatus::Verified, BugStatus::InProgress, 'Somchai Jaidee', 'somchai@example.com'],
            ['Mobile layout broken', BugCategory::UiUx, BugSeverity::Medium, BugPriority::Medium, 5, VerificationStatus::Verified, BugStatus::Open, null, null],
            ['Typo on home page', BugCategory::Content, BugSeverity::Low, BugPriority::Low, 2, VerificationStatus::Verified, BugStatus::Fixed, 'Malee', null],
            ['PDF export takes more than 30 seconds', BugCategory::Performance, BugSeverity::High, BugPriority::Medium, 7, VerificationStatus::Verified, BugStatus::Closed, null, 'tester@example.com'],
            ['ปุ่มบันทึกไม่ทำงานบน Safari', BugCategory::Compatibility, null, null, null, VerificationStatus::Pending, BugStatus::Open, 'Anong', 'anong@example.com'],
            ['Profile photo upload fails for large images', BugCategory::Functional, null, null, null, VerificationStatus::Pending, BugStatus::Open, null, null],
            ['Login does not work', BugCategory::Functional, null, null, null, VerificationStatus::Duplicate, BugStatus::Duplicate, null, null],
            ['I do not like the colour', BugCategory::NotSure, null, null, null, VerificationStatus::Rejected, BugStatus::Rejected, null, null],
        ]);

        $this->bugs($library, [
            ['Booking shows the wrong room number', BugCategory::Functional, BugSeverity::Critical, BugPriority::High, 9, VerificationStatus::Verified, BugStatus::InProgress, null, 'student01@example.com'],
            ['Calendar is hard to use on small screens', BugCategory::UiUx, BugSeverity::Medium, BugPriority::Low, 4, VerificationStatus::Verified, BugStatus::Open, null, null],
            ['Reminder email arrives twice', BugCategory::Other, null, null, null, VerificationStatus::Pending, BugStatus::Open, null, null],
        ]);

        $this->bugs($canteen, [
            ['Queue time never updates on Android', BugCategory::Compatibility, BugSeverity::High, BugPriority::High, 7, VerificationStatus::Verified, BugStatus::Fixed, null, null],
        ]);

        foreach ([$portfolio, $library, $canteen] as $project) {
            $qrCodes->generate($project);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function project(array $attributes): Project
    {
        return Project::updateOrCreate(['project_code' => $attributes['project_code']], $attributes);
    }

    /**
     * @param  list<array{0: string, 1: BugCategory, 2: ?BugSeverity, 3: ?BugPriority, 4: ?int, 5: VerificationStatus, 6: BugStatus, 7: ?string, 8: ?string}>  $bugs
     */
    private function bugs(Project $project, array $bugs): void
    {
        if ($project->bugs()->exists()) {
            return;
        }

        foreach ($bugs as [$title, $category, $severity, $priority, $score, $verification, $status, $name, $email]) {
            $project->bugs()->create([
                'title' => $title,
                'description' => "{$title}. Found while trying the demo right after the presentation.",
                'category' => $category,
                'steps_to_reproduce' => "1. Open the demo\n2. Go to the related page\n3. Try the action again",
                'expected_result' => 'The action completes normally.',
                'actual_result' => 'It does not behave as expected.',
                'page_screen' => 'Home',
                'browser' => 'Chrome 140',
                'operating_system' => 'Android 16',
                'device' => 'Mobile',
                'reporter_name' => $name,
                'reporter_email' => $email,
                'reporter_ip' => '203.0.113.'.random_int(1, 254),
                'severity' => $severity,
                'priority' => $priority,
                'score' => $score,
                'verification_status' => $verification,
                'status' => $status,
                'admin_note' => $verification === VerificationStatus::Pending ? null : 'Reviewed during the demo session.',
            ]);
        }
    }
}
