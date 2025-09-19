import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, Search, Filter, Users as UsersIcon, UserCheck, Building, MoreHorizontal, Edit, Trash2, Eye } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { UserListData, User } from '@/types/user';

export default function UsersIndex({ users, company, roles, filters, stats }: UserListData) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedRole, setSelectedRole] = useState(filters.role?.toString() || 'all');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || 'all');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/users', {
            search: searchTerm,
            role: selectedRole === 'all' ? '' : selectedRole,
            status: selectedStatus === 'all' ? '' : selectedStatus,
        }, { preserveState: true });
    };

    const clearFilters = () => {
        setSearchTerm('');
        setSelectedRole('all');
        setSelectedStatus('all');
        router.get('/users');
    };

    const getInitials = (name: string) => {
        return name.split(' ').map(n => n[0]).join('').toUpperCase();
    };

    const getRoleBadgeVariant = (roleName: string) => {
        switch (roleName.toLowerCase()) {
            case 'admin':
                return 'destructive';
            case 'manager':
                return 'default';
            case 'user':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    const getRoleNameForUser = (user: User): string => {
        if (!user.companies || user.companies.length === 0) return 'Unknown';
        const currentCompanyRole = user.companies.find(c => c.id === company.id);
        if (!currentCompanyRole) return 'Unknown';

        const role = roles.find(r => r.id === currentCompanyRole.pivot.role_id);
        return role?.name || 'Unknown';
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString();
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
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="User Management" />

            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">User Management</h1>
                        <p className="text-muted-foreground">
                            Manage users in {company.name}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/users/available">
                                <UserCheck className="mr-2 h-4 w-4" />
                                Assign Existing User
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href="/users/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Add User
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Users</CardTitle>
                            <UsersIcon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_users}</div>
                            <p className="text-xs text-muted-foreground">
                                Users in {company.name}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Active Users</CardTitle>
                            <UserCheck className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.active_users}</div>
                            <p className="text-xs text-muted-foreground">
                                Currently active
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Administrators</CardTitle>
                            <Building className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.admin_users}</div>
                            <p className="text-xs text-muted-foreground">
                                Admin role users
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-6">
                        <form onSubmit={handleSearch} className="flex items-end gap-4">
                            <div className="flex-1">
                                <label className="text-sm font-medium">Search Users</label>
                                <div className="relative mt-1">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Search by name or email..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        className="pl-9"
                                    />
                                </div>
                            </div>

                            <div className="min-w-[150px]">
                                <label className="text-sm font-medium">Role</label>
                                <Select value={selectedRole} onValueChange={setSelectedRole}>
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="All roles" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All roles</SelectItem>
                                        {roles.map((role) => (
                                            <SelectItem key={role.id} value={role.id.toString()}>
                                                {role.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="min-w-[150px]">
                                <label className="text-sm font-medium">Status</label>
                                <Select value={selectedStatus} onValueChange={setSelectedStatus}>
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="All status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All status</SelectItem>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="inactive">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit">
                                    <Filter className="mr-2 h-4 w-4" />
                                    Filter
                                </Button>
                                <Button type="button" variant="outline" onClick={clearFilters}>
                                    Clear
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Users Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Users ({users.total})</CardTitle>
                        <CardDescription>
                            Manage users and their roles within {company.name}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {users.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <UsersIcon className="h-12 w-12 text-muted-foreground mb-4" />
                                <h3 className="text-lg font-semibold">No users found</h3>
                                <p className="text-muted-foreground mb-4">
                                    {searchTerm || (selectedRole !== 'all') || (selectedStatus !== 'all')
                                        ? 'No users match your search criteria.'
                                        : 'Start by adding your first user to the company.'
                                    }
                                </p>
                                <Button asChild>
                                    <Link href="/users/create">
                                        <Plus className="mr-2 h-4 w-4" />
                                        Add First User
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>User</TableHead>
                                            <TableHead>Role</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Joined</TableHead>
                                            <TableHead className="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {users.data.map((user) => {
                                            const roleName = getRoleNameForUser(user);
                                            const companyAssignment = user.companies?.find(c => c.id === company.id);

                                            return (
                                                <TableRow key={user.id}>
                                                    <TableCell>
                                                        <div className="flex items-center gap-3">
                                                            <Avatar>
                                                                <AvatarFallback>
                                                                    {getInitials(user.name)}
                                                                </AvatarFallback>
                                                            </Avatar>
                                                            <div>
                                                                <div className="font-semibold">{user.name}</div>
                                                                <div className="text-sm text-muted-foreground">
                                                                    {user.email}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant={getRoleBadgeVariant(roleName)}>
                                                            {roleName}
                                                        </Badge>
                                                        {companyAssignment?.pivot.is_default && (
                                                            <Badge variant="outline" className="ml-2">
                                                                Default
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant="secondary">
                                                            Active
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground">
                                                        {formatDate(companyAssignment?.pivot.created_at || user.created_at)}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger asChild>
                                                                <Button variant="ghost" className="h-8 w-8 p-0">
                                                                    <MoreHorizontal className="h-4 w-4" />
                                                                </Button>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent align="end">
                                                                <DropdownMenuItem asChild>
                                                                    <Link href={`/users/${user.id}`}>
                                                                        <Eye className="mr-2 h-4 w-4" />
                                                                        View Details
                                                                    </Link>
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem asChild>
                                                                    <Link href={`/users/${user.id}/edit`}>
                                                                        <Edit className="mr-2 h-4 w-4" />
                                                                        Edit User
                                                                    </Link>
                                                                </DropdownMenuItem>
                                                                <DropdownMenuSeparator />
                                                                <DropdownMenuItem
                                                                    className="text-destructive"
                                                                    onClick={() => {
                                                                        if (confirm('Are you sure you want to remove this user from the company?')) {
                                                                            router.delete(`/users/${user.id}`);
                                                                        }
                                                                    }}
                                                                >
                                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                                    Remove from Company
                                                                </DropdownMenuItem>
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>

                                {/* Pagination */}
                                {users.last_page > 1 && (
                                    <div className="flex items-center justify-between px-2 py-4">
                                        <div className="text-sm text-muted-foreground">
                                            Showing {users.from} to {users.to} of {users.total} users
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            {users.prev_page_url && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => router.get(users.prev_page_url!)}
                                                >
                                                    Previous
                                                </Button>
                                            )}
                                            <span className="text-sm">
                                                Page {users.current_page} of {users.last_page}
                                            </span>
                                            {users.next_page_url && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => router.get(users.next_page_url!)}
                                                >
                                                    Next
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
