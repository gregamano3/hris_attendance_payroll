@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Name</label>
        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
            class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
            class="form-control @error('email') is-invalid @enderror" required>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="password" class="form-label">Password</label>
        <input type="password" id="password" name="password" autocomplete="new-password"
            class="form-control @error('password') is-invalid @enderror" @unless ($user->exists) required @endunless>
        @if ($user->exists)
            <div class="form-text">Leave blank to keep the current password.</div>
        @endif
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">Confirm password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
            class="form-control">
    </div>

    <div class="col-md-6">
        <label for="role" class="form-label">Role</label>
        <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
            <option value="">Select a role…</option>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user->primaryRole()?->value) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check form-switch">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input @error('is_active') is-invalid @enderror" type="checkbox" role="switch"
                id="is_active" name="is_active" value="1" @checked(old('is_active', $user->is_active))>
            <label class="form-check-label" for="is_active">Active</label>
            @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
