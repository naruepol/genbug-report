<?php

namespace Tests\Feature;

use App\Models\Bug;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScreenshotUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Project::factory()->published()->create(['project_code' => 'SHOTS']);
    }

    private function report(?UploadedFile $screenshot)
    {
        return $this->post('/project/SHOTS/report-bug', [
            'title' => 'Layout overlaps',
            'description' => 'See the screenshot.',
            'screenshot' => $screenshot,
        ]);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function acceptedImages(): array
    {
        return [
            'png' => ['screen.png', 'image/png'],
            'jpg' => ['screen.jpg', 'image/jpeg'],
            'jpeg' => ['screen.jpeg', 'image/jpeg'],
            'webp' => ['screen.webp', 'image/webp'],
        ];
    }

    #[DataProvider('acceptedImages')]
    public function test_supported_images_are_stored_as_attachments(string $name, string $mime): void
    {
        $this->report(UploadedFile::fake()->image($name, 640, 480))->assertSessionHasNoErrors();

        $bug = Bug::firstOrFail();
        $attachment = $bug->attachments()->firstOrFail();

        $this->assertSame($name, $attachment->file_name);
        $this->assertSame($mime, $attachment->mime_type);
        $this->assertGreaterThan(0, $attachment->file_size);
        $this->assertStringStartsWith("bugs/{$bug->id}/", $attachment->file_path);
        Storage::disk('public')->assertExists($attachment->file_path);
    }

    public function test_a_screenshot_is_optional(): void
    {
        $this->report(null)->assertSessionHasNoErrors();

        $this->assertSame(1, Bug::count());
        $this->assertDatabaseCount('bug_attachments', 0);
    }

    public function test_screenshots_up_to_five_megabytes_are_accepted(): void
    {
        $this->report(UploadedFile::fake()->image('max.png')->size(5120))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('bug_attachments', 1);
    }

    public function test_screenshots_larger_than_five_megabytes_are_rejected(): void
    {
        $this->report(UploadedFile::fake()->image('huge.png')->size(5121))
            ->assertSessionHasErrors(['screenshot' => 'The screenshot may not be larger than 5 MB.']);

        $this->assertSame(0, Bug::count());
        $this->assertSame([], Storage::disk('public')->allFiles('bugs'));
    }

    /**
     * @return array<string, array{0: UploadedFile}>
     */
    public static function rejectedFiles(): array
    {
        return [
            'pdf' => [UploadedFile::fake()->create('report.pdf', 100, 'application/pdf')],
            'gif' => [UploadedFile::fake()->image('animation.gif')],
            'svg' => [UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')],
            'text' => [UploadedFile::fake()->createWithContent('notes.txt', 'plain text')],
        ];
    }

    #[DataProvider('rejectedFiles')]
    public function test_other_file_types_are_rejected(UploadedFile $file): void
    {
        $this->report($file)->assertSessionHasErrors('screenshot');

        $this->assertSame(0, Bug::count());
    }

    public function test_file_content_is_checked_not_only_the_extension(): void
    {
        // A real (non-fake) upload, so the MIME type is detected from the content like in production.
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, '<html><body><script>alert(1)</script></body></html>');
        $file = new UploadedFile($path, 'innocent-looking.png', 'image/png', null, true);

        $this->report($file)->assertSessionHasErrors('screenshot');

        $this->assertSame(0, Bug::count());
        @unlink($path);
    }

    public function test_screenshot_is_shown_on_the_public_bug_page(): void
    {
        $this->report(UploadedFile::fake()->image('screen.png'));
        $bug = Bug::firstOrFail();
        $attachment = $bug->attachments()->firstOrFail();

        $this->get("/bug/{$bug->bug_code}")
            ->assertInertia(fn (Assert $page) => $page
                ->has('bug.screenshots', 1)
                ->where('bug.screenshots.0.url', "/storage/{$attachment->file_path}")
                ->where('bug.screenshots.0.mime_type', 'image/png'));
    }

    public function test_admin_sees_screenshot_details(): void
    {
        $this->report(UploadedFile::fake()->image('screen.png'));
        $bug = Bug::firstOrFail();

        $this->actingAs($this->admin())
            ->get("/admin/bugs/{$bug->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('bug.screenshots.0.file_name', 'screen.png')
                ->has('bug.screenshots.0.file_size'));
    }

    public function test_screenshot_file_is_deleted_with_the_bug(): void
    {
        $this->report(UploadedFile::fake()->image('screen.png'));
        $bug = Bug::firstOrFail();
        $path = $bug->attachments()->firstOrFail()->file_path;

        $bug->delete();

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseCount('bug_attachments', 0);
    }
}
