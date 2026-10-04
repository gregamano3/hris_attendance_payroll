<?php $__env->startSection('title', 'Import time logs'); ?>

<?php $__env->startSection('page'); ?>
    <?php if(session('import_errors')): ?>
        <div class="alert alert-warning">
            <strong>Some rows were skipped:</strong>
            <ul class="mb-0">
                <?php $__currentLoopData = session('import_errors'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-6">
            <form method="post" action="<?php echo e(route('attendance.import.store')); ?>" enctype="multipart/form-data" class="card">
                <?php echo csrf_field(); ?>
                <div class="card-body">
                    <label for="file" class="form-label">CSV file</label>
                    <input type="file" id="file" name="file" accept=".csv,text/csv" class="form-control <?php $__errorArgs = ['file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                    <?php $__errorArgs = ['file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary"><i class="bi bi-upload me-1"></i> Import</button>
                    <a href="<?php echo e(route('attendance.logs.index')); ?>" class="btn btn-outline-secondary">Back to time logs</a>
                </div>
            </form>
        </div>
        <div class="col-lg-6">
            <div class="callout callout-info">
                <h5>Expected format</h5>
                <p>One punch per row with a header line. Duplicate punches are skipped automatically.</p>
<pre class="mb-0">employee_no,logged_at,type
EMP-00001,2026-10-05 07:58,in
EMP-00001,2026-10-05 17:04,out</pre>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Attendance/Views/time-logs/import.blade.php ENDPATH**/ ?>