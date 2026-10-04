<?php $layoutHelper = app('JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper'); ?>
<?php $assetHelper = app('JeroenNoten\LaravelAdminLte\Helpers\AssetHelper'); ?>

<?php
    // Resolve the Laravel asset bundling mode.

    $bundling = config('adminlte.laravel_asset_bundling', false);

    // When the user bundles the assets on its own, the AdminLTE core stylesheet
    // and script are expected to be part of the generated bundle.

    $bundlesAdminlte = in_array($bundling, ['mix', 'vite', 'vite_js_only'], true);

    // Resolve the location of every base asset. A null value means the asset
    // is disabled on the configuration file.

    $fontsCss = $assetHelper->fontsCss();
    $overlayScrollbarsCss = $assetHelper->overlayScrollbarsCss();
    $bootstrapIconsCss = $assetHelper->bootstrapIconsCss();
    $adminlteCss = $bundlesAdminlte ? null : $assetHelper->adminlteCss();
    $colorsCss = $bundlesAdminlte ? null : $assetHelper->colorsCss();

    $overlayScrollbarsJs = $assetHelper->overlayScrollbarsJs();
    $bootstrapJs = $assetHelper->bootstrapJs();
    $adminlteJs = $bundlesAdminlte ? null : $assetHelper->adminlteJs();

    // Bridge the Livewire navigation events to the AdminLTE lifecycle. The
    // template binds Turbo on its own, but it knows nothing about Livewire.

    $spaNavigation = config('adminlte.spa_navigation', true) !== false;

    // Resolve the color mode setup. The 'authored' color mode is the one the
    // page declares by itself, the 'auto' mode declares nothing and lets the
    // client resolve the mode from the OS preference.

    $colorMode = $layoutHelper->getColorMode();
    $authoredColorMode = $colorMode === 'auto' ? null : $colorMode;
    $rememberColorMode = (bool) config('adminlte.color_mode.remember', true);

    // The OverlayScrollbars setup only makes sense when there is a sidebar.

    $setupScrollbars = $overlayScrollbarsJs && ! $layoutHelper->isLayoutTopnavEnabled();

    // Extra options for the OverlayScrollbars instance of the sidebar. They
    // are merged into the 'scrollbars' object of its setup, so they may also
    // override the ones the dedicated options above resolve.

    $scrollbarOptions = config('adminlte.sidebar_scrollbar_options', []);

    $scrollbarExtraOptions = is_array($scrollbarOptions) && ! empty($scrollbarOptions)
        ? "\n".str_repeat(' ', 32).'...'.json_encode(
            $scrollbarOptions,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ).','
        : '';

    // The viewport width (in pixels) at or below which the OverlayScrollbars
    // instance is not created, so touch scrolling is not disturbed.

    $scrollbarDisableBelow = config('adminlte.sidebar_scrollbar_disable_below', 992);

    $scrollbarDisableBelow = is_numeric($scrollbarDisableBelow)
        ? 0 + $scrollbarDisableBelow
        : 992;

    // The theme (a class name) of the sidebar scrollbars. OverlayScrollbars
    // expects a string here, anything else would be dropped by the plugin
    // together with the rest of its 'scrollbars' options.

    $scrollbarTheme = config('adminlte.sidebar_scrollbar_theme', 'os-theme-light');

    $scrollbarTheme = is_string($scrollbarTheme) && trim($scrollbarTheme) !== ''
        ? trim($scrollbarTheme)
        : 'os-theme-light';

    // The event that hides the sidebar scrollbars again. Only the tokens of
    // the OverlayScrollbars plugin are accepted, so an unsupported one does
    // not end up rejected (and reported on the console) by the plugin.

    $scrollbarAutoHide = config('adminlte.sidebar_scrollbar_auto_hide', 'leave');

    $scrollbarAutoHide = in_array($scrollbarAutoHide, ['never', 'scroll', 'leave', 'move'], true)
        ? $scrollbarAutoHide
        : 'leave';

    // The 'crossorigin' attribute is only required on the assets served from
    // an external origin (usually a CDN).

    $crossOrigin = static function ($url) {
        return preg_match('#^(https?:)?//#', (string) $url) ? ' crossorigin="anonymous"' : '';
    };
?>
<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" <?php echo $layoutHelper->makeHtmlData(); ?>>

