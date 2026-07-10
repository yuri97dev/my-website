import { onBeforeUnmount, watch } from "vue";

const stack = [];
let ignorePops = 0;

window.addEventListener("popstate", () => {
    if( ignorePops > 0 ) {
        ignorePops--;
        return;
    }

    stack.pop()?.close();
});

function removeEntry(entry) {
    const idx = stack.indexOf(entry);

    if( idx === -1 ) return;

    const wasTop = idx === stack.length - 1;

    stack.splice(idx, 1);

    if( wasTop ) {
        ignorePops++;
        history.back();
    }
}

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
            removeEntry(entry);
            entry = null;
        }
    });

    onBeforeUnmount(() => {
        if( !entry || !isOpen.value ) return;
        removeEntry(entry);
    });
}
