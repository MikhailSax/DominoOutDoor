<template>
    <div class="flex min-h-[calc(100vh-72px)] flex-col bg-slate-100 text-slate-800 lg:h-[calc(100vh-72px)] lg:flex-row lg:overflow-hidden">
        <div class="sticky top-0 z-20 flex gap-2 border-b border-slate-200 bg-white/95 p-2 backdrop-blur lg:hidden">
            <button
                type="button"
                class="flex-1 rounded-xl px-4 py-2 text-sm font-semibold transition"
                :class="mobileView === 'list' ? 'bg-red-600 text-white shadow' : 'bg-slate-100 text-slate-700'"
                @click="mobileView = 'list'"
            >
                Фильтры
            </button>
            <button
                type="button"
                class="flex-1 rounded-xl px-4 py-2 text-sm font-semibold transition"
                :class="mobileView === 'map' ? 'bg-red-600 text-white shadow' : 'bg-slate-100 text-slate-700'"
                @click="mobileView = 'map'"
            >
                Карта
            </button>
        </div>

        <aside class="w-full border-b border-slate-200 bg-white lg:w-[380px] lg:border-b-0 lg:border-r" :class="mobileView === 'map' ? 'hidden lg:block' : 'block'">
            <div class="flex h-full flex-col">
                <div class="border-b border-slate-100 px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <h1 class="text-xl font-bold text-slate-900">Каталог конструкций</h1>
                        <span v-if="hasActiveFilters" class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                            Умный фильтр включен
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Подберите рекламные поверхности по параметрам.</p>
                </div>

                <div class="space-y-3 border-b border-slate-100 bg-slate-50 p-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Тип продукции</label>
                        <select
                            v-model="filters.productType"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-100"
                        >
                            <option value="">Все типы продукции</option>
                            <option v-for="item in productTypes" :key="item.id" :value="String(item.id)">
                                {{ item.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Тип конструкции</label>
                        <select
                            v-model="filters.constrTypeId"
                            :disabled="isLoadingFilters"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-100 disabled:cursor-not-allowed disabled:bg-slate-100"
                        >
                            <option value="">Все типы конструкций</option>
                            <option v-for="item in constrTypes" :key="item.id" :value="String(item.id)">
                                {{ item.name }}
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
                        <button
                            v-for="preset in datePresets"
                            :key="preset.key"
                            type="button"
                            class="rounded-lg border px-2 py-1.5 font-semibold transition"
                            :class="activeDatePreset === preset.key ? 'border-red-600 bg-red-600 text-white' : 'border-slate-300 bg-white text-slate-600 hover:border-red-200 hover:text-red-600'"
                            @click="applyDatePreset(preset.key)"
                        >
                            {{ preset.label }}
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <label class="text-sm">
                            <span class="mb-1 block text-slate-600">Свободно с</span>
                            <input v-model="filters.bookingFrom" type="date" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm" />
                        </label>
                        <label class="text-sm">
                            <span class="mb-1 block text-slate-600">Свободно до</span>
                            <input v-model="filters.bookingTo" type="date" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm" />
                        </label>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="flex-1 rounded-xl border border-red-600 bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                            @click="resetFilters"
                        >
                            Сбросить
                        </button>
                        <button
                            type="button"
                            class="flex-1 rounded-xl bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700"
                            @click="applyFilters"
                        >
                            Подобрать
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 text-sm">
                    <span class="font-medium text-slate-600">Найдено поверхностей:</span>
                    <span class="rounded-full bg-red-600 px-3 py-1 text-white">{{ objects.length }}</span>
                </div>

                <div class="max-h-[45vh] min-h-0 flex-1 overflow-y-auto p-3 lg:max-h-none">
                    <div v-if="isLoadingObjects" class="p-4 text-sm text-slate-500">Загрузка объектов...</div>
                    <div v-else-if="objects.length === 0"
                         class="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-500">
                        По выбранным фильтрам ничего не найдено.
                    </div>

                    <button
                        v-for="item in objects"
                        :key="item.id"
                        type="button"
                        class="mb-2 w-full rounded-xl border bg-white p-4 text-left shadow-sm transition hover:shadow-md"
                        :class="activeObjectId === item.id ? 'border-red-400 ring-2 ring-red-100' : 'border-slate-200'"
                        @click="focusObject(item.id)"
                    >
                        <div class="mb-1 flex items-start justify-between gap-3">
                            <h3 class="line-clamp-2 text-sm font-semibold text-slate-900">
                                {{ item.address || 'Адрес не указан' }}
                            </h3>
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">#{{ item.id }}</span>
                        </div>
                        <p class="text-xs text-slate-600">
                            {{ item.category || '—' }} • {{ item.type || '—' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">Стороны: {{ formatSides(item.sides) }}</p>
                        <p class="mt-1 text-xs font-medium" 
                           :class="getItemStatus(item, bookingRange.from, bookingRange.to).busy ? 'text-red-600' : 'text-emerald-600'">
                            {{ getItemStatus(item, bookingRange.from, bookingRange.to).text }}
                        </p>
                    </button>
                </div>
            </div>
        </aside>

        <section class="relative min-h-[360px] flex-1 p-2 sm:p-3 lg:p-5" :class="mobileView === 'list' ? 'hidden lg:block' : 'block'">
            <div v-if="mapError" class="flex h-full items-center justify-center rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-700">
                {{ mapError }}
            </div>
            <div v-else-if="!isMapLoaded" class="flex h-full items-center justify-center rounded-2xl border border-slate-200 bg-white text-sm text-slate-500">
                Загрузка карты...
            </div>
            
            <div v-show="isMapLoaded" ref="mapContainer" class="h-[calc(100vh-140px)] min-h-[380px] w-full rounded-2xl border-2 border-white shadow-lg lg:h-full"></div>

            <article
                v-if="activeObject && activeSide"
                class="absolute inset-x-2 bottom-2 z-20 max-h-[68vh] w-auto overflow-y-auto rounded-2xl bg-white shadow-2xl sm:right-4 sm:top-4 sm:bottom-auto sm:max-h-[calc(100%-2rem)] sm:w-[520px] sm:max-w-[calc(100%-2rem)] sm:rounded-3xl lg:inset-x-auto lg:right-0 lg:top-1/2 lg:max-h-[calc(100%-3rem)] lg:w-[520px] lg:max-w-[calc(100%-24px)] lg:-translate-y-1/2"
            >
                <div class="relative">
                    <div class="absolute left-3 top-3 z-10 flex max-w-[calc(100%-90px)] overflow-x-auto rounded-full bg-white p-1 shadow-lg sm:left-4 sm:top-4">
                        <button
                            v-for="side in activeObject.side_details"
                            :key="side.code"
                            type="button"
                            class="min-w-[42px] rounded-full px-3 py-1 text-sm font-semibold sm:py-1.5 sm:text-base"
                            :class="activeSideCode === side.code 
                                ? 'bg-red-600 text-white' 
                                : (getSideStatus(activeObject, side.code, bookingRange.from, bookingRange.to).busy ? 'bg-red-50 text-red-600' : 'text-emerald-700 hover:bg-emerald-50')"
                            @click="selectSide(side.code)"
                        >
                            {{ side.code }}
                        </button>
                    </div>

                    <button
                        type="button"
                        class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white text-xl font-light text-slate-700 shadow hover:bg-slate-100 sm:right-4 sm:top-4 sm:h-10 sm:w-10 sm:text-2xl"
                        @click="closeCard"
                    >
                        ×
                    </button>

                    <img
                        :src="activeSide.image_url || '/images/orig.png'"
                        alt="Фото стороны"
                        class="h-44 w-full object-cover sm:h-56 lg:h-64"
                    >
                </div>

                <div class="space-y-3 p-4 sm:space-y-4 sm:p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg leading-tight font-bold text-slate-900 sm:text-2xl">{{ activeObject.address }}</h3>
                            <p class="mt-1 text-sm font-semibold tracking-wide text-slate-400 sm:text-lg">GID {{ activeObject.id }}</p>
                        </div>
                        <a :href="`/api/advertisements/${activeObject.id}`" target="_blank" class="pt-1 text-sm font-medium text-blue-600 hover:text-blue-700 sm:text-base">
                            Подробнее
                        </a>
                    </div>

                    <dl class="grid grid-cols-[1fr_auto] gap-x-4 gap-y-2 text-sm sm:gap-x-5 sm:text-lg">
                        <dt class="border-b border-slate-200 pb-1 text-slate-600">Формат</dt>
                        <dd class="border-b border-slate-200 pb-1 font-semibold text-slate-800">{{ activeObject.type }}</dd>
                        <dt class="border-b border-slate-200 pb-1 text-slate-600">Сторона</dt>
                        <dd class="border-b border-slate-200 pb-1 font-semibold text-slate-800">{{ activeSide.code }}</dd>
                        <dt class="border-b border-slate-200 pb-1 text-slate-600">Описание стороны</dt>
                        <dd class="border-b border-slate-200 pb-1 text-right text-xs font-medium text-slate-700 sm:text-base">
                            {{ activeSide.description || 'Описание пока не заполнено' }}
                        </dd>
                        <dt class="text-slate-500">Прайс без НДС</dt>
                        <dd class="text-right text-xl font-extrabold text-slate-900 sm:text-3xl">{{ formatPrice(activeSide.price) }}</dd>
                    </dl>

                    <p v-if="activeSideStatus" class="rounded-lg px-3 py-2 text-sm font-medium" 
                       :class="activeSideStatus.busy ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'">
                        {{ activeSideStatus.text }}
                    </p>

                    <button
                        type="button"
                        class="w-full rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white hover:bg-red-700"
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
                        <button type="button" class="text-2xl text-slate-500 hover:text-slate-800" @click="closeRequestModal">×</button>
                    </div>

                    <form class="space-y-3" @submit.prevent="submitRequest">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="text-sm">
                                <span class="mb-1 block text-slate-600">Ваше имя</span>
                                <input v-model.trim="requestForm.name" :readonly="isAuthenticated" required class="w-full rounded-lg border border-slate-300 px-3 py-2"/>
                            </label>
                            <label class="text-sm">
                                <span class="mb-1 block text-slate-600">Телефон</span>
                                <input v-model.trim="requestForm.phone" :readonly="isAuthenticated" required class="w-full rounded-lg border border-slate-300 px-3 py-2"/>
                            </label>
                        </div>

                        <p v-if="isAuthenticated" class="text-xs text-slate-500">
                            Контактные данные подтянуты из вашего аккаунта.
                        </p>

                        <label class="text-sm block">
                            <span class="mb-1 block text-slate-600">Комментарий</span>
                            <textarea v-model.trim="requestForm.comment" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </label>
                        
                        <p v-if="requestStatusMessage" class="rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-600">{{ requestStatusMessage }}</p>

                        <div class="flex gap-2">
                            <button type="button" class="flex-1 rounded-lg border border-slate-300 py-2 text-sm font-semibold hover:bg-slate-50" @click="closeRequestModal">Отмена</button>
                            <button type="submit" :disabled="isSubmittingRequest" class="flex-1 rounded-lg bg-red-600 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60">
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
    authUser: {
        type: Object,
        required: true,
    },
})

// --- Состояние ---
const productTypes = ref([])
const constrTypes = ref([])
const objects = ref([])
const filters = reactive({ productType: '', constrTypeId: '', bookingFrom: '', bookingTo: '' })
const isLoadingFilters = ref(false)
const isLoadingObjects = ref(false)
const mobileView = ref('list')
const activeDatePreset = ref('')

const mapContainer = ref(null)
const isMapLoaded = ref(false)
const mapError = ref('')
const activeObjectId = ref(null)
const activeSideCode = ref('')
const isRequestModalOpen = ref(false)
const isSubmittingRequest = ref(false)
const requestStatusMessage = ref('')
const requestForm = reactive({ name: '', phone: '', comment: '', startedAt: 0 })

let map = null
let placemarks = new Map()
let applyTimer = null

const datePresets = [
    { key: 'today', label: 'Сегодня' },
    { key: 'week', label: '7 дней' },
    { key: 'month', label: '30 дней' },
    { key: 'clear', label: 'Без дат' },
]

// --- Вычисляемые свойства ---
const bookingRange = computed(() => {
    const from = parseDate(filters.bookingFrom)
    const to = parseDate(filters.bookingTo)
    if (from && to && to < from) return { from: to, to: from }
    return { from, to }
})

const activeObject = computed(() => 
    objects.value.find(o => String(o.id) === String(activeObjectId.value)) || null
)

const activeSide = computed(() => {
    if (!activeObject.value) return null
    return activeObject.value.side_details.find(s => s.code === activeSideCode.value) || activeObject.value.side_details[0] || null
})

const activeSideStatus = computed(() => {
    if (!activeObject.value || !activeSide.value) return null
    return getSideStatus(activeObject.value, activeSide.value.code, bookingRange.value.from, bookingRange.value.to)
})

const requestSummary = computed(() => {
    if (!activeObject.value || !activeSide.value) return 'Выберите конструкцию'
    return `${activeObject.value.address} • сторона ${activeSide.value.code}`
})

const isAuthenticated = computed(() => Boolean(props.authUser?.isAuthenticated))
const hasActiveFilters = computed(() => Boolean(filters.productType || filters.constrTypeId || filters.bookingFrom || filters.bookingTo))

// --- Вспомогательные функции ---
function parseDate(value) {
    if (!value) return null
    const date = new Date(`${value}T00:00:00`)
    return isNaN(date.getTime()) ? null : date
}

function toInputDate(date) {
    return date.toISOString().slice(0, 10)
}

function applyDatePreset(presetKey) {
    activeDatePreset.value = presetKey
    const now = new Date()

    if (presetKey === 'clear') {
        filters.bookingFrom = ''
        filters.bookingTo = ''
        return
    }

    const from = new Date(now)
    const to = new Date(now)
    if (presetKey === 'week') to.setDate(now.getDate() + 7)
    if (presetKey === 'month') to.setDate(now.getDate() + 30)

    filters.bookingFrom = toInputDate(from)
    filters.bookingTo = toInputDate(to)
}

function scheduleApplyFilters() {
    clearTimeout(applyTimer)
    applyTimer = setTimeout(() => {
        applyFilters()
    }, 250)
}

function formatSides(sides) {
    return Array.isArray(sides) && sides.length > 0 ? sides.join(', ') : '—'
}

function formatPrice(price) {
    if (!price) return 'По запросу'
    return `${new Intl.NumberFormat('ru-RU').format(price)} ₽`
}

function normalizeSideDetails(item) {
    const sides = Array.isArray(item?.sides) ? item.sides : []
    if (item?.side_details?.length) return item.side_details
    return sides.map(code => ({ code: String(code).toUpperCase(), price: null, image_url: null }))
}

function getSideStatus(item, sideCode, fromDate, toDate) {
    const bookings = (item?.bookings || []).filter(b => b.side_code === sideCode)
    const from = fromDate || new Date()
    const to = toDate || from
    
    const overlap = bookings.find(b => {
        const start = parseDate(b.start_date)
        const end = parseDate(b.end_date)
        return start && end && start <= to && end >= from
    })

    if (overlap) {
        const d = parseDate(overlap.end_date)
        d.setDate(d.getDate() + 1)
        return { busy: true, text: `Занята до ${d.toLocaleDateString('ru-RU')}` }
    }
    return { busy: false, text: 'Свободна' }
}

function getItemStatus(item, from, to) {
    const statuses = item.side_details.map(s => getSideStatus(item, s.code, from, to))
    return statuses.every(s => s.busy)
        ? { busy: true, text: 'Занята' }
        : { busy: false, text: 'Есть свободные стороны' }
}

// --- API ---
async function loadFilters() {
    isLoadingFilters.value = true
    try {
        const res = await fetch(`${props.filtersUrl}${filters.productType ? '?productType=' + filters.productType : ''}`)
        const data = await res.json()
        productTypes.value = data.productTypes || []
        constrTypes.value = data.constrTypes || []
    } finally { isLoadingFilters.value = false }
}

async function loadAdvertisements() {
    isLoadingObjects.value = true
    try {
        const params = new URLSearchParams()
        if (filters.productType) params.append('productType', filters.productType)
        if (filters.constrTypeId) params.append('constrTypeId', filters.constrTypeId)
        
        const res = await fetch(`${props.advertisementsUrl}?${params.toString()}`)
        const data = await res.json()
        objects.value = (data || []).map(item => ({
            ...item,
            side_details: normalizeSideDetails(item),
            sides: normalizeSideDetails(item).map(s => s.code),
            bookings: item.bookings || []
        }))
        syncMapPlacemarks()
    } finally { isLoadingObjects.value = false }
}

// --- Карта ---
function syncMapPlacemarks() {
    if (!map) return
    placemarks.forEach(p => map.geoObjects.remove(p))
    placemarks.clear()

    objects.value.forEach(item => {
        if (!item.location?.latitude) return
        const p = new window.ymaps.Placemark(
            [item.location.latitude, item.location.longitude],
            {}, 
            { preset: 'islands#redCircleDotIcon' }
        )
        p.events.add('click', () => focusObject(item.id))
        placemarks.set(item.id, p)
        map.geoObjects.add(p)
    })
}

function focusObject(id) {
    activeObjectId.value = id
    const item = objects.value.find(o => o.id === id)
    activeSideCode.value = item?.sides[0] || ''
    if (map && item?.location) {
        map.setCenter([item.location.latitude, item.location.longitude], 15, { duration: 300 })
    }
}

// --- Действия ---
function applyFilters() {
    activeObjectId.value = null
    loadAdvertisements()
}

function resetFilters() {
    Object.assign(filters, { productType: '', constrTypeId: '', bookingFrom: '', bookingTo: '' })
    activeDatePreset.value = ''
    applyFilters()
}

function selectSide(code) { activeSideCode.value = code }
function closeCard() { activeObjectId.value = null }

function openRequestModal() {
    requestStatusMessage.value = ''
    requestForm.startedAt = Date.now()

    if (isAuthenticated.value) {
        requestForm.name = props.authUser?.name || requestForm.name
        requestForm.phone = props.authUser?.phone || requestForm.phone
    }

    isRequestModalOpen.value = true
}

function closeRequestModal() { isRequestModalOpen.value = false }

async function submitRequest() {
    isSubmittingRequest.value = true
    try {
        const response = await fetch(props.productRequestsUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                website: '',
                formStartedAt: requestForm.startedAt,
                advertisementId: activeObject.value.id,
                side: activeSide.value.code,
                contactName: requestForm.name,
                contactPhone: requestForm.phone,
                comment: requestForm.comment || null,
                userId: props.authUser?.id ?? null,
                userEmail: props.authUser?.email || null,
                isAuthenticated: isAuthenticated.value,
            })
        })
        if (response.ok) {
            requestStatusMessage.value = 'Заявка успешно отправлена!'
            setTimeout(closeRequestModal, 1500)
        } else {
            throw new Error()
        }
    } catch {
        requestStatusMessage.value = 'Ошибка при отправке.'
    } finally {
        isSubmittingRequest.value = false
    }
}

onMounted(async () => {
    await loadFilters()
    await loadAdvertisements()
    
    if (!window.ymaps) {
        const script = document.createElement('script')
        script.src = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU'
        script.onload = () => window.ymaps.ready(initMap)
        document.head.appendChild(script)
    } else {
        window.ymaps.ready(initMap)
    }
})

function initMap() {
    map = new window.ymaps.Map(mapContainer.value, {
        center: [51.8335, 107.5841],
        zoom: 10,
        controls: ['zoomControl']
    })
    isMapLoaded.value = true
    syncMapPlacemarks()
}

onBeforeUnmount(() => map?.destroy())
onBeforeUnmount(() => clearTimeout(applyTimer))

watch(() => filters.productType, () => {
    filters.constrTypeId = ''
    loadFilters()
})

watch(() => filters.constrTypeId, scheduleApplyFilters)
watch(() => [filters.bookingFrom, filters.bookingTo], ([from, to]) => {
    if (from && to && parseDate(to) < parseDate(from)) {
        filters.bookingTo = from
    }
    if (!from && !to) activeDatePreset.value = ''
    scheduleApplyFilters()
})
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
