<template>
    <div class="flex h-[calc(100vh-72px)] min-h-[680px] flex-col overflow-hidden bg-slate-100 text-slate-800 lg:flex-row">
        <aside class="w-full border-b border-slate-200 bg-white lg:w-[380px] lg:border-b-0 lg:border-r">
            <div class="flex h-full flex-col">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h1 class="text-xl font-bold text-slate-900">Каталог конструкций</h1>
                    <p class="mt-1 text-sm text-slate-500">Подберите рекламные поверхности по параметрам.</p>
                </div>

                <div class="space-y-3 border-b border-slate-100 bg-slate-50 p-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Тип продукции</label>
                        <select
                            v-model="filters.productType"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Все типы продукции</option>
                            <option v-for="item in productTypes" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Тип конструкции</label>
                        <select
                            v-model="filters.constrTypeId"
                            :disabled="isLoadingFilters"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100"
                        >
                            <option value="">Все типы конструкций</option>
                            <option v-for="item in constrTypes" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                            @click="resetFilters"
                        >
                            Сбросить
                        </button>
                        <button
                            type="button"
                            class="flex-1 rounded-xl border border-blue-600 bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700"
                            @click="applyFilters"
                        >
                            Подобрать
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 text-sm">
                    <span class="font-medium text-slate-600">Найдено поверхностей:</span>
                    <span class="rounded-full bg-blue-600 px-3 py-1 text-white">{{ objects.length }}</span>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-3">
                    <div v-if="isLoadingObjects" class="p-4 text-sm text-slate-500">Загрузка объектов...</div>
                    <div v-else-if="objects.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-500">
                        По выбранным фильтрам ничего не найдено.
                    </div>

                    <button
                        v-for="item in objects"
                        :key="item.id"
                        type="button"
                        class="mb-2 w-full rounded-xl border bg-white p-4 text-left shadow-sm transition hover:shadow-md"
                        :class="activeObjectId === item.id ? 'border-blue-400 ring-2 ring-blue-100' : 'border-slate-200'"
                        @click="focusObject(item.id)"
                    >
                        <div class="mb-1 flex items-start justify-between gap-3">
                            <h3 class="line-clamp-2 text-sm font-semibold text-slate-900">{{ item.address || 'Адрес не указан' }}</h3>
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">#{{ item.id }}</span>
                        </div>
                        <p class="text-xs text-slate-600">{{ item.category || 'Категория не указана' }} • {{ item.type || 'Тип не указан' }}</p>
                        <p class="mt-1 text-xs text-slate-500">Стороны: {{ formatSides(item.sides) }}</p>
                    </button>
                </div>
            </div>
        </aside>

        <section class="relative min-h-[380px] flex-1 p-3 lg:p-5">
            <div v-if="mapError" class="flex h-full items-center justify-center rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-700">
                {{ mapError }}
            </div>
            <div v-else-if="!isMapLoaded" class="flex h-full items-center justify-center rounded-2xl border border-slate-200 bg-white text-sm text-slate-500">
                Загрузка карты...
            </div>
            <div v-show="isMapLoaded" ref="mapContainer" class="h-full w-full rounded-2xl border-2 border-white shadow-lg"></div>

            <article
                v-if="activeObject && activeSide"
                class="absolute right-6 top-6 z-20 w-[520px] max-w-[calc(100%-48px)] overflow-hidden rounded-3xl bg-white shadow-2xl"
            >
                <div class="relative">
                    <div class="absolute left-4 top-4 z-10 flex rounded-full bg-white p-1 shadow-lg">
                        <button
                            v-for="side in normalizeSideDetails(activeObject)"
                            :key="side.code"
                            type="button"
                            class="min-w-[42px] rounded-full px-3 py-1.5 text-base font-semibold"
                            :class="activeSide.code === side.code ? 'bg-blue-600 text-white' : 'text-blue-700 hover:bg-blue-50'"
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
                        class="h-64 w-full object-cover"
                    >
                </div>

                <div class="space-y-4 p-5">
                    <p v-if="isLoadingCardDetails" class="rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-500">Загружаем данные...</p>
                    
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-3xl leading-tight font-bold text-slate-900">{{ activeObject.address || 'Адрес не указан' }}</h3>
                            <p class="mt-1 text-lg font-semibold tracking-wide text-slate-400">GID {{ activeObject.code || activeObject.place_number || activeObject.id }}</p>
                        </div>
                        <a
                            :href="`/api/advertisements/${activeObject.id}`"
                            target="_blank"
                            class="pt-1 text-base font-medium text-blue-600 hover:text-blue-700"
                        >
                            Подробнее
                        </a>
                    </div>

                    <dl class="grid grid-cols-[1fr_auto] gap-x-5 gap-y-2 text-lg">
                        <dt class="border-b border-slate-200 pb-1 text-slate-600">Формат</dt>
                        <dd class="border-b border-slate-200 pb-1 font-semibold text-slate-800">{{ activeObject.type || '—' }}</dd>

                        <dt class="border-b border-slate-200 pb-1 text-slate-600">Сторона</dt>
                        <dd class="border-b border-slate-200 pb-1 font-semibold text-slate-800">{{ activeSide.code }}</dd>

                        <dt class="border-b border-slate-200 pb-1 text-slate-600">Код поверхности</dt>
                        <dd class="border-b border-slate-200 pb-1 font-semibold text-slate-800">{{ activeObject.place_number || '—' }}</dd>

                        <dt class="border-b border-slate-200 pb-1 text-slate-600">Описание стороны</dt>
                        <dd class="border-b border-slate-200 pb-1 text-right text-base font-medium text-slate-700">{{ activeSide.description || 'Не указано' }}</dd>

                        <dt class="text-slate-500">Прайс без НДС</dt>
                        <dd class="text-right text-3xl font-extrabold text-slate-900">{{ formatPrice(activeSide.price) }}</dd>
                    </dl>

                    <button
                        type="button"
                        class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                        @click="openRequestModal"
                    >
                        Оставить заявку на экран
                    </button>
                </div>
            </article>

            <div v-if="isRequestModalOpen" class="absolute inset-0 z-30 flex items-center justify-center bg-slate-900/55 p-4">
                <div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-2xl">
                    <div class="mb-4 flex items-start justify-between">
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">Заявка на размещение</h4>
                            <p class="text-sm text-slate-500">{{ requestSummary }}</p>
                        </div>
                        <button type="button" class="rounded-full px-3 py-1 text-slate-500 hover:bg-slate-100" @click="closeRequestModal">×</button>
                    </div>

                    <form class="space-y-3" @submit.prevent="submitRequest">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="text-sm">
                                <span class="mb-1 block text-slate-600">Ваше имя</span>
                                <input v-model.trim="requestForm.name" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                            </label>
                            <label class="text-sm">
                                <span class="mb-1 block text-slate-600">Телефон</span>
                                <input v-model.trim="requestForm.phone" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                            </label>
                        </div>

                        <label class="text-sm block">
                            <span class="mb-1 block text-slate-600">Комментарий</span>
                            <textarea v-model.trim="requestForm.comment" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </label>

                        <p v-if="requestStatusMessage" class="rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-600">{{ requestStatusMessage }}</p>

                        <div class="flex gap-2">
                            <button type="button" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm" @click="closeRequestModal">Отмена</button>
                            <button type="submit" :disabled="isSubmittingRequest" class="flex-1 rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white disabled:opacity-60">
                                {{ isSubmittingRequest ? 'Отправляем...' : 'Отправить заявку' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'

const props = defineProps({
    filtersUrl: { type: String, required: true },
    advertisementsUrl: { type: String, required: true },
    productRequestsUrl: { type: String, required: true },
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
const isLoadingCardDetails = ref(false)
const isRequestModalOpen = ref(false)
const isSubmittingRequest = ref(false)
const requestStatusMessage = ref('')
const requestForm = reactive({ name: '', phone: '', comment: '' })

let map = null
let placemarks = new Map()

// --- Computed ---
const filteredParams = computed(() => {
    const params = new URLSearchParams()
    if (filters.productType) params.append('productType', filters.productType)
    if (filters.constrTypeId) params.append('constrTypeId', filters.constrTypeId)
    return params
})

const activeObject = computed(() => 
    objects.value.find((item) => String(item.id) === String(activeObjectId.value)) || null
)

const activeSide = computed(() => {
    if (!activeObject.value) return null
    const sides = activeObject.value.side_details || []
    if (sides.length === 0) return null
    return sides.find((item) => item.code === activeSideCode.value) || sides[0]
})

const requestSummary = computed(() => {
    if (!activeObject.value || !activeSide.value) return 'Выберите конструкцию на карте'
    return `${activeObject.value.address || 'Адрес не указан'} • сторона ${activeSide.value.code} • ${activeObject.value.type || 'Формат не указан'}`
})

// --- Helpers ---
function formatSides(sides) {
    if (!Array.isArray(sides) || sides.length === 0) return '—'
    return sides.join(', ')
}

function formatPrice(price) {
    if (!price) return 'По запросу'
    const value = Number(price)
    return Number.isNaN(value) ? String(price) : `${new Intl.NumberFormat('ru-RU').format(value)} ₽`
}

function normalizeImageUrl(imageUrl, imageName) {
    const value = imageUrl || imageName
    if (!value || typeof value !== 'string') return null
    if (value.startsWith('http') || value.startsWith('/')) return value
    return imageUrl ? `/${value}` : `/uploads/advertisements/${value}`
}

function normalizeSideDetails(item) {
    const details = Array.isArray(item?.side_details) ? item.side_details : []
    const normalized = details.map((side) => ({
        code: String(side?.code || '').trim().toUpperCase(),
        description: side?.description ?? null,
        price: side?.price ?? null,
        image_url: normalizeImageUrl(side?.image_url, side?.image),
    })).filter(s => s.code)

    if (normalized.length > 0) return normalized

    const rawSides = Array.isArray(item?.sides) ? item.sides : (typeof item?.sides === 'string' ? item.sides.split(',') : [])
    return rawSides.map(code => ({
        code: String(code).trim().toUpperCase(),
        description: null, price: null, image_url: null
    })).filter(s => s.code).length > 0 ? rawSides.map(code => ({ code: String(code).trim().toUpperCase() })) : [{ code: '—' }]
}

function normalizeAdvertisement(item) {
    const typeName = typeof item?.type === 'object' ? item?.type?.name : item?.type
    const categoryName = typeof item?.type === 'object' ? item?.type?.category : item?.category
    const sideDetails = normalizeSideDetails(item)
    return {
        ...item,
        type: typeName || null,
        category: categoryName || null,
        place_number: item?.place_number ?? item?.placeNumber ?? null,
        side_details: sideDetails,
        sides: sideDetails.map(s => s.code)
    }
}

// --- API ---
async function fetchJson(url) {
    const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
    if (!response.ok) throw new Error(`Ошибка: ${response.status}`)
    return response.json()
}

async function loadFilters() {
    isLoadingFilters.value = true
    try {
        const query = filters.productType ? `?productType=${filters.productType}` : ''
        const data = await fetchJson(props.filtersUrl + query)
        productTypes.value = data.productTypes || []
        constrTypes.value = data.constrTypes || []
    } finally { isLoadingFilters.value = false }
}

async function loadAdvertisements() {
    isLoadingObjects.value = true
    try {
        const query = filteredParams.value.toString()
        const url = query ? `${props.advertisementsUrl}?${query}` : props.advertisementsUrl
        const data = await fetchJson(url)
        objects.value = (Array.isArray(data) ? data : []).map(normalizeAdvertisement)
        syncMapPlacemarks()
    } finally { isLoadingObjects.value = false }
}

async function loadAdvertisementDetails(objectId) {
    isLoadingCardDetails.value = true
    try {
        const data = await fetchJson(`${props.advertisementsUrl}/${objectId}`)
        const normalized = normalizeAdvertisement(data)
        objects.value = objects.value.map(item => String(item.id) === String(objectId) ? normalized : item)
        if (!normalized.sides.includes(activeSideCode.value)) activeSideCode.value = normalized.sides[0] || ''
    } catch (e) { console.error(e) } finally { isLoadingCardDetails.value = false }
}

// --- Map Logic ---
function loadYandexMap() {
    return new Promise((resolve, reject) => {
        if (window.ymaps) return window.ymaps.ready(resolve)
        const script = document.createElement('script')
        script.src = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU'
        script.onload = () => window.ymaps.ready(resolve)
        script.onerror = reject
        document.head.appendChild(script)
    })
}

function syncMapPlacemarks() {
    if (!map || !isMapLoaded.value) return
    placemarks.forEach(p => map.geoObjects.remove(p))
    placemarks.clear()

    objects.value.forEach(item => {
        if (!item.location?.latitude || !item.location?.longitude) return
        const p = new window.ymaps.Placemark(
            [item.location.latitude, item.location.longitude],
            {}, { preset: 'islands#redIcon' }
        )
        p.events.add('click', () => focusObject(item.id))
        placemarks.set(item.id, p)
        map.geoObjects.add(p)
    })

    if (objects.value.length > 0) {
        const bounds = map.geoObjects.getBounds()
        if (bounds) map.setBounds(bounds, { checkZoomRange: true, zoomMargin: 30 })
    }
}

async function focusObject(objectId) {
    activeObjectId.value = objectId
    const item = objects.value.find(obj => String(obj.id) === String(objectId))
    activeSideCode.value = item?.sides[0] || ''
    await loadAdvertisementDetails(objectId)
    const p = placemarks.get(objectId)
    if (p && map) map.setCenter(p.geometry.getCoordinates(), 16, { duration: 300 })
}

// --- Actions ---
function resetFilters() { 
    filters.productType = ''
    filters.constrTypeId = ''
    applyFilters() 
}

function applyFilters() { 
    activeObjectId.value = null
    loadFilters().then(loadAdvertisements) 
}

function selectSide(code) { activeSideCode.value = code }
function closeCard() { activeObjectId.value = null }
function openRequestModal() { requestStatusMessage.value = ''; isRequestModalOpen.value = true }
function closeRequestModal() { isRequestModalOpen.value = false }

async function submitRequest() {
    if (!activeObject.value || !activeSide.value) return
    isSubmittingRequest.value = true

    try {
        const response = await fetch(props.productRequestsUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                advertisementId: activeObject.value.id,
                side: activeSide.value.code,
                contactName: requestForm.name,
                contactPhone: requestForm.phone,
                comment: requestForm.comment,
            }),
        })

        const data = await response.json().catch(() => ({}))
        if (!response.ok) {
            requestStatusMessage.value = data.message || 'Не удалось отправить заявку.'
            return
        }

        requestStatusMessage.value = data.message || 'Заявка отправлена.'
        setTimeout(() => {
            isRequestModalOpen.value = false
            requestForm.name = ''
            requestForm.phone = ''
            requestForm.comment = ''
        }, 1000)
    } finally {
        isSubmittingRequest.value = false
    }
}


// --- Lifecycle ---
onMounted(async () => {
    await loadFilters()
    await loadAdvertisements()
    try {
        await loadYandexMap()
        map = new window.ymaps.Map(mapContainer.value, { center: [55.75, 37.61], zoom: 10, controls: ['zoomControl'] })
        isMapLoaded.value = true
        syncMapPlacemarks()
    } catch (e) { mapError.value = 'Ошибка загрузки карты' }
})

onBeforeUnmount(() => map?.destroy())

watch(() => filters.productType, () => { filters.constrTypeId = ''; loadFilters() })
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
