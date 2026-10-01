<?php

namespace App\Http\Controllers;

use App\Actions\IssueCertificate;
use App\Models\Certificate;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('studio/credentials', ['certificates' => Certificate::whereHas('enrollment', fn ($q) => $q->where('user_id', $request->user()->id))->whereNotNull('current_enrollment_id')->select('credential_id', 'course_title', 'status', 'generation_status', 'issued_at')->latest()->paginate(20), 'eligible' => Enrollment::where('user_id', $request->user()->id)->where('status', 'active')->whereNotNull('completed_at')->whereHas('course', fn ($q) => $q->where('certificate_enabled', true))->whereDoesntHave('certificates', fn ($q) => $q->whereNotNull('current_enrollment_id'))->with('course:id,title')->get()->map(fn ($e) => ['id' => $e->id, 'title' => $e->course->title])]);
    }

    public function retry(Request $request, string $credential): RedirectResponse
    {
        $c = $this->owned($request, $credential);
        abort_unless($c->generation_status === 'failed', 409, 'Only failed generation can be retried.');
        $c->update(['generation_status' => 'pending']);
        \App\Jobs\GenerateCertificate::dispatch($c->id);

        return back();
    }

    public function adminDownload(Certificate $certificate): StreamedResponse
    {
        abort_unless($certificate->generation_status === 'ready' && $certificate->private_pdf_path, 409, 'PDF generation is pending.');
        DB::table('audit_logs')->insert(['actor_id' => auth()->id(), 'action' => 'certificate.downloaded', 'subject_type' => 'certificate', 'subject_id' => $certificate->id, 'created_at' => now()]);

        return Storage::disk('local')->download($certificate->private_pdf_path, 'certificate-'.$certificate->credential_id.'.pdf', ['Cache-Control' => 'no-store, private']);
    }

    public function issue(Request $request, Enrollment $enrollment, IssueCertificate $action): RedirectResponse
    {
        $c = $action->execute($request->user(), $enrollment);

        return redirect('/certificates/'.$c->credential_id);
    }

    private function owned(Request $request, string $credential): Certificate
    {
        return Certificate::where('credential_id', $credential)->whereHas('enrollment', fn ($q) => $q->where('user_id', $request->user()->id))->firstOrFail();
    }

    public function show(Request $request, string $credential): Response
    {
        $c = $this->owned($request, $credential);

        return Inertia::render('studio/certificate', ['certificate' => [...$c->only('credential_id', 'learner_name', 'course_title', 'issuer_name', 'issued_at', 'status', 'generation_status'), 'public_enabled' => (bool) $c->public_enabled_at, 'verification_url' => url('/certificates/verify/'.$c->verification_token)], 'linkedin_url' => config('platform.linkedin_url')]);
    }

    public function download(Request $request, string $credential): StreamedResponse
    {
        $c = $this->owned($request, $credential);
        abort_unless($c->generation_status === 'ready' && $c->private_pdf_path, 409, 'PDF generation is pending.');

        return Storage::disk('local')->download($c->private_pdf_path, 'certificate-'.$c->credential_id.'.pdf', ['Cache-Control' => 'no-store, private']);
    }

    public function sharing(Request $request, string $credential): RedirectResponse
    {
        $c = $this->owned($request, $credential);
        $data = $request->validate(['enabled' => 'required|boolean', 'consent' => 'required_if:enabled,true|accepted']);
        $c->update(['public_enabled_at' => $data['enabled'] ? now() : null, 'consent_version' => $data['enabled'] ? 'public-credential-v1' : null]);

        return back();
    }

    public function verify(string $token): \Illuminate\Http\Response
    {
        $c = Certificate::where('verification_token', $token)->first();
        $available = $c && $c->public_enabled_at;

        return response()->view('certificates.verify', ['certificate' => $available ? $c : null], $available ? 200 : 404)->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
