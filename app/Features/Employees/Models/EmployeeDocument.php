<?php

namespace App\Features\Employees\Models;

use App\Features\Employees\Enums\DocumentCategory;
use App\Models\User;
use App\Shared\Audit\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * 201 file document, stored on the private "local" disk.
 *
 * @property int $id
 * @property int $employee_id
 * @property DocumentCategory $category
 * @property string $title
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property bool $is_encrypted
 * @property int|null $uploaded_by
 * @property-read Employee $employee
 * @property-read User|null $uploader
 */
#[Fillable(['employee_id', 'category', 'title', 'path', 'original_name', 'mime_type', 'size', 'is_encrypted', 'uploaded_by'])]
class EmployeeDocument extends Model
{
    use Auditable;

    public const DISK = 'local';

    protected static function booted(): void
    {
        static::deleted(fn (self $document) => Storage::disk(self::DISK)->delete($document->path));
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['category' => DocumentCategory::class, 'size' => 'integer', 'is_encrypted' => 'boolean'];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function humanSize(): string
    {
        return $this->size >= 1048576 ? round($this->size / 1048576, 1).' MB' : max(1, (int) round($this->size / 1024)).' KB';
    }
}
