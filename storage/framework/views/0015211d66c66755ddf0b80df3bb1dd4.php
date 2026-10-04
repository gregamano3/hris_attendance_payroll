<li <?php if(isset($item['id'])): ?> id="<?php echo e($item['id']); ?>" <?php endif; ?> class="nav-item">

    <a class="nav-link <?php echo e($item['class']); ?> <?php if(isset($item['shift'])): ?> <?php echo e($item['shift']); ?> <?php endif; ?>"
       href="<?php echo e($item['href']); ?>" <?php if(isset($item['target'])): ?> target="<?php echo e($item['target']); ?>" <?php endif; ?>
       <?php echo $item['data-compiled'] ?? ''; ?>>

        <i class="nav-icon <?php echo e($item['icon'] ?? 'bi bi-circle'); ?> <?php echo e(isset($item['icon_color']) ? 'text-'.$item['icon_color'] : ''); ?>"></i>

        <p>
            <?php echo e($item['text']); ?>


            <?php if(isset($item['label'])): ?>
                <span class="nav-badge badge text-bg-<?php echo e($item['label_color'] ?? 'primary'); ?> me-3">
                    <?php echo e($item['label']); ?>

                </span>
            <?php endif; ?>
        </p>

    </a>

</li>
<?php /**PATH /var/www/html/vendor/jeroennoten/laravel-adminlte/src/../resources/views/partials/sidebar/menu-item-link.blade.php ENDPATH**/ ?>