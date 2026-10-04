<?php $__env->startSection('title', 'Time clock'); ?>

<?php $__env->startSection('page'); ?>
    <?php if(! $employee): ?>
        <div class="callout callout-info">Your account is not linked to an employee record. Please contact Human Resources.</div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-5">
                <div class="card card-primary card-outline text-center">
                    <div class="card-body py-5">
                        <div class="text-body-secondary"><?php echo e(now()->format('l, F j, Y')); ?></div>
                        <div class="display-4 fw-semibold my-3" id="live-clock"><?php echo e(now()->format('g:i:s A')); ?></div>
                        <form method="post" action="<?php echo e(route('attendance.clock.store')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" id="punch-button"
                                class="btn btn-lg px-5 <?php echo e($nextType->value === 'in' ? 'btn-success' : 'btn-danger'); ?>">
                                <i class="bi <?php echo e($nextType->value === 'in' ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right'); ?> me-1"></i>
                                <?php echo e($nextType->value === 'in' ? 'Clock in' : 'Clock out'); ?>

                            </button>
                        </form>
                        <div class="small text-body-secondary mt-3"><?php echo e($employee->full_name); ?> · <?php echo e($employee->employee_no); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Recent punches</h3></div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead><tr><th>Date</th><th>Time</th><th>Type</th><th>Source</th></tr></thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($log->logged_at->format('D, M j')); ?></td>
                                        <td><?php echo e($log->logged_at->format('g:i A')); ?></td>
                                        <td><span class="badge text-bg-<?php echo e($log->type->value === 'in' ? 'success' : 'danger'); ?>"><?php echo e($log->type->label()); ?></span></td>
                                        <td><?php echo e($log->source->label()); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="4" class="text-center text-body-secondary py-4">No punches yet today.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('js'); ?>
    <script>
        setInterval(() => {
            const el = document.getElementById('live-clock');
            if (el) el.textContent = new Date().toLocaleTimeString('en-PH', { timeZone: <?php echo json_encode(config('app.timezone'), 15, 512) ?> });
        }, 1000);
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Attendance/Views/clock.blade.php ENDPATH**/ ?>