<?php

namespace App\Services;

use App\Models\Project;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Generates the QR code that points to a project's public page (/project/{project_code}).
 *
 * Files are stored on the public disk so they can be shown on pages, downloaded,
 * and used in slides or posters.
 */
class QrCodeService
{
    public const FORMATS = ['png', 'svg'];

    public function generate(Project $project): void
    {
        $qrCode = new QrCode(
            data: $project->publicUrl(),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 800,
            margin: 32,
        );

        $this->disk()->put($this->path($project, 'png'), (new PngWriter)->write($qrCode)->getString());
        $this->disk()->put($this->path($project, 'svg'), (new SvgWriter)->write($qrCode)->getString());
    }

    /**
     * Generate the files if they are missing (e.g. seeded data or cleared storage).
     */
    public function ensure(Project $project): void
    {
        foreach (self::FORMATS as $format) {
            if (! $this->disk()->exists($this->path($project, $format))) {
                $this->generate($project);

                return;
            }
        }
    }

    public function exists(Project $project, string $format = 'png'): bool
    {
        return $this->disk()->exists($this->path($project, $format));
    }

    /**
     * Public URL with a cache-busting version so regenerated codes show up immediately.
     */
    public function url(Project $project, string $format = 'svg'): ?string
    {
        $path = $this->path($project, $format);

        if (! $this->disk()->exists($path)) {
            return null;
        }

        return $this->disk()->url($path).'?v='.$this->disk()->lastModified($path);
    }

    public function path(Project $project, string $format = 'png'): string
    {
        return "qr-codes/project-{$project->getKey()}.{$format}";
    }

    public function downloadName(Project $project, string $format = 'png'): string
    {
        return "{$project->project_code}-qr-code.{$format}";
    }

    public function delete(Project $project): void
    {
        $this->disk()->delete(array_map(fn (string $format) => $this->path($project, $format), self::FORMATS));
    }

    private function disk(): Filesystem
    {
        return Storage::disk('public');
    }
}
