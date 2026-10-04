<?php

namespace App\Features\Users\ListUsers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListUsersController
{
    public function __invoke(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $users = User::query()
            ->with('roles')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->whereLike('name', "%{$search}%", caseSensitive: false)
                    ->orWhereLike('email', "%{$search}%", caseSensitive: false);
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users::index', compact('users', 'search'));
    }
}
