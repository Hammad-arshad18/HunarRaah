import { Form, Head, router, usePage } from '@inertiajs/react';
import { ArrowRight, ShieldCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';

type SetupProps = { twoFactorEnabled: boolean; pendingSetup: boolean };

export default function AdminSetup({ twoFactorEnabled, pendingSetup }: SetupProps) {
    const { csrfToken } = usePage<{ csrfToken: string }>().props;
    const {
        qrCodeSvg, manualSetupKey, fetchSetupData, errors,
        recoveryCodesList, fetchRecoveryCodes,
    } = useTwoFactorAuth();
    const [starting, setStarting] = useState(false);
    const [startError, setStartError] = useState('');

    useEffect(() => {
        if (pendingSetup && !twoFactorEnabled) void fetchSetupData();
    }, [pendingSetup, twoFactorEnabled, fetchSetupData]);

    const start = () => {
        setStarting(true);
        setStartError('');
        router.post('/user/two-factor-authentication', {}, {
            preserveScroll: true,
            onError: () => setStartError('Setup could not be started. Please try again.'),
            onFinish: () => setStarting(false),
        });
    };

    return (
        <>
            <Head title="Administrator setup" />
            <div className="admin-onboarding">
                <div className="onboarding-icon"><ShieldCheck size={26} /></div>
                <h1>{twoFactorEnabled ? 'Your teaching desk is ready.' : 'One step to your teaching desk.'}</h1>
                <p className="onboarding-intro">
                    {twoFactorEnabled
                        ? 'Your administrator account is protected. Save your recovery codes, then open administration.'
                        : 'Connect an authenticator to protect student and payment records. This is a one-time setup for your administrator account.'}
                </p>

                {twoFactorEnabled ? (
                    <>
                        <Button variant="outline" onClick={() => void fetchRecoveryCodes()}>
                            Show recovery codes
                        </Button>
                        {recoveryCodesList.length > 0 && (
                            <div className="onboarding-codes">
                                <p>Keep these private. Each code can be used once if you lose access to your authenticator.</p>
                                <div>{recoveryCodesList.map(code => <code key={code}>{code}</code>)}</div>
                            </div>
                        )}
                        <a className="btn onboarding-continue" href="/admin">
                            Open administration <ArrowRight size={18} />
                        </a>
                    </>
                ) : qrCodeSvg && manualSetupKey ? (
                    <>
                        <div className="onboarding-step">
                            <span>01</span>
                            <div>
                                <h2>Connect your authenticator</h2>
                                <p>Scan this code with your authenticator app, or enter the setup key manually.</p>
                            </div>
                        </div>
                        <div className="onboarding-qr" dangerouslySetInnerHTML={{ __html: qrCodeSvg }} />
                        <details className="onboarding-key">
                            <summary>Use a setup key instead</summary>
                            <code>{manualSetupKey}</code>
                        </details>
                        <div className="onboarding-step">
                            <span>02</span>
                            <div>
                                <h2>Confirm the connection</h2>
                                <p>Enter the six-digit code from your authenticator.</p>
                            </div>
                        </div>
                        <Form action="/user/confirmed-two-factor-authentication" method="post" resetOnSuccess>
                            {({ errors: validation, processing }) => (
                                <div className="onboarding-confirm">
                                    <Label htmlFor="admin-auth-code">Authentication code</Label>
                                    <Input id="admin-auth-code" name="code" inputMode="numeric"
                                        autoComplete="one-time-code" maxLength={6} pattern="[0-9]{6}"
                                        required placeholder="000000" />
                                    <InputError message={validation.code} />
                                    <Button disabled={processing}>Confirm and finish setup</Button>
                                </div>
                            )}
                        </Form>
                    </>
                ) : (
                    <Button disabled={starting} onClick={start}>
                        {starting ? 'Starting setup…' : 'Connect authenticator'}
                    </Button>
                )}

                {(startError || errors.length > 0) && (
                    <div className="operation-error" role="alert">
                        {startError || errors.join('. ')}
                        <button type="button" onClick={() => {
                            setStartError('');
                            if (pendingSetup) void fetchSetupData();
                            else start();
                        }}>Try again</button>
                    </div>
                )}
                <form action="/logout" method="post">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <Button type="submit" variant="link">Log out</Button>
                </form>
            </div>
        </>
    );
}
