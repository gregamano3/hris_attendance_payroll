<?php

namespace App\Features\Recruitment\ManageApplicants;

use App\Features\Recruitment\Enums\ApplicantStage;
use App\Features\Recruitment\Models\Applicant;
use App\Features\Recruitment\Models\JobOpening;
use App\Shared\Security\EncryptedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ApplicantsController
{
    public function store(Request $request, JobOpening $opening): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'source' => ['nullable', 'string', 'max:50'],
            'resume' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx'],
        ]);
        unset($data['resume']);

        if ($request->hasFile('resume')) {
            $path = "resumes/{$opening->id}/".Str::uuid().'.enc';
            EncryptedFiles::put('local', $path, (string) file_get_contents($request->file('resume')->getRealPath()));
            $data += ['resume_path' => $path, 'resume_name' => $request->file('resume')->getClientOriginalName()];
        }

        $applicant = $opening->applicants()->create([...$data, 'stage' => ApplicantStage::Applied, 'stage_changed_at' => now()]);
        $applicant->events()->create(['to_stage' => ApplicantStage::Applied->value, 'note' => 'Application received', 'user_id' => $request->user()?->id]);

        return back()->with('success', "{$applicant->fullName()} added to the pipeline.");
    }

    public function show(Applicant $applicant): View
    {
        return view('recruitment::applicants.show', [
            'applicant' => $applicant->load(['opening', 'events.user', 'employee']),
            'stages' => collect(ApplicantStage::cases())->reject(fn (ApplicantStage $s) => $s === ApplicantStage::Hired)
                ->mapWithKeys(fn (ApplicantStage $s) => [$s->value => $s->label()])->all(),
        ]);
    }

    public function move(Request $request, Applicant $applicant): RedirectResponse
    {
        $data = $request->validate([
            'stage' => ['required', Rule::in([ApplicantStage::Applied->value, ApplicantStage::Screening->value, ApplicantStage::Interview->value, ApplicantStage::Offer->value, ApplicantStage::Rejected->value])],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($applicant->stage === ApplicantStage::Hired) {
            return back()->with('error', 'Hired applicants cannot be moved.');
        }

        $from = $applicant->stage;
        $applicant->update(['stage' => ApplicantStage::from($data['stage']), 'stage_changed_at' => now()]);
        $applicant->events()->create(['from_stage' => $from->value, 'to_stage' => $data['stage'], 'note' => $data['note'] ?? null, 'user_id' => $request->user()?->id]);

        return back()->with('success', 'Applicant moved to '.ApplicantStage::from($data['stage'])->label().'.');
    }

    public function note(Request $request, Applicant $applicant): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $applicant->events()->create(['note' => $data['note'], 'user_id' => $request->user()?->id]);

        return back()->with('success', 'Note added.');
    }

    public function resume(Applicant $applicant): Response
    {
        abort_if($applicant->resume_path === null, 404);

        return response(EncryptedFiles::get('local', $applicant->resume_path), 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.str_replace('"', '', (string) $applicant->resume_name).'"',
        ]);
    }
}
