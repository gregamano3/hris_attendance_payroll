<?php $__env->startSection('title', 'Statutory rates'); ?>

<?php $__env->startSection('page'); ?>
    <div class="callout callout-warning">
        Rates are effective-dated: add a new version when SSS, PhilHealth, Pag-IBIG or BIR publish new rates.
        Each payroll run uses the versions in effect at the end of its period. Always verify against the official circulars.
    </div>

    <div class="row">
        <?php $__currentLoopData = $schemes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $scheme): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $versions = $rates->get($scheme->value, collect()); $current = $versions->first(); ?>
            <div class="col-xl-4 col-lg-6">
                <div class="card">
                    <div class="card-header"><h3 class="card-title"><?php echo e($scheme->label()); ?></h3></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Parameter</th><?php $__currentLoopData = $versions->take(2); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $version): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><th class="text-end"><?php echo e($version->effective_from->format('M j, Y')); ?></th><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tr></thead>
                            <tbody>
                                <?php $__currentLoopData = $scheme->parameters(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr><td class="small"><?php echo e($label); ?></td><?php $__currentLoopData = $versions->take(2); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $version): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><td class="text-end"><?php echo e($version->parameters[$key] ?? '—'); ?></td><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    <form method="post" action="<?php echo e(route('payroll.statutory.store')); ?>" class="card-footer">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="scheme" value="<?php echo e($scheme->value); ?>">
                        <details>
                            <summary class="mb-2">Add new version</summary>
                            <div class="row g-2">
                                <?php if (isset($component)) { $__componentOriginal5c2a97ab476b69c1189ee85d1a95204b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.input','data' => ['name' => 'effective_from','label' => 'Effective from','type' => 'date','col' => 'col-12','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'effective_from','label' => 'Effective from','type' => 'date','col' => 'col-12','required' => true]); ?>
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
                                <?php $__currentLoopData = $scheme->parameters(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="col-6">
                                        <label class="form-label small mb-0" for="<?php echo e($scheme->value); ?>-<?php echo e($key); ?>"><?php echo e($label); ?></label>
                                        <input type="number" step="any" min="0" class="form-control form-control-sm" id="<?php echo e($scheme->value); ?>-<?php echo e($key); ?>"
                                            name="parameters[<?php echo e($key); ?>]" value="<?php echo e($current?->parameters[$key]); ?>" required>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <div class="col-12"><button class="btn btn-sm btn-primary">Save version</button></div>
                            </div>
                        </details>
                    </form>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="row">
        <?php $__currentLoopData = $taxTables; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $brackets): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php [$frequency, $from] = explode('|', $key); ?>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Withholding tax — <?php echo e(str_replace('_', '-', $frequency)); ?> (from <?php echo e(\Illuminate\Support\Carbon::parse($from)->format('M j, Y')); ?>)</h3></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead><tr><th class="text-end">Over</th><th class="text-end">Not over</th><th class="text-end">Base tax</th><th class="text-end">Rate on excess</th></tr></thead>
                            <tbody>
                                <?php $__currentLoopData = $brackets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bracket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td class="text-end"><?php echo e($bracket->lower_bound->format(false)); ?></td>
                                        <td class="text-end"><?php echo e($bracket->upper_bound?->format(false) ?? '—'); ?></td>
                                        <td class="text-end"><?php echo e($bracket->base_tax->format(false)); ?></td>
                                        <td class="text-end"><?php echo e(rtrim(rtrim(number_format((float) $bracket->rate * 100, 2), '0'), '.')); ?>%</td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <form method="post" action="<?php echo e(route('payroll.statutory.tax.store')); ?>" class="card">
        <?php echo csrf_field(); ?>
        <div class="card-header"><h3 class="card-title">Add withholding tax table</h3></div>
        <div class="card-body">
            <div class="row g-2 mb-3">
                <?php if (isset($component)) { $__componentOriginal8cee41e4af1fe2df52d1d5acd06eed36 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8cee41e4af1fe2df52d1d5acd06eed36 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.select','data' => ['name' => 'frequency','label' => 'Frequency','options' => ['semi_monthly' => 'Semi-monthly', 'monthly' => 'Monthly'],'col' => 'col-md-3','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'frequency','label' => 'Frequency','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['semi_monthly' => 'Semi-monthly', 'monthly' => 'Monthly']),'col' => 'col-md-3','required' => true]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.input','data' => ['name' => 'effective_from','label' => 'Effective from','type' => 'date','col' => 'col-md-3','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'effective_from','label' => 'Effective from','type' => 'date','col' => 'col-md-3','required' => true]); ?>
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
            <?php $__errorArgs = ['brackets'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger small mb-2"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            <table class="table table-sm">
                <thead><tr><th>Over (₱)</th><th>Not over (₱, blank = no limit)</th><th>Base tax (₱)</th><th>Rate on excess (0–1)</th></tr></thead>
                <tbody>
                    <?php for($i = 0; $i < 7; $i++): ?>
                        <tr>
                            <?php $__currentLoopData = ['lower', 'upper', 'base', 'rate']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <td><input type="number" step="any" min="0" name="brackets[<?php echo e($i); ?>][<?php echo e($field); ?>]" class="form-control form-control-sm" aria-label="<?php echo e($field); ?> <?php echo e($i + 1); ?>"></td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Save table</button></div>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Payroll/Views/statutory/index.blade.php ENDPATH**/ ?>