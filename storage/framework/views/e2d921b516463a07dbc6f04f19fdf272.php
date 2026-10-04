<?php $__env->startSection('title', 'My attendance'); ?>

<?php $__env->startSection('page'); ?>
    <?php if(! $employee): ?>
        <div class="callout callout-info">Your account is not linked to an employee record. Please contact Human Resources.</div>
    <?php else: ?>
        <form method="get" class="card card-body row g-2 flex-row align-items-end mb-3">
            <?php echo $__env->make('attendance::_period-filter', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="col-md-2 d-grid"><button class="btn btn-outline-secondary">Show</button></div>
        </form>
        <h2 class="fs-5 mb-3"><?php echo e($period->label()); ?></h2>
        <?php echo $__env->make('attendance::_dtr-table', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Attendance/Views/my-attendance.blade.php ENDPATH**/ ?>