<template>
    <div
        ref="editorRef"
        class="w-100 h-100"
    ></div>
</template>

<script setup>
    import { ref, watch, onMounted, onBeforeUnmount } from "vue";

    import * as monaco from "monaco-editor/esm/vs/editor/editor.api";
    import "monaco-editor/esm/vs/editor/editor.all.js";
    import "monaco-editor/esm/vs/basic-languages/html/html.contribution.js";
    import "monaco-editor/esm/vs/language/html/monaco.contribution.js";
    import { emmetHTML } from "emmet-monaco-es";

    import editorWorkerUrl from "monaco-editor/esm/vs/editor/editor.worker?worker&url";
    import htmlWorkerUrl from "monaco-editor/esm/vs/language/html/html.worker?worker&url";

    const model = defineModel({ type: String, default: "" });

    const props = defineProps({
        language: {
            type: String,
            default: "html",
        },
        options: {
            type: Object,
            default: () => ({}),
        },
    });

    const editorRef = ref(null);

    let editor = null;
    let isUpdating = false;

    function createWorker(workerUrl) {
        const url = new URL(workerUrl, import.meta.url).href;
        const blob = new Blob([`import ${JSON.stringify(url)};`], { type: "text/javascript" });

        return new Worker(URL.createObjectURL(blob), { type: "module" });
    }

    self.MonacoEnvironment = {
        getWorker(_, label) {
            if( ["html", "handlebars", "razor"].includes(label) ) {
                return createWorker(htmlWorkerUrl);
            }

            return createWorker(editorWorkerUrl);
        },
    };

    emmetHTML(monaco);

    watch(model, (value) => {
        if( !editor || isUpdating ) return;

        const currentValue = editor.getValue();

        if( value !== currentValue ) {
            isUpdating = true;
            editor.setValue(value ?? "");
            isUpdating = false;
        }
    });

    watch(() => props.language, (language) => {
        if( !editor ) return;
        monaco.editor.setModelLanguage(editor.getModel(), language);
    });

    onMounted(() => {
        console.log("iniciando editor");
        editor = monaco.editor.create(editorRef.value, {
            value: model.value,
            language: props.language,
            automaticLayout: true,
            minimap: { enabled: false },
            tabSize: 4,
            theme: "vs-dark",
            // wordWrap: "on",
            scrollBeyondLastLine: false,
            ...props.options,
        });

        editor.onDidChangeModelContent(() => {
            if( isUpdating ) return;

            isUpdating = true;
            model.value = editor.getValue();
            isUpdating = false;
        });
    });

    onBeforeUnmount(() => {
        editor?.dispose();
    });
</script>
