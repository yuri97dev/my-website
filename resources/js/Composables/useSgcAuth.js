import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

export function useSgcAuth() {
    const page = usePage();

    const companies = computed(() => page.props.auth?.sgc?.companies ?? []);

    function findCompany(companyId) {
        if( companyId == null ) {
            return null;
        }

        return companies.value.find((company) => company.id === Number(companyId)) ?? null;
    }

    function canManageCompany(companyId) {
        const company = findCompany(companyId);

        if( !company ) {
            return false;
        }

        return company.is_owner || company.role === "admin";
    }

    function isCompanyOwner(companyId) {
        const company = findCompany(companyId);
        return company?.is_owner ?? false;
    }

    return {
        canManageCompany,
        isCompanyOwner,
    };
}
