<?php

namespace App\Features\Attendance\RequestLeave;

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\LeaveType;
use App\Features\Attendance\Notifications\RequestSubmitted;
use App\Features\Attendance\Queries\LeaveBalances;
use App\Features\Employees\Models\Employee;
use App\Shared\Authorization\Permission;
use App\Shared\Notifications\Recipients;
use App\Shared\Security\EncryptedFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Validates and files an employee's leave request (web form or API).
 */
class SubmitLeaveRequest
{
    public function __construct(private LeaveBalances $balances) {}

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function handle(Employee $employee, array $input, ?UploadedFile $attachment, ?int $userId): LeaveRequest
    {
        $data = Validator::make([...$input, 'attachment' => $attachment], [
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'day_part' => ['nullable', Rule::in(['full', 'am', 'pm'])],
            'reason' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ])->validate();
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

        if ($type->requiresAttachment($days) && $attachment === null) {
            throw ValidationException::withMessages(['attachment' => "{$type->name} of more than {$type->attachment_required_after_days} day(s) needs a supporting document (e.g. medical certificate)."]);
        }

        $stored = [];

        if ($attachment !== null) {
            $path = "leave-attachments/{$employee->id}/".Str::uuid().'.enc';
            EncryptedFiles::put('local', $path, (string) file_get_contents($attachment->getRealPath()));
            $stored = ['attachment_path' => $path, 'attachment_name' => $attachment->getClientOriginalName(), 'attachment_mime' => $attachment->getMimeType()];
        }

        $leave = LeaveRequest::query()->create([
            ...$data,
            ...$stored,
            'employee_id' => $employee->id,
            'days' => $days,
            'status' => LeaveStatus::Pending,
        ]);

        Notification::send(Recipients::approversFor($employee, Permission::LeavesApprove, $userId), new RequestSubmitted($leave));

        return $leave;
    }
}
