<?php use App\Shared\Format; ?>

<div class="row">
    <?php $__currentLoopData = [
        ['Present', $totals['days_present'], 'success'],
        ['Absent', $totals['days_absent'], 'danger'],
        ['On leave', $totals['days_on_leave'], 'primary'],
        ['Late (h:mm)', Format::minutes($totals['late_minutes']), 'warning'],
        ['Undertime (h:mm)', Format::minutes($totals['undertime_minutes']), 'warning'],
        ['Overtime (h:mm)', Format::minutes($totals['regular_ot_minutes'] + $totals['rest_day_ot_minutes'] + $totals['special_holiday_ot_minutes'] + $totals['regular_holiday_ot_minutes']), 'info'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $theme]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-6 col-md-2">
            <div class="info-box mb-3">
                <span class="info-box-icon text-bg-<?php echo e($theme); ?>"><i class="bi bi-calendar3"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text"><?php echo e($label); ?></span>
                    <span class="info-box-number"><?php echo e($value); ?></span>
                </div>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="card">
    <div class="card-body p-0 table-responsive">
        <table class="table table-sm table-striped table-hover mb-0 align-middle" id="dtr-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Shift</th>
                    <th>In</th>
                    <th>Out</th>
                    <th class="text-end">Worked</th>
                    <th class="text-end">Late</th>
                    <th class="text-end">UT</th>
                    <th class="text-end">OT</th>
                    <th class="text-end">ND</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $days; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="<?php echo \Illuminate\Support\Arr::toCssClasses(['table-secondary' => $day->is_rest_day]); ?>">
                        <td class="text-nowrap"><?php echo e($day->date->format('D, M j')); ?></td>
                        <td class="small text-body-secondary"><?php echo e($day->shift?->name ?? '—'); ?></td>
                        <td><?php echo e($day->time_in?->format('g:i A') ?? '—'); ?></td>
                        <td><?php echo e($day->time_out?->format('g:i A') ?? '—'); ?></td>
                        <td class="text-end"><?php echo e(Format::minutes($day->worked_minutes)); ?></td>
                        <td class="text-end"><?php echo e(Format::minutes($day->late_minutes)); ?></td>
                        <td class="text-end"><?php echo e(Format::minutes($day->undertime_minutes)); ?></td>
                        <td class="text-end"><?php echo e(Format::minutes($day->overtime_minutes)); ?></td>
                        <td class="text-end"><?php echo e(Format::minutes($day->night_diff_minutes)); ?></td>
                        <td class="text-nowrap">
                            <span class="badge text-bg-<?php echo e($day->status->badge()); ?>"><?php echo e($day->status->label()); ?></span>
                            <?php if($day->holiday_type): ?>
                                <span class="badge text-bg-<?php echo e($day->holiday_type->badge()); ?>"><?php echo e($day->holiday_type->label()); ?></span>
                            <?php endif; ?>
                            <?php if($day->leaveRequest): ?>
                                <span class="badge text-bg-light border"><?php echo e($day->leaveRequest->leaveType->code); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="10" class="text-center text-body-secondary py-4">No records for this period.</td></tr>
                <?php endif; ?>
            </tbody>
            <?php if($days->isNotEmpty()): ?>
                <tfoot class="fw-semibold">
                    <tr>
                        <td colspan="4">Totals</td>
                        <td class="text-end"><?php echo e(Format::minutes($totals['worked_minutes'])); ?></td>
                        <td class="text-end"><?php echo e(Format::minutes($totals['late_minutes'])); ?></td>
                        <td class="text-end"><?php echo e(Format::minutes($totals['undertime_minutes'])); ?></td>
                        <td class="text-end"><?php echo e(Format::minutes($days->sum('overtime_minutes'))); ?></td>
                        <td class="text-end"><?php echo e(Format::minutes($totals['night_diff_minutes'])); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php /**PATH /var/www/html/app/Features/Attendance/Views/_dtr-table.blade.php ENDPATH**/ ?>