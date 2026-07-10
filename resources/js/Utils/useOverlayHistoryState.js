import { onBeforeUnmount, watch } from "vue";

const stack = [];
let ignorePop = false;

window.addEventListener("popstate", () => {
    if( ignorePop ) {
        ignorePop = false;
        return;
    }

    stack.pop()?.close();
});

export function useOverlayHistoryState(isOpen) {
    let entry = null;

    watch(isOpen, (open, wasOpen) => {
        if( open && !wasOpen ) {
            entry = { close: () => { isOpen.value = false; } };
            history.pushState(null, "", location.href);
            stack.push(entry);
            return;
        }

        if( !open && wasOpen && entry ) {
            const idx = stack.indexOf(entry);

            if( idx === -1 ) {
                entry = null;
                return;
            }

            stack.splice(idx, 1);

            if( idx === stack.length ) {
                ignorePop = true;
                history.back();
            }

            entry = null;
        }
    });

    onBeforeUnmount(() => {
        if( !entry || !isOpen.value ) return;

        const idx = stack.indexOf(entry);

        if( idx === -1 ) return;

        stack.splice(idx, 1);

        if( idx === stack.length ) {
            ignorePop = true;
            history.back();
        }
    });
}