<head>

    
    <meta charset="utf-8">

    <?php if(config('adminlte.color_mode.no_flash_script', true)): ?>
        
        <script>
            (() => {
                'use strict';
                const root = document.documentElement;

                // Applications with their own theming opt out of AdminLTE's
                // color mode entirely, here as well as in the bundle.
                if (root.getAttribute('data-lte-color-mode') === 'off') {
                    return;
                }

                const STORAGE_KEY = 'lte-theme';
                const authored = <?php echo json_encode($authoredColorMode, 15, 512) ?>;
                let stored = null;

                <?php if($rememberColorMode): ?>
                    try {
                        stored = localStorage.getItem(STORAGE_KEY);
                    } catch (e) {
                        // localStorage may be unavailable (private mode, sandboxed iframe).
                    }
                <?php endif; ?>

                // Mirror the precedence in color-mode.ts: the visitor's stored
                // choice wins, then a theme this page declared itself, then the
                // OS preference.
                let resolved = 'light';

                if (stored === 'dark' || stored === 'light') {
                    resolved = stored;
                } else if (authored === 'dark' || authored === 'light') {
                    resolved = authored;
                } else if (globalThis.matchMedia('(prefers-color-scheme: dark)').matches) {
                    resolved = 'dark';
                }

                root.setAttribute('data-bs-theme', resolved);
                root.style.colorScheme = resolved;

                // Flag values computed here, so the bundle does not mistake
                // them for a theme the page declared and stop following the OS
                // preference.
                if (resolved !== authored) {
                    root.setAttribute('data-lte-theme-resolved', '');
                }
            })();
        </script>
    <?php endif; ?>

    
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <?php ($lightThemeColor = config('adminlte.color_mode.theme_color.light')); ?>
    <?php ($darkThemeColor = config('adminlte.color_mode.theme_color.dark')); ?>

    <?php if($lightThemeColor): ?>
        <meta name="theme-color" content="<?php echo e($lightThemeColor); ?>" media="(prefers-color-scheme: light)">
    <?php endif; ?>

    <?php if($darkThemeColor): ?>
        <meta name="theme-color" content="<?php echo e($darkThemeColor); ?>" media="(prefers-color-scheme: dark)">
    <?php endif; ?>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    
    <?php echo $__env->yieldContent('meta_tags'); ?>

    
    <title>
        <?php echo $__env->yieldContent('title_prefix', config('adminlte.title_prefix', '')); ?>
        <?php echo $__env->yieldContent('title', config('adminlte.title', 'AdminLTE 4')); ?>
        <?php echo $__env->yieldContent('title_postfix', config('adminlte.title_postfix', '')); ?>
    </title>

    
    <?php echo $__env->yieldContent('adminlte_css_pre'); ?>

    
    <?php if(isset($fontsCss)): ?>
        <link rel="stylesheet" href="<?php echo e($fontsCss); ?>"<?php echo $crossOrigin($fontsCss); ?>>
    <?php endif; ?>

    
    <?php if(isset($overlayScrollbarsCss)): ?>
        <link rel="stylesheet" href="<?php echo e($overlayScrollbarsCss); ?>"<?php echo $crossOrigin($overlayScrollbarsCss); ?>>
    <?php endif; ?>

    
    <?php if(isset($bootstrapIconsCss)): ?>
        <link rel="stylesheet" href="<?php echo e($bootstrapIconsCss); ?>"<?php echo $crossOrigin($bootstrapIconsCss); ?>>
    <?php endif; ?>

    
    <?php switch($bundling):
        case ('mix'): ?>
            <link rel="stylesheet" href="<?php echo e(mix(config('adminlte.laravel_css_path', 'css/app.css'))); ?>">
        <?php break; ?>

        <?php case ('vite'): ?>
            <?php echo app('Illuminate\Foundation\Vite')([config('adminlte.laravel_css_path', 'resources/css/app.css'), config('adminlte.laravel_js_path', 'resources/js/app.js')]); ?>
        <?php break; ?>

        <?php case ('vite_js_only'): ?>
            <?php echo app('Illuminate\Foundation\Vite')(config('adminlte.laravel_js_path', 'resources/js/app.js')); ?>
        <?php break; ?>

        <?php default: ?>
            
            <?php if(isset($adminlteCss)): ?>
                <link rel="stylesheet" href="<?php echo e($adminlteCss); ?>">
            <?php endif; ?>

            
            <?php if(isset($colorsCss)): ?>
                <link rel="stylesheet" href="<?php echo e($colorsCss); ?>">

                
                <?php echo $__env->make('adminlte::partials.common.extended-colors', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>
    <?php endswitch; ?>

    
    <?php echo $__env->make('adminlte::plugins', ['type' => 'css'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php if(config('adminlte.livewire')): ?>
        @livewireStyles
    <?php endif; ?>

    
    <?php echo $__env->make('adminlte::partials.common.css-variables', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php echo $__env->yieldContent('adminlte_css'); ?>

    
    <?php if(config('adminlte.use_ico_only')): ?>
        <link rel="shortcut icon" href="<?php echo e(asset('favicons/favicon.ico')); ?>" />
    <?php elseif(config('adminlte.use_full_favicon')): ?>
        <link rel="shortcut icon" href="<?php echo e(asset('favicons/favicon.ico')); ?>" />
        <link rel="apple-touch-icon" sizes="57x57" href="<?php echo e(asset('favicons/apple-icon-57x57.png')); ?>">
        <link rel="apple-touch-icon" sizes="60x60" href="<?php echo e(asset('favicons/apple-icon-60x60.png')); ?>">
        <link rel="apple-touch-icon" sizes="72x72" href="<?php echo e(asset('favicons/apple-icon-72x72.png')); ?>">
        <link rel="apple-touch-icon" sizes="76x76" href="<?php echo e(asset('favicons/apple-icon-76x76.png')); ?>">
        <link rel="apple-touch-icon" sizes="114x114" href="<?php echo e(asset('favicons/apple-icon-114x114.png')); ?>">
        <link rel="apple-touch-icon" sizes="120x120" href="<?php echo e(asset('favicons/apple-icon-120x120.png')); ?>">
        <link rel="apple-touch-icon" sizes="144x144" href="<?php echo e(asset('favicons/apple-icon-144x144.png')); ?>">
        <link rel="apple-touch-icon" sizes="152x152" href="<?php echo e(asset('favicons/apple-icon-152x152.png')); ?>">
        <link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(asset('favicons/apple-icon-180x180.png')); ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?php echo e(asset('favicons/favicon-16x16.png')); ?>">
        <link rel="icon" type="image/png" sizes="32x32" href="<?php echo e(asset('favicons/favicon-32x32.png')); ?>">
        <link rel="icon" type="image/png" sizes="96x96" href="<?php echo e(asset('favicons/favicon-96x96.png')); ?>">
        <link rel="icon" type="image/png" sizes="192x192" href="<?php echo e(asset('favicons/android-icon-192x192.png')); ?>">
        <link rel="manifest" crossorigin="use-credentials" href="<?php echo e(asset('favicons/manifest.json')); ?>">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="msapplication-TileImage" content="<?php echo e(asset('favicons/ms-icon-144x144.png')); ?>">
    <?php endif; ?>

</head>

<body class="<?php echo $__env->yieldContent('classes_body'); ?>" <?php echo $__env->yieldContent('body_data'); ?>>

    
    <?php echo $__env->yieldContent('body'); ?>

    

    
    <?php if(isset($overlayScrollbarsJs)): ?>
        <script src="<?php echo e($overlayScrollbarsJs); ?>"<?php echo $crossOrigin($overlayScrollbarsJs); ?> data-navigate-once></script>
    <?php endif; ?>

    
    <?php if(isset($bootstrapJs)): ?>
        <script src="<?php echo e($bootstrapJs); ?>"<?php echo $crossOrigin($bootstrapJs); ?> data-navigate-once></script>
    <?php endif; ?>

    
    <?php switch($bundling):
        case ('mix'): ?>
            <script src="<?php echo e(mix(config('adminlte.laravel_js_path', 'js/app.js'))); ?>"></script>
        <?php break; ?>

        <?php case ('vite'): ?>
        <?php case ('vite_js_only'): ?>
        <?php break; ?>

        <?php default: ?>
            
            <?php if(isset($adminlteJs)): ?>
                <script src="<?php echo e($adminlteJs); ?>" data-navigate-once></script>
            <?php endif; ?>
    <?php endswitch; ?>

    
    <?php echo $__env->make('adminlte::partials.common.lifecycle', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php if($setupScrollbars): ?>
        
        <script>
            (() => {
                'use strict';
                const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
                const Default = {
                    scrollbarTheme: <?php echo json_encode($scrollbarTheme, 15, 512) ?>,
                    scrollbarAutoHide: <?php echo json_encode($scrollbarAutoHide, 15, 512) ?>,
                    scrollbarClickScroll: <?php echo json_encode((bool) config('adminlte.sidebar_scrollbar_click_scroll', true), 512) ?>,
                };

                window._AdminLTE_Ready(function () {
                    const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);

                    // Disable OverlayScrollbars on mobile devices to prevent
                    // touch interference.
                    const isMobile = window.innerWidth <= <?php echo json_encode($scrollbarDisableBelow, 15, 512) ?>;

                    if (
                        sidebarWrapper &&
                        typeof OverlayScrollbarsGlobal !== 'undefined' &&
                        OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined &&
                        !isMobile
                    ) {
                        OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                            scrollbars: {
                                theme: Default.scrollbarTheme,
                                autoHide: Default.scrollbarAutoHide,
                                clickScroll: Default.scrollbarClickScroll,<?php echo $scrollbarExtraOptions; ?>

                            },
                        });
                    }
                });
            })();
        </script>
    <?php endif; ?>

    
    <?php echo $__env->make('adminlte::plugins', ['type' => 'js'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php if(config('adminlte.livewire')): ?>
        @livewireScripts
    <?php endif; ?>

    
    <?php echo $__env->yieldContent('adminlte_js'); ?>

</body>

</html>
<?php /**PATH /var/www/html/vendor/jeroennoten/laravel-adminlte/src/../resources/views/master.blade.php ENDPATH**/ ?>