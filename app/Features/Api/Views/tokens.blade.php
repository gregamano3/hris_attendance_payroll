@extends('layouts.app')

@section('title', 'API tokens')

@section('page')
    @if (session('plain_token'))
        <div class="alert alert-warning" role="alert">
            <p class="mb-2"><strong>Your new token.</strong> Copy it now. For your security it will not be shown again.</p>
            <div class="input-group">
                <input type="text" class="form-control font-monospace" id="plain-token" value="{{ session('plain_token') }}" readonly aria-label="New API token">
                <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('plain-token').value)"><i class="bi bi-clipboard"></i> Copy</button>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-5">
            <form method="post" action="{{ route('account.api-tokens.store') }}" class="card">
                @csrf
                <div class="card-header"><h3 class="card-title">New token</h3></div>
                <div class="card-body row g-3">
                    <x-form.input name="name" label="Name" col="col-12" placeholder="e.g. Mobile app" required />
                    <div class="col-12">
                        <span class="form-label d-block">Scopes</span>
                        @foreach ($abilities as $ability)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="abilities[]" value="{{ $ability->value }}" id="ability-{{ $loop->index }}"
                                    @checked(in_array($ability->value, old('abilities', []), true))>
                                <label class="form-check-label" for="ability-{{ $loop->index }}"><code>{{ $ability->value }}</code> <span class="text-body-secondary small">{{ $ability->description() }}</span></label>
                            </div>
                        @endforeach
                        @error('abilities')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <x-form.select name="expires_in_days" label="Expires in" col="col-12" value="90"
                        :options="collect([30, 90, 180, 365])->filter(fn ($d) => $d <= $maxDays)->mapWithKeys(fn ($d) => [$d => $d.' days'])->all()" />
                </div>
                <div class="card-footer"><button class="btn btn-primary">Create token</button></div>
            </form>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Your tokens</h3>
                    <div class="card-tools"><a href="{{ route('api.v1.openapi') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-filetype-yml me-1"></i> OpenAPI</a></div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 align-middle" id="tokens-table">
                        <thead><tr><th>Name</th><th>Scopes</th><th>Last used</th><th>Expires</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($tokens as $token)
                                <tr>
                                    <td>{{ $token->name }}</td>
                                    <td class="small">{{ implode(', ', $token->abilities) }}</td>
                                    <td class="small">{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                                    <td class="small">{{ $token->expires_at?->format('M j, Y') ?? 'Never' }}</td>
                                    <td class="text-end">
                                        <form method="post" action="{{ route('account.api-tokens.destroy', $token->id) }}">
                                            @csrf @method('delete')
                                            <button class="btn btn-sm btn-outline-danger">Revoke</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-body-secondary py-3">No tokens yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer small text-body-secondary">
                    Send the token as <code>Authorization: Bearer &lt;token&gt;</code> to <code>{{ url('/api/v1') }}</code>.
                    A token can only do what both its scopes and your role allow.
                </div>
            </div>
        </div>
    </div>
@stop
