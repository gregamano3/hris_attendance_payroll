<?php

namespace App\Features\Performance\ManageCycles;

use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeDirectory;
use App\Features\Performance\Models\ReviewCycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CyclesController
{
    public function index(): View
    {
        return view('performance::cycles.index', [
            'cycles' => ReviewCycle::query()->withCount([
                'reviews',
                'reviews as done_count' => fn ($q) => $q->whereIn('status', ['completed', 'acknowledged']),
            ])->latest('period_end')->get(),
        ]);
    }

    /**
     * Launching a cycle creates one review per active employee, assigned to
     * their supervisor (or department head); HR reviews the rest.
     */
    public function store(Request $request, EmployeeDirectory $directory): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after:period_start'],
            'due_on' => ['required', 'date'],
            'criteria' => ['required', 'string', 'max:2000'],
        ]);

        $criteria = collect(preg_split('/\R/', $data['criteria']) ?: [])->map(fn ($l) => trim((string) $l))->filter()
            ->map(function (string $line) {
                [$name, $weight] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '1');

                return ['name' => $name, 'weight' => max(1, (int) $weight)];
            })->values()->all();

        $cycle = DB::transaction(function () use ($data, $criteria, $directory) {
            $cycle = ReviewCycle::query()->create([...$data, 'criteria' => $criteria, 'status' => 'open']);

            Employee::query()->active()->whereDate('hired_at', '<=', $data['period_end'])->each(fn (Employee $employee) => $cycle->reviews()->create([
                'employee_id' => $employee->id,
                'reviewer_id' => $directory->approverFor($employee)?->id,
                'status' => 'pending',
            ]));

            return $cycle;
        });

        return redirect()->route('performance.cycles.show', $cycle)->with('success', "Review cycle launched with {$cycle->reviews()->count()} review(s).");
    }

    public function show(ReviewCycle $cycle): View
    {
        return view('performance::cycles.show', [
            'cycle' => $cycle,
            'reviews' => $cycle->reviews()->with(['employee', 'reviewer'])->get()->sortBy(fn ($r) => $r->employee->last_name),
        ]);
    }

    public function close(ReviewCycle $cycle): RedirectResponse
    {
        $cycle->update(['status' => $cycle->status === 'open' ? 'closed' : 'open']);

        return back()->with('success', 'Cycle '.($cycle->status === 'open' ? 'reopened' : 'closed').'.');
    }
}
