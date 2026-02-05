<template>
    <div
        class="flex h-[calc(100vh-72px)] min-h-[680px] flex-col overflow-hidden bg-gradient-to-br from-gray-50 to-gray-100 text-gray-800 lg:flex-row">
        <aside class="w-full border-b border-gray-200 bg-white lg:w-[430px] lg:border-b-0 lg:border-r">
            <div class="flex h-full flex-col">
                <div class="border-b border-gray-100 p-5">
                    <h1 class="text-2xl font-bold text-gray-900">Карта рекламных конструкций</h1>
                    <p class="mt-1 text-sm text-gray-500">Выберите параметры, чтобы найти подходящие площадки.</p>
                </div>

                <div class="space-y-3 border-b border-gray-100 bg-gray-50 p-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Тип продукции</label>
                        <select
                            v-model="filters.productType"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-200"
                        >
                            <option value="">Все типы продукции</option>
                            <option v-for="item in productTypes" :key="item.id" :value="String(item.id)">{{
                                    item.name
                                }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Тип конструкции</label>
                        <select
                            v-model="filters.constrTypeId"
                            :disabled="isLoadingFilters"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-200 disabled:cursor-not-allowed disabled:bg-gray-100"
                        >
                            <option value="">Все типы конструкций</option>
                            <option v-for="item in constrTypes" :key="item.id" :value="String(item.id)">{{
                                    item.name
                                }}
                            </option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                            @click="resetFilters"
                        >
                            Сбросить
                        </button>
                        <button
                            type="button"
                            class="flex-1 rounded-lg border border-red-600 bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700"
                            @click="applyFilters"
                        >
                            Применить
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3 text-sm">
                    <span class="font-medium text-gray-600">Найдено объектов:</span>
                    <span class="rounded-full bg-red-600 px-3 py-1 text-white">{{ objects.length }}</span>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-3">
                    <div v-if="isLoadingObjects" class="p-4 text-sm text-gray-500">Загрузка объектов...</div>
                    <div v-else-if="objects.length === 0"
                         class="rounded-lg border border-dashed border-gray-300 bg-white p-4 text-sm text-gray-500">
                        По выбранным фильтрам ничего не найдено.
                    </div>

                    <button
                        v-for="item in objects"
                        :key="item.id"
                        type="button"
                        class="mb-2 w-full rounded-xl border bg-white p-4 text-left shadow-sm transition hover:shadow-md"
                        :class="activeObjectId === item.id ? 'border-red-400 ring-2 ring-red-100' : 'border-gray-200'"
                        @click="focusObject(item.id)"
                    >
                        <div class="mb-1 flex items-start justify-between gap-3">
                            <h3 class="line-clamp-2 text-sm font-semibold text-gray-900">
                                {{ item.address || 'Адрес не указан' }}</h3>
                            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600">#{{ item.id }}</span>
                        </div>
                        <p class="text-xs text-gray-600">{{ item.category || 'Категория не указана' }} •
                            {{ item.type || 'Тип не указан' }}</p>
                        <p class="mt-1 text-xs text-gray-500">Стороны: {{ formatSides(item.sides) }}</p>
                    </button>
                </div>
            </div>
        </aside>

        <section class="relative min-h-[380px] flex-1 p-3 lg:p-5">
            <div v-if="mapError"
                 class="flex h-full items-center justify-center rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-700">
                {{ mapError }}
            </div>
            <div v-else-if="!isMapLoaded"
                 class="flex h-full items-center justify-center rounded-2xl border border-gray-200 bg-white text-sm text-gray-500">
                Загрузка карты...
            </div>
            <div v-show="isMapLoaded" ref="mapContainer"
                 class="h-full w-full rounded-2xl border-2 border-white shadow-lg"></div>
        </section>
    </div>
</template>

<script setup>
import {computed, onBeforeUnmount, onMounted, reactive, ref, watch} from 'vue'

const props = defineProps({
    filtersUrl: {
        type: String,
        required: true,
    },
    advertisementsUrl: {
        type: String,
        required: true,
    },
})

const productTypes = ref([])
const constrTypes = ref([])
const objects = ref([])
const filters = reactive({productType: '', constrTypeId: ''})
const isLoadingFilters = ref(false)
const isLoadingObjects = ref(false)

const mapContainer = ref(null)
const isMapLoaded = ref(false)
const mapError = ref('')
const activeObjectId = ref(null)

let map = null
let placemarks = new Map()

const filteredParams = computed(() => {
    const params = new URLSearchParams()
    if (filters.productType) {
        params.append('productType', filters.productType)
    }
    if (filters.constrTypeId) {
        params.append('constrTypeId', filters.constrTypeId)
    }

    return params
})

function formatSides(sides) {
    if (!Array.isArray(sides) || sides.length === 0) {
        return '—'
    }

    return sides.join(', ')
}

async function fetchJson(url) {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
        },
        credentials: 'same-origin',
    })

    if (!response.ok) {
        throw new Error(`Ошибка запроса (${response.status})`)
    }

    return response.json()
}

