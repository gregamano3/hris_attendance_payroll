<?php

namespace App\Shared\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Records created / updated / deleted events of a model in audit_logs with
 * the acting user and the changed values. Secrets are never stored.
 *
 * @mixin Model
 */
trait Auditable
{
    /** @var list<string> Attributes that are never written to the audit log. */
    private static array $auditRedacted = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    /** @var list<string> Bookkeeping attributes that are not worth auditing. */
    private static array $auditIgnored = ['created_at', 'updated_at', 'last_login_at'];

    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => self::writeAudit($model, 'created', null, $model->getAttributes()));

        static::updated(function (Model $model) {
            $changes = $model->getChanges();
            $old = array_intersect_key($model->getRawOriginal(), $changes);

            self::writeAudit($model, 'updated', $old, $changes);
        });

        static::deleted(fn (Model $model) => self::writeAudit($model, 'deleted', $model->getRawOriginal(), null));
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    private static function writeAudit(Model $model, string $event, ?array $old, ?array $new): void
    {
        $clean = function (?array $values): ?array {
            if ($values === null) {
                return null;
            }

            $values = array_diff_key($values, array_flip(self::$auditIgnored));

            foreach (self::$auditRedacted as $key) {
                if (array_key_exists($key, $values)) {
                    $values[$key] = '[redacted]';
                }
            }

            return $values;
        };

        $old = $clean($old);
        $new = $clean($new);

        if ($event === 'updated' && empty($new)) {
            return;
        }

        // Queue workers, seeders and commands have no routed request.
        $httpRequest = request()->route() instanceof Route;

        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $httpRequest ? Request::ip() : null,
            'url' => $httpRequest ? mb_substr(Request::fullUrl(), 0, 500) : 'console',
        ]);
    }
}
