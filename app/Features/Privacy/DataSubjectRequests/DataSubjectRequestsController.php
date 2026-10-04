<?php

namespace App\Features\Privacy\DataSubjectRequests;

use App\Features\Employees\Queries\EmployeeDirectory;
use App\Features\Privacy\Enums\RequestStatus;
use App\Features\Privacy\Enums\RequestType;
use App\Features\Privacy\Models\DataSubjectRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DataSubjectRequestsController
{
    /** The user's own privacy page: export, requests and their status. */
    public function mine(Request $request): View
    {
        return view('privacy::mine', [
            'requests' => DataSubjectRequest::query()->where('user_id', $request->user()?->id)->latest()->get(),
            'types' => RequestType::options(),
        ]);
    }

    public function store(Request $request, EmployeeDirectory $directory): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(RequestType::class)],
            'details' => ['required', 'string', 'max:2000'],
        ]);

        DataSubjectRequest::query()->create([
            ...$data,
            'user_id' => $request->user()?->id,
            'employee_id' => $directory->forUser($request->user())?->id,
            'status' => RequestStatus::Open,
        ]);

        return back()->with('success', 'Your request was sent to the data protection officer.');
    }

    /** Queue for the data protection officer / administrators. */
    public function index(Request $request): View
    {
        $status = RequestStatus::tryFrom($request->string('status')->toString()) ?? RequestStatus::Open;

        return view('privacy::requests', [
            'status' => $status,
            'requests' => DataSubjectRequest::query()->with(['user', 'employee', 'handler'])->where('status', $status)->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function update(Request $request, DataSubjectRequest $dataSubjectRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([RequestStatus::Completed->value, RequestStatus::Rejected->value])],
            'response' => ['required', 'string', 'max:2000'],
        ]);

        $dataSubjectRequest->update([...$data, 'handled_by' => $request->user()?->id, 'handled_at' => now()]);

        return back()->with('success', 'Request updated.');
    }
}