async function loadFilters() {
    isLoadingFilters.value = true
    try {
        const query = new URLSearchParams()
        if (filters.productType) {
            query.append('productType', filters.productType)
        }

        const url = query.toString() ? `${props.filtersUrl}?${query.toString()}` : props.filtersUrl
        const data = await fetchJson(url)
        productTypes.value = Array.isArray(data.productTypes) ? data.productTypes : []
        constrTypes.value = Array.isArray(data.constrTypes) ? data.constrTypes : []
    } finally {
        isLoadingFilters.value = false
    }
}

async function loadAdvertisements() {
    isLoadingObjects.value = true
    try {
        const query = filteredParams.value.toString()
        const url = query ? `${props.advertisementsUrl}?${query}` : props.advertisementsUrl
        const data = await fetchJson(url)
        objects.value = Array.isArray(data) ? data : []

        syncMapPlacemarks()
    } finally {
        isLoadingObjects.value = false
    }
}

function resetFilters() {
    filters.productType = ''
    filters.constrTypeId = ''
    applyFilters()
}

async function applyFilters() {
    activeObjectId.value = null
    await loadFilters()
    await loadAdvertisements()
}

function loadYandexMap() {
    return new Promise((resolve, reject) => {
        if (window.ymaps) {
            window.ymaps.ready(resolve)
            return
        }

        const script = document.createElement('script')
        script.src = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU'
        script.onload = () => {
            if (window.ymaps) {
                window.ymaps.ready(resolve)
            } else {
                reject(new Error('Yandex Maps не загрузились'))
            }
        }
        script.onerror = () => reject(new Error('Ошибка загрузки Яндекс.Карт'))
        document.head.appendChild(script)
    })
}

function createBalloonMarkup(item) {
    return `
        <div style="font-family: Inter, sans-serif; width: 260px;">
            <div style="font-size: 14px; font-weight: 700; margin-bottom: 8px;">${item.address || 'Адрес не указан'}</div>
            <div style="font-size: 13px; color: #374151; line-height: 1.45;">
                <div><b>Категория:</b> ${item.category || '—'}</div>
                <div><b>Тип:</b> ${item.type || '—'}</div>
                <div><b>Стороны:</b> ${formatSides(item.sides)}</div>
            </div>
        </div>
    `
}

function clearPlacemarks() {
    if (!map) {
        return
    }

    placemarks.forEach((placemark) => {
        map.geoObjects.remove(placemark)
    })
    placemarks.clear()
}

function syncMapPlacemarks() {
    if (!map || !isMapLoaded.value) {
        return
    }

    clearPlacemarks()

    objects.value.forEach((item) => {
        if (!item.location || typeof item.location.latitude !== 'number' || typeof item.location.longitude !== 'number') {
            return
        }

        const placemark = new window.ymaps.Placemark(
            [item.location.latitude, item.location.longitude],
            {
                balloonContent: createBalloonMarkup(item),
            },
            {
                preset: 'islands#redIcon',
            },
        )

        placemark.events.add('click', () => {
            activeObjectId.value = item.id
        })

        placemarks.set(item.id, placemark)
        map.geoObjects.add(placemark)
    })

    if (objects.value.length > 0) {
        const bounds = map.geoObjects.getBounds()
        if (bounds) {
            map.setBounds(bounds, {
                checkZoomRange: true,
                zoomMargin: 30,
            })
        }
    }
}

function focusObject(objectId) {
    activeObjectId.value = objectId
    const placemark = placemarks.get(objectId)
    if (!placemark || !map) {
        return
    }

    const coordinates = placemark.geometry.getCoordinates()
    map.setCenter(coordinates, 16, {duration: 300})
    placemark.balloon.open()
}

async function initMap() {
    try {
        await loadYandexMap()
        map = new window.ymaps.Map(mapContainer.value, {
            center: [51.8272, 107.606],
            zoom: 10,
            controls: ['zoomControl', 'fullscreenControl'],
        })

        isMapLoaded.value = true
        syncMapPlacemarks()
    } catch (error) {
        mapError.value = 'Не удалось загрузить карту. Попробуйте обновить страницу.'
        console.error(error)
    }
}

watch(
    () => filters.productType,
    async () => {
        filters.constrTypeId = ''
        await loadFilters()
    },
)

onMounted(async () => {
    await loadFilters()
    await loadAdvertisements()
    await initMap()
})

onBeforeUnmount(() => {
    clearPlacemarks()
    if (map) {
        map.destroy()
        map = null
    }
})
</script>

<style scoped>
.line-clamp-2 {
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
</style>
