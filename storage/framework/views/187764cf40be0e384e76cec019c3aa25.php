<?php $__env->startSection('title', 'My payslips'); ?>

<?php $__env->startSection('page'); ?>
    <?php if(! $employee): ?>
        <div class="callout callout-info">Your account is not linked to an employee record. Please contact Human Resources.</div>
    <?php else: ?>
        <div class="card">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead><tr><th>Period</th><th>Pay date</th><th class="text-end">Gross</th><th class="text-end">Net pay</th><th class="actions"></th></tr></thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $payslips; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payslip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($payslip->run->period()->label()); ?></td>
                                <td><?php echo e($payslip->run->pay_date->format('M j, Y')); ?></td>
                                <td class="text-end"><?php echo e($payslip->gross_pay->format()); ?></td>
                                <td class="text-end fw-semibold"><?php echo e($payslip->net_pay->format()); ?></td>
                                <td class="actions">
                                    <a href="<?php echo e(route('payroll.payslips.show', $payslip)); ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="<?php echo e(route('payroll.payslips.pdf', $payslip)); ?>" class="btn btn-sm btn-outline-secondary">PDF</a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="5" class="text-center text-body-secondary py-4">No payslips yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if($payslips->hasPages()): ?>
                <div class="card-footer"><?php echo e($payslips->links()); ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Payroll/Views/payslips/mine.blade.php ENDPATH**/ ?>