<div class="col-md-3">
    <label for="from" class="form-label small mb-1">From</label>
    <input type="date" id="from" name="from" value="{{ $period->from->toDateString() }}" class="form-control">
</div>
<div class="col-md-3">
    <label for="to" class="form-label small mb-1">To</label>
    <input type="date" id="to" name="to" value="{{ $period->to->toDateString() }}" class="form-control">
</div>
