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
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

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

const linkUrl = ref('');
const linkOpen = ref(false);
const imageUrl = ref('');
const imageOpen = ref(false);
const linkInput = ref<InstanceType<typeof Input> | null>(null);
const imageInput = ref<InstanceType<typeof Input> | null>(null);

function openLinkPopover() {
    const existing = editor.value?.getAttributes('link').href ?? '';
    linkUrl.value = existing;
    linkOpen.value = true;
    nextTick(() => {
        (linkInput.value?.$el as HTMLInputElement | undefined)?.focus();
    });
}

function submitLink() {
    if (linkUrl.value) {
        editor.value?.chain().focus().setLink({ href: linkUrl.value }).run();
    } else {
        editor.value?.chain().focus().unsetLink().run();
    }
    linkOpen.value = false;
    linkUrl.value = '';
}

function openImagePopover() {
    imageUrl.value = '';
    imageOpen.value = true;
    nextTick(() => {
        (imageInput.value?.$el as HTMLInputElement | undefined)?.focus();
    });
}

function submitImage() {
    if (imageUrl.value) {
        editor.value?.chain().focus().setImage({ src: imageUrl.value }).run();
    }
    imageOpen.value = false;
    imageUrl.value = '';
}

onBeforeUnmount(() => {
    editor.value?.destroy();
});

const characterCount = computed(
    () => editor.value?.storage.characterCount?.characters?.() ?? 0,
);
</script>

