<?php

namespace App\Features\Api\ManageTokens;

use App\Features\Api\ApiAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Personal access tokens for the REST API. The plain token is shown once.
 */
class ApiTokensController
{
    private const MAX_TOKENS = 10;

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_if($user === null, 403);

        return view('api::tokens', [
            'tokens' => $user->tokens()->latest()->get(),
            'abilities' => ApiAbility::grantableBy($user),
            'maxDays' => (int) config('hris.api.max_token_days'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => [Rule::in(array_map(fn (ApiAbility $a) => $a->value, ApiAbility::grantableBy($user)))],
            'expires_in_days' => ['required', 'integer', 'min:1', 'max:'.(int) config('hris.api.max_token_days')],
        ]);

        if ($user->tokens()->count() >= self::MAX_TOKENS) {
            return back()->with('error', 'You already have '.self::MAX_TOKENS.' tokens. Revoke one first.');
        }

        $token = $user->createToken($data['name'], array_values(array_unique($data['abilities'])), now()->addDays((int) $data['expires_in_days']));

        return to_route('account.api-tokens')
            ->with('plain_token', $token->plainTextToken)
            ->with('success', 'Token created. Copy it now; it will not be shown again.');
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $deleted = $request->user()?->tokens()->whereKey($token)->delete();
        abort_unless($deleted, 404);

        return back()->with('success', 'Token revoked.');
    }
}
