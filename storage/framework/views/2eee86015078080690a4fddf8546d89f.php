
<?php
    $earnings = $payslip->lines->where('kind', 'earning');
    $deductions = $payslip->lines->where('kind', 'deduction');
    $employer = $payslip->lines->where('kind', 'employer');
    $qty = fn ($line) => $line->quantity !== null ? rtrim(rtrim(number_format((float) $line->quantity, 2), '0'), '.').' '.$line->unit : '';
?>

<table class="payslip-meta" width="100%">
    <tr>
        <td><strong><?php echo e($payslip->employee_name); ?></strong><br><?php echo e($payslip->employee_no); ?><br><?php echo e($payslip->position); ?> · <?php echo e($payslip->department); ?></td>
        <td style="text-align:right">
            Period: <strong><?php echo e($payslip->run->period()->label()); ?></strong><br>
            Pay date: <?php echo e($payslip->run->pay_date->format('M j, Y')); ?><br>
            Rate: <?php echo e($payslip->basic_rate->format()); ?> / <?php echo e($payslip->rate_type === 'monthly' ? 'month' : 'day'); ?>

            (daily <?php echo e($payslip->daily_rate->format()); ?>)
        </td>
    </tr>
</table>

<table class="payslip-lines" width="100%">
    <thead><tr><th colspan="3">Earnings</th></tr></thead>
    <tbody>
        <?php $__currentLoopData = $earnings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr><td><?php echo e($line->label); ?></td><td class="qty"><?php echo e($qty($line)); ?></td><td class="amount"><?php echo e($line->amount->format(false)); ?></td></tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <tr class="total"><td colspan="2">Gross pay</td><td class="amount"><?php echo e($payslip->gross_pay->format(false)); ?></td></tr>
    </tbody>
    <thead><tr><th colspan="3">Deductions</th></tr></thead>
    <tbody>
        <?php $__currentLoopData = $deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr><td><?php echo e($line->label); ?></td><td class="qty"></td><td class="amount"><?php echo e($line->amount->format(false)); ?></td></tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <tr class="total"><td colspan="2">Total deductions</td><td class="amount"><?php echo e($payslip->total_deductions->format(false)); ?></td></tr>
    </tbody>
    <tbody>
        <tr class="net"><td colspan="2">NET PAY</td><td class="amount"><?php echo e($payslip->net_pay->format()); ?></td></tr>
    </tbody>
</table>

<table class="payslip-lines small" width="100%">
    <thead><tr><th colspan="2">Employer contributions (not deducted)</th></tr></thead>
    <tbody>
        <?php $__currentLoopData = $employer; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr><td><?php echo e($line->label); ?></td><td class="amount"><?php echo e($line->amount->format(false)); ?></td></tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
    <thead><tr><th colspan="2">Attendance</th></tr></thead>
    <tbody>
        <tr><td>Days worked / absent / paid leave</td><td class="amount"><?php echo e($payslip->attendance['days_worked'] ?? 0); ?> / <?php echo e($payslip->attendance['days_absent'] ?? 0); ?> / <?php echo e($payslip->attendance['paid_leave_days'] ?? 0); ?></td></tr>
        <tr><td>Late / undertime / overtime (minutes)</td><td class="amount"><?php echo e($payslip->attendance['late_minutes'] ?? 0); ?> / <?php echo e($payslip->attendance['undertime_minutes'] ?? 0); ?> / <?php echo e($payslip->attendance['overtime_minutes'] ?? 0); ?></td></tr>
        <tr><td>Taxable income</td><td class="amount"><?php echo e($payslip->taxable_income->format(false)); ?></td></tr>
    </tbody>
</table>
<?php /**PATH /var/www/html/app/Features/Payroll/Views/payslips/_body.blade.php ENDPATH**/ ?>