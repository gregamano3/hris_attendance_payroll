<?php

namespace App\Features\Attendance\RequestLeave;

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\LeaveType;
use App\Features\Attendance\Notifications\RequestSubmitted;
use App\Features\Attendance\Queries\LeaveBalances;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeDirectory;
use App\Shared\Authorization\Permission;
use App\Shared\Notifications\Recipients;
use App\Shared\Security\EncryptedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LeaveRequestsController
{
    public function __construct(
        private EmployeeDirectory $directory,
        private LeaveBalances $balances,
    ) {}

    public function index(Request $request): View
    {
        $employee = $this->directory->forUser($request->user());

        return view('attendance::leaves.index', [
            'employee' => $employee,
            'requests' => $employee
                ? LeaveRequest::query()->with('leaveType')->where('employee_id', $employee->id)->latest('start_date')->paginate(15)
                : null,
            'balances' => $employee ? $this->balances->forEmployee($employee->id, today()->year) : collect(),
            'leaveTypes' => LeaveType::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->employeeOrFail($request);

        $data = $request->validate([
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'day_part' => ['nullable', Rule::in(['full', 'am', 'pm'])],
            'reason' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ]);
        unset($data['attachment']);

        $data['day_part'] ??= 'full';

        if ($data['day_part'] !== 'full' && $data['start_date'] !== $data['end_date']) {
            throw ValidationException::withMessages(['day_part' => 'Half-day leaves cover a single date.']);
        }

        $from = Carbon::parse($data['start_date']);
        $to = Carbon::parse($data['end_date']);
        $days = $this->balances->workingDays($employee->id, $from, $to) * ($data['day_part'] === 'full' ? 1 : 0.5);

        if ($days === 0) {
            throw ValidationException::withMessages(['end_date' => 'The selected dates contain no working days.']);
        }

        $overlaps = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', [LeaveStatus::Pending, LeaveStatus::Approved])
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages(['start_date' => 'You already have a leave request for these dates.']);
        }

        $type = LeaveType::query()->findOrFail($data['leave_type_id']);
        $balance = $this->balances->forEmployee($employee->id, $from->year)->firstWhere('type.id', $type->id);

        if ($balance !== null && $balance['remaining'] !== null && $days > $balance['remaining']) {
            throw ValidationException::withMessages([
                'end_date' => "Only {$balance['remaining']} day(s) of {$type->name} remain this year.",
            ]);
        }

        if ($type->requiresAttachment($days) && ! $request->hasFile('attachment')) {
            throw ValidationException::withMessages(['attachment' => "{$type->name} of more than {$type->attachment_required_after_days} day(s) needs a supporting document (e.g. medical certificate)."]);
        }

        $attachment = [];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = "leave-attachments/{$employee->id}/".Str::uuid().'.enc';
            EncryptedFiles::put('local', $path, (string) file_get_contents($file->getRealPath()));
            $attachment = ['attachment_path' => $path, 'attachment_name' => $file->getClientOriginalName(), 'attachment_mime' => $file->getMimeType()];
        }

        $leave = LeaveRequest::query()->create([
            ...$data,
            ...$attachment,
            'employee_id' => $employee->id,
            'days' => $days,
            'status' => LeaveStatus::Pending,
        ]);

        Notification::send(Recipients::approversFor($employee, Permission::LeavesApprove, $request->user()?->id), new RequestSubmitted($leave));

        return back()->with('success', "Leave request for {$days} day(s) submitted.");
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $employee = $this->employeeOrFail($request);

        abort_unless($leaveRequest->employee_id === $employee->id, 403);

        if ($leaveRequest->status !== LeaveStatus::Pending) {
            return back()->with('error', 'Only pending requests can be cancelled.');
        }

        $leaveRequest->update(['status' => LeaveStatus::Cancelled]);

        return back()->with('success', 'Leave request cancelled.');
    }

    private function employeeOrFail(Request $request): Employee
    {
        return $this->directory->forUser($request->user())
            ?? abort(403, 'Your account is not linked to an employee record.');
    }

    /**
     * Supporting document: the requester, HR approvers or the requester's supervisor.
     */
    public function attachment(Request $request, LeaveRequest $leaveRequest): Response
    {
        $user = $request->user();
        $own = $this->directory->forUser($user)?->id === $leaveRequest->employee_id;
        $approver = $user?->can('leaves.approve') || $this->directory->teamIdsOf($user)->contains($leaveRequest->employee_id);
        abort_unless(($own || $approver) && $leaveRequest->attachment_path, $leaveRequest->attachment_path ? 403 : 404);

        return response(EncryptedFiles::get('local', $leaveRequest->attachment_path), 200, [
            'Content-Type' => $leaveRequest->attachment_mime ?? 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.str_replace('"', '', (string) $leaveRequest->attachment_name).'"',
        ]);
    }
}
