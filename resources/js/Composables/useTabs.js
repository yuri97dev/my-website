import { ref, computed } from "vue";

let nextSeq = 1;

const tabs = ref({});
const active = ref(null);

const list = computed(() => {
    return Object.values(tabs.value).sort((a, b) => a.seq - b.seq);
});

export function useTabs() {
    function open({ id, title, icon, href, seq, payload, openTab = true } = {}) {
        if( list.value.length === 0 ) {
            nextSeq = 1;
        }

        const tabId = id ?? crypto.randomUUID();
        const tabSeq = seq ?? nextSeq++;

        if( tabSeq >= nextSeq ) {
            nextSeq = tabSeq + 1;
        }

        tabs.value[tabId] = {
            id: tabId,
            title: title ?? `Tab ${tabSeq}`,
            seq: tabSeq,
            icon: icon ?? null,
            href: href ?? null,
            payload: payload ?? null,
        };

        if( openTab ) {
            setTimeout(() => {
                active.value = tabId;
            }, 10);
        }

        return tabId;
    }

    function rename(id, title) {
        if( tabs.value[id] ) {
            tabs.value[id].title = title;
        }
    }

    function close(id) {
        delete tabs.value[id];

        if( list.value.length > 0 ) {
            if( active.value === id ) {
                const [ firstTab ] = list.value;
                active.value = firstTab.id;
            }

            return;
        }

        active.value = null;
    }

    function clear() {
        tabs.value = {};
        active.value = null;
        nextSeq = 1;
    }

    function sync(items, activeId) {
        const sameSet = items.length === list.value.length
            && items.every(item => tabs.value[item.id]);

        if( sameSet ) {
            active.value = activeId;
            return;
        }

        clear();

        for( const item of items ) {
            open({ ...item, openTab: false });
        }

        active.value = activeId;
    }

    return {
        list,
        active,
        open,
        rename,
        close,
        clear,
        sync,
    };
}
