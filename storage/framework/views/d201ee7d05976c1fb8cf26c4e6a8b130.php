<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['action', 'label' => 'Delete', 'confirm' => 'Are you sure?']));

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

foreach (array_filter((['action', 'label' => 'Delete', 'confirm' => 'Are you sure?']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<form method="post" action="<?php echo e($action); ?>" class="d-inline" data-confirm="<?php echo e($confirm); ?>">
    <?php echo csrf_field(); ?>
    <?php echo method_field('delete'); ?>
    <button type="submit" <?php echo e($attributes->class(['btn btn-sm btn-outline-danger'])); ?>><?php echo e($label); ?></button>
</form>
<?php /**PATH /var/www/html/resources/views/components/delete-button.blade.php ENDPATH**/ ?>