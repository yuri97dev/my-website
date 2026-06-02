import { router } from "@inertiajs/vue3";

export function goTo(p) {
    if( !p ) {
        throw new Error("No event or href provided");
    }

    if( typeof p == "string" ) {
        router.visit(p);
        return;
    }

    if( p instanceof Event ) {
        p.preventDefault();

        const href = p.currentTarget?.getAttribute("href");

        if( href ) {
            router.visit(href);
        }

        return;
    }

    throw new Error("Invalid parameter provided");
}
