<?php $layoutHelper = app('JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper'); ?>
<?php $preloaderHelper = app('JeroenNoten\LaravelAdminLte\Helpers\PreloaderHelper'); ?>

<?php $__env->startSection('classes_body', $layoutHelper->makeBodyClasses()); ?>

<?php $__env->startSection('body_data', $layoutHelper->makeBodyData()); ?>

<?php
    // The footer is rendered when a 'footer' section is available, or when the
    // fixed footer layout is enabled (the layout reserves the related space).

    $fixedFooter = $layoutHelper->isFixedFooterEnabled();
?>

<?php $__env->startSection('body'); ?>
    
    <div class="skip-links">
        <a href="#main" class="skip-link"><?php echo e(__('adminlte::adminlte.skip_to_content')); ?></a>
        <a href="#navigation" class="skip-link"><?php echo e(__('adminlte::adminlte.skip_to_navigation')); ?></a>
    </div>

    <div class="<?php echo e($layoutHelper->makeWrapperClasses()); ?>">

        
        <?php if($preloaderHelper->isPreloaderEnabled()): ?>
            <?php echo $__env->make('adminlte::partials.common.preloader', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>

        
        <?php if($layoutHelper->isLayoutTopnavEnabled()): ?>
            <?php echo $__env->make('adminlte::partials.navbar.navbar-layout-topnav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php else: ?>
            <?php echo $__env->make('adminlte::partials.navbar.navbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>

        
        <?php if(!$layoutHelper->isLayoutTopnavEnabled()): ?>
            <?php echo $__env->make('adminlte::partials.sidebar.left-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>

        
        <?php if(empty($iFrameEnabled)): ?>
            <?php echo $__env->make('adminlte::partials.cwrapper.cwrapper-default', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php else: ?>
            <?php echo $__env->make('adminlte::partials.cwrapper.cwrapper-iframe', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>

        
        <?php if($fixedFooter || View::hasSection('footer')): ?>
            <?php echo $__env->make('adminlte::partials.footer.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>

        
        <?php if($layoutHelper->isRightSidebarEnabled()): ?>
            <?php echo $__env->make('adminlte::partials.sidebar.right-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>

    </div>
<?php $__env->stopSection(); ?>



<?php $__env->startSection('adminlte_css'); ?>
    <?php echo $__env->yieldPushContent('css'); ?>
    <?php echo $__env->yieldContent('css'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('adminlte_js'); ?>
    <?php echo $__env->yieldPushContent('js'); ?>
    <?php echo $__env->yieldContent('js'); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('adminlte::master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/vendor/jeroennoten/laravel-adminlte/src/../resources/views/page.blade.php ENDPATH**/ ?>