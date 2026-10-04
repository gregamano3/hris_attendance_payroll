<?php

namespace App\Features\Privacy\AcknowledgeNotice;

use App\Features\Privacy\Models\PrivacyAcknowledgement;
use App\Features\Privacy\Models\PrivacyNotice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signed-in users must acknowledge the current privacy notice once per version.
 */
class RequirePrivacyAcknowledgement
{
    public const CACHE_KEY = 'privacy:current-notice';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $request->routeIs('privacy.notice', 'privacy.notice.acknowledge', 'logout', 'two-factor.*', 'account*')) {
            return $next($request);
        }

        $noticeId = (int) Cache::remember(self::CACHE_KEY, 300, fn (): int => (int) PrivacyNotice::current()?->getKey());

        if ($noticeId !== 0 && ! PrivacyAcknowledgement::query()->where(['privacy_notice_id' => $noticeId, 'user_id' => $user->id])->exists()) {
            if ($request->expectsJson()) {
                abort(403, 'Please acknowledge the privacy notice.');
            }

            return redirect()->guest(route('privacy.notice'));
        }

        return $next($request);
    }
}
