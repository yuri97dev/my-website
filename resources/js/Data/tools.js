import { route } from "ziggy-js";

export const tools = [
    {
        key: "spreadsheet-formatter",
        title: "Formatador de CSV e XLSX",
        subtitle: "Limpe e padronize textos de planilhas em poucos cliques",
        icon: "mdi-table-edit",
        get href() {
            return route("tools.spreadsheet-formatter");
        },
        features: [
            "Remove HTML",
            "Padroniza texto",
            "Exporta CSV/XLSX",
        ],
    },
    {
        key: "sgc",
        title: "SGC",
        subtitle: "Sistema de gestão comercial",
        icon: "mdi-storefront-outline",
        get href() {
            return route("tools.sgc");
        },
        features: [
            "Empresas",
            "Clientes",
            "Produtos",
        ],
    },
    {
        key: "licenses",
        title: "Gerenciador de Licenças",
        subtitle: "Controle o acesso dos seus clientes às suas aplicações",
        icon: "mdi-key-chain-variant",
        get href() {
            return route("tools.licenses");
        },
        features: [
            "Chave RSA por licença",
            "Trava por dispositivo",
            "Revogação imediata",
        ],
    },
];
