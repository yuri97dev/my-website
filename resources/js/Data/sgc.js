import { route } from "ziggy-js";

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

export function getSgcLinks() {
    return sgcSections.map(section => ({
        key: section.key,
        icon: section.icon,
        text: section.title,
        href: section.href,
        active: route().current(`tools.sgc.${section.key}`),
    }));
}
