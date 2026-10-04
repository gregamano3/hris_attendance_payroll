<?php

namespace App\Features\Recruitment\Onboarding;

use App\Features\Employees\Models\Employee;
use App\Features\Recruitment\Models\OnboardingTask;
use App\Features\Recruitment\Models\OnboardingTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OnboardingController
{
    /** New hires with open onboarding tasks. */
    public function index(): View
    {
        return view('recruitment::onboarding.index', [
            'employees' => Employee::query()
                ->whereHas('onboardingTasks')
                ->withCount(['onboardingTasks', 'onboardingTasks as done_count' => fn ($q) => $q->whereNotNull('completed_at')])
                ->latest('hired_at')->limit(50)->get(),
            'templates' => OnboardingTemplate::query()->orderBy('name')->get(),
        ]);
    }

    public function show(Employee $employee): View
    {
        return view('recruitment::onboarding.show', [
            'employee' => $employee,
            'tasks' => $employee->onboardingTasks()->with('completer')->orderBy('sort')->get(),
            'templates' => OnboardingTemplate::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function start(Request $request, Employee $employee, StartOnboarding $onboarding): RedirectResponse
    {
        $data = $request->validate(['template_id' => ['required', 'integer', Rule::exists('onboarding_templates', 'id')]]);
        $count = $onboarding->handle($employee, OnboardingTemplate::query()->findOrFail($data['template_id']));

        return back()->with('success', "{$count} onboarding task(s) added.");
    }

    public function toggle(Request $request, OnboardingTask $task): RedirectResponse
    {
        $task->update($task->completed_at === null
            ? ['completed_at' => now(), 'completed_by' => $request->user()?->id]
            : ['completed_at' => null, 'completed_by' => null]);

        return back();
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('onboarding_templates')],
            'items' => ['required', 'string', 'max:5000'],
            'is_default' => ['boolean'],
        ]);

        // One task per line, optionally "Title | days after hire".
        $items = collect(preg_split('/\R/', $data['items']) ?: [])->map(fn ($line) => trim((string) $line))->filter()
            ->map(function (string $line) {
                [$title, $days] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '0');

                return ['title' => $title, 'due_after_days' => (int) $days];
            })->values()->all();

        if ($request->boolean('is_default')) {
            OnboardingTemplate::query()->update(['is_default' => false]);
        }

        OnboardingTemplate::query()->create(['name' => $data['name'], 'items' => $items, 'is_default' => $request->boolean('is_default')]);

        return back()->with('success', 'Onboarding template saved.');
    }
}
