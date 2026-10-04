<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payslip <?php echo e($payslip->employee_no); ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #222; }
        h1 { font-size: 14px; margin: 0 0 6px; }
        .payslip-meta td { vertical-align: top; padding-bottom: 6px; }
        .payslip-lines { margin-top: 8px; border-collapse: collapse; }
        .payslip-lines th { background: #eee; text-align: left; padding: 3px 4px; text-transform: uppercase; font-size: 8.5px; }
        .payslip-lines td { padding: 2px 4px; border-bottom: 1px solid #ddd; }
        .amount { text-align: right; white-space: nowrap; }
        .qty { text-align: right; color: #666; }
        .total td { font-weight: bold; }
        .net td { font-weight: bold; font-size: 12px; border-bottom: 0; }
        .small { font-size: 8.5px; }
    </style>
</head>
<body>
    <h1><?php echo e(config('app.name')); ?> — Payslip</h1>
    <?php echo $__env->make('payroll::payslips._body', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <p class="small" style="margin-top:12px;color:#666">Generated <?php echo e(now()->format('M j, Y g:i A')); ?>. This is a system-generated payslip.</p>
</body>
</html>
<?php /**PATH /var/www/html/app/Features/Payroll/Views/payslips/pdf.blade.php ENDPATH**/ ?>