<?php $__env->startSection('title', 'Shifts'); ?>

<?php $__env->startSection('page_actions'); ?>
    <a href="<?php echo e(route('shifts.create')); ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New shift</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page'); ?>
    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead>
                    <tr><th>Name</th><th>Schedule</th><th>Break</th><th>Grace</th><th>Work days</th><th class="text-end">Assignments</th><th class="actions"></th></tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $shifts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shift): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($shift->name); ?> <?php if($shift->is_default): ?><span class="badge text-bg-primary">Default</span><?php endif; ?></td>
                            <td><?php echo e(substr($shift->start_time, 0, 5)); ?>–<?php echo e(substr($shift->end_time, 0, 5)); ?> <?php if($shift->crossesMidnight()): ?><i class="bi bi-moon-stars" title="Overnight"></i><?php endif; ?></td>
                            <td><?php echo e($shift->break_minutes); ?> min</td>
                            <td><?php echo e($shift->grace_minutes); ?> min</td>
                            <td><?php echo e($shift->workDaysLabel()); ?></td>
                            <td class="text-end"><?php echo e($shift->assignments_count); ?></td>
                            <td class="actions">
                                <a href="<?php echo e(route('shifts.edit', $shift)); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <?php if (isset($component)) { $__componentOriginalec2502b834f860c8e30d229aa8f280e2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalec2502b834f860c8e30d229aa8f280e2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.delete-button','data' => ['action' => route('shifts.destroy', $shift),'confirm' => 'Delete this shift?']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('delete-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('shifts.destroy', $shift)),'confirm' => 'Delete this shift?']); ?>
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
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <form method="post" action="<?php echo e(route('shifts.assign')); ?>" class="card">
                <?php echo csrf_field(); ?>
                <div class="card-header"><h3 class="card-title">Assign shift</h3></div>
                <div class="card-body row g-3">
                    <?php if (isset($component)) { $__componentOriginal8cee41e4af1fe2df52d1d5acd06eed36 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8cee41e4af1fe2df52d1d5acd06eed36 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.select','data' => ['name' => 'shift_id','label' => 'Shift','options' => $shifts->mapWithKeys(fn ($s) => [$s->id => $s->label()]),'col' => 'col-12','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'shift_id','label' => 'Shift','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shifts->mapWithKeys(fn ($s) => [$s->id => $s->label()])),'col' => 'col-12','required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8cee41e4af1fe2df52d1d5acd06eed36)): ?>
<?php $attributes = $__attributesOriginal8cee41e4af1fe2df52d1d5acd06eed36; ?>
<?php unset($__attributesOriginal8cee41e4af1fe2df52d1d5acd06eed36); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8cee41e4af1fe2df52d1d5acd06eed36)): ?>
<?php $component = $__componentOriginal8cee41e4af1fe2df52d1d5acd06eed36; ?>
<?php unset($__componentOriginal8cee41e4af1fe2df52d1d5acd06eed36); ?>
<?php endif; ?>
                    <div class="col-12">
                        <label for="employee_ids" class="form-label">Employees</label>
                        <select id="employee_ids" name="employee_ids[]" multiple size="8" class="form-select <?php $__errorArgs = ['employee_ids'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                            <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($employee->id); ?>"><?php echo e($employee->employee_no); ?> — <?php echo e($employee->full_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <div class="form-text">Hold Ctrl / Cmd to select several.</div>
                        <?php $__errorArgs = ['employee_ids'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <?php if (isset($component)) { $__componentOriginal5c2a97ab476b69c1189ee85d1a95204b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.input','data' => ['name' => 'effective_from','label' => 'Effective from','type' => 'date','value' => today()->toDateString(),'col' => 'col-12','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'effective_from','label' => 'Effective from','type' => 'date','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(today()->toDateString()),'col' => 'col-12','required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b)): ?>
<?php $attributes = $__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b; ?>
<?php unset($__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5c2a97ab476b69c1189ee85d1a95204b)): ?>
<?php $component = $__componentOriginal5c2a97ab476b69c1189ee85d1a95204b; ?>
<?php unset($__componentOriginal5c2a97ab476b69c1189ee85d1a95204b); ?>
<?php endif; ?>
                </div>
                <div class="card-footer"><button class="btn btn-primary">Assign</button></div>
            </form>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Recent assignments</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Employee</th><th>Shift</th><th>Effective</th></tr></thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $assignments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($assignment->employee->full_name); ?></td>
                                    <td><?php echo e($assignment->shift->name); ?></td>
                                    <td><?php echo e($assignment->effective_from->format('M j, Y')); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="3" class="text-center text-body-secondary py-3">Employees without an assignment follow the default shift.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Attendance/Views/shifts/index.blade.php ENDPATH**/ ?>