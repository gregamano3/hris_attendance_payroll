<?php

namespace App\Features\Attendance\ManageDevices;

use App\Features\Attendance\Models\AttendanceDevice;
use App\Features\Employees\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DevicesController
{
    public function index(): View
    {
        return view('attendance::devices.index', [
            'devices' => AttendanceDevice::query()->with('branch')->orderBy('name')->get(),
            'branches' => Branch::query()->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
        ]);

        $device = AttendanceDevice::query()->create([...$data, 'token_hash' => hash('sha256', uniqid('', true))]);

        return back()->with('device_token', $device->issueToken())->with('success', "Device {$device->name} registered. Copy its token now; it won't be shown again.");
    }

    public function regenerate(AttendanceDevice $device): RedirectResponse
    {
        return back()->with('device_token', $device->issueToken())->with('success', "New token for {$device->name}. The old token stopped working.");
    }

    public function toggle(AttendanceDevice $device): RedirectResponse
    {
        $device->update(['is_active' => ! $device->is_active]);

        return back()->with('success', $device->is_active ? 'Device enabled.' : 'Device disabled.');
    }
}
