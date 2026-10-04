<?php $layoutHelper = app('JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper'); ?>

<?php
    $loginUrl = View::getSection('login_url') ?? config('adminlte.login_url', 'login');
    $registerUrl = View::getSection('register_url') ?? config('adminlte.register_url', 'register');
    $passResetUrl = View::getSection('password_reset_url') ?? config('adminlte.password_reset_url', 'password/reset');

    $loginUrl = $layoutHelper->makeUrl($loginUrl);
    $registerUrl = $layoutHelper->makeUrl($registerUrl);
    $passResetUrl = $layoutHelper->makeUrl($passResetUrl);
?>

<?php $__env->startSection('auth_header', __('adminlte::adminlte.login_message')); ?>

<?php $__env->startSection('auth_body'); ?>
    <form action="<?php echo e($loginUrl); ?>" method="post">
        <?php echo csrf_field(); ?>

        
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

        
        <div class="row">
            <div class="col-7">
                <div class="form-check" title="<?php echo e(__('adminlte::adminlte.remember_me_hint')); ?>">
                    <input class="form-check-input" type="checkbox" name="remember"
                           id="remember" <?php echo e(old('remember') ? 'checked' : ''); ?>>
                    <label class="form-check-label" for="remember">
                        <?php echo e(__('adminlte::adminlte.remember_me')); ?>

                    </label>
                </div>
            </div>

            <div class="col-5">
                <div class="d-grid">
                    <button type="submit" class="btn <?php echo e(config('adminlte.classes_auth_btn', 'btn-primary')); ?>">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        <?php echo e(__('adminlte::adminlte.sign_in')); ?>

                    </button>
                </div>
            </div>
        </div>
    </form>
<?php echo $__env->make('adminlte::auth.social-links', ['fallbackText' => __('adminlte::adminlte.sign_in')], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('auth_footer'); ?>
    
    <?php if($passResetUrl): ?>
        <p class="my-0">
            <a href="<?php echo e($passResetUrl); ?>">
                <?php echo e(__('adminlte::adminlte.i_forgot_my_password')); ?>

            </a>
        </p>
    <?php endif; ?>

    
    <?php if($registerUrl): ?>
        <p class="my-0">
            <a href="<?php echo e($registerUrl); ?>">
                <?php echo e(__('adminlte::adminlte.register_a_new_membership')); ?>

            </a>
        </p>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('adminlte::auth.auth-page', ['authType' => 'login'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/vendor/jeroennoten/laravel-adminlte/src/../resources/views/auth/login.blade.php ENDPATH**/ ?>