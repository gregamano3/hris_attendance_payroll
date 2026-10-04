<div <?php echo e($attributes->merge(['class' => $makeBoxClass()])); ?>>

    
    <div class="inner">
        <?php if(isset($titleSlot) || isset($title)): ?>
            <h3><?php echo e($titleSlot ?? $title); ?></h3>
        <?php endif; ?>

        <?php if(isset($textSlot) || isset($text)): ?>
            <p><?php echo e($textSlot ?? $text); ?></p>
        <?php endif; ?>
    </div>

    
    <?php if(isset($icon)): ?>
        <i class="<?php echo e($makeIconClass()); ?>" aria-hidden="true"></i>
    <?php endif; ?>

    
    <?php if(isset($footerSlot) && isset($url)): ?>
        <a href="<?php echo e($url); ?>"
            class="<?php echo e($makeFooterLinkClass()); ?>"><?php echo e($footerSlot); ?></a>
    <?php elseif(isset($footerSlot)): ?>
        <div class="small-box-footer"><?php echo e($footerSlot); ?></div>
    <?php elseif(isset($url)): ?>
        <a href="<?php echo e($url); ?>"
            class="<?php echo e($makeFooterLinkClass()); ?>">

            <?php if(! empty($urlText)): ?>
                <?php echo e($urlText); ?>

            <?php endif; ?>

            <?php if(! empty($footerIcon)): ?>
                <i class="<?php echo e($footerIcon); ?>" aria-hidden="true"></i>
            <?php endif; ?>
        </a>
    <?php endif; ?>

    
    <div class="<?php echo e($makeOverlayClass()); ?>" style="z-index:20;">
        <div class="spinner-border text-body-secondary" role="status">
            <span class="visually-hidden"><?php echo e(__('adminlte::adminlte.loading')); ?></span>
        </div>
    </div>

</div>



<?php if (! $__env->hasRenderedOnce('9ca17d06-38b9-492d-9752-758547215f2d')): $__env->markAsRenderedOnce('9ca17d06-38b9-492d-9752-758547215f2d'); ?>
<?php $__env->startPush('js'); ?>
<script>

    class _AdminLTE_SmallBox {

        /**
         * Constructor.
         *
         * target: The id of the target small box.
         */
        constructor(target)
        {
            this.target = target;
        }

        /**
         * Update the small box.
         *
         * data: An object with the new data.
         */
        update(data)
        {
            const t = document.getElementById(this.target);

            if (! t || ! data) {
                return;
            }

            if (data.title) {
                const title = t.querySelector('.inner h3');
                if (title) { title.textContent = data.title; }
            }

            if (data.text) {
                const text = t.querySelector('.inner p');
                if (text) { text.textContent = data.text; }
            }

            if (data.icon) {
                const icon = t.querySelector('.small-box-icon');

                if (icon) {
                    icon.className = 'small-box-icon lh-1 ' + data.icon;
                }
            }

            if (data.url) {
                const footer = t.querySelector('a.small-box-footer');
                if (footer) { footer.href = data.url; }
            }
        }

        /**
         * Toggle the loading overlay of the small box.
         */
        toggleLoading()
        {
            const t = document.getElementById(this.target);

            if (! t) {
                return;
            }

            const overlay = t.querySelector('.small-box-overlay');

            if (overlay) {
                overlay.classList.toggle('d-none');
            }
        }
    }

</script>
<?php $__env->stopPush(); ?>
<?php endif; ?>
<?php /**PATH /var/www/html/vendor/jeroennoten/laravel-adminlte/src/../resources/views/components/widget/small-box.blade.php ENDPATH**/ ?>