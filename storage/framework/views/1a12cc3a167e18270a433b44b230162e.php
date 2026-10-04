<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('page'); ?>
    <div class="row">
        <div class="col-lg-3 col-6">
            <?php if (isset($component)) { $__componentOriginal28a68399664384fcdb4ffafd23cbfe61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal28a68399664384fcdb4ffafd23cbfe61 = $attributes; } ?>
<?php $component = JeroenNoten\LaravelAdminLte\View\Components\Widget\SmallBox::resolve(['title' => ''.e(number_format($stats['active_employees'])).'','text' => 'Active employees','icon' => 'bi bi-person-vcard','theme' => 'success','url' => auth()->user()->can('employees.view') ? route('employees.index') : null,'urlText' => 'View employees'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('adminlte-small-box'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\JeroenNoten\LaravelAdminLte\View\Components\Widget\SmallBox::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal28a68399664384fcdb4ffafd23cbfe61)): ?>
<?php $attributes = $__attributesOriginal28a68399664384fcdb4ffafd23cbfe61; ?>
<?php unset($__attributesOriginal28a68399664384fcdb4ffafd23cbfe61); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal28a68399664384fcdb4ffafd23cbfe61)): ?>
<?php $component = $__componentOriginal28a68399664384fcdb4ffafd23cbfe61; ?>
<?php unset($__componentOriginal28a68399664384fcdb4ffafd23cbfe61); ?>
<?php endif; ?>
        </div>
        <div class="col-lg-3 col-6">
            <?php if (isset($component)) { $__componentOriginal28a68399664384fcdb4ffafd23cbfe61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal28a68399664384fcdb4ffafd23cbfe61 = $attributes; } ?>
<?php $component = JeroenNoten\LaravelAdminLte\View\Components\Widget\SmallBox::resolve(['title' => ''.e(number_format($stats['active_users'])).'','text' => 'Active users','icon' => 'bi bi-people-fill','theme' => 'primary'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('adminlte-small-box'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\JeroenNoten\LaravelAdminLte\View\Components\Widget\SmallBox::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal28a68399664384fcdb4ffafd23cbfe61)): ?>
<?php $attributes = $__attributesOriginal28a68399664384fcdb4ffafd23cbfe61; ?>
<?php unset($__attributesOriginal28a68399664384fcdb4ffafd23cbfe61); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal28a68399664384fcdb4ffafd23cbfe61)): ?>
<?php $component = $__componentOriginal28a68399664384fcdb4ffafd23cbfe61; ?>
<?php unset($__componentOriginal28a68399664384fcdb4ffafd23cbfe61); ?>
<?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            Welcome back, <strong><?php echo e(auth()->user()->name); ?></strong>.
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Dashboard/Views/index.blade.php ENDPATH**/ ?>