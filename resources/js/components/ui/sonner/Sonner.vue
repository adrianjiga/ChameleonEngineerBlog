<script setup lang="ts">
import { Toaster } from "vue-sonner"
import { useAppearance } from "@/composables/useAppearance"

const { resolvedAppearance } = useAppearance()
</script>

<template>
  <Toaster
    data-slot="sonner"
    :theme="resolvedAppearance"
    class="toaster group"
  />
</template>
