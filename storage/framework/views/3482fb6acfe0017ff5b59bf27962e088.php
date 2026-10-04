<?php $__env->startSection('auth_header'); ?>
    <?php echo e(__('adminlte::adminlte.login_message')); ?>


    <?php if(session('status')): ?>
        <div class="alert alert-success small mt-3 mb-0" role="alert"><?php echo e(session('status')); ?></div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('adminlte::auth.login', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Auth/Views/login.blade.php ENDPATH**/ ?>