<?php $preloaderHelper = app('JeroenNoten\LaravelAdminLte\Helpers\PreloaderHelper'); ?>

<?php
    $isCWrapperMode = $preloaderHelper->isPreloaderEnabled('cwrapper');

    // Setup the set of classes of the preloader overlay. AdminLTE v4 does not
    // provide a '.preloader' style anymore, so the overlay is built with the
    // Bootstrap 5.3 utilities. The legacy '.preloader' class is still emitted
    // by the helper, so custom styles keep working.

    $preloaderClasses = $preloaderHelper->makePreloaderClasses();
    $preloaderClasses .= ' d-flex w-100 h-100 top-0 start-0 bg-body';
    $preloaderClasses .= $isCWrapperMode ? '' : ' position-fixed';

    // Setup the inline styles of the preloader overlay.

    $preloaderStyles = array_filter([
        $preloaderHelper->makePreloaderStyle(),
        $isCWrapperMode ? '' : 'z-index:9999',
        'transition:opacity .3s ease-in-out',
    ]);

    // Setup the animation of the default preloader image. The legacy
    // 'animation__*' effect classes are still emitted, but AdminLTE v4 only
    // provides the underlying keyframes, so they are applied inline.

    $imgEffect = (string) config('adminlte.preloader.img.effect', 'animation__shake');
    $imgKeyframes = null;

    if (preg_match('/^animation__(shake|wobble|flipInX|fadeIn|fadeOut|spin)$/', $imgEffect, $m)) {
        $imgKeyframes = $m[1];
    }

    $imgStyles = ['animation-iteration-count:infinite'];

    if ($imgKeyframes) {
        $imgStyles[] = "animation-name:{$imgKeyframes}";
        $imgStyles[] = 'animation-duration:1s';
    }
?>

<div id="adminlte-preloader" class="<?php echo e($preloaderClasses); ?>" style="<?php echo e(implode(';', $preloaderStyles)); ?>">

    <?php if (! empty(trim($__env->yieldContent('preloader')))): ?>

        
        <?php echo $__env->yieldContent('preloader'); ?>

    <?php else: ?>

        
        <img src="<?php echo e(asset(config('adminlte.preloader.img.path', 'vendor/adminlte/dist/assets/img/AdminLTELogo.png'))); ?>"
             class="rounded-circle <?php echo e($imgEffect); ?>"
             alt="<?php echo e(config('adminlte.preloader.img.alt', 'AdminLTE Preloader Image')); ?>"
             width="<?php echo e(config('adminlte.preloader.img.width', 60)); ?>"
             height="<?php echo e(config('adminlte.preloader.img.height', 60)); ?>"
             style="<?php echo e(implode(';', $imgStyles)); ?>">

    <?php endif; ?>

</div>


<style>
    @media (prefers-reduced-motion: reduce) {
        #adminlte-preloader {
            transition: none !important;
        }

        #adminlte-preloader img {
            animation: none !important;
        }
    }
</style>


<script>
    (() => {
        'use strict';
        const preloader = document.getElementById('adminlte-preloader');

        if (! preloader) {
            return;
        }

        const hidePreloader = () => {
            preloader.style.opacity = '0';
            window.setTimeout(() => preloader.remove(), 300);
        };

        if (document.readyState === 'complete') {
            hidePreloader();
        } else {
            window.addEventListener('load', hidePreloader);
        }
    })();
</script>
<?php /**PATH /var/www/html/vendor/jeroennoten/laravel-adminlte/src/../resources/views/partials/common/preloader.blade.php ENDPATH**/ ?>