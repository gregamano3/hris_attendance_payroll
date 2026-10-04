<?php $__env->startSection('title', 'Payslip'); ?>

<?php $__env->startSection('page_actions'); ?>
    <a href="<?php echo e(route('payroll.payslips.pdf', $payslip)); ?>" class="btn btn-outline-primary"><i class="bi bi-filetype-pdf me-1"></i> Download PDF</a>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payroll.view')): ?>
        <a href="<?php echo e(route('payroll.runs.show', $payslip->run)); ?>" class="btn btn-outline-secondary">Back to run</a>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('css'); ?>
    <style>
        .payslip-lines { margin-top: 1rem; border-collapse: collapse; }
        .payslip-lines th { background: var(--bs-tertiary-bg); padding: .4rem .5rem; text-transform: uppercase; font-size: .8rem; }
        .payslip-lines td { padding: .3rem .5rem; border-bottom: 1px solid var(--bs-border-color); }
        .payslip-lines .amount { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .payslip-lines .qty { text-align: right; color: var(--bs-secondary-color); white-space: nowrap; }
        .payslip-lines .total td { font-weight: 600; }
        .payslip-lines .net td { font-weight: 700; font-size: 1.15rem; border-bottom: 0; }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('page'); ?>
    <?php if($payslip->warnings): ?>
        <div class="alert alert-warning"><?php echo e(implode(' ', $payslip->warnings)); ?></div>
    <?php endif; ?>
    <?php if (! ($payslip->run->isLocked())): ?>
        <div class="alert alert-info">Draft: this payslip may still change until the payroll is finalized.</div>
    <?php endif; ?>
    <div class="card"><div class="card-body"><?php echo $__env->make('payroll::payslips._body', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></div></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/app/Features/Payroll/Views/payslips/show.blade.php ENDPATH**/ ?>