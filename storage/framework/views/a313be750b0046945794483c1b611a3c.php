<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['name', 'label', 'options' => [], 'value' => null, 'col' => 'col-md-6', 'placeholder' => null]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['name', 'label', 'options' => [], 'value' => null, 'col' => 'col-md-6', 'placeholder' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $selected = (string) old($name, $value instanceof \BackedEnum ? $value->value : $value);
?>

<div class="<?php echo e($col); ?>">
    <label for="<?php echo e($name); ?>" class="form-label"><?php echo e($label); ?></label>
    <select id="<?php echo e($name); ?>" name="<?php echo e($name); ?>" <?php echo e($attributes->class(['form-select', 'is-invalid' => $errors->has($name)])); ?>>
        <?php if($placeholder !== null): ?>
            <option value=""><?php echo e($placeholder); ?></option>
        <?php endif; ?>
        <?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optionValue => $optionLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(is_array($optionLabel)): ?>
                <optgroup label="<?php echo e($optionValue); ?>">
                    <?php $__currentLoopData = $optionLabel; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupValue => $groupLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($groupValue); ?>" <?php if($selected === (string) $groupValue): echo 'selected'; endif; ?>><?php echo e($groupLabel); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </optgroup>
            <?php else: ?>
                <option value="<?php echo e($optionValue); ?>" <?php if($selected === (string) $optionValue): echo 'selected'; endif; ?>><?php echo e($optionLabel); ?></option>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <?php $__errorArgs = [$name];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>
<?php /**PATH /var/www/html/resources/views/components/form/select.blade.php ENDPATH**/ ?>