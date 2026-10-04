

<?php if($dropdownMode): ?>

    

    <li class="nav-item dropdown adminlte-darkmode-widget">

        <a class="nav-link" href="#" id="adminlte-color-mode" role="button"
           data-bs-toggle="dropdown" aria-expanded="false"
           aria-label="<?php echo e(__('adminlte::adminlte.toggle_color_mode')); ?>">

            <i class="<?php echo e(implode(' ', $makeIconDisabledClasses())); ?> <?php if($currentColorMode() !== 'light'): ?> d-none <?php endif; ?>"
               data-lte-theme-icon="light"></i>

            <i class="<?php echo e(implode(' ', $makeIconEnabledClasses())); ?> <?php if($currentColorMode() !== 'dark'): ?> d-none <?php endif; ?>"
               data-lte-theme-icon="dark"></i>

            <i class="<?php echo e(implode(' ', $makeIconAutoClasses())); ?> <?php if($currentColorMode() !== 'auto'): ?> d-none <?php endif; ?>"
               data-lte-theme-icon="auto"></i>

        </a>

        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminlte-color-mode"
            style="--bs-dropdown-min-width: 8rem;">

            <li>
                <button type="button" class="dropdown-item d-flex align-items-center <?php if($currentColorMode() === 'light'): ?> active <?php endif; ?>"
                        data-bs-theme-value="light" aria-pressed="<?php echo e($currentColorMode() === 'light' ? 'true' : 'false'); ?>">
                    <i class="<?php echo e(implode(' ', $makeIconDisabledClasses())); ?> me-2"></i>
                    <?php echo e(__('adminlte::adminlte.color_mode_light')); ?>

                    <i class="bi bi-check-lg ms-auto d-none"></i>
                </button>
            </li>

            <li>
                <button type="button" class="dropdown-item d-flex align-items-center <?php if($currentColorMode() === 'dark'): ?> active <?php endif; ?>"
                        data-bs-theme-value="dark" aria-pressed="<?php echo e($currentColorMode() === 'dark' ? 'true' : 'false'); ?>">
                    <i class="<?php echo e(implode(' ', $makeIconEnabledClasses())); ?> me-2"></i>
                    <?php echo e(__('adminlte::adminlte.color_mode_dark')); ?>

                    <i class="bi bi-check-lg ms-auto d-none"></i>
                </button>
            </li>

            <li>
                <button type="button" class="dropdown-item d-flex align-items-center <?php if($currentColorMode() === 'auto'): ?> active <?php endif; ?>"
                        data-bs-theme-value="auto" aria-pressed="<?php echo e($currentColorMode() === 'auto' ? 'true' : 'false'); ?>">
                    <i class="<?php echo e(implode(' ', $makeIconAutoClasses())); ?> me-2"></i>
                    <?php echo e(__('adminlte::adminlte.color_mode_auto')); ?>

                    <i class="bi bi-check-lg ms-auto d-none"></i>
                </button>
            </li>

        </ul>

    </li>

<?php else: ?>

    

    <li class="nav-item adminlte-darkmode-widget">

        <a class="nav-link" href="#" role="button"
           aria-label="<?php echo e(__('adminlte::adminlte.toggle_color_mode')); ?>">
            <i class="<?php echo e($makeIconClass()); ?>"></i>
        </a>

    </li>

    <?php if (! $__env->hasRenderedOnce('adb9810d-09b4-4321-8f8c-7c7c0bde41e7')): $__env->markAsRenderedOnce('adb9810d-09b4-4321-8f8c-7c7c0bde41e7'); ?>
    <?php $__env->startPush('js'); ?>
    <script>

        window._AdminLTE_Ready(() => {

            const root = document.documentElement;
            const widget = document.querySelector('li.adminlte-darkmode-widget');
            const widgetIcon = widget.querySelector('i');

            // Get the set of classes to be toggled on the widget icon.

            const iconClasses = [
                ...<?php echo json_encode($makeIconEnabledClasses(), 15, 512) ?>,
                ...<?php echo json_encode($makeIconDisabledClasses(), 15, 512) ?>
            ];

            // Add 'click' event listener for the darkmode widget.

            widget.addEventListener('click', (event) => {

                event.preventDefault();

                // Toggle the color mode on the html element (AdminLTE v4 uses
                // the Bootstrap 5.3 native color modes).

                const isDark = root.getAttribute('data-bs-theme') === 'dark';
                const newMode = isDark ? 'light' : 'dark';

                root.setAttribute('data-bs-theme', newMode);
                root.style.colorScheme = newMode;

                // Toggle the classes on the widget icon.

                iconClasses.forEach((c) => widgetIcon.classList.toggle(c));

                // Notify the server to persist the dark mode preference.

                fetch("<?php echo e(route('adminlte.darkmode.toggle')); ?>", {
                    headers: {'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'},
                    method: 'POST',
                })
                .catch((error) => {
                    console.log(
                        'Failed to notify server that dark mode was toggled',
                        error
                    );
                });
            });

        });

    </script>
    <?php $__env->stopPush(); ?>
    <?php endif; ?>

<?php endif; ?>
<?php /**PATH /var/www/html/vendor/jeroennoten/laravel-adminlte/src/../resources/views/components/layout/navbar-darkmode-widget.blade.php ENDPATH**/ ?>