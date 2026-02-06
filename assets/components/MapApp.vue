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
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100"
                        >
                            <option value="">Все типы конструкций</option>
                            <option v-for="item in constrTypes" :key="item.id" :value="String(item.id)">
                                {{ item.name }}
                            </option>
                        </select>
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
                    <span class="rounded-full bg-blue-600 px-3 py-1 text-white">{{ objects.length }}</span>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-3">
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
                            v-for="side in activeObject.side_details"
                            :key="side.code"
                            type="button"
                            class="min-w-[42px] rounded-full px-3 py-1.5 text-base font-semibold"
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
                        class="absolute right-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white text-2xl font-light text-slate-700 shadow hover:bg-slate-100"
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
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-2xl leading-tight font-bold text-slate-900">{{ activeObject.address }}</h3>
                            <p class="mt-1 text-lg font-semibold tracking-wide text-slate-400">GID {{ activeObject.id }}</p>
                        </div>
                        <a :href="`/api/advertisements/${activeObject.id}`" target="_blank" class="pt-1 text-base font-medium text-blue-600 hover:text-blue-700">
                            Подробнее
                        </a>
                    </div>

                    <dl class="grid grid-cols-[1fr_auto] gap-x-5 gap-y-2 text-lg">
                        <dt class="border-b border-slate-200 pb-1 text-slate-600">Формат</dt>
                        <dd class="border-b border-slate-200 pb-1 font-semibold text-slate-800">{{ activeObject.type }}</dd>
                        <dt class="border-b border-slate-200 pb-1 text-slate-600">Сторона</dt>
                        <dd class="border-b border-slate-200 pb-1 font-semibold text-slate-800">{{ activeSide.code }}</dd>
                        <dt class="border-b border-slate-200 pb-1 text-slate-600">Описание стороны</dt>
                        <dd class="border-b border-slate-200 pb-1 text-right text-base font-medium text-slate-700">
                            {{ activeSide.description || 'Описание пока не заполнено' }}
                        </dd>
                        <dt class="text-slate-500">Прайс без НДС</dt>
                        <dd class="text-right text-3xl font-extrabold text-slate-900">{{ formatPrice(activeSide.price) }}</dd>
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

// --- Вспомогательные функции ---
function parseDate(value) {
    if (!value) return null
    const date = new Date(`${value}T00:00:00`)
    return isNaN(date.getTime()) ? null : date
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
            { preset: 'islands#redIcon' }
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
        center: [55.75, 37.61],
        zoom: 10,
        controls: ['zoomControl']
    })
    isMapLoaded.value = true
    syncMapPlacemarks()
}

onBeforeUnmount(() => map?.destroy())

watch(() => filters.productType, () => {
    filters.constrTypeId = ''
    loadFilters()
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
