<?php $layoutHelper = app('JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper'); ?>

<?php
    $dashboard_url = View::getSection('dashboard_url') ?? config('adminlte.dashboard_url', 'home');
    $dashboard_url = $layoutHelper->makeUrl($dashboard_url);

    $logoImg = asset(config('adminlte.logo_img', 'vendor/adminlte/dist/assets/img/AdminLTELogo.png'));
    $logoImgAlt = config('adminlte.logo_img_alt', 'Admin Logo');
    $logoImgClass = config('adminlte.logo_img_class', 'brand-image opacity-75 shadow');
    $logoText = config('adminlte.logo', '<b>Admin</b>LTE');
    $brandClass = config('adminlte.classes_brand', '');
    $brandTextClass = config('adminlte.classes_brand_text', 'fw-light');
?>

<?php if($layoutHelper->isLayoutTopnavEnabled()): ?>

    
    <a href="<?php echo e($dashboard_url); ?>" class="navbar-brand d-flex align-items-center <?php echo e($brandClass); ?>">

        
        <img src="<?php echo e($logoImg); ?>" alt="<?php echo e($logoImgAlt); ?>" width="30" height="30"
             class="<?php echo e($logoImgClass); ?> me-2">

        
        <span class="<?php echo e($brandTextClass); ?>"><?php echo $logoText; ?></span>

    </a>

<?php else: ?>

    
    <div class="sidebar-brand">
        <a href="<?php echo e($dashboard_url); ?>" class="brand-link <?php echo e($brandClass); ?>">

            
            <img src="<?php echo e($logoImg); ?>" alt="<?php echo e($logoImgAlt); ?>" class="<?php echo e($logoImgClass); ?>">

            
            <span class="brand-text <?php echo e($brandTextClass); ?>"><?php echo $logoText; ?></span>

        </a>
    </div>

<?php endif; ?>
<?php /**PATH /var/www/html/vendor/jeroennoten/laravel-adminlte/src/../resources/views/partials/common/brand-logo-xs.blade.php ENDPATH**/ ?>