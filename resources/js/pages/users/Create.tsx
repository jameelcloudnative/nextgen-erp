import { Head, Link, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowLeft, Plus, UserPlus, Search } from 'lucide-react';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Checkbox } from '@/components/ui/checkbox';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Company, Role, CreateUserFormData } from '@/types/user';

interface CreateUserProps {
    company: Company;
    roles: Role[];
}

interface AvailableUser {
    id: number;
    name: string;
    email: string;
    created_at: string;
}

export default function CreateUser({ company, roles }: CreateUserProps) {
    const [activeTab, setActiveTab] = useState('create-new');
    const [availableUsers, setAvailableUsers] = useState<AvailableUser[]>([]);
    const [searchingUsers, setSearchingUsers] = useState(false);
    const [userSearch, setUserSearch] = useState('');

    const { data, setData, processing, errors } = useForm<CreateUserFormData>({
        action: 'create_new',
        role_id: 0,
        is_default: false,
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        user_id: 0,
    });

    const searchAvailableUsers = async () => {
        if (!userSearch.trim()) return;

        setSearchingUsers(true);
        try {
            const response = await fetch(`/users/available?search=${encodeURIComponent(userSearch)}`);
            const result = await response.json();
            setAvailableUsers(result.data || []);
        } catch (error) {
            console.error('Error searching users:', error);
            setAvailableUsers([]);
        } finally {
            setSearchingUsers(false);
        }
    };

    const handleTabChange = (value: string) => {
        setActiveTab(value);
        setData('action', value === 'create-new' ? 'create_new' : 'assign_existing');

        // Clear form data when switching tabs
        if (value === 'create-new') {
            setData({
                ...data,
                action: 'create_new',
                user_id: 0,
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
            });
        } else {
            setData({
                ...data,
                action: 'assign_existing',
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
            });
        }
    };

    const selectUser = (user: AvailableUser) => {
        setData('user_id', user.id);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Prepare data based on action
        let submitData: Partial<CreateUserFormData> = {
            action: data.action,
            role_id: data.role_id,
            is_default: data.is_default,
        };

        if (data.action === 'create_new') {
            submitData = {
                ...submitData,
                name: data.name,
                email: data.email,
                password: data.password,
                password_confirmation: data.password_confirmation,
            };
        } else {
            submitData = {
                ...submitData,
                user_id: data.user_id,
            };
        }

        // Use router.post with the filtered data
        router.post('/users', submitData);
    };

    const getInitials = (name: string) => {
        return name.split(' ').map(n => n[0]).join('').toUpperCase();
    };

    const selectedUser = availableUsers.find(u => u.id === data.user_id);

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
            title: 'Add User',
            href: '/users/create',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add User" />

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
                            <h1 className="text-3xl font-bold tracking-tight">Add User</h1>
                            <p className="text-muted-foreground">
                                Add a new user or assign an existing user to {company.name}
                            </p>
                        </div>
                    </div>
                </div>

                <div className="max-w-2xl">
                    <Card>
                        <CardHeader>
                            <CardTitle>User Assignment</CardTitle>
                            <CardDescription>
                                Choose whether to create a new user or assign an existing user to your company.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <Tabs value={activeTab} onValueChange={handleTabChange}>
                                    <TabsList className="grid w-full grid-cols-2">
                                        <TabsTrigger value="create-new">
                                            <Plus className="mr-2 h-4 w-4" />
                                            Create New User
                                        </TabsTrigger>
                                        <TabsTrigger value="assign-existing">
                                            <UserPlus className="mr-2 h-4 w-4" />
                                            Assign Existing User
                                        </TabsTrigger>
                                    </TabsList>

                                    <TabsContent value="create-new" className="space-y-4">
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
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="password">Password</Label>
                                                <Input
                                                    id="password"
                                                    type="password"
                                                    value={data.password}
                                                    onChange={(e) => setData('password', e.target.value)}
                                                    placeholder="Enter user's password"
                                                    className={errors.password ? 'border-destructive' : ''}
                                                />
                                                {errors.password && (
                                                    <p className="text-sm text-destructive">{errors.password}</p>
                                                )}
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="password_confirmation">Confirm Password</Label>
                                                <Input
                                                    id="password_confirmation"
                                                    type="password"
                                                    value={data.password_confirmation}
                                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                                    placeholder="Confirm user's password"
                                                    className={errors.password_confirmation ? 'border-destructive' : ''}
                                                />
                                                {errors.password_confirmation && (
                                                    <p className="text-sm text-destructive">{errors.password_confirmation}</p>
                                                )}
                                            </div>
                                        </div>
                                    </TabsContent>

                                    <TabsContent value="assign-existing" className="space-y-4">
                                        <div className="space-y-4">
                                            <div className="grid gap-2">
                                                <Label>Search Users</Label>
                                                <div className="flex gap-2">
                                                    <Input
                                                        type="text"
                                                        value={userSearch}
                                                        onChange={(e) => setUserSearch(e.target.value)}
                                                        placeholder="Search by name or email..."
                                                        onKeyPress={(e) => e.key === 'Enter' && searchAvailableUsers()}
                                                    />
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        onClick={searchAvailableUsers}
                                                        disabled={searchingUsers}
                                                    >
                                                        <Search className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>

                                            {/* Available Users List */}
                                            {availableUsers.length > 0 && (
                                                <div className="space-y-2">
                                                    <Label>Available Users</Label>
                                                    <div className="border rounded-md max-h-60 overflow-y-auto">
                                                        {availableUsers.map((user) => (
                                                            <div
                                                                key={user.id}
                                                                className={`flex items-center gap-3 p-3 border-b last:border-b-0 cursor-pointer hover:bg-muted/50 ${
                                                                    data.user_id === user.id ? 'bg-muted' : ''
                                                                }`}
                                                                onClick={() => selectUser(user)}
                                                            >
                                                                <Avatar className="h-8 w-8">
                                                                    <AvatarFallback className="text-xs">
                                                                        {getInitials(user.name)}
                                                                    </AvatarFallback>
                                                                </Avatar>
                                                                <div className="flex-1">
                                                                    <div className="font-medium text-sm">{user.name}</div>
                                                                    <div className="text-xs text-muted-foreground">{user.email}</div>
                                                                </div>
                                                                {data.user_id === user.id && (
                                                                    <Badge variant="secondary">Selected</Badge>
                                                                )}
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}

                                            {/* Selected User Display */}
                                            {selectedUser && (
                                                <div className="border rounded-md p-3 bg-muted/30">
                                                    <Label>Selected User</Label>
                                                    <div className="flex items-center gap-3 mt-2">
                                                        <Avatar>
                                                            <AvatarFallback>
                                                                {getInitials(selectedUser.name)}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <div>
                                                            <div className="font-medium">{selectedUser.name}</div>
                                                            <div className="text-sm text-muted-foreground">{selectedUser.email}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}

                                            {errors.user_id && (
                                                <p className="text-sm text-destructive">{errors.user_id}</p>
                                            )}
                                        </div>
                                    </TabsContent>
                                </Tabs>

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

                                {/* General Errors */}
                                {Object.keys(errors).some(key => !['name', 'email', 'password', 'password_confirmation', 'role_id', 'user_id'].includes(key)) && (
                                    <div className="p-3 bg-destructive/10 border border-destructive rounded-md">
                                        <p className="text-sm text-destructive">Please check the form for errors and try again.</p>
                                    </div>
                                )}

                                {/* Submit Buttons */}
                                <div className="flex justify-end gap-3">
                                    <Button type="button" variant="outline" asChild>
                                        <Link href="/users">Cancel</Link>
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Processing...' : (
                                            activeTab === 'create-new' ? 'Create & Assign User' : 'Assign User'
                                        )}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
