import { router } from '@inertiajs/react';
import { useState } from 'react';
import StudioShell from '@/components/studio-shell';
type Certificate = {
    credential_id: string;
    learner_name: string;
    course_title: string;
    issuer_name: string;
    issued_at: string;
    status: string;
    generation_status: string;
    public_enabled: boolean;
    verification_url: string;
};
export default function CertificatePage({
    certificate: c,
    linkedin_url,
}: {
    certificate: Certificate;
    linkedin_url: string | null;
}) {
    const [publicEnabled, setPublicEnabled] = useState(c.public_enabled);
    const [sharingPending, setSharingPending] = useState(false);
    const changeSharing = (enabled: boolean) => {
        setPublicEnabled(enabled);
        setSharingPending(true);
        router.patch(
            `/certificates/${c.credential_id}/sharing`,
            { enabled, consent: true },
            {
                onError: () => {
                    setPublicEnabled(c.public_enabled);
                    setNotice('Sharing preference could not be saved.');
                },
                onFinish: () => setSharingPending(false),
            },
        );
    };
    const [notice, setNotice] = useState('');
    const copy = async (value: string) => {
        try {
            await navigator.clipboard.writeText(value);
            setNotice('Copied.');
        } catch {
            setNotice('Copy unavailable. Select and copy the field manually.');
        }
    };
    return (
        <StudioShell title="Your certificate">
            <section className="section reading">
                <p className="eyebrow">Your completed chapter</p>
                <h1>Certificate of Completion</h1>
                <div className="credential-preview">
                    <p>{c.issuer_name}</p>
                    <h2>{c.learner_name}</h2>
                    <h3>{c.course_title}</h3>
                    <p>Issued {new Date(c.issued_at).toLocaleDateString()}</p>
                    <span className="status">{c.status}</span>
                </div>
                <p>PDF status: {c.generation_status}</p>
                {c.generation_status === 'ready' && (
                    <a
                        className="button"
                        href={`/certificates/${c.credential_id}/download`}
                    >
                        Download PDF
                    </a>
                )}
                {c.generation_status === 'failed' && (
                    <button
                        className="button secondary"
                        onClick={() =>
                            router.post(
                                `/certificates/${c.credential_id}/retry`,
                            )
                        }
                    >
                        Retry PDF generation
                    </button>
                )}
                {c.generation_status === 'pending' && (
                    <p className="note">
                        Your PDF is being prepared. Refresh to check its status.
                    </p>
                )}
                <section className="section">
                    <h3>Choose what you share.</h3>
                    <p>
                        Public verification lets anyone with the link see your
                        name, course and issue date. It is private by default.
                    </p>
                    <label>
                        <input
                            type="checkbox"
                            checked={publicEnabled}
                            disabled={sharingPending}
                            onChange={(e) => changeSharing(e.target.checked)}
                        />{' '}
                        Enable public verification with my consent
                    </label>
                    <div className="actions">
                        <button
                            className="button secondary"
                            disabled={!publicEnabled || sharingPending}
                            onClick={() => copy(c.verification_url)}
                        >
                            Copy verification link
                        </button>
                        {linkedin_url && (
                            <a
                                className="button secondary"
                                target="_blank"
                                rel="noopener noreferrer"
                                href={linkedin_url}
                            >
                                Add to LinkedIn profile ↗
                            </a>
                        )}
                    </div>
                    {[
                        ['Certificate name', c.course_title],
                        ['Issuer', c.issuer_name],
                        [
                            'Issue date',
                            new Date(c.issued_at).toLocaleDateString(),
                        ],
                        ['Credential ID', c.credential_id],
                        ['Verification URL', c.verification_url],
                    ].map(([label, value]) => (
                        <div key={label}>
                            <label>{label}</label>
                            <p style={{ overflowWrap: 'anywhere' }}>{value}</p>
                            <button
                                className="secondary"
                                onClick={() => copy(value)}
                            >
                                Copy {label.toLowerCase()}
                            </button>
                        </div>
                    ))}
                    <p role="status">{notice}</p>
                </section>
            </section>
        </StudioShell>
    );
}
