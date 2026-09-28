<?php

namespace Tests\Feature;

use App\Models\Bug;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Critical privacy requirement (spec §46): public responses must never contain
 * reporter_name, reporter_email or admin_note (nor the reporter's IP address) —
 * not in the project bug list, the bug detail, the Inertia props, or JSON responses.
 */
class BugPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private const PRIVATE_KEYS = ['reporter_name', 'reporter_email', 'reporter_ip', 'admin_note'];

    private const SECRET_NAME = 'Somchai Secretname';

    private const SECRET_EMAIL = 'secret.reporter@example.com';

    private const SECRET_NOTE = 'Internal admin note: reporter is a staff member';

    private const SECRET_IP = '198.51.100.23';

    private Project $project;

    private Bug $bug;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->project = Project::factory()->published()->create(['project_code' => 'PRIVATE-TEST']);
        $this->bug = Bug::factory()->for($this->project)->assessed()->create([
            'title' => 'Checkout button is broken',
            'reporter_name' => self::SECRET_NAME,
            'reporter_email' => self::SECRET_EMAIL,
            'reporter_ip' => self::SECRET_IP,
            'admin_note' => self::SECRET_NOTE,
        ]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function publicPages(): array
    {
        return [
            'home' => ['/'],
            'project list' => ['/projects'],
            'project bug list' => ['/project/PRIVATE-TEST'],
            'filtered bug list' => ['/project/PRIVATE-TEST?search=checkout&verification=verified'],
            'bug detail' => ['/bug/{bug}'],
            'report form' => ['/project/PRIVATE-TEST/report-bug'],
        ];
    }

    private function url(string $uri): string
    {
        return str_replace('{bug}', $this->bug->bug_code, $uri);
    }

    #[DataProvider('publicPages')]
    public function test_first_page_load_html_contains_no_reporter_identity_or_admin_note(string $uri): void
    {
        $response = $this->get($this->url($uri))->assertOk();

        $this->assertNoSecrets($response->getContent());
    }

    #[DataProvider('publicPages')]
    public function test_inertia_props_contain_no_private_keys(string $uri): void
    {
        $props = $this->get($this->url($uri))->assertOk()->inertiaProps();

        $this->assertNoPrivateKeys($props);
        $this->assertNoSecrets(json_encode($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    #[DataProvider('publicPages')]
    public function test_inertia_json_responses_contain_no_private_keys(string $uri): void
    {
        $response = $this->inertiaGet($this->url($uri));

        $response->assertOk()->assertHeader('X-Inertia', 'true');
        $this->assertNoPrivateKeys($response->json('props'));
        $this->assertNoSecrets($response->getContent());
    }

    #[DataProvider('publicPages')]
    public function test_json_requests_contain_no_private_keys(string $uri): void
    {
        $response = $this->getJson($this->url($uri))->assertOk();

        $this->assertNoSecrets($response->getContent());
    }

    public function test_public_bug_list_items_use_only_whitelisted_fields(): void
    {
        $this->get('/project/PRIVATE-TEST')->assertInertia(fn (Assert $page) => $page
            ->has('bugs.data.0', fn (Assert $bug) => $bug
                ->where('bug_code', $this->bug->bug_code)
                ->where('title', 'Checkout button is broken')
                ->missing('reporter_name')
                ->missing('reporter_email')
                ->missing('reporter_ip')
                ->missing('admin_note')
                ->etc()));
    }

    public function test_public_bug_detail_uses_only_whitelisted_fields(): void
    {
        $this->get("/bug/{$this->bug->bug_code}")->assertInertia(fn (Assert $page) => $page
            ->component('Public/Bugs/Show')
            ->has('bug', fn (Assert $bug) => $bug
                ->where('bug_code', $this->bug->bug_code)
                ->missing('reporter_name')
                ->missing('reporter_email')
                ->missing('reporter_ip')
                ->missing('admin_note')
                ->missing('id')
                ->etc()));
    }

    public function test_screenshot_original_file_name_is_not_exposed_publicly(): void
    {
        $this->post('/project/PRIVATE-TEST/report-bug', [
            'title' => 'Bug with screenshot',
            'description' => 'See attached',
            'screenshot' => UploadedFile::fake()->image('somchai-secretname-iphone.png'),
        ]);
        $bug = Bug::latest('id')->firstOrFail();

        $response = $this->get("/bug/{$bug->bug_code}")->assertOk();

        $this->assertStringNotContainsString('somchai-secretname-iphone', $response->getContent());
        $response->assertInertia(fn (Assert $page) => $page
            ->has('bug.screenshots', 1)
            ->missing('bug.screenshots.0.file_name'));
    }

    public function test_a_signed_in_admin_still_gets_the_public_shape_on_public_pages(): void
    {
        $response = $this->actingAs($this->admin())->get("/bug/{$this->bug->bug_code}")->assertOk();

        $this->assertNoPrivateKeys($response->inertiaProps('bug'));
        $this->assertNoSecrets($response->getContent());
    }

    public function test_admin_bug_detail_does_include_reporter_information(): void
    {
        // Positive control: the private data exists and is shown where it belongs.
        $this->actingAs($this->admin())
            ->get("/admin/bugs/{$this->bug->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('bug.reporter_name', self::SECRET_NAME)
                ->where('bug.reporter_email', self::SECRET_EMAIL)
                ->where('bug.reporter_ip', self::SECRET_IP)
                ->where('bug.admin_note', self::SECRET_NOTE));
    }

    public function test_serialized_bug_models_hide_private_fields_by_default(): void
    {
        $array = $this->bug->fresh()->toArray();

        foreach (self::PRIVATE_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $array);
        }
    }

    private function assertNoPrivateKeys(mixed $data): void
    {
        $this->assertIsArray($data);

        foreach (array_keys(Arr::dot($data)) as $path) {
            foreach (self::PRIVATE_KEYS as $key) {
                $this->assertDoesNotMatchRegularExpression(
                    '/(^|\.)'.preg_quote($key, '/').'($|\.)/',
                    (string) $path,
                    "Private field [{$key}] found in public props at [{$path}].",
                );
            }
        }
    }

    private function assertNoSecrets(string|false $content): void
    {
        $this->assertIsString($content);

        foreach ([self::SECRET_NAME, self::SECRET_EMAIL, self::SECRET_NOTE, self::SECRET_IP] as $secret) {
            $this->assertStringNotContainsString($secret, $content);
            $this->assertStringNotContainsString(json_encode($secret), $content);
        }

        foreach (self::PRIVATE_KEYS as $key) {
            $this->assertStringNotContainsString($key, $content);
        }
    }
}
