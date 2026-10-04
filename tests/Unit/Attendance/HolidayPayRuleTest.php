<?php

use App\Features\Attendance\Compute\HolidayPayRule;
use App\Features\Attendance\Enums\AttendanceStatus;

it('applies the holiday pay eligibility rule', function (?AttendanceStatus $previous, bool $paidLeave, bool $eligible) {
    expect(HolidayPayRule::isEligible($previous, $paidLeave))->toBe($eligible);
})->with([
    'present' => [AttendanceStatus::Present, false, true],
    'incomplete punches' => [AttendanceStatus::Incomplete, false, true],
    'paid leave' => [AttendanceStatus::OnLeave, true, true],
    'unpaid leave' => [AttendanceStatus::OnLeave, false, false],
    'absent' => [AttendanceStatus::Absent, false, false],
    'no previous work day' => [null, false, true],
]);
