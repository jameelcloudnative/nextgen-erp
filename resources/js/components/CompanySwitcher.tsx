import { Building } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import axios from 'axios';

interface Company {
    id: number;
    name: string;
    code: string;
    currency: string;
}

interface CompanySwitcherProps {
    className?: string;
}

interface PageProps {
    company: {
        active: Company | null;
    };
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
        } | null;
    };
    [key: string]: unknown;
}

export default function CompanySwitcher({ className = '' }: CompanySwitcherProps) {
    const { company, auth } = usePage<PageProps>().props;
    const [companies, setCompanies] = useState<Company[]>([]);
    const [loading, setLoading] = useState(false);
    const [activeCompany, setActiveCompany] = useState<Company | null>(company?.active || null);

    useEffect(() => {
        if (auth?.user) {
            fetchAccessibleCompanies();
        }
    }, [auth?.user]);

    // Don't render if user is not authenticated
    if (!auth?.user) {
        return null;
    }

    const fetchAccessibleCompanies = async () => {
        try {
            const response = await axios.get('/company/accessible');
            setCompanies(response.data.companies);

            // Find and set the active company
            const active = response.data.companies.find(
                (comp: Company) => comp.id === response.data.active_company_id
            );
            if (active) {
                setActiveCompany(active);
            }
        } catch (error) {
            console.error('Failed to fetch companies:', error);
        }
    };

    const switchCompany = async (companyId: string) => {
        const company = companies.find(c => c.id === parseInt(companyId));
        if (!company || company.id === activeCompany?.id) return;

        setLoading(true);
        try {
            await axios.post('/company/switch', { company_id: company.id });
            setActiveCompany(company);

            // Reload the page to refresh all data with new company context
            window.location.reload();
        } catch (error) {
            console.error('Failed to switch company:', error);
            alert('Failed to switch company. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    if (!companies.length || !activeCompany) {
        return null;
    }

    return (
        <Select
            value={activeCompany.id.toString()}
            onValueChange={switchCompany}
            disabled={loading}
        >
            <SelectTrigger className={`w-[250px] ${className}`}>
                <div className="flex items-center gap-2">
                    <Building className="h-4 w-4 text-muted-foreground" />
                    <SelectValue>
                        <span className="font-medium">{activeCompany.name}</span>
                        <span className="text-muted-foreground ml-2">({activeCompany.code})</span>
                    </SelectValue>
                </div>
            </SelectTrigger>
            <SelectContent>
                {companies.map((comp) => (
                    <SelectItem key={comp.id} value={comp.id.toString()}>
                        <div className="flex items-center gap-2">
                            <Building className="h-4 w-4 text-muted-foreground" />
                            <span className="font-medium">{comp.name}</span>
                            <span className="text-muted-foreground">({comp.code})</span>
                            {comp.currency && (
                                <span className="text-xs text-muted-foreground ml-auto">
                                    {comp.currency}
                                </span>
                            )}
                        </div>
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
