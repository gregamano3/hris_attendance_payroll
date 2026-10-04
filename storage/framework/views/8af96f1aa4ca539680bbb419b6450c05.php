<?php $__env->startSection('content_header'); ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0 fs-3"><?php echo $__env->yieldContent('page_title', View::getSection('title')); ?></h1>
        <div><?php echo $__env->yieldContent('page_actions'); ?></div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->yieldContent('page'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('css'); ?>
    <?php echo app('Illuminate\Foundation\Vite')('resources/css/app.css'); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('js'); ?>
    <?php echo app('Illuminate\Foundation\Vite')('resources/js/app.js'); ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('adminlte::page', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/layouts/app.blade.php ENDPATH**/ ?>