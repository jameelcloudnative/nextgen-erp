// Components
import { useForm } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <AuthLayout title="Forgot password" description="Enter your email to receive a password reset link">
            <Head title="Forgot password" />

            {status && <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>}

            <form onSubmit={submit} className="space-y-6">
                <div className="grid gap-2">
                    <Label htmlFor="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        autoComplete="username"
                        className="mt-1 block w-full"
                        autoFocus
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <Button disabled={processing} className="w-full">
                    {processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
                    Send reset link
                </Button>

                <div className="text-center text-sm">
                    Remember your password?{' '}
                    <TextLink href="/login">log in</TextLink>
                </div>
            </form>
        </AuthLayout>
    );
}
