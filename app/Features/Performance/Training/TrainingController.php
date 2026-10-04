<?php

namespace App\Features\Performance\Training;

use App\Features\Employees\Models\Employee;
use App\Features\Employees\Queries\EmployeeDirectory;
use App\Features\Performance\Models\Training;
use App\Shared\Security\EncryptedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class TrainingController
{
    public function index(Request $request): View
    {
        $employeeId = $request->integer('employee') ?: null;

        return view('performance::training.index', [
            'trainings' => Training::query()->with('employee')
                ->when($employeeId, fn ($q, $id) => $q->where('employee_id', $id))
                ->latest('completed_on')->paginate(30)->withQueryString(),
            'expiring' => Training::query()->with('employee')->whereNotNull('expires_on')
                ->whereBetween('expires_on', [today()->subDays(30), today()->addDays(60)])->orderBy('expires_on')->get(),
            'employees' => Employee::query()->active()->orderBy('last_name')->orderBy('first_name')->get()
                ->mapWithKeys(fn (Employee $e): array => [$e->id => "{$e->employee_no} — {$e->full_name}"])->all(),
            'employeeId' => $employeeId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:255'],
            'completed_on' => ['required', 'date'],
            'hours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'expires_on' => ['nullable', 'date', 'after:completed_on'],
            'certificate' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ]);
        unset($data['certificate']);

        if ($request->hasFile('certificate')) {
            $path = "training-certificates/{$data['employee_id']}/".Str::uuid().'.enc';
            EncryptedFiles::put('local', $path, (string) file_get_contents($request->file('certificate')->getRealPath()));
            $data += ['certificate_path' => $path, 'certificate_name' => $request->file('certificate')->getClientOriginalName()];
        }

        Training::query()->create($data);

        return back()->with('success', 'Training recorded.');
    }

    public function destroy(Training $training): RedirectResponse
    {
        $training->delete();

        return back()->with('success', 'Training removed.');
    }

    public function certificate(Request $request, Training $training, EmployeeDirectory $directory): Response
    {
        $user = $request->user();
        abort_unless($training->certificate_path && ($user?->can('performance.manage') || $directory->forUser($user)?->id === $training->employee_id), 403);

        return response(EncryptedFiles::get('local', $training->certificate_path), 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.str_replace('"', '', (string) $training->certificate_name).'"',
        ]);
    }
}
