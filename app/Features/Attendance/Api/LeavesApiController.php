<?php

namespace App\Features\Attendance\Api;

use App\Features\Attendance\Enums\LeaveStatus;
use App\Features\Attendance\Models\LeaveRequest;
use App\Features\Attendance\Models\LeaveType;
use App\Features\Attendance\Queries\LeaveBalances;
use App\Features\Attendance\RequestLeave\SubmitLeaveRequest;
use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The caller's own leave requests.
 */
class LeavesApiController
{
    public function __construct(private EmployeeDirectory $directory) {}

    public function index(Request $request): JsonResponse
    {
        $requests = LeaveRequest::query()
            ->with('leaveType')
            ->where('employee_id', $this->employee($request)->id)
            ->latest('start_date')
            ->paginate(25);

        return response()->json([
            'data' => collect($requests->items())->map(fn (LeaveRequest $l) => $this->present($l))->values(),
            'meta' => ['current_page' => $requests->currentPage(), 'last_page' => $requests->lastPage(), 'total' => $requests->total()],
        ]);
    }

    public function types(Request $request, LeaveBalances $balances): JsonResponse
    {
        $remaining = $balances->forEmployee($this->employee($request)->id, today()->year)->mapWithKeys(fn (array $b) => [$b['type']->id => $b['remaining']]);

        return response()->json([
            'data' => LeaveType::query()->orderBy('name')->get()->map(fn (LeaveType $t) => [
                'id' => $t->id,
                'code' => $t->code,
                'name' => $t->name,
                'is_paid' => $t->is_paid,
                'attachment_required_after_days' => $t->attachment_required_after_days,
                'remaining_days' => $remaining[$t->id] ?? null,
            ])->values(),
        ]);
    }

    /**
     * Leaves that need a supporting document must be filed on the web.
     */
    public function store(Request $request, SubmitLeaveRequest $submit): JsonResponse
    {
        $leave = $submit->handle(
            $this->employee($request),
            $request->only(['leave_type_id', 'start_date', 'end_date', 'day_part', 'reason']),
            null,
            $request->user()?->id,
        );

        return response()->json(['data' => $this->present($leave->load('leaveType'))], 201);
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        abort_unless($leaveRequest->employee_id === $this->employee($request)->id, 404);
        abort_unless($leaveRequest->status === LeaveStatus::Pending, 409, 'Only pending requests can be cancelled.');

        $leaveRequest->update(['status' => LeaveStatus::Cancelled]);

        return response()->json(['data' => $this->present($leaveRequest->load('leaveType'))]);
    }

    private function employee(Request $request): Employee
    {
        return $this->directory->forUser($request->user() ?? abort(401))
            ?? abort(403, 'Your account is not linked to an employee record.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(LeaveRequest $leave): array
    {
        return [
            'id' => $leave->id,
            'leave_type' => ['id' => $leave->leaveType->id, 'code' => $leave->leaveType->code, 'name' => $leave->leaveType->name],
            'start_date' => $leave->start_date->toDateString(),
            'end_date' => $leave->end_date->toDateString(),
            'day_part' => $leave->day_part,
            'days' => (float) $leave->days,
            'reason' => $leave->reason,
            'status' => $leave->status->value,
            'review_remarks' => $leave->review_remarks,
            'reviewed_at' => $leave->reviewed_at?->toIso8601String(),
        ];
    }
}
