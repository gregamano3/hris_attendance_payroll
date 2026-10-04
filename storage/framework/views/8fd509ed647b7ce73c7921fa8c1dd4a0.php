<?php $__env->startSection('title', $employee->full_name); ?>

<?php $__env->startSection('page_actions'); ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('employees.manage')): ?>
        <a href="<?php echo e(route('employees.edit', $employee)); ?>" class="btn btn-primary"><i class="bi bi-pencil me-1"></i> Edit</a>
        <?php if (isset($component)) { $__componentOriginalec2502b834f860c8e30d229aa8f280e2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalec2502b834f860c8e30d229aa8f280e2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.delete-button','data' => ['action' => route('employees.archive', $employee),'label' => 'Archive','class' => 'btn-outline-danger btn-md','confirm' => 'Archive this employee? Their history is kept.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('delete-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('employees.archive', $employee)),'label' => 'Archive','class' => 'btn-outline-danger btn-md','confirm' => 'Archive this employee? Their history is kept.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalec2502b834f860c8e30d229aa8f280e2)): ?>
<?php $attributes = $__attributesOriginalec2502b834f860c8e30d229aa8f280e2; ?>
<?php unset($__attributesOriginalec2502b834f860c8e30d229aa8f280e2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalec2502b834f860c8e30d229aa8f280e2)): ?>
<?php $component = $__componentOriginalec2502b834f860c8e30d229aa8f280e2; ?>
<?php unset($__componentOriginalec2502b834f860c8e30d229aa8f280e2); ?>
<?php endif; ?>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page'); ?>
    <?php echo $__env->make('employees::employees._details', ['showUser' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Employees/Views/employees/show.blade.php ENDPATH**/ ?>