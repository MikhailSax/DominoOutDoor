<template>
    <div class="flex h-[calc(100vh-72px)] min-h-[680px] flex-col overflow-hidden bg-gradient-to-br from-gray-50 to-gray-100 text-gray-800 lg:flex-row">
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
                            <option v-for="item in productTypes" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
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
                            <option v-for="item in constrTypes" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
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
                    <div v-else-if="objects.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-white p-4 text-sm text-gray-500">
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
                            <h3 class="line-clamp-2 text-sm font-semibold text-gray-900">{{ item.address || 'Адрес не указан' }}</h3>
                            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600">#{{ item.id }}</span>
                        </div>
                        <p class="text-xs text-gray-600">{{ item.category || 'Категория не указана' }} • {{ item.type || 'Тип не указан' }}</p>
                        <p class="mt-1 text-xs text-gray-500">Стороны: {{ formatSides(item.sides) }}</p>
                    </button>
                </div>
            </div>
        </aside>

        <section class="relative min-h-[380px] flex-1 p-3 lg:p-5">
            <div v-if="mapError" class="flex h-full items-center justify-center rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-700">
                {{ mapError }}
            </div>
            <div v-else-if="!isMapLoaded" class="flex h-full items-center justify-center rounded-2xl border border-gray-200 bg-white text-sm text-gray-500">
                Загрузка карты...
            </div>
            <div v-show="isMapLoaded" ref="mapContainer" class="h-full w-full rounded-2xl border-2 border-white shadow-lg"></div>

            <article
                v-if="activeObject && activeSide"
                class="absolute left-7 top-7 z-20 w-[560px] max-w-[calc(100%-56px)] overflow-hidden rounded-3xl bg-white shadow-2xl"
            >
                <div class="relative">
                    <div class="absolute left-4 top-4 z-10 flex rounded-full bg-white p-1 shadow">
                        <button
                            v-for="side in activeObject.side_details"
                            :key="side.code"
                            type="button"
                            class="min-w-9 rounded-full px-3 py-1 text-lg font-semibold"
                            :class="activeSide.code === side.code ? 'bg-blue-100 text-blue-700' : 'text-blue-700 hover:bg-blue-50'"
                            @click="selectSide(side.code)"
                        >
                            {{ side.code }}
                        </button>
                    </div>

                    <button
                        type="button"
                        class="absolute right-4 top-4 z-10 rounded-full bg-white p-3 text-lg font-semibold text-slate-700 shadow hover:bg-slate-100"
                        @click="closeCard"
                    >
                        ×
                    </button>

                    <img
                        :src="activeSide.image_url || '/images/orig.png'"
                        alt="Фото стороны"
                        class="h-72 w-full object-cover"
                    >
                </div>

                <div class="space-y-4 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-4 leading-tight font-bold text-slate-900">{{ activeObject.address || 'Адрес не указан' }}</h3>
                            <p class="mt-1 text-xl font-semibold tracking-wide text-slate-400">GID {{ activeObject.code || activeObject.place_number }}</p>
                        </div>
                        <a
                            :href="`/api/advertisements/${activeObject.id}`"
                            target="_blank"
                            class="text-xl font-medium text-blue-600 hover:text-blue-700"
                        >
                            Подробнее
                        </a>
                    </div>

                    <dl class="grid grid-cols-[1fr_auto] gap-x-5 gap-y-2 text-xl">
                        <dt class="border-b border-slate-200 pb-1 text-slate-600">Формат</dt>
                        <dd class="border-b border-slate-200 pb-1 font-semibold text-slate-800">{{ activeObject.type || '—' }}</dd>

                        <dt class="border-b border-slate-200 pb-1 text-slate-600">Сторона</dt>
                        <dd class="border-b border-slate-200 pb-1 font-semibold text-slate-800">{{ activeSide.code }}</dd>

                        <dt class="border-b border-slate-200 pb-1 text-slate-600">Город</dt>
                        <dd class="border-b border-slate-200 pb-1 font-semibold text-slate-800">Иркутск</dd>

                        <dt class="text-slate-400">Прайс без НДС</dt>
                        <dd class="text-right text-4 font-extrabold text-slate-900">{{ formatPrice(activeSide.price) }}</dd>
                    </dl>
                </div>
            </article>
        </section>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'

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
const filters = reactive({ productType: '', constrTypeId: '' })
const isLoadingFilters = ref(false)
const isLoadingObjects = ref(false)

const mapContainer = ref(null)
const isMapLoaded = ref(false)
const mapError = ref('')
const activeObjectId = ref(null)
const activeSideCode = ref('')

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

const activeObject = computed(() => objects.value.find((item) => item.id === activeObjectId.value) || null)

const activeSide = computed(() => {
    if (!activeObject.value) {
        return null
    }

    const sides = Array.isArray(activeObject.value.side_details) ? activeObject.value.side_details : []
    if (sides.length === 0) {
        return null
    }

    return sides.find((item) => item.code === activeSideCode.value) || sides[0]
})

function formatSides(sides) {
    if (!Array.isArray(sides) || sides.length === 0) {
        return '—'
    }

    return sides.join(', ')
}

function formatPrice(price) {
    if (price === null || price === undefined || price === '') {
        return 'По запросу'
    }

    const value = Number(price)
    if (Number.isNaN(value)) {
        return String(price)
    }

    return `${new Intl.NumberFormat('ru-RU').format(value)} ₽`
}

function normalizeSideDetails(item) {
    const details = Array.isArray(item.side_details) ? item.side_details : []
    if (details.length > 0) {
        return details
    }

    const sides = Array.isArray(item.sides) ? item.sides : []
    return sides.map((code) => ({
        code,
        description: null,
        price: null,
        image: null,
        image_url: null,
    }))
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
        objects.value = (Array.isArray(data) ? data : []).map((item) => ({
            ...item,
            side_details: normalizeSideDetails(item),
        }))

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
    activeSideCode.value = ''
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
            {},
            {
                preset: 'islands#redIcon',
            },
        )

        placemark.events.add('click', () => {
            focusObject(item.id)
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
    const item = objects.value.find((obj) => obj.id === objectId)
    activeSideCode.value = item?.side_details?.[0]?.code || ''

    const placemark = placemarks.get(objectId)
    if (!placemark || !map) {
        return
    }

    const coordinates = placemark.geometry.getCoordinates()
    map.setCenter(coordinates, 16, { duration: 300 })
}

function selectSide(code) {
    activeSideCode.value = code
}

function closeCard() {
    activeObjectId.value = null
    activeSideCode.value = ''
}

async function initMap() {
    try {
        await loadYandexMap()
        map = new window.ymaps.Map(mapContainer.value, {
            center: [55.751244, 37.618423],
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

.text-4 {
    font-size: 2rem;
}

.text-xl {
    font-size: 1.25rem;
}
</style>
