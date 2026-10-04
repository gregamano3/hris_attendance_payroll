<?php $__env->startSection('title', 'Daily time records'); ?>

<?php $__env->startSection('page'); ?>
    <form method="get" class="card card-body row g-2 flex-row align-items-end mb-3">
        <div class="col-md-4">
            <label for="employee" class="form-label small mb-1">Employee</label>
            <select id="employee" name="employee" class="form-select" required>
                <option value="">Select an employee…</option>
                <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($option->id); ?>" <?php if($employee?->id === $option->id): echo 'selected'; endif; ?>><?php echo e($option->employee_no); ?> — <?php echo e($option->full_name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <?php echo $__env->make('attendance::_period-filter', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="col-md-2 d-grid"><button class="btn btn-primary">Show</button></div>
    </form>

    <?php if($employee): ?>
        <div class="d-flex justify-content-between align-items-baseline mb-3">
            <h2 class="fs-5 mb-0"><?php echo e($employee->full_name); ?> <small class="text-body-secondary"><?php echo e($employee->employee_no); ?></small></h2>
            <span class="text-body-secondary"><?php echo e($period->label()); ?></span>
        </div>
        <?php echo $__env->make('attendance::_dtr-table', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Attendance/Views/dtr.blade.php ENDPATH**/ ?>