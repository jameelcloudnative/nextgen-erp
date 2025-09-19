import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/confirm-password', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout
            title="Confirm your password"
            description="This is a secure area of the application. Please confirm your password before continuing."
        >
            <Head title="Confirm password" />

            <form onSubmit={submit} className="space-y-6">
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
                        autoFocus
                    />
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <Button disabled={processing} className="w-full">
                    {processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
                    Confirm
                </Button>
            </form>
        </AuthLayout>
    );
}
