<?php use App\Features\Payroll\Enums\PayrollRunStatus; ?>


<?php $__env->startSection('title', $run->name); ?>

<?php $__env->startSection('page_actions'); ?>
    <div class="d-flex flex-wrap gap-2">
        <?php if($run->payslips()->exists()): ?>
            <a href="<?php echo e(route('payroll.runs.register', $run)); ?>" class="btn btn-outline-secondary"><i class="bi bi-filetype-csv me-1"></i> Register</a>
        <?php endif; ?>
        <?php if (! ($run->isLocked())): ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payroll.manage')): ?>
                <form method="post" action="<?php echo e(route('payroll.runs.compute', $run)); ?>">
                    <?php echo csrf_field(); ?>
                    <button class="btn btn-primary" id="compute-button"><i class="bi bi-calculator me-1"></i> <?php echo e($run->computed_at ? 'Recompute' : 'Compute'); ?></button>
                </form>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payroll.finalize')): ?>
                <?php if($run->status === PayrollRunStatus::Computed): ?>
                    <form method="post" action="<?php echo e(route('payroll.runs.finalize', $run)); ?>" data-confirm="Finalize this payroll? It can no longer be changed.">
                        <?php echo csrf_field(); ?>
                        <button class="btn btn-success" id="finalize-button"><i class="bi bi-lock me-1"></i> Finalize</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payroll.manage')): ?>
                <?php if (isset($component)) { $__componentOriginalec2502b834f860c8e30d229aa8f280e2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalec2502b834f860c8e30d229aa8f280e2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.delete-button','data' => ['action' => route('payroll.runs.destroy', $run),'label' => 'Delete','class' => 'btn-md','confirm' => 'Delete this payroll run?']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('delete-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('payroll.runs.destroy', $run)),'label' => 'Delete','class' => 'btn-md','confirm' => 'Delete this payroll run?']); ?>
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
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page'); ?>
    <div class="row">
        <div class="col-md-3 col-6">
            <div class="info-box"><span class="info-box-icon text-bg-<?php echo e($run->status->badge()); ?>"><i class="bi bi-flag"></i></span>
                <div class="info-box-content"><span class="info-box-text">Status</span><span class="info-box-number"><?php echo e($run->status->label()); ?></span></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="info-box"><span class="info-box-icon text-bg-primary"><i class="bi bi-cash"></i></span>
                <div class="info-box-content"><span class="info-box-text">Gross pay</span><span class="info-box-number"><?php echo e($run->total_gross->format()); ?></span></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="info-box"><span class="info-box-icon text-bg-success"><i class="bi bi-wallet2"></i></span>
                <div class="info-box-content"><span class="info-box-text">Net pay</span><span class="info-box-number"><?php echo e($run->total_net->format()); ?></span></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="info-box"><span class="info-box-icon text-bg-warning"><i class="bi bi-building"></i></span>
                <div class="info-box-content"><span class="info-box-text">Employer share</span><span class="info-box-number"><?php echo e($run->total_employer->format()); ?></span></div></div>
        </div>
    </div>

    <p class="text-body-secondary">
        Period <?php echo e($run->period()->label()); ?> · Pay date <?php echo e($run->pay_date->format('M j, Y')); ?>

        <?php if($run->finalized_at): ?> · Finalized <?php echo e($run->finalized_at->format('M j, Y g:i A')); ?> by <?php echo e($run->finalizer?->name); ?> <?php endif; ?>
    </p>

    <?php if($run->status === PayrollRunStatus::Draft && $run->computed_at): ?>
        <div class="alert alert-warning">Adjustments changed since the last computation. Recompute before finalizing.</div>
    <?php endif; ?>
    <?php if($run->period_end->gte(today()) && ! $run->isLocked()): ?>
        <div class="alert alert-info">The period has not ended yet. Days still to come are not paid or deducted until you recompute.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Payslips</h3></div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-hover table-striped mb-0 align-middle" id="payslips-table">
                <thead>
                    <tr><th>Employee</th><th>Department</th><th class="text-end">Days worked</th><th class="text-end">Absent</th><th class="text-end">Gross</th><th class="text-end">Deductions</th><th class="text-end">Net</th><th></th></tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $payslips; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payslip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($payslip->employee_name); ?> <span class="small text-body-secondary"><?php echo e($payslip->employee_no); ?></span>
                                <?php if($payslip->warnings): ?><i class="bi bi-exclamation-triangle text-warning" title="<?php echo e(implode(' ', $payslip->warnings)); ?>"></i><?php endif; ?>
                            </td>
                            <td><?php echo e($payslip->department ?? '—'); ?></td>
                            <td class="text-end"><?php echo e($payslip->attendance['days_worked'] ?? 0); ?></td>
                            <td class="text-end"><?php echo e($payslip->attendance['days_absent'] ?? 0); ?></td>
                            <td class="text-end"><?php echo e($payslip->gross_pay->format(false)); ?></td>
                            <td class="text-end"><?php echo e($payslip->total_deductions->format(false)); ?></td>
                            <td class="text-end fw-semibold"><?php echo e($payslip->net_pay->format(false)); ?></td>
                            <td class="actions"><a href="<?php echo e(route('payroll.payslips.show', $payslip)); ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="8" class="text-center text-body-secondary py-4">No payslips yet. Compute the run to generate them.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Adjustments</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 align-middle">
                        <thead><tr><th>Employee</th><th>Type</th><th>Description</th><th class="text-end">Amount</th><th class="actions"></th></tr></thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $adjustments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $adjustment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($adjustment->employee->full_name); ?></td>
                                    <td><?php echo e($adjustment->kind === 'earning' ? ($adjustment->taxable ? 'Taxable earning' : 'Non-taxable earning') : 'Deduction'); ?></td>
                                    <td><?php echo e($adjustment->label); ?></td>
                                    <td class="text-end"><?php echo e($adjustment->amount->format()); ?></td>
                                    <td class="actions">
                                        <?php if(! $run->isLocked() && auth()->user()->can('payroll.manage')): ?>
                                            <?php if (isset($component)) { $__componentOriginalec2502b834f860c8e30d229aa8f280e2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalec2502b834f860c8e30d229aa8f280e2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.delete-button','data' => ['action' => route('payroll.runs.adjustments.destroy', [$run, $adjustment]),'label' => 'Remove','confirm' => 'Remove this adjustment?']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('delete-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('payroll.runs.adjustments.destroy', [$run, $adjustment])),'label' => 'Remove','confirm' => 'Remove this adjustment?']); ?>
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
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="5" class="text-center text-body-secondary py-3">No adjustments.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php if(! $run->isLocked() && auth()->user()->can('payroll.manage')): ?>
            <div class="col-lg-4">
                <form method="post" action="<?php echo e(route('payroll.runs.adjustments.store', $run)); ?>" class="card">
                    <?php echo csrf_field(); ?>
                    <div class="card-header"><h3 class="card-title">Add adjustment</h3></div>
                    <div class="card-body row g-3">
                        <?php if (isset($component)) { $__componentOriginal8cee41e4af1fe2df52d1d5acd06eed36 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8cee41e4af1fe2df52d1d5acd06eed36 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.select','data' => ['name' => 'employee_id','label' => 'Employee','options' => $employees,'col' => 'col-12','placeholder' => 'Select…','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'employee_id','label' => 'Employee','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($employees),'col' => 'col-12','placeholder' => 'Select…','required' => true]); ?>
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
                        <?php if (isset($component)) { $__componentOriginal8cee41e4af1fe2df52d1d5acd06eed36 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8cee41e4af1fe2df52d1d5acd06eed36 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.select','data' => ['name' => 'kind','label' => 'Type','options' => ['earning' => 'Earning (allowance, bonus…)', 'deduction' => 'Deduction (loan, cash advance…)'],'col' => 'col-12','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'kind','label' => 'Type','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['earning' => 'Earning (allowance, bonus…)', 'deduction' => 'Deduction (loan, cash advance…)']),'col' => 'col-12','required' => true]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.input','data' => ['name' => 'label','label' => 'Description','col' => 'col-12','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'label','label' => 'Description','col' => 'col-12','required' => true]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.input','data' => ['name' => 'amount','label' => 'Amount (₱)','type' => 'number','step' => '0.01','min' => '0','col' => 'col-12','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'amount','label' => 'Amount (₱)','type' => 'number','step' => '0.01','min' => '0','col' => 'col-12','required' => true]); ?>
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
                        <div class="col-12">
                            <div class="form-check">
                                <input type="hidden" name="taxable" value="0">
                                <input class="form-check-input" type="checkbox" id="taxable" name="taxable" value="1" checked>
                                <label class="form-check-label" for="taxable">Taxable (earnings only)</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer"><button class="btn btn-primary">Add</button></div>
                </form>
            </div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Payroll/Views/runs/show.blade.php ENDPATH**/ ?>