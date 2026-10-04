<?php $layoutHelper = app('JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper'); ?>
<?php $sidebarItemHelper = app('JeroenNoten\LaravelAdminLte\Helpers\SidebarItemHelper'); ?>

<?php
    // The HTML id of the sidebar menu. The sidebar search box targets it.
    $sidebarMenuId = 'adminlte-sidebar-menu';

    // In AdminLTE v4, the search box lives outside of the scrollable menu
    // wrapper. So, we split the search items from the regular menu items.
    $sidebarMenu = $adminlte->menu('sidebar');

    $sidebarSearchItems = array_filter($sidebarMenu, function ($item) use ($sidebarItemHelper) {
        return $sidebarItemHelper->isSearch($item);
    });

    $sidebarMenuItems = array_filter($sidebarMenu, function ($item) use ($sidebarItemHelper) {
        return ! $sidebarItemHelper->isSearch($item);
    });
?>

<aside class="<?php echo e($layoutHelper->makeSidebarWrapperClasses()); ?>" <?php echo $layoutHelper->makeSidebarData(); ?>>

    
    <?php if(config('adminlte.logo_img_xl')): ?>
        <?php echo $__env->make('adminlte::partials.common.brand-logo-xl', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php else: ?>
        <?php echo $__env->make('adminlte::partials.common.brand-logo-xs', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>

    
    <?php $__currentLoopData = $sidebarSearchItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php echo $__env->make('adminlte::partials.sidebar.menu-item', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    
    <div class="sidebar-wrapper">
        
        <nav id="navigation"
            aria-label="<?php echo e(config('adminlte.sidebar_nav_aria_label') ?: __('adminlte::adminlte.main_navigation')); ?>"
            class="mt-2">
            <ul class="<?php echo e($layoutHelper->makeSidebarNavClasses()); ?>"
                id="<?php echo e($sidebarMenuId); ?>"
                data-lte-toggle="treeview"
                <?php echo $layoutHelper->makeSidebarNavData(); ?>>
                
                <?php echo $__env->renderEach('adminlte::partials.sidebar.menu-item', $sidebarMenuItems, 'item'); ?>
            </ul>
        </nav>
    </div>

</aside>
<?php /**PATH /var/www/html/vendor/jeroennoten/laravel-adminlte/src/../resources/views/partials/sidebar/left-sidebar.blade.php ENDPATH**/ ?>