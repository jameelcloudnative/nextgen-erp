// Components
import { Form, Head, router } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';

export default function VerifyEmail({ status }: { status?: string }) {
    const handleLogout = () => {
        router.post('/logout');
    };

    return (
        <AuthLayout title="Verify email" description="Please verify your email address by clicking on the link we just emailed to you.">
            <Head title="Email verification" />

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    A new verification link has been sent to the email address you provided during registration.
                </div>
            )}

            <Form>
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Resend verification email
                        </Button>

                        <button
                            type="button"
                            onClick={handleLogout}
                            className="mx-auto block text-sm text-blue-600 hover:text-blue-500 underline underline-offset-4"
                        >
                            Log out
                        </button>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
