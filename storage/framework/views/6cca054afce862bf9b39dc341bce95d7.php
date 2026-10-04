<?php use App\Features\Employees\Enums\GovernmentId; ?>

<div class="row">
    <div class="col-lg-4">
        <div class="card card-primary card-outline">
            <div class="card-body text-center">
                <i class="bi bi-person-circle display-3 text-body-secondary"></i>
                <h3 class="fs-4 mt-2 mb-0"><?php echo e($employee->first_name); ?> <?php echo e($employee->last_name); ?> <?php echo e($employee->suffix); ?></h3>
                <p class="text-body-secondary mb-2"><?php echo e($employee->position->title ?? 'No position'); ?></p>
                <span class="badge text-bg-<?php echo e($employee->status->badge()); ?>"><?php echo e($employee->status->label()); ?></span>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span>Employee no.</span><strong><?php echo e($employee->employee_no); ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Department</span><strong><?php echo e($employee->department->name ?? '—'); ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Type</span><strong><?php echo e($employee->employment_type->label()); ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Hired</span><strong><?php echo e($employee->hired_at->format('M d, Y')); ?></strong></li>
            </ul>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Personal information</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Full name</dt><dd class="col-sm-8"><?php echo e($employee->full_name); ?></dd>
                    <dt class="col-sm-4">Birth date</dt><dd class="col-sm-8"><?php echo e($employee->birth_date?->format('M d, Y') ?? '—'); ?></dd>
                    <dt class="col-sm-4">Gender</dt><dd class="col-sm-8"><?php echo e($employee->gender?->label() ?? '—'); ?></dd>
                    <dt class="col-sm-4">Civil status</dt><dd class="col-sm-8"><?php echo e($employee->civil_status?->label() ?? '—'); ?></dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><?php echo e($employee->email ?? '—'); ?></dd>
                    <dt class="col-sm-4">Mobile</dt><dd class="col-sm-8"><?php echo e($employee->mobile ?? '—'); ?></dd>
                    <dt class="col-sm-4">Address</dt><dd class="col-sm-8"><?php echo e($employee->address ?? '—'); ?></dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Employment &amp; compensation</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Regularized</dt><dd class="col-sm-8"><?php echo e($employee->regularized_at?->format('M d, Y') ?? '—'); ?></dd>
                    <dt class="col-sm-4">Separated</dt><dd class="col-sm-8"><?php echo e($employee->separated_at?->format('M d, Y') ?? '—'); ?></dd>
                    <dt class="col-sm-4">Basic rate</dt><dd class="col-sm-8"><?php echo e($employee->basic_rate->format()); ?> / <?php echo e($employee->rate_type->unit()); ?></dd>
                    <?php $__currentLoopData = GovernmentId::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <dt class="col-sm-4"><?php echo e($id->label()); ?></dt><dd class="col-sm-8"><?php echo e($employee->governmentId($id) ?: '—'); ?></dd>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php if(isset($showUser)): ?>
                        <dt class="col-sm-4">User account</dt><dd class="col-sm-8"><?php echo e($employee->user?->email ?? 'Not linked'); ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /var/www/html/app/Features/Employees/Views/employees/_details.blade.php ENDPATH**/ ?>