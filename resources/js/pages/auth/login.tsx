import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout title="Log in to your account" description="Enter your email and password below to log in">
            <Head title="Log in" />

            <form onSubmit={submit} className="flex flex-col gap-6">
                <div className="grid gap-6">
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

                    <div className="grid gap-2">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            autoComplete="current-password"
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.password} className="mt-2" />

                        {canResetPassword && (
                            <div className="flex justify-end">
                                <TextLink href="/forgot-password" className="ml-auto text-sm" tabIndex={5}>
                                    Forgot your password?
                                </TextLink>
                            </div>
                        )}
                    </div>

                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="remember"
                            name="remember"
                            checked={data.remember}
                            onCheckedChange={(checked) => setData('remember', checked)}
                        />
                        <Label htmlFor="remember" className="text-sm">
                            Remember me
                        </Label>
                    </div>
                </div>

                {status && <div className="mb-4 text-sm font-medium text-green-600">{status}</div>}

                <Button disabled={processing} className="w-full">
                    {processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
                    Log in
                </Button>

                <div className="mt-6 text-center text-sm">
                    Don't have an account?{' '}
                    <TextLink href="/register" tabIndex={5}>
                        Sign up
                    </TextLink>
                </div>
            </form>
        </AuthLayout>
    );
}
