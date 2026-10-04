<?php

namespace App\Features\Recruitment\ManageOpenings;

use App\Features\Employees\Enums\EmploymentType;
use App\Features\Employees\Queries\EmployeeOptions;
use App\Features\Recruitment\Enums\ApplicantStage;
use App\Features\Recruitment\Models\JobOpening;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OpeningsController
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString() === 'closed' ? 'closed' : 'open';

        return view('recruitment::openings.index', [
            'status' => $status,
            'openings' => JobOpening::query()->with(['department', 'branch'])->withCount([
                'applicants',
                'applicants as active_count' => fn ($q) => $q->whereNotIn('stage', [ApplicantStage::Hired, ApplicantStage::Rejected]),
                'applicants as hired_count' => fn ($q) => $q->where('stage', ApplicantStage::Hired),
            ])->where('status', $status)->latest()->get(),
        ]);
    }

    public function create(EmployeeOptions $options): View
    {
        return view('recruitment::openings.form', ['opening' => new JobOpening(['slots' => 1]), ...$this->formData($options)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $opening = JobOpening::query()->create([...$this->validated($request), 'status' => 'open']);

        return redirect()->route('recruitment.openings.show', $opening)->with('success', 'Job opening created.');
    }

    public function show(JobOpening $opening): View
    {
        $opening->load(['department', 'position', 'branch']);

        return view('recruitment::openings.show', [
            'opening' => $opening,
            'columns' => collect(ApplicantStage::pipeline())->mapWithKeys(fn (ApplicantStage $s) => [
                $s->value => $opening->applicants()->where('stage', $s)->latest('stage_changed_at')->get(),
            ]),
        ]);
    }

    public function edit(JobOpening $opening, EmployeeOptions $options): View
    {
        return view('recruitment::openings.form', ['opening' => $opening, ...$this->formData($options)]);
    }

    public function update(Request $request, JobOpening $opening): RedirectResponse
    {
        $opening->update($this->validated($request));

        return redirect()->route('recruitment.openings.show', $opening)->with('success', 'Job opening updated.');
    }

    public function toggle(JobOpening $opening): RedirectResponse
    {
        $closing = $opening->status === 'open';
        $opening->update(['status' => $closing ? 'closed' : 'open', 'closed_at' => $closing ? now() : null]);

        return back()->with('success', $closing ? 'Job opening closed.' : 'Job opening reopened.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(EmployeeOptions $options): array
    {
        return [
            'departments' => $options->departments(),
            'positions' => $options->positions(),
            'branches' => $options->branches(),
            'employmentTypes' => EmploymentType::options(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'position_id' => ['nullable', 'integer', Rule::exists('positions', 'id')],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'slots' => ['required', 'integer', 'min:1', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'],
        ]);
    }
}
