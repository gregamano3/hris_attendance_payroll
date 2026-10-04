<?php $__env->startSection('title', 'New employee'); ?>

<?php $__env->startSection('page'); ?>
    <form method="post" action="<?php echo e(route('employees.store')); ?>">
        <?php echo $__env->make('employees::employees._form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary">Create employee</button>
            <a href="<?php echo e(route('employees.index')); ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Employees/Views/employees/create.blade.php ENDPATH**/ ?>