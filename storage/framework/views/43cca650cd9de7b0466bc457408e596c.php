<div class="col-md-3">
    <label for="from" class="form-label small mb-1">From</label>
    <input type="date" id="from" name="from" value="<?php echo e($period->from->toDateString()); ?>" class="form-control">
</div>
<div class="col-md-3">
    <label for="to" class="form-label small mb-1">To</label>
    <input type="date" id="to" name="to" value="<?php echo e($period->to->toDateString()); ?>" class="form-control">
</div>
<?php /**PATH /var/www/html/app/Features/Attendance/Views/_period-filter.blade.php ENDPATH**/ ?>