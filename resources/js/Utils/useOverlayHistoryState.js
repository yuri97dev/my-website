import { onBeforeUnmount, onMounted, watch } from "vue";

function createOverlayId() {
    return crypto.randomUUID?.() ?? `${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

export function useOverlayHistoryState(isOpen) {
    const overlayId = createOverlayId();

    let pushedByOverlay = false;
    let closingByPopstate = false;

    function onPopstate(event) {
        if( !pushedByOverlay || !isOpen.value ) return;

        if( event.state?.overlayId === overlayId ) return;

        closingByPopstate = true;
        pushedByOverlay = false;

        isOpen.value = false;
    }

    watch(isOpen, (open, wasOpen) => {
        if( open && !wasOpen ) {
            history.pushState({ overlayId }, "", window.location.href);
            pushedByOverlay = true;
            return;
        }

        if( !open && wasOpen ) {
            if( closingByPopstate ) {
                closingByPopstate = false;
                return;
            }

            if( pushedByOverlay ) {
                pushedByOverlay = false;
                history.back();
            }
        }
    });

    onMounted(() => {
        window.addEventListener("popstate", onPopstate);
    });

    onBeforeUnmount(() => {
        window.removeEventListener("popstate", onPopstate);

        if( pushedByOverlay && isOpen.value ) {
            pushedByOverlay = false;
            history.back();
        }
    });
}
