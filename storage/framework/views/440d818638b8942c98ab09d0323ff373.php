<?php $__env->startSection('title', 'Positions'); ?>

<?php $__env->startSection('page_actions'); ?>
    <a href="<?php echo e(route('positions.create')); ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New position</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page'); ?>
    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr><th>Title</th><th>Department</th><th class="text-end">Employees</th><th class="actions"></th></tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $position): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($position->title); ?></td>
                            <td><?php echo e($position->department->name ?? '—'); ?></td>
                            <td class="text-end"><?php echo e($position->employees_count); ?></td>
                            <td class="actions">
                                <a href="<?php echo e(route('positions.edit', $position)); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <?php if (isset($component)) { $__componentOriginalec2502b834f860c8e30d229aa8f280e2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalec2502b834f860c8e30d229aa8f280e2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.delete-button','data' => ['action' => route('positions.destroy', $position),'confirm' => 'Delete this position?']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('delete-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('positions.destroy', $position)),'confirm' => 'Delete this position?']); ?>
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
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="4" class="text-center text-body-secondary py-4">No positions yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($positions->hasPages()): ?>
            <div class="card-footer"><?php echo e($positions->links()); ?></div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Employees/Views/positions/index.blade.php ENDPATH**/ ?>