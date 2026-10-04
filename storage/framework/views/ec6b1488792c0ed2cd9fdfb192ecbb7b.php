

<?php
    // Only well formed custom property names are accepted, and a value that
    // could break out of the declaration is dropped, so a configuration value
    // cannot inject arbitrary CSS.

    $cssVarsSanitize = function ($cfg) {
        $vars = [];

        if (! is_array($cfg)) {
            return $vars;
        }

        foreach ($cfg as $name => $value) {
            $name = is_string($name) ? trim($name) : '';
            $value = is_scalar($value) ? trim((string) $value) : '';

            if (! preg_match('/^--[a-zA-Z0-9_-]+$/', $name)) {
                continue;
            }

            if ($value === '' || preg_match('/[;{}<>\\\\]|\/\*|@import|expression\s*\(/i', $value)) {
                continue;
            }

            $vars[$name] = $value;
        }

        return $vars;
    };

    $cssVars = $cssVarsSanitize(config('adminlte.css_variables', []));
    $cssVarsSidebar = $cssVarsSanitize(config('adminlte.css_variables_sidebar', []));

    $cssVarsScope = config('adminlte.css_variables_scope', ':root');
    $cssVarsScope = in_array($cssVarsScope, [':root', 'body'], true)
        ? $cssVarsScope
        : ':root';

    // AdminLTE redeclares the sidebar properties on the sidebar element under
    // a color mode selector, which beats any ':root' declaration. So, the
    // sidebar block matches that specificity and comes later on the document.

    $cssVarsSidebarScope = '[data-bs-theme] .app-sidebar, [data-bs-theme].app-sidebar, .app-sidebar';
?>

<?php if(! empty($cssVars) || ! empty($cssVarsSidebar)): ?>
    <style>
        <?php if(! empty($cssVars)): ?>
            <?php echo e($cssVarsScope); ?> {
                <?php $__currentLoopData = $cssVars; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cssVarName => $cssVarValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php echo e($cssVarName); ?>: <?php echo e($cssVarValue); ?>;
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            }
        <?php endif; ?>

        <?php if(! empty($cssVarsSidebar)): ?>
            <?php echo e($cssVarsSidebarScope); ?> {
                <?php $__currentLoopData = $cssVarsSidebar; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cssVarName => $cssVarValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php echo e($cssVarName); ?>: <?php echo e($cssVarValue); ?>;
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            }
        <?php endif; ?>
    </style>
<?php endif; ?>
<?php /**PATH /var/www/html/vendor/jeroennoten/laravel-adminlte/src/../resources/views/partials/common/css-variables.blade.php ENDPATH**/ ?>