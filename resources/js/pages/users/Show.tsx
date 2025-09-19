import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Edit3, Mail, Calendar, Building, UserCog, Activity, Shield } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Company, Role, User, UserCompanyAssignment } from '@/types/user';

interface ShowUserProps {
    user: User;
    company: Company;
    roles: Role[];
    userCompanies: UserCompanyAssignment[];
}

export default function ShowUser({ user, company, roles, userCompanies }: ShowUserProps) {
    const getInitials = (name: string) => {
        return name.split(' ').map(n => n[0]).join('').toUpperCase();
    };

    const currentRole = roles.find(role => role.id === user.pivot?.role_id);
    const isDefaultCompany = user.pivot?.is_default;

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const formatDateTime = (dateString: string) => {
        return new Date(dateString).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

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
            title: user.name,
            href: `/users/${user.id}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={user.name} />

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
                        <div className="flex items-center gap-3">
                            <Avatar className="h-12 w-12">
                                <AvatarFallback className="text-lg font-semibold">
                                    {getInitials(user.name)}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <h1 className="text-3xl font-bold tracking-tight">{user.name}</h1>
                                <p className="text-muted-foreground flex items-center gap-2">
                                    <Mail className="h-4 w-4" />
                                    {user.email}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-2">
                            {isDefaultCompany && (
                                <Badge variant="default">Default Company</Badge>
                            )}
                            {currentRole && (
                                <Badge variant="outline" className="flex items-center gap-1">
                                    <Shield className="h-3 w-3" />
                                    {currentRole.name}
                                </Badge>
                            )}
                        </div>
                        <Button asChild>
                            <Link href={`/users/${user.id}/edit`}>
                                <Edit3 className="mr-2 h-4 w-4" />
                                Edit User
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-3">
                    {/* Main Information */}
                    <div className="md:col-span-2 space-y-6">
                        {/* Basic Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <UserCog className="h-5 w-5" />
                                    User Information
                                </CardTitle>
                                <CardDescription>
                                    Basic information about {user.name}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <div className="text-sm font-medium text-muted-foreground">Full Name</div>
                                        <div className="text-base font-medium">{user.name}</div>
                                    </div>

                                    <div>
                                        <div className="text-sm font-medium text-muted-foreground">Email Address</div>
                                        <div className="text-base">{user.email}</div>
                                    </div>

                                    <div>
                                        <div className="text-sm font-medium text-muted-foreground">Member Since</div>
                                        <div className="text-base flex items-center gap-2">
                                            <Calendar className="h-4 w-4" />
                                            {formatDate(user.created_at)}
                                        </div>
                                    </div>

                                    <div>
                                        <div className="text-sm font-medium text-muted-foreground">Email Verified</div>
                                        <div className="text-base">
                                            {user.email_verified_at ? (
                                                <Badge variant="outline" className="text-green-600">
                                                    Verified
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline" className="text-amber-600">
                                                    Unverified
                                                </Badge>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Company Role */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Building className="h-5 w-5" />
                                    Role in {company.name}
                                </CardTitle>
                                <CardDescription>
                                    User's role and permissions in the current company
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <div className="text-sm font-medium text-muted-foreground">Current Role</div>
                                        <div className="text-base font-medium flex items-center gap-2">
                                            <Shield className="h-4 w-4" />
                                            {currentRole?.name || 'No Role Assigned'}
                                        </div>
                                    </div>

                                    <div>
                                        <div className="text-sm font-medium text-muted-foreground">Company Status</div>
                                        <div className="text-base">
                                            {isDefaultCompany ? (
                                                <Badge variant="default">Default Company</Badge>
                                            ) : (
                                                <Badge variant="secondary">Secondary Company</Badge>
                                            )}
                                        </div>
                                    </div>

                                    {user.pivot?.created_at && (
                                        <div>
                                            <div className="text-sm font-medium text-muted-foreground">Joined Company</div>
                                            <div className="text-base">{formatDate(user.pivot.created_at)}</div>
                                        </div>
                                    )}

                                    {user.pivot?.last_activity_at && (
                                        <div>
                                            <div className="text-sm font-medium text-muted-foreground">Last Activity</div>
                                            <div className="text-base flex items-center gap-2">
                                                <Activity className="h-4 w-4" />
                                                {formatDateTime(user.pivot.last_activity_at)}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar Information */}
                    <div className="space-y-6">
                        {/* Quick Stats */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">Quick Stats</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex justify-between items-center">
                                    <span className="text-sm text-muted-foreground">Total Companies</span>
                                    <Badge variant="outline">{userCompanies.length}</Badge>
                                </div>

                                <Separator />

                                <div className="flex justify-between items-center">
                                    <span className="text-sm text-muted-foreground">Account Status</span>
                                    <Badge variant="outline" className="text-green-600">
                                        Active
                                    </Badge>
                                </div>

                                {user.email_verified_at && (
                                    <>
                                        <Separator />
                                        <div className="flex justify-between items-center">
                                            <span className="text-sm text-muted-foreground">Email Verified</span>
                                            <Badge variant="outline" className="text-green-600">
                                                {formatDate(user.email_verified_at)}
                                            </Badge>
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* All Companies */}
                        {userCompanies.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">All Companies</CardTitle>
                                    <CardDescription>
                                        Companies this user belongs to
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {userCompanies.map((userCompany, index) => (
                                            <div key={index} className="flex items-center justify-between p-3 border rounded-lg">
                                                <div className="flex-1">
                                                    <div className="font-medium text-sm">{userCompany.company_name}</div>
                                                    <div className="text-xs text-muted-foreground">{userCompany.role_name}</div>
                                                </div>
                                                <div className="flex flex-col items-end gap-1">
                                                    {userCompany.is_default && (
                                                        <Badge variant="default" className="text-xs">Default</Badge>
                                                    )}
                                                    <div className="text-xs text-muted-foreground">
                                                        {formatDate(userCompany.assigned_at)}
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
