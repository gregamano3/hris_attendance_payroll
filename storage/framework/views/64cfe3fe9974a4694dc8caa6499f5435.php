<?php $__env->startSection('title', 'Employees'); ?>

<?php $__env->startSection('page_actions'); ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('employees.manage')): ?>
        <a href="<?php echo e(route('employees.create')); ?>" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> New employee</a>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page'); ?>
    <div class="card">
        <div class="card-header">
            <form method="get" class="row g-2" role="search">
                <div class="col-md-5">
                    <input type="search" name="search" value="<?php echo e($filters['search']); ?>" class="form-control"
                        placeholder="Search name or employee no." aria-label="Search employees">
                </div>
                <div class="col-md-3">
                    <select name="department" class="form-select" aria-label="Department">
                        <option value="">All departments</option>
                        <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($id); ?>" <?php if($filters['department'] === $id): echo 'selected'; endif; ?>><?php echo e($name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select" aria-label="Status">
                        <option value="">All statuses</option>
                        <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if($filters['status']?->value === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-outline-secondary" type="submit">Filter</button>
                </div>
            </form>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Employee no.</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="actions"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($employee->employee_no); ?></td>
                            <td><a href="<?php echo e(route('employees.show', $employee)); ?>"><?php echo e($employee->full_name); ?></a></td>
                            <td><?php echo e($employee->department->name ?? '—'); ?></td>
                            <td><?php echo e($employee->position->title ?? '—'); ?></td>
                            <td><?php echo e($employee->employment_type->label()); ?></td>
                            <td><span class="badge text-bg-<?php echo e($employee->status->badge()); ?>"><?php echo e($employee->status->label()); ?></span></td>
                            <td class="actions">
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('employees.manage')): ?>
                                    <a href="<?php echo e(route('employees.edit', $employee)); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">No employees found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($employees->hasPages()): ?>
            <div class="card-footer"><?php echo e($employees->links()); ?></div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Employees/Views/employees/index.blade.php ENDPATH**/ ?>