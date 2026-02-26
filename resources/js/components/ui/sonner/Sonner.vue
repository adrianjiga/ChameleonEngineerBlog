<script setup lang="ts">
import { Toaster } from "vue-sonner"
import { useAppearance } from "@/composables/useAppearance"

withDefaults(defineProps<{
  position?: 'top-left' | 'top-center' | 'top-right' | 'bottom-left' | 'bottom-center' | 'bottom-right'
  richColors?: boolean
}>(), {
  position: 'bottom-right',
  richColors: false,
})

const { resolvedAppearance } = useAppearance()
</script>

<template>
  <Toaster
    data-slot="sonner"
    :theme="resolvedAppearance"
    :position="position"
    :rich-colors="richColors"
    class="toaster group"
  />
</template>
