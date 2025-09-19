import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, Trash2, UserCog } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Company, Role, User, UpdateUserFormData } from '@/types/user';

interface EditUserProps {
    user: User;
    company: Company;
    roles: Role[];
}

export default function EditUser({ user, company, roles }: EditUserProps) {
    const { data, setData, put, processing, errors } = useForm<UpdateUserFormData>({
        name: user.name || '',
        email: user.email || '',
        role_id: user.pivot?.role_id || 0,
        is_default: user.pivot?.is_default || false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/users/${user.id}`);
    };

    const handleDelete = () => {
        // Using DELETE method to remove user from company
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/users/${user.id}`;

        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';

        const tokenField = document.createElement('input');
        tokenField.type = 'hidden';
        tokenField.name = '_token';
        tokenField.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        form.appendChild(methodField);
        form.appendChild(tokenField);
        document.body.appendChild(form);
        form.submit();
    };

    const currentRole = roles.find(role => role.id === user.pivot?.role_id);
    const canChangePassword = user.pivot?.role_id !== 1; // Assuming role 1 is super admin

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
        {
            title: 'User Management',
            href: '/users',
        },
        {
            title: 'Edit User',
            href: `/users/${user.id}/edit`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${user.name}`} />

            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/users">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Back to Users
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Edit User</h1>
                            <p className="text-muted-foreground">
                                Update {user.name}'s information and role in {company.name}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Badge variant={user.pivot?.is_default ? 'default' : 'secondary'}>
                            {user.pivot?.is_default ? 'Default Company' : 'Secondary Company'}
                        </Badge>
                        {currentRole && (
                            <Badge variant="outline">{currentRole.name}</Badge>
                        )}
                    </div>
                </div>

                <div className="max-w-2xl">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <UserCog className="h-5 w-5" />
                                User Information
                            </CardTitle>
                            <CardDescription>
                                Update the user's basic information and their role in your company.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Basic Information */}
                                <div className="grid gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Full Name</Label>
                                        <Input
                                            id="name"
                                            type="text"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            placeholder="Enter user's full name"
                                            className={errors.name ? 'border-destructive' : ''}
                                        />
                                        {errors.name && (
                                            <p className="text-sm text-destructive">{errors.name}</p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email Address</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            placeholder="Enter user's email address"
                                            className={errors.email ? 'border-destructive' : ''}
                                        />
                                        {errors.email && (
                                            <p className="text-sm text-destructive">{errors.email}</p>
                                        )}
                                        <p className="text-xs text-muted-foreground">
                                            User joined on {new Date(user.created_at).toLocaleDateString()}
                                        </p>
                                    </div>
                                </div>

                                {/* Role Selection */}
                                <div className="grid gap-2">
                                    <Label htmlFor="role">Role in Company</Label>
                                    <Select
                                        value={data.role_id.toString()}
                                        onValueChange={(value) => setData('role_id', parseInt(value))}
                                    >
                                        <SelectTrigger className={errors.role_id ? 'border-destructive' : ''}>
                                            <SelectValue placeholder="Select a role" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {roles.map((role) => (
                                                <SelectItem key={role.id} value={role.id.toString()}>
                                                    {role.name}
                                                    {role.id === currentRole?.id && ' (Current)'}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.role_id && (
                                        <p className="text-sm text-destructive">{errors.role_id}</p>
                                    )}
                                </div>

                                {/* Default Company Checkbox */}
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_default"
                                        checked={data.is_default}
                                        onCheckedChange={(checked) => setData('is_default', checked as boolean)}
                                    />
                                    <Label htmlFor="is_default" className="text-sm">
                                        Set {company.name} as default company for this user
                                    </Label>
                                </div>

                                {data.is_default && (
                                    <div className="p-3 bg-blue-50 border border-blue-200 rounded-md">
                                        <p className="text-sm text-blue-800">
                                            This user will be redirected to {company.name} when they log in.
                                        </p>
                                    </div>
                                )}

                                {/* User Stats */}
                                {user.pivot?.last_activity_at && (
                                    <div className="p-3 bg-muted/30 rounded-md">
                                        <div className="text-sm">
                                            <strong>Last Activity:</strong>{' '}
                                            {new Date(user.pivot.last_activity_at).toLocaleString()}
                                        </div>
                                        {user.companies && user.companies.length > 1 && (
                                            <div className="text-sm mt-1">
                                                <strong>Total Companies:</strong> {user.companies.length}
                                            </div>
                                        )}
                                    </div>
                                )}

                                {/* Action Buttons */}
                                <div className="flex justify-between">
                                    <AlertDialog>
                                        <AlertDialogTrigger asChild>
                                            <Button type="button" variant="destructive" size="sm">
                                                <Trash2 className="mr-2 h-4 w-4" />
                                                Remove from Company
                                            </Button>
                                        </AlertDialogTrigger>
                                        <AlertDialogContent>
                                            <AlertDialogHeader>
                                                <AlertDialogTitle>Remove User from Company</AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    Are you sure you want to remove {user.name} from {company.name}?
                                                    They will lose access to all company resources.
                                                    {user.pivot?.is_default && (
                                                        <span className="block mt-2 text-amber-600 font-medium">
                                                            Warning: This is their default company. They will need to select
                                                            a new default company if they belong to others.
                                                        </span>
                                                    )}
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>
                                            <AlertDialogFooter>
                                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                <AlertDialogAction
                                                    onClick={handleDelete}
                                                    className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                >
                                                    Remove User
                                                </AlertDialogAction>
                                            </AlertDialogFooter>
                                        </AlertDialogContent>
                                    </AlertDialog>

                                    <div className="flex gap-3">
                                        <Button type="button" variant="outline" asChild>
                                            <Link href="/users">Cancel</Link>
                                        </Button>
                                        <Button type="submit" disabled={processing}>
                                            <Save className="mr-2 h-4 w-4" />
                                            {processing ? 'Updating...' : 'Update User'}
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Password Reset Card */}
                    {canChangePassword && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Password Reset</CardTitle>
                                <CardDescription>
                                    Send a password reset email to {user.name}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        // This would trigger password reset email
                                        const form = document.createElement('form');
                                        form.method = 'POST';
                                        form.action = `/users/${user.id}/reset-password`;

                                        const tokenField = document.createElement('input');
                                        tokenField.type = 'hidden';
                                        tokenField.name = '_token';
                                        tokenField.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                                        form.appendChild(tokenField);
                                        document.body.appendChild(form);
                                        form.submit();
                                    }}
                                >
                                    Send Password Reset Email
                                </Button>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
