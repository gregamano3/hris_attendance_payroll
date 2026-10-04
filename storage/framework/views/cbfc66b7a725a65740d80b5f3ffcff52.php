<?php $__env->startSection('title', 'Users'); ?>

<?php $__env->startSection('page_actions'); ?>
    <a href="<?php echo e(route('users.create')); ?>" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i> New user
    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page'); ?>
    <div class="card">
        <div class="card-header">
            <form method="get" class="d-flex gap-2" role="search">
                <input type="search" name="search" value="<?php echo e($search); ?>" class="form-control"
                    placeholder="Search name or email" aria-label="Search users">
                <button class="btn btn-outline-secondary" type="submit">Search</button>
            </form>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last login</th>
                        <th class="actions"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($user->name); ?></td>
                            <td><?php echo e($user->email); ?></td>
                            <td><?php echo e($user->primaryRole()?->label() ?? '—'); ?></td>
                            <td>
                                <?php if($user->is_active): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($user->last_login_at?->diffForHumans() ?? 'Never'); ?></td>
                            <td class="actions">
                                <a href="<?php echo e(route('users.edit', $user)); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-4">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($users->hasPages()): ?>
            <div class="card-footer"><?php echo e($users->links()); ?></div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Users/Views/index.blade.php ENDPATH**/ ?>