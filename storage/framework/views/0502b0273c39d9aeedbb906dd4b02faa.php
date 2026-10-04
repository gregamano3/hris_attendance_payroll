<?php $layoutHelper = app('JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper'); ?>
<?php $preloaderHelper = app('JeroenNoten\LaravelAdminLte\Helpers\PreloaderHelper'); ?>

<?php
    // On the topnav layout the content uses the same container as the navbar,
    // otherwise the brand and the content would not share their left edge.

    $def_container_class = $layoutHelper->isLayoutTopnavEnabled()
        ? config('adminlte.classes_topnav_container', 'container-fluid')
        : 'container-fluid';
?>


<main class="<?php echo e($layoutHelper->makeContentWrapperClasses()); ?>">

    
    <?php if($preloaderHelper->isPreloaderEnabled('cwrapper')): ?>
        <?php echo $__env->make('adminlte::partials.common.preloader', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>

    
    <?php if (! empty(trim($__env->yieldContent('content_top_area')))): ?>
        <div class="app-content-top-area">
            <div class="<?php echo e(config('adminlte.classes_content_top_area') ?: $def_container_class); ?>">
                <?php echo $__env->yieldContent('content_top_area'); ?>
            </div>
        </div>
    <?php endif; ?>

    
    <?php if (! empty(trim($__env->yieldContent('content_header')))): ?>
        <div class="app-content-header">
            <div class="<?php echo e(config('adminlte.classes_content_header') ?: $def_container_class); ?>">
                <?php echo $__env->yieldContent('content_header'); ?>
            </div>
        </div>
    <?php endif; ?>

    
    <div class="app-content <?php if (! (View::hasSection('content_header') || View::hasSection('content_top_area'))): ?> pt-3 <?php endif; ?>">
        <div class="<?php echo e(config('adminlte.classes_content') ?: $def_container_class); ?>">
            <?php echo $__env->yieldPushContent('content'); ?>
            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </div>

    
    <?php if (! empty(trim($__env->yieldContent('content_bottom_area')))): ?>
        <div class="app-content-bottom-area">
            <div class="<?php echo e(config('adminlte.classes_content_bottom_area') ?: $def_container_class); ?>">
                <?php echo $__env->yieldContent('content_bottom_area'); ?>
            </div>
        </div>
    <?php endif; ?>

</main>
<?php /**PATH /var/www/html/vendor/jeroennoten/laravel-adminlte/src/../resources/views/partials/cwrapper/cwrapper-default.blade.php ENDPATH**/ ?>