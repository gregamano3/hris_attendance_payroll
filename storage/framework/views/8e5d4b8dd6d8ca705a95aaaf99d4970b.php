<?php use App\Features\Attendance\Enums\LeaveStatus; ?>


<?php $__env->startSection('title', 'Leave approvals'); ?>

<?php $__env->startSection('page'); ?>
    <ul class="nav nav-tabs mb-3">
        <?php $__currentLoopData = LeaveStatus::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $case): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="nav-item">
                <a class="nav-link <?php if($case === $status): ?> active <?php endif; ?>" href="<?php echo e(route('leaves.review', ['status' => $case->value])); ?>"><?php echo e($case->label()); ?></a>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead>
                    <tr><th>Employee</th><th>Type</th><th>Dates</th><th class="text-end">Days</th><th>Reason</th>
                        <?php if($status === LeaveStatus::Pending): ?><th>Decision</th><?php else: ?><th>Reviewed by</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $leave): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($leave->employee->full_name); ?></td>
                            <td><?php echo e($leave->leaveType->name); ?></td>
                            <td class="text-nowrap"><?php echo e($leave->start_date->format('M j')); ?> – <?php echo e($leave->end_date->format('M j, Y')); ?></td>
                            <td class="text-end"><?php echo e((float) $leave->days); ?></td>
                            <td class="small"><?php echo e($leave->reason); ?></td>
                            <td>
                                <?php if($status === LeaveStatus::Pending): ?>
                                    <form method="post" action="<?php echo e(route('leaves.review.update', $leave)); ?>" class="d-flex gap-1">
                                        <?php echo csrf_field(); ?> <?php echo method_field('patch'); ?>
                                        <input type="text" name="review_remarks" class="form-control form-control-sm" placeholder="Remarks" aria-label="Remarks">
                                        <button name="decision" value="approved" class="btn btn-sm btn-success">Approve</button>
                                        <button name="decision" value="rejected" class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <?php echo e($leave->reviewer?->name ?? '—'); ?>

                                    <div class="small text-body-secondary"><?php echo e($leave->review_remarks); ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">Nothing here.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($requests->hasPages()): ?>
            <div class="card-footer"><?php echo e($requests->links()); ?></div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Attendance/Views/leaves/review.blade.php ENDPATH**/ ?>