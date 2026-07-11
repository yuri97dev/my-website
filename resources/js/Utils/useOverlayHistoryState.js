import { onBeforeUnmount, watch } from "vue";

const stack = [];
let selfPops = 0;

function keepEntryClean() {
    if( stack.length === 0 ) return;

    queueMicrotask(() => {
        if( stack.length === 0 ) return;
        history.replaceState(null, "", location.href);
    });
}

window.addEventListener("popstate", () => {
    if( selfPops > 0 ) {
        selfPops--;
    } else {
        stack.pop()?.close();
    }

    keepEntryClean();
});

function removeEntry(entry) {
    const idx = stack.indexOf(entry);

    if( idx === -1 ) return;

    const wasTop = idx === stack.length - 1;

    stack.splice(idx, 1);

    if( wasTop ) {
        selfPops++;
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
        if( !entry ) return;

        const idx = stack.indexOf(entry);

        if( idx !== -1 ) stack.splice(idx, 1);

        entry = null;
    });
}
