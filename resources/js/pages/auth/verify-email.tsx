// Components
import { Form, Head, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    const { csrfToken } = usePage<{ csrfToken: string }>().props;
    return (
        <>
            <Head title="Email verification" />

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    A new verification link has been sent to the email address
                    you provided during registration.
                </div>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && <Spinner />}
                            Resend verification email
                        </Button>

                    </>
                )}
            </Form>
            <form action="/logout" method="post" className="mt-4 text-center">
                <input type="hidden" name="_token" value={csrfToken} />
                <Button type="submit" variant="link">Log out</Button>
            </form>
        </>
    );
}

VerifyEmail.layout = {
    title: 'Email verification',
    description:
        'Please verify your email address by clicking on the link we just emailed to you.',
};
