<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Fillable(['file_name', 'file_path', 'mime_type', 'file_size'])]
class BugAttachment extends Model
{
    public const UPDATED_AT = null;

    public const DISK = 'public';

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (BugAttachment $attachment) {
            DB::afterCommit(fn () => Storage::disk(self::DISK)->delete($attachment->file_path));
        });
    }

    /**
     * @return BelongsTo<Bug, $this>
     */
    public function bug(): BelongsTo
    {
        return $this->belongsTo(Bug::class);
    }

    public function url(): string
    {
        return Storage::disk(self::DISK)->url($this->file_path);
    }
}
