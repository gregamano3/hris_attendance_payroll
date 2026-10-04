<?php $__env->startSection('title', 'My profile'); ?>

<?php $__env->startSection('page'); ?>
    <?php if($employee): ?>
        <?php echo $__env->make('employees::employees._details', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php else: ?>
        <div class="callout callout-info">
            Your user account is not linked to an employee record yet. Please contact Human Resources.
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Employees/Views/my-profile.blade.php ENDPATH**/ ?>