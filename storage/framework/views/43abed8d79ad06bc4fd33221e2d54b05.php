<?php $layoutHelper = app('JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper'); ?>

<?php
    $passResetUrl = View::getSection('password_reset_url') ?? config('adminlte.password_reset_url', 'password/reset');
    $passResetUrl = $layoutHelper->makeUrl($passResetUrl);
?>

<?php $__env->startSection('auth_header', __('adminlte::adminlte.password_reset_message')); ?>

<?php $__env->startSection('auth_body'); ?>
    <form action="<?php echo e($passResetUrl); ?>" method="post">
        <?php echo csrf_field(); ?>

        
        <input type="hidden" name="token" value="<?php echo e($token); ?>">

        
        <label for="email" class="visually-hidden"><?php echo e(__('adminlte::adminlte.email')); ?></label>

        <div class="input-group mb-3">
            <input type="email" name="email" id="email"
                class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                value="<?php echo e(old('email')); ?>" placeholder="<?php echo e(__('adminlte::adminlte.email')); ?>" autofocus>

            <div class="input-group-text">
                <span class="bi bi-envelope <?php echo e(config('adminlte.classes_auth_icon', '')); ?>"></span>
            </div>

            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <span class="invalid-feedback" role="alert">
                    <strong><?php echo e($message); ?></strong>
                </span>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        
        <label for="password" class="visually-hidden"><?php echo e(__('adminlte::adminlte.password')); ?></label>

        <div class="input-group mb-3">
            <input type="password" name="password" id="password"
                class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                placeholder="<?php echo e(__('adminlte::adminlte.password')); ?>">

            <div class="input-group-text">
                <span class="bi bi-lock-fill <?php echo e(config('adminlte.classes_auth_icon', '')); ?>"></span>
            </div>

            <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <span class="invalid-feedback" role="alert">
                    <strong><?php echo e($message); ?></strong>
                </span>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        
        <label for="password_confirmation" class="visually-hidden">
            <?php echo e(__('adminlte::adminlte.retype_password')); ?>

        </label>

        <div class="input-group mb-3">
            <input type="password" name="password_confirmation" id="password_confirmation"
                class="form-control <?php $__errorArgs = ['password_confirmation'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                placeholder="<?php echo e(trans('adminlte::adminlte.retype_password')); ?>">

            <div class="input-group-text">
                <span class="bi bi-lock-fill <?php echo e(config('adminlte.classes_auth_icon', '')); ?>"></span>
            </div>

            <?php $__errorArgs = ['password_confirmation'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <span class="invalid-feedback" role="alert">
                    <strong><?php echo e($message); ?></strong>
                </span>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        
        <div class="d-grid">
            <button type="submit" class="btn <?php echo e(config('adminlte.classes_auth_btn', 'btn-primary')); ?>">
                <i class="bi bi-arrow-repeat me-1"></i>
                <?php echo e(__('adminlte::adminlte.reset_password')); ?>

            </button>
        </div>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('adminlte::auth.auth-page', ['authType' => 'login'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/vendor/jeroennoten/laravel-adminlte/src/../resources/views/auth/passwords/reset.blade.php ENDPATH**/ ?>