<template>
    <div class="overflow-hidden rounded-md border border-input">
        <!-- Toolbar -->
        <div class="flex flex-wrap gap-0.5 border-b bg-muted/50 p-1.5">
            <button
                type="button"
                :class="[
                    'rounded p-1.5 transition-colors hover:bg-muted',
                    editor?.isActive('bold')
                        ? 'bg-muted text-foreground'
                        : 'text-muted-foreground',
                ]"
                :disabled="!editor"
                aria-label="Bold"
                @click="editor?.chain().focus().toggleBold().run()"
            >
                <Bold class="size-4" />
            </button>
            <button
                type="button"
                :class="[
                    'rounded p-1.5 transition-colors hover:bg-muted',
                    editor?.isActive('italic')
                        ? 'bg-muted text-foreground'
                        : 'text-muted-foreground',
                ]"
                :disabled="!editor"
                aria-label="Italic"
                @click="editor?.chain().focus().toggleItalic().run()"
            >
                <Italic class="size-4" />
            </button>
            <div class="mx-1 w-px self-stretch bg-border" />
            <button
                type="button"
                :class="[
                    'rounded p-1.5 transition-colors hover:bg-muted',
                    editor?.isActive('heading', { level: 2 })
                        ? 'bg-muted text-foreground'
                        : 'text-muted-foreground',
                ]"
                :disabled="!editor"
                aria-label="Heading 2"
                @click="
                    editor?.chain().focus().toggleHeading({ level: 2 }).run()
                "
            >
                <Heading2 class="size-4" />
            </button>
            <button
                type="button"
                :class="[
                    'rounded p-1.5 transition-colors hover:bg-muted',
                    editor?.isActive('heading', { level: 3 })
                        ? 'bg-muted text-foreground'
                        : 'text-muted-foreground',
                ]"
                :disabled="!editor"
                aria-label="Heading 3"
                @click="
                    editor?.chain().focus().toggleHeading({ level: 3 }).run()
                "
            >
                <Heading3 class="size-4" />
            </button>
            <div class="mx-1 w-px self-stretch bg-border" />
            <button
                type="button"
                :class="[
                    'rounded p-1.5 transition-colors hover:bg-muted',
                    editor?.isActive('bulletList')
                        ? 'bg-muted text-foreground'
                        : 'text-muted-foreground',
                ]"
                :disabled="!editor"
                aria-label="Bullet list"
                @click="editor?.chain().focus().toggleBulletList().run()"
            >
                <List class="size-4" />
            </button>
            <button
                type="button"
                :class="[
                    'rounded p-1.5 transition-colors hover:bg-muted',
                    editor?.isActive('orderedList')
                        ? 'bg-muted text-foreground'
                        : 'text-muted-foreground',
                ]"
                :disabled="!editor"
                aria-label="Ordered list"
                @click="editor?.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="size-4" />
            </button>
            <button
                type="button"
                :class="[
                    'rounded p-1.5 transition-colors hover:bg-muted',
                    editor?.isActive('blockquote')
                        ? 'bg-muted text-foreground'
                        : 'text-muted-foreground',
                ]"
                :disabled="!editor"
                aria-label="Blockquote"
                @click="editor?.chain().focus().toggleBlockquote().run()"
            >
                <Quote class="size-4" />
            </button>
            <button
                type="button"
                :class="[
                    'rounded p-1.5 transition-colors hover:bg-muted',
                    editor?.isActive('code')
                        ? 'bg-muted text-foreground'
                        : 'text-muted-foreground',
                ]"
                :disabled="!editor"
                aria-label="Inline code"
                @click="editor?.chain().focus().toggleCode().run()"
            >
                <Code class="size-4" />
            </button>
            <button
                type="button"
                :class="[
                    'rounded p-1.5 transition-colors hover:bg-muted',
                    editor?.isActive('codeBlock')
                        ? 'bg-muted text-foreground'
                        : 'text-muted-foreground',
                ]"
                :disabled="!editor"
                aria-label="Code block"
                @click="editor?.chain().focus().toggleCodeBlock().run()"
            >
                <Code2 class="size-4" />
            </button>
            <div class="mx-1 w-px self-stretch bg-border" />
            <Popover v-model:open="linkOpen">
                <PopoverTrigger as-child>
                    <button
                        type="button"
                        :class="[
                            'rounded p-1.5 transition-colors hover:bg-muted',
                            editor?.isActive('link')
                                ? 'bg-muted text-foreground'
                                : 'text-muted-foreground',
                        ]"
                        :disabled="!editor"
                        aria-label="Insert link"
                        @click="openLinkPopover"
                    >
                        <LinkIcon class="size-4" />
                    </button>
                </PopoverTrigger>
                <PopoverContent class="w-80 p-3" side="bottom" align="start">
                    <form class="flex gap-2" @submit.prevent="submitLink">
                        <Input
                            ref="linkInput"
                            v-model="linkUrl"
                            type="url"
                            placeholder="https://example.com"
                            class="h-8 text-xs"
                        />
                        <button
                            type="submit"
                            class="shrink-0 rounded-md bg-primary px-3 py-1 text-xs font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Apply
                        </button>
                    </form>
                </PopoverContent>
            </Popover>
            <Popover v-model:open="imageOpen">
                <PopoverTrigger as-child>
                    <button
                        type="button"
                        class="rounded p-1.5 text-muted-foreground transition-colors hover:bg-muted"
                        :disabled="!editor"
                        aria-label="Insert image"
                        @click="openImagePopover"
                    >
                        <ImageIcon class="size-4" />
                    </button>
                </PopoverTrigger>
                <PopoverContent class="w-80 p-3" side="bottom" align="start">
                    <form class="flex gap-2" @submit.prevent="submitImage">
                        <Input
                            ref="imageInput"
                            v-model="imageUrl"
                            type="url"
                            placeholder="https://example.com/image.png"
                            class="h-8 text-xs"
                        />
                        <button
                            type="submit"
                            class="shrink-0 rounded-md bg-primary px-3 py-1 text-xs font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Insert
                        </button>
                    </form>
                </PopoverContent>
            </Popover>
            <div class="ml-auto flex gap-0.5">
                <button
                    type="button"
                    class="rounded p-1.5 text-muted-foreground transition-colors hover:bg-muted disabled:opacity-40"
                    :disabled="!editor?.can().undo()"
                    aria-label="Undo"
                    @click="editor?.chain().focus().undo().run()"
                >
                    <Undo class="size-4" />
                </button>
                <button
                    type="button"
                    class="rounded p-1.5 text-muted-foreground transition-colors hover:bg-muted disabled:opacity-40"
                    :disabled="!editor?.can().redo()"
                    aria-label="Redo"
                    @click="editor?.chain().focus().redo().run()"
                >
                    <Redo class="size-4" />
                </button>
            </div>
        </div>

        <!-- Editor -->
        <EditorContent :editor="editor" />

        <!-- Character count -->
        <div
            v-if="maxLength !== undefined"
            class="border-t px-4 py-1.5 text-right text-xs text-muted-foreground"
        >
            {{ characterCount }} / {{ maxLength }}
        </div>
    </div>
</template>
