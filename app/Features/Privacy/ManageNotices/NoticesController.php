<?php

namespace App\Features\Privacy\ManageNotices;

use App\Features\Privacy\AcknowledgeNotice\RequirePrivacyAcknowledgement;
use App\Features\Privacy\Models\PrivacyNotice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Versioned privacy notices. Publishing a new version asks every user to
 * acknowledge it again.
 */
class NoticesController
{
    public function index(): View
    {
        return view('privacy::notices.index', [
            'notices' => PrivacyNotice::query()->withCount('acknowledgements')->latest()->get(),
            'activeUsers' => User::query()->where('is_active', true)->count(),
            'template' => view('privacy::notices.template')->render(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'version' => ['required', 'string', 'max:20', Rule::unique('privacy_notices')],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:50000'],
            'publish' => ['boolean'],
        ]);

        PrivacyNotice::query()->create([
            ...array_diff_key($data, ['publish' => true]),
            'published_at' => $request->boolean('publish') ? now() : null,
            'created_by' => $request->user()?->id,
        ]);
        Cache::forget(RequirePrivacyAcknowledgement::CACHE_KEY);

        return back()->with('success', $request->boolean('publish') ? 'Notice published. Users will be asked to acknowledge it.' : 'Draft saved.');
    }

    public function publish(PrivacyNotice $notice): RedirectResponse
    {
        $notice->update(['published_at' => now()]);
        Cache::forget(RequirePrivacyAcknowledgement::CACHE_KEY);

        return back()->with('success', "Version {$notice->version} published.");
    }
}
