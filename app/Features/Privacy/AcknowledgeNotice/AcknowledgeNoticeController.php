<?php

namespace App\Features\Privacy\AcknowledgeNotice;

use App\Features\Privacy\Models\PrivacyAcknowledgement;
use App\Features\Privacy\Models\PrivacyNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcknowledgeNoticeController
{
    public function show(Request $request): View
    {
        $notice = PrivacyNotice::current();

        return view('privacy::notice', [
            'notice' => $notice,
            'acknowledged' => $notice !== null && PrivacyAcknowledgement::query()->where(['privacy_notice_id' => $notice->id, 'user_id' => $request->user()?->id])->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $notice = PrivacyNotice::current() ?? abort(404);

        PrivacyAcknowledgement::query()->firstOrCreate(
            ['privacy_notice_id' => $notice->id, 'user_id' => $request->user()?->id],
            ['ip_address' => $request->ip(), 'acknowledged_at' => now()],
        );

        return redirect()->intended(route('dashboard'))->with('success', 'Thank you for acknowledging the privacy notice.');
    }
}
