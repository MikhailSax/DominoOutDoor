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
const isLoadingCardDetails = ref(false)

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

const activeObject = computed(() =>
    objects.value.find((item) => String(item.id) === String(activeObjectId.value)) || null
)

const activeSide = computed(() => {
    if (!activeObject.value) return null

    const sides = normalizeSideDetails(activeObject.value)
    if (sides.length === 0) return null

    return sides.find((item) => item.code === activeSideCode.value) || sides[0]
})

function formatSides(sides) {
    if (!Array.isArray(sides) || sides.length === 0) return '—'
    return sides.join(', ')
}

function formatPrice(price) {
    if (price === null || price === undefined || price === '') return 'По запросу'

    const value = Number(price)
    if (Number.isNaN(value)) return String(price)

    return `${new Intl.NumberFormat('ru-RU').format(value)} ₽`
}

function normalizeImageUrl(imageUrl, imageName) {
    const value = imageUrl || imageName
    if (!value || typeof value !== 'string') return null

    if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/')) {
        return value
    }

    if (imageUrl) {
        return `/${value}`
    }

    return `/uploads/advertisements/${value}`
}

function normalizeSideDetails(item) {
    const details = Array.isArray(item?.side_details) ? item.side_details : []

    const normalizedDetails = details
        .map((side) => ({
            code: String(side?.code || '').trim().toUpperCase(),
            description: side?.description ?? null,
            price: side?.price ?? null,
            image: side?.image ?? null,
            image_url: normalizeImageUrl(side?.image_url ?? null, side?.image ?? null),
        }))
        .filter((side) => side.code)

    if (normalizedDetails.length > 0) {
        return normalizedDetails
    }

    const rawSides = Array.isArray(item?.sides)
        ? item.sides
        : typeof item?.sides === 'string' && item.sides.length > 0
            ? item.sides.split(',')
            : []

    const normalizedFromSides = rawSides
        .map((code) => String(code || '').trim().toUpperCase())
        .filter(Boolean)
        .map((code) => ({
            code,
            description: null,
            price: null,
            image: null,
            image_url: null,
        }))

    if (normalizedFromSides.length > 0) {
        return normalizedFromSides
    }

    return [{
        code: '—',
        description: item?.address ? `Конструкция по адресу: ${item.address}` : null,
        price: null,
        image: null,
        image_url: null,
    }]
}

function normalizeAdvertisement(item) {
    const typeName = typeof item?.type === 'object' ? item?.type?.name : item?.type
    const categoryName = typeof item?.type === 'object' ? item?.type?.category : item?.category
    const sideDetails = normalizeSideDetails(item || {})

    return {
        ...item,
        type: typeName || null,
        category: categoryName || null,
        place_number: item?.place_number ?? item?.placeNumber ?? null,
        side_details: sideDetails,
        sides: sideDetails.map((side) => side.code),
    }
}

async function fetchJson(url) {
    const response = await fetch(url, {
        headers: { Accept: 'application/json' },
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

        const url = query.toString()
            ? `${props.filtersUrl}?${query.toString()}`
            : props.filtersUrl

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
        const url = query
            ? `${props.advertisementsUrl}?${query}`
            : props.advertisementsUrl

        const data = await fetchJson(url)
        objects.value = (Array.isArray(data) ? data : [])
            .map((item) => normalizeAdvertisement(item))

        syncMapPlacemarks()
    } finally {
        isLoadingObjects.value = false
    }
}

async function loadAdvertisementDetails(objectId) {
    isLoadingCardDetails.value = true
    try {
        const url = `${props.advertisementsUrl}/${encodeURIComponent(String(objectId))}`
        const data = await fetchJson(url)
        const normalized = normalizeAdvertisement(data)

        objects.value = objects.value.map((item) =>
            String(item.id) === String(objectId)
                ? { ...item, ...normalized }
                : item
        )

        const updated = objects.value.find(
            (item) => String(item.id) === String(objectId)
        )

        const sideCodes = normalizeSideDetails(updated || {}).map((side) => side.code)

        if (!sideCodes.includes(activeSideCode.value)) {
            activeSideCode.value = sideCodes[0] || ''
        }
    } catch (error) {
        console.error('Не удалось загрузить детали конструкции', error)
    } finally {
        isLoadingCardDetails.value = false
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

        script.onerror = () =>
            reject(new Error('Ошибка загрузки Яндекс.Карт'))

        document.head.appendChild(script)
    })
}

function clearPlacemarks() {
    if (!map) return

    placemarks.forEach((placemark) => {
        map.geoObjects.remove(placemark)
    })
    placemarks.clear()
}

function syncMapPlacemarks() {
    if (!map || !isMapLoaded.value) return

    clearPlacemarks()

    objects.value.forEach((item) => {
        if (!item.location ||
            typeof item.location.latitude !== 'number' ||
            typeof item.location.longitude !== 'number') {
            return
        }

        const placemark = new window.ymaps.Placemark(
            [item.location.latitude, item.location.longitude],
            {},
            { preset: 'islands#redIcon' }
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

async function focusObject(objectId) {
    activeObjectId.value = objectId

    const item = objects.value.find(
        (obj) => String(obj.id) === String(objectId)
    )

    activeSideCode.value =
        normalizeSideDetails(item || {})[0]?.code || ''

    await loadAdvertisementDetails(objectId)

    const placemark = placemarks.get(objectId)
    if (!placemark || !map) return

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
        mapError.value =
            'Не удалось загрузить карту. Попробуйте обновить страницу.'
        console.error(error)
    }
}

watch(
    () => filters.productType,
    async () => {
        filters.constrTypeId = ''
        await loadFilters()
    }
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
