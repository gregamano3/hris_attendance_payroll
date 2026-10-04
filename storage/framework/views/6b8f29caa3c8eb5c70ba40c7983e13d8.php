<?php $layoutHelper = app('JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper'); ?>
<?php $assetHelper = app('JeroenNoten\LaravelAdminLte\Helpers\AssetHelper'); ?>

<?php
    $isRtlEnabled = $layoutHelper->isRtlEnabled();

    // A configuration that is not a set of plugins is ignored, so a wrong
    // value does not break every page of the panel.

    $plugins = config('adminlte.plugins', []);
    $plugins = is_array($plugins) ? $plugins : [];
?>

<?php $__currentLoopData = $plugins; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pluginName => $plugin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

    

    <?php
        $plugSection = View::getSection('plugins.' . ($plugin['name'] ?? $pluginName));
        $isPlugActive = ! empty($plugin['active'])
            ? ! isset($plugSection) || $plugSection
            : ! empty($plugSection);
    ?>

    

    <?php if($isPlugActive): ?>
        <?php $__currentLoopData = $plugin['files'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

            

            <?php
                $location = $file['location'] ?? null;

                if ($isRtlEnabled && ! empty($file['rtl'])) {
                    $location = $file['rtl'];
                }

                if (! empty($file['asset'])) {
                    $location = asset($location);
                }

                // A plugin may point to an asset of the AdminLTE distribution,
                // so the version placeholder is resolved here too.

                $location = $assetHelper->applyVersion($location);
            ?>

            

            <?php if(! empty($location) && ($file['type'] ?? null) === $type): ?>
                <?php if($type === 'css'): ?>
                    <link rel="stylesheet" href="<?php echo e($location); ?>">
                <?php elseif($type === 'js'): ?>
                    <script src="<?php echo e($location); ?>" <?php if(! empty($file['defer'])): ?> defer <?php endif; ?>></script>
                <?php endif; ?>
            <?php endif; ?>

        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH /var/www/html/vendor/jeroennoten/laravel-adminlte/src/../resources/views/plugins.blade.php ENDPATH**/ ?>