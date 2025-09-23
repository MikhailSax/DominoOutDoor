<template>
    <div class="flex flex-col md:flex-row h-screen bg-gray-100 text-gray-800">
        <!-- Левая панель -->
        <div class="w-full md:w-1/3 h-1/2 md:h-full p-4 overflow-y-auto bg-white shadow-md">
            <h2 class="text-2xl font-bold mb-4 text-gray-900">Каталог конструкций</h2>

            <!-- Фильтры -->
            <div class="bg-gray-50 p-4 rounded-lg shadow-sm mb-6 space-y-4">
                <div class="overflow-x-auto flex gap-2 pb-2">
                    <button
                        v-for="pt in productTypes"
                        :key="pt.id"
                        @click="filters.productType = pt.id"
                        :class="[
              'px-4 py-2 rounded-full text-sm font-medium border transition whitespace-nowrap',
              filters.productType === pt.id
                ? 'bg-red-600 text-white border-red-600'
                : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-100'
            ]"
                    >
                        {{ pt.name }}
                    </button>
                    <button
                        @click="filters.productType = ''"
                        class="px-4 py-2 rounded-full text-sm font-medium border border-gray-300 text-gray-500 hover:bg-gray-100"
                    >
                        Все
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Тип конструкции</label>
                    <select
                        v-model="filters.constrTypeId"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-red-500 focus:border-red-500"
                    >
                        <option value="">Все</option>
                        <option v-for="c in constrTypes" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Район</label>
                    <select
                        v-model="filters.areaId"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-red-500 focus:border-red-500"
                    >
                        <option value="">Все</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>

                <button
                    @click="resetFilters"
                    class="w-full text-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                >
                    Сбросить фильтры
                </button>
            </div>

            <!-- Карточки -->
            <div class="space-y-4">
                <div
                    v-for="item in filteredObjects"
                    :key="item.id"
                    @click="focusOnMap(item)"
                    class="bg-white border rounded-lg p-4 shadow-sm hover:shadow-md transition cursor-pointer"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">{{ item.name }}</h3>
                            <p class="text-sm text-gray-500">
                                {{ getProductTypeName(item.productType) }} ·
                                {{ getConstrTypeName(item.constrTypeId) }} ·
                                {{ getAreaName(item.areaId) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-red-600 text-lg font-bold">{{ item.price.toLocaleString() }} ₽</p>
                            <p class="text-xs text-gray-400">/мес</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Карта -->
        <div class="w-full md:w-2/3 h-1/2 md:h-full p-4">
            <div ref="mapContainer" class="w-full h-full rounded-xl border shadow-md"></div>
        </div>
    </div>
</template>


<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'

const productTypes = [
    { id: 'prod1', name: 'Билборд' },
    { id: 'prod2', name: 'Цифровой билборд' },
    { id: 'prod3', name: 'Ситиборд' },
    { id: 'prod4', name: 'Скроллер' }
]

const constrTypes = [
    { id: '16', name: 'Статичная' },
    { id: '17', name: 'Цифровая' },
    { id: '18', name: 'С подсветкой' }
]

const areas = [
    { id: '1', name: 'Советский' },
    { id: '2', name: 'Октябрьский' },
    { id: '3', name: 'Железнодорожный' }
]

const filters = reactive({
    productType: '',
    constrTypeId: '',
    areaId: ''
})

const objects = []
const baseLat = 51.833437
const baseLon = 107.584547

for (let i = 1; i <= 50; i++) {
    objects.push({
        id: i,
        name: `Рекламная конструкция #${i}`,
        productType: productTypes[Math.floor(Math.random() * productTypes.length)].id,
        constrTypeId: constrTypes[Math.floor(Math.random() * constrTypes.length)].id,
        areaId: areas[Math.floor(Math.random() * areas.length)].id,
        coords: [
            +(baseLat + Math.random() * 0.02 - 0.01).toFixed(6),
            +(baseLon + Math.random() * 0.02 - 0.01).toFixed(6)
        ],
        price: Math.floor(Math.random() * 40000 + 20000)
    })
}

const filteredObjects = computed(() => {
    return objects.filter(obj => {
        const matchProd = filters.productType ? obj.productType === filters.productType : true
        const matchType = filters.constrTypeId ? obj.constrTypeId === filters.constrTypeId : true
        const matchArea = filters.areaId ? obj.areaId === filters.areaId : true
        return matchProd && matchType && matchArea
    })
})

const mapContainer = ref(null)
let map = null
let placemarks = []

const getProductTypeName = id => productTypes.find(p => p.id === id)?.name || ''
const getConstrTypeName = id => constrTypes.find(c => c.id === id)?.name || ''
const getAreaName = id => areas.find(a => a.id === id)?.name || ''

const focusOnMap = (item) => {
    if (map) {
        map.setCenter(item.coords, 15, { duration: 300 })
    }
}

const resetFilters = () => {
    filters.productType = ''
    filters.constrTypeId = ''
    filters.areaId = ''
}

const loadYandexMap = () => {
    return new Promise(resolve => {
        if (window.ymaps) {
            ymaps.ready(resolve)
        } else {
            const script = document.createElement('script')
            script.src = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=c623fc99-6a74-48cf-94b5-b7cf1ccf7254'
            script.onload = () => ymaps.ready(resolve)
            document.head.appendChild(script)
        }
    })
}

onMounted(async () => {
    await loadYandexMap()
    map = new ymaps.Map(mapContainer.value, {
        center: [baseLat, baseLon],
        zoom: 13,
        controls: ['zoomControl']
    })
    renderPlacemarks()
})

watch(filteredObjects, () => {
    if (map) {
        clearPlacemarks()
        renderPlacemarks()
    }
})

const clearPlacemarks = () => {
    placemarks.forEach(pm => map.geoObjects.remove(pm))
    placemarks = []
}

const renderPlacemarks = () => {
    filteredObjects.value.forEach(obj => {
        const placemark = new ymaps.Placemark(obj.coords, {
            balloonContentHeader: `<strong>${obj.name}</strong>`,
            balloonContentBody: `
        <div style="font-size:14px; color:#333;">
          <div><b>Продукция:</b> ${getProductTypeName(obj.productType)}</div>
          <div><b>Тип:</b> ${getConstrTypeName(obj.constrTypeId)}</div>
          <div><b>Район:</b> ${getAreaName(obj.areaId)}</div>
          <div><b>Цена:</b> ${obj.price.toLocaleString()} ₽/мес.</div>
        </div>
      `
        }, {
            preset: 'islands#darkBlueDotIcon'
        })
        map.geoObjects.add(placemark)
        placemarks.push(placemark)
    })
}
</script>

<style scoped>
.ymaps-2-1-79-map {
    height: 100% !important;
    border-radius: 16px;
}
</style>
