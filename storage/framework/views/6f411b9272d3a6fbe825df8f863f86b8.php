<?php $layoutHelper = app('JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper'); ?>


<?php
    $umUser = Auth::user();

    $umValueOf = static function ($method) use ($umUser) {
        return is_object($umUser) && method_exists($umUser, $method)
            ? $umUser->{$method}()
            : null;
    };

    $umName = $umUser->name ?? '';
    $umImage = config('adminlte.usermenu_image') ? $umValueOf('adminlte_image') : null;
    $umDesc = config('adminlte.usermenu_desc') ? $umValueOf('adminlte_desc') : null;

    $logout_url = View::getSection('logout_url') ?? config('adminlte.logout_url', 'logout');
    $profile_url = View::getSection('profile_url') ?? config('adminlte.profile_url', false);

    if (config('adminlte.usermenu_profile_url', false)) {
        $profile_url = $umValueOf('adminlte_profile_url');
    }

    $profile_url = $layoutHelper->makeUrl($profile_url);
    $logout_url = $layoutHelper->makeUrl($logout_url);
?>


<?php
    $umBsColors = [
        'primary', 'secondary', 'success', 'danger', 'warning', 'info',
        'light', 'dark',
    ];

    $umHeaderCfg = config('adminlte.usermenu_header_class', 'bg-primary');
    $umHeaderClass = collect(preg_split('/\s+/', trim((string) $umHeaderCfg)))
        ->map(function ($class) use ($umBsColors) {
            $color = str_starts_with($class, 'bg-') ? substr($class, 3) : null;

            return in_array($color, $umBsColors, true) ? "text-bg-{$color}" : $class;
        })
        ->filter()
        ->implode(' ');
?>

<li class="nav-item dropdown user-menu">

    
    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"
       aria-expanded="false">
        <?php if($umImage): ?>
            <img src="<?php echo e($umImage); ?>"
                 class="user-image rounded-circle shadow"
                 alt="<?php echo e($umName); ?>">
        <?php endif; ?>
        <span <?php if($umImage): ?> class="d-none d-md-inline" <?php endif; ?>>
            <?php echo e($umName); ?>

        </span>
    </a>

    
    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">

        
        <?php if(!View::hasSection('usermenu_header') && config('adminlte.usermenu_header')): ?>
            <li class="user-header <?php echo e($umHeaderClass); ?>"
                <?php if(! $umImage): ?> style="min-height:auto" <?php endif; ?>>
                <?php if($umImage): ?>
                    <img src="<?php echo e($umImage); ?>"
                         class="rounded-circle shadow"
                         alt="<?php echo e($umName); ?>">
                <?php endif; ?>
                <p class="<?php if(! $umImage): ?> mt-0 <?php endif; ?>">
                    <?php echo e($umName); ?>

                    <?php if($umDesc): ?>
                        <small><?php echo e($umDesc); ?></small>
                    <?php endif; ?>
                </p>
            </li>
        <?php else: ?>
            <?php echo $__env->yieldContent('usermenu_header'); ?>
        <?php endif; ?>

        
        <?php echo $__env->renderEach('adminlte::partials.navbar.dropdown-item', $adminlte->menu("navbar-user"), 'item'); ?>

        
        <?php if (! empty(trim($__env->yieldContent('usermenu_body')))): ?>
            <li class="user-body">
                <?php echo $__env->yieldContent('usermenu_body'); ?>
            </li>
        <?php endif; ?>

        
        <li class="user-footer">
            <?php if($profile_url): ?>
                <a href="<?php echo e($profile_url); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-person me-1"></i>
                    <?php echo e(__('adminlte::menu.profile')); ?>

                </a>
            <?php endif; ?>
            <a class="btn btn-outline-danger float-end <?php if(!$profile_url): ?> w-100 <?php endif; ?>"
               href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="bi bi-power me-1"></i>
                <?php echo e(__('adminlte::adminlte.log_out')); ?>

            </a>
            <form id="logout-form" action="<?php echo e($logout_url); ?>" method="POST" style="display: none;">
                <?php if(config('adminlte.logout_method')): ?>
                    <?php echo e(method_field(config('adminlte.logout_method'))); ?>

                <?php endif; ?>
                <?php echo e(csrf_field()); ?>

            </form>
        </li>

    </ul>

</li>
<?php /**PATH /var/www/html/vendor/jeroennoten/laravel-adminlte/src/../resources/views/partials/navbar/menu-item-dropdown-user-menu.blade.php ENDPATH**/ ?>