<?php

namespace App\Jobs;

use App\Models\Certificate;
use App\Services\TransactionalMail;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Storage;

class GenerateCertificate implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $certificateId) {}

    /** @return array<int, WithoutOverlapping> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('certificate-'.$this->certificateId))->releaseAfter(10)->expireAfter(180)];
    }

    public function handle(): void
    {
        $certificate = Certificate::findOrFail($this->certificateId);
        if ($certificate->generation_status === 'ready') {
            return;
        }
        try {
            $verification = url('/certificates/verify/'.$certificate->verification_token);
            $renderer = new ImageRenderer(new RendererStyle(130), new SvgImageBackEnd);
            $qr = base64_encode((new Writer($renderer))->writeString($verification));
            $pdf = Pdf::loadView('certificates.pdf', compact('certificate', 'verification', 'qr'))->setPaper('a4', 'landscape')->setOption('isRemoteEnabled', false)->setOption('isPhpEnabled', false)->output();
            $path = 'certificates/'.$certificate->credential_id.'.pdf';
            if (! Storage::disk('local')->put($path, $pdf)) {
                throw new \RuntimeException('certificate_storage_failed');
            }$certificate->update(['private_pdf_path' => $path, 'generation_status' => 'ready']);
            app(TransactionalMail::class)->queue($certificate->enrollment->user_id, 'certificate', (string) $certificate->id);
        } catch (\Throwable $e) {
            $certificate->update(['generation_status' => 'failed']);
            throw $e;
        }
    }
}
