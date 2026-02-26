<script setup lang="ts">
import CharacterCount from '@tiptap/extension-character-count';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import {
    Bold,
    Code,
    Code2,
    Heading2,
    Heading3,
    Image as ImageIcon,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Quote,
    Undo,
    Redo,
} from 'lucide-vue-next';
import { watch } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        placeholder?: string;
        maxLength?: number;
    }>(),
    { placeholder: 'Start writing...', maxLength: undefined },
);

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Placeholder.configure({ placeholder: props.placeholder }),
        Link.configure({ openOnClick: false }),
        Image,
        ...(props.maxLength !== undefined
            ? [CharacterCount.configure({ limit: props.maxLength })]
            : []),
    ],
    editorProps: {
        attributes: {
            class: 'prose dark:prose-invert prose-sm max-w-none min-h-[200px] p-4 outline-none focus:outline-none',
        },
    },
    onUpdate: ({ editor: e }) => {
        emit('update:modelValue', e.getHTML());
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (editor.value && editor.value.getHTML() !== value) {
            editor.value.commands.setContent(value, false);
        }
    },
);

function setLink() {
    const url = window.prompt('Enter URL');
    if (url) {
        editor.value?.chain().focus().setLink({ href: url }).run();
    }
}

function addImage() {
    const url = window.prompt('Enter image URL');
    if (url) {
        editor.value?.chain().focus().setImage({ src: url }).run();
    }
}

const characterCount = () => editor.value?.storage.characterCount?.characters?.() ?? 0;
</script>

<template>
    <div class="border-input overflow-hidden rounded-md border">
        <!-- Toolbar -->
        <div class="bg-muted/50 flex flex-wrap gap-0.5 border-b p-1.5">
            <button
                type="button"
                :class="['rounded p-1.5 transition-colors hover:bg-muted', editor?.isActive('bold') ? 'bg-muted text-foreground' : 'text-muted-foreground']"
                :disabled="!editor"
                @click="editor?.chain().focus().toggleBold().run()"
            >
                <Bold class="size-4" />
            </button>
            <button
                type="button"
                :class="['rounded p-1.5 transition-colors hover:bg-muted', editor?.isActive('italic') ? 'bg-muted text-foreground' : 'text-muted-foreground']"
                :disabled="!editor"
                @click="editor?.chain().focus().toggleItalic().run()"
            >
                <Italic class="size-4" />
            </button>
            <div class="bg-border mx-1 w-px self-stretch" />
            <button
                type="button"
                :class="['rounded p-1.5 transition-colors hover:bg-muted', editor?.isActive('heading', { level: 2 }) ? 'bg-muted text-foreground' : 'text-muted-foreground']"
                :disabled="!editor"
                @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()"
            >
                <Heading2 class="size-4" />
            </button>
            <button
                type="button"
                :class="['rounded p-1.5 transition-colors hover:bg-muted', editor?.isActive('heading', { level: 3 }) ? 'bg-muted text-foreground' : 'text-muted-foreground']"
                :disabled="!editor"
                @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()"
            >
                <Heading3 class="size-4" />
            </button>
            <div class="bg-border mx-1 w-px self-stretch" />
            <button
                type="button"
                :class="['rounded p-1.5 transition-colors hover:bg-muted', editor?.isActive('bulletList') ? 'bg-muted text-foreground' : 'text-muted-foreground']"
                :disabled="!editor"
                @click="editor?.chain().focus().toggleBulletList().run()"
            >
                <List class="size-4" />
            </button>
            <button
                type="button"
                :class="['rounded p-1.5 transition-colors hover:bg-muted', editor?.isActive('orderedList') ? 'bg-muted text-foreground' : 'text-muted-foreground']"
                :disabled="!editor"
                @click="editor?.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="size-4" />
            </button>
            <button
                type="button"
                :class="['rounded p-1.5 transition-colors hover:bg-muted', editor?.isActive('blockquote') ? 'bg-muted text-foreground' : 'text-muted-foreground']"
                :disabled="!editor"
                @click="editor?.chain().focus().toggleBlockquote().run()"
            >
                <Quote class="size-4" />
            </button>
            <button
                type="button"
                :class="['rounded p-1.5 transition-colors hover:bg-muted', editor?.isActive('code') ? 'bg-muted text-foreground' : 'text-muted-foreground']"
                :disabled="!editor"
                @click="editor?.chain().focus().toggleCode().run()"
            >
                <Code class="size-4" />
            </button>
            <button
                type="button"
                :class="['rounded p-1.5 transition-colors hover:bg-muted', editor?.isActive('codeBlock') ? 'bg-muted text-foreground' : 'text-muted-foreground']"
                :disabled="!editor"
                @click="editor?.chain().focus().toggleCodeBlock().run()"
            >
                <Code2 class="size-4" />
            </button>
            <div class="bg-border mx-1 w-px self-stretch" />
            <button
                type="button"
                :class="['rounded p-1.5 transition-colors hover:bg-muted', editor?.isActive('link') ? 'bg-muted text-foreground' : 'text-muted-foreground']"
                :disabled="!editor"
                @click="setLink"
            >
                <LinkIcon class="size-4" />
            </button>
            <button
                type="button"
                class="text-muted-foreground rounded p-1.5 transition-colors hover:bg-muted"
                :disabled="!editor"
                @click="addImage"
            >
                <ImageIcon class="size-4" />
            </button>
            <div class="ml-auto flex gap-0.5">
                <button
                    type="button"
                    class="text-muted-foreground rounded p-1.5 transition-colors hover:bg-muted disabled:opacity-40"
                    :disabled="!editor?.can().undo()"
                    @click="editor?.chain().focus().undo().run()"
                >
                    <Undo class="size-4" />
                </button>
                <button
                    type="button"
                    class="text-muted-foreground rounded p-1.5 transition-colors hover:bg-muted disabled:opacity-40"
                    :disabled="!editor?.can().redo()"
                    @click="editor?.chain().focus().redo().run()"
                >
                    <Redo class="size-4" />
                </button>
            </div>
        </div>

        <!-- Editor -->
        <EditorContent :editor="editor" />

        <!-- Character count -->
        <div v-if="maxLength !== undefined" class="text-muted-foreground border-t px-4 py-1.5 text-right text-xs">
            {{ characterCount() }} / {{ maxLength }}
        </div>
    </div>
</template>
