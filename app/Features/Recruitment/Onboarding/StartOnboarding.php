<?php

namespace App\Features\Recruitment\Onboarding;

use App\Features\Employees\Models\Employee;
use App\Features\Recruitment\Models\OnboardingTemplate;

class StartOnboarding
{
    public function handle(Employee $employee, ?OnboardingTemplate $template = null): int
    {
        $template ??= OnboardingTemplate::query()->where('is_default', true)->first();

        if ($template === null) {
            return 0;
        }

        foreach ($template->items as $i => $item) {
            $employee->onboardingTasks()->create([
                'title' => $item['title'],
                'due_on' => $employee->hired_at->copy()->addDays($item['due_after_days']),
                'sort' => $i,
            ]);
        }

        return count($template->items);
    }
}
