<?php $__env->startSection('title', 'My leaves'); ?>

<?php $__env->startSection('page'); ?>
    <?php if(! $employee): ?>
        <div class="callout callout-info">Your account is not linked to an employee record. Please contact Human Resources.</div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Balances <?php echo e(today()->year); ?></h3></div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Leave type</th><th class="text-end">Allowance</th><th class="text-end">Used / pending</th><th class="text-end">Remaining</th></tr></thead>
                            <tbody>
                                <?php $__currentLoopData = $balances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $balance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($balance['type']->name); ?> <?php if (! ($balance['type']->is_paid)): ?><span class="badge text-bg-secondary">Unpaid</span><?php endif; ?></td>
                                        <td class="text-end"><?php echo e($balance['allowance'] ?: '—'); ?></td>
                                        <td class="text-end"><?php echo e(rtrim(rtrim(number_format($balance['used'], 1), '0'), '.')); ?></td>
                                        <td class="text-end"><?php echo e($balance['remaining'] === null ? '—' : rtrim(rtrim(number_format($balance['remaining'], 1), '0'), '.')); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">My requests</h3></div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead><tr><th>Type</th><th>Dates</th><th class="text-end">Days</th><th>Status</th><th>Remarks</th><th class="actions"></th></tr></thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $leave): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($leave->leaveType->name); ?></td>
                                        <td class="text-nowrap"><?php echo e($leave->start_date->format('M j')); ?> – <?php echo e($leave->end_date->format('M j, Y')); ?></td>
                                        <td class="text-end"><?php echo e((float) $leave->days); ?></td>
                                        <td><span class="badge text-bg-<?php echo e($leave->status->badge()); ?>"><?php echo e($leave->status->label()); ?></span></td>
                                        <td class="small"><?php echo e($leave->review_remarks); ?></td>
                                        <td class="actions">
                                            <?php if($leave->status === \App\Features\Attendance\Enums\LeaveStatus::Pending): ?>
                                                <form method="post" action="<?php echo e(route('leaves.cancel', $leave)); ?>" data-confirm="Cancel this request?">
                                                    <?php echo csrf_field(); ?> <?php echo method_field('patch'); ?>
                                                    <button class="btn btn-sm btn-outline-secondary">Cancel</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="6" class="text-center text-body-secondary py-4">No leave requests yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if($requests->hasPages()): ?>
                        <div class="card-footer"><?php echo e($requests->links()); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <form method="post" action="<?php echo e(route('leaves.store')); ?>" class="card">
                    <?php echo csrf_field(); ?>
                    <div class="card-header"><h3 class="card-title">Request leave</h3></div>
                    <div class="card-body row g-3">
                        <?php if (isset($component)) { $__componentOriginal8cee41e4af1fe2df52d1d5acd06eed36 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8cee41e4af1fe2df52d1d5acd06eed36 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.select','data' => ['name' => 'leave_type_id','label' => 'Leave type','options' => $leaveTypes,'col' => 'col-12','placeholder' => 'Select…','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'leave_type_id','label' => 'Leave type','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($leaveTypes),'col' => 'col-12','placeholder' => 'Select…','required' => true]); ?>
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
                        <?php if (isset($component)) { $__componentOriginal5c2a97ab476b69c1189ee85d1a95204b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.input','data' => ['name' => 'start_date','label' => 'From','type' => 'date','col' => 'col-6','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'start_date','label' => 'From','type' => 'date','col' => 'col-6','required' => true]); ?>
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
                        <?php if (isset($component)) { $__componentOriginal5c2a97ab476b69c1189ee85d1a95204b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.input','data' => ['name' => 'end_date','label' => 'To','type' => 'date','col' => 'col-6','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'end_date','label' => 'To','type' => 'date','col' => 'col-6','required' => true]); ?>
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
                        <?php if (isset($component)) { $__componentOriginalcd97a59301ba78d56b3ed60dd41409ab = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcd97a59301ba78d56b3ed60dd41409ab = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.textarea','data' => ['name' => 'reason','label' => 'Reason','rows' => '3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'reason','label' => 'Reason','rows' => '3']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcd97a59301ba78d56b3ed60dd41409ab)): ?>
<?php $attributes = $__attributesOriginalcd97a59301ba78d56b3ed60dd41409ab; ?>
<?php unset($__attributesOriginalcd97a59301ba78d56b3ed60dd41409ab); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcd97a59301ba78d56b3ed60dd41409ab)): ?>
<?php $component = $__componentOriginalcd97a59301ba78d56b3ed60dd41409ab; ?>
<?php unset($__componentOriginalcd97a59301ba78d56b3ed60dd41409ab); ?>
<?php endif; ?>
                    </div>
                    <div class="card-footer"><button class="btn btn-primary">Submit request</button></div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Attendance/Views/leaves/index.blade.php ENDPATH**/ ?>