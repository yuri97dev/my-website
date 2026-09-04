import { onUnmounted, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";

import { useTabs } from "@/Composables/useTabs";

let leaveWatchRegistered = false;

export const sgcSections = [
    {
        key: "companies",
        title: "Empresas",
        subtitle: "Gerencie empresas e filiais",
        icon: "mdi-domain",
        get href() {
            return route("tools.sgc.companies");
        },
        features: [
            "Matriz e filiais",
            "Endereço",
            "Documento",
        ],
    },
    {
        key: "employees",
        title: "Funcionários",
        subtitle: "Equipe vinculada às empresas",
        icon: "mdi-badge-account-horizontal-outline",
        get href() {
            return route("tools.sgc.employees");
        },
        features: [
            "Vínculo por empresa",
            "Cargos",
            "Acesso por e-mail",
        ],
    },
    {
        key: "customers",
        title: "Clientes",
        subtitle: "Cadastro de clientes por empresa",
        icon: "mdi-account-group",
        get href() {
            return route("tools.sgc.customers");
        },
        features: [
            "Dados pessoais",
            "Endereços",
            "Observações",
        ],
    },
    {
        key: "products",
        title: "Produtos",
        subtitle: "Catálogo de produtos por empresa",
        icon: "mdi-package-variant-closed",
        get href() {
            return route("tools.sgc.products");
        },
        features: [
            "Marcas",
            "Categorias",
            "Especificações",
        ],
    },
    {
        key: "orders",
        title: "Pedidos",
        subtitle: "Vendas e pedidos por empresa",
        icon: "mdi-clipboard-list-outline",
        get href() {
            return route("tools.sgc.orders");
        },
        features: [
            "Cliente e empresa",
            "Formas de pagamento",
            "Itens e parcelas",
        ],
    },
];

export function isSgcRoute() {
    return route().current("tools.sgc") || route().current("tools.sgc.*");
}

export function registerSgcTabs() {
    const { sync, clear } = useTabs();

    const items = [
        {
            id: "index",
            title: "Início",
            icon: "mdi-view-dashboard-outline",
            href: route("tools.sgc"),
            seq: 1,
        },
        ...sgcSections.map((section, index) => ({
            id: section.key,
            title: section.title,
            icon: section.icon,
            href: section.href,
            seq: index + 2,
        })),
    ];

    let activeId = "index";

    if( !route().current("tools.sgc") ) {
        const section = sgcSections.find(item => route().current(`tools.sgc.${item.key}`));
        activeId = section?.key ?? "index";
    }

    sync(items, activeId);

    if( !leaveWatchRegistered ) {
        leaveWatchRegistered = true;

        const page = usePage();

        watch(() => page.url, () => {
            if( !isSgcRoute() ) {
                clear();
            }
        });
    }

    onUnmounted(() => {
        if( !isSgcRoute() ) {
            clear();
        }
    });
}
