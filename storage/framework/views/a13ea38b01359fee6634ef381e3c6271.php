<?php $layoutHelper = app('JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper'); ?>

<?php
    $authType = $authType ?? 'login';
    $dashboardUrl = View::getSection('dashboard_url') ?? config('adminlte.dashboard_url', 'home');
    $dashboardUrl = $layoutHelper->makeUrl($dashboardUrl);

    // The AdminLTE v4 authentication pages center their content by using the
    // '{login,register}-page' body class. The RTL direction and the color mode
    // are already setup on the <html> tag by the master layout.

    $bodyClasses = "{$authType}-page bg-body-secondary";
?>

<?php $__env->startSection('adminlte_css'); ?>
    <?php echo $__env->yieldPushContent('css'); ?>
    <?php echo $__env->yieldContent('css'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('classes_body'); ?><?php echo e($bodyClasses); ?><?php $__env->stopSection(); ?>

<?php $__env->startSection('body'); ?>
    <main class="<?php echo e($authType); ?>-box">

        
        <h1 class="<?php echo e($authType); ?>-logo">
            <a href="<?php echo e($dashboardUrl); ?>">

                
                <?php if(config('adminlte.auth_logo.enabled', false)): ?>
                    <img src="<?php echo e(asset(config('adminlte.auth_logo.img.path'))); ?>"
                         alt="<?php echo e(config('adminlte.auth_logo.img.alt')); ?>"
                         <?php if(config('adminlte.auth_logo.img.class', null)): ?>
                            class="<?php echo e(config('adminlte.auth_logo.img.class')); ?>"
                         <?php endif; ?>
                         <?php if(config('adminlte.auth_logo.img.width', null)): ?>
                            width="<?php echo e(config('adminlte.auth_logo.img.width')); ?>"
                         <?php endif; ?>
                         <?php if(config('adminlte.auth_logo.img.height', null)): ?>
                            height="<?php echo e(config('adminlte.auth_logo.img.height')); ?>"
                         <?php endif; ?>>
                <?php else: ?>
                    <img src="<?php echo e(asset(config('adminlte.logo_img'))); ?>"
                         alt="<?php echo e(config('adminlte.logo_img_alt')); ?>" height="50">
                <?php endif; ?>

                
                <?php echo config('adminlte.logo', '<b>Admin</b>LTE'); ?>


            </a>
        </h1>

        
        <div class="card <?php echo e(config('adminlte.classes_auth_card', 'card-outline card-primary')); ?>">

            
            <?php if (! empty(trim($__env->yieldContent('auth_header')))): ?>
                <div class="card-header <?php echo e(config('adminlte.classes_auth_header', '')); ?>">
                    <h3 class="card-title float-none text-center">
                        <?php echo $__env->yieldContent('auth_header'); ?>
                    </h3>
                </div>
            <?php endif; ?>

            
            <div class="card-body <?php echo e($authType); ?>-card-body <?php echo e(config('adminlte.classes_auth_body', '')); ?>">
                <?php echo $__env->yieldContent('auth_body'); ?>
            </div>

            
            <?php if (! empty(trim($__env->yieldContent('auth_footer')))): ?>
                <div class="card-footer <?php echo e(config('adminlte.classes_auth_footer', '')); ?>">
                    <?php echo $__env->yieldContent('auth_footer'); ?>
                </div>
            <?php endif; ?>

        </div>

    </main>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('adminlte_js'); ?>
    <?php echo $__env->yieldPushContent('js'); ?>
    <?php echo $__env->yieldContent('js'); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('adminlte::master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/vendor/jeroennoten/laravel-adminlte/src/../resources/views/auth/auth-page.blade.php ENDPATH**/ ?>