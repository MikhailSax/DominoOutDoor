<template>
    <div>
        <Spinner v-if="!isMapReady" message="Загрузка.." />
        <MapSection v-else />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Spinner from './Spinner.vue'
import MapSection from './MapApp.vue'

const isMapReady = ref(false)

onMounted(async () => {
    const el = document.getElementById('initial-spinner')
    if (el) el.remove()

    isMapReady.value = true
})
</script>
