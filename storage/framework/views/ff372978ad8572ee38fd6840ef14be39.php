<?php $layoutHelper = app('JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper'); ?>

<?php
    $passEmailUrl = View::getSection('password_email_url') ?? config('adminlte.password_email_url', 'password/email');
    $loginUrl = View::getSection('login_url') ?? config('adminlte.login_url', 'login');
    $registerUrl = View::getSection('register_url') ?? config('adminlte.register_url', 'register');

    $passEmailUrl = $layoutHelper->makeUrl($passEmailUrl);
    $loginUrl = $layoutHelper->makeUrl($loginUrl);
    $registerUrl = $layoutHelper->makeUrl($registerUrl);
?>

<?php $__env->startSection('auth_header', __('adminlte::adminlte.password_reset_message')); ?>

<?php $__env->startSection('auth_body'); ?>

    <?php if(session('status')): ?>
        <div class="alert alert-success" role="alert">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <form action="<?php echo e($passEmailUrl); ?>" method="post">
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

        
        <div class="d-grid">
            <button type="submit" class="btn <?php echo e(config('adminlte.classes_auth_btn', 'btn-primary')); ?>">
                <i class="bi bi-send me-1"></i>
                <?php echo e(__('adminlte::adminlte.send_password_reset_link')); ?>

            </button>
        </div>
    </form>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('auth_footer'); ?>
    
    <?php if($loginUrl): ?>
        <p class="my-0">
            <a href="<?php echo e($loginUrl); ?>">
                <?php echo e(__('adminlte::adminlte.i_already_have_a_membership')); ?>

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

<?php echo $__env->make('adminlte::auth.auth-page', ['authType' => 'login'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/vendor/jeroennoten/laravel-adminlte/src/../resources/views/auth/passwords/email.blade.php ENDPATH**/ ?>