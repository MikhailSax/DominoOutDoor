<template>
    <div class="flex flex-col md:flex-row w-full h-screen bg-gradient-to-br from-gray-50 to-gray-100 text-gray-800 overflow-hidden">
        <!-- Левая панель -->
        <div class="w-full md:w-96 h-1/2 md:h-full flex flex-col flex-shrink-0 mt-8 rounded-4">
            <!-- Контент с скроллом -->
            <div class="p-3 sm:p-4 md:p-6 overflow-y-auto bg-white shadow-lg md:shadow-xl border-b md:border-r border-gray-200 flex-1">
                <!-- Заголовок -->
                <div class="mb-4 md:mb-6 lg:mb-8">
                    <h2 class="text-lg sm:text-xl md:text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                        Каталог конструкций
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Выберите подходящий вариант</p>
                </div>

                <!-- Фильтры -->
                <div class="bg-gradient-to-br from-gray-50 to-white p-3 sm:p-4 md:p-5 rounded-lg sm:rounded-xl md:rounded-2xl shadow-sm border border-gray-100 mb-3 sm:mb-4 md:mb-6 space-y-2 sm:space-y-3 md:space-y-4">
                    <!-- Типы продукции -->
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold mb-1 sm:mb-2 text-gray-700">Тип продукции</label>
                        <div class="relative">
                            <select
                                v-model="filters.productType"
                                class="w-full px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 md:py-3 text-xs sm:text-sm md:text-base border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-red-300 focus:border-red-400 appearance-none bg-white cursor-pointer"
                            >
                                <option value="">Все типы продукции</option>
                                <option v-for="pt in productTypes" :key="pt.id" :value="pt.id">{{ pt.name }}</option>
                            </select>
                            <div class="absolute right-2 sm:right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Тип конструкции -->
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold mb-1 sm:mb-2 text-gray-700">Тип конструкции</label>
                        <div class="relative">
                            <select
                                v-model="filters.constrTypeId"
                                class="w-full px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 md:py-3 text-xs sm:text-sm md:text-base border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-red-300 focus:border-red-400 appearance-none bg-white cursor-pointer"
                            >
                                <option value="">Все типы конструкций</option>
                                <option v-for="c in constrTypes" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                            <div class="absolute right-2 sm:right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Район -->

                    <!-- Статистика фильтров -->
                    <div v-if="objects.length !== objects.length"
                         class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-2 md:p-3">
                        <div class="flex items-center justify-between text-xs sm:text-sm">
                            <span class="text-green-700 font-medium">Найдено объектов:</span>
                            <span class="bg-green-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                                {{ objects.length }} из {{ objects.length }}
                            </span>
                        </div>
                    </div>

                    <!-- Кнопки управления -->
                    <div class="flex gap-2 sm:gap-3">
                        <button
                            @click="resetFilters"
                            class="flex-1 px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 md:py-3 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 rounded-lg font-medium hover:from-gray-200 hover:to-gray-300 transition-all duration-200 transform hover:scale-[1.02] border-2 border-gray-100 shadow-sm flex items-center justify-center gap-1 sm:gap-2 text-xs sm:text-sm"
                        >
                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Сбросить
                        </button>
                        <button
                            @click="applyFilters"
                            class="flex-1 px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 md:py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg font-medium hover:from-red-600 hover:to-red-700 transition-all duration-200 transform hover:scale-[1.02] shadow-lg flex items-center justify-center gap-1 sm:gap-2 text-xs sm:text-sm"
                        >
                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Применить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Карта -->
        <div class="flex-1 h-1/2 md:h-full p-1 sm:p-2 md:p-3 lg:p-4 xl:p-6">
            <!-- Спиннер загрузки -->
            <div v-if="!isMapLoaded" class="w-full h-full flex flex-col items-center justify-center bg-gray-100 rounded-lg sm:rounded-xl md:rounded-2xl border-2 border-dashed border-gray-300">
                <div class="animate-spin rounded-full h-8 sm:h-12 md:h-16 w-8 sm:w-12 md:w-16 border-4 border-red-200 border-t-red-600 mb-2 sm:mb-4"></div>
                <p class="text-gray-600 font-medium text-sm sm:text-base">Загрузка карты...</p>
                <p class="text-gray-500 text-xs sm:text-sm mt-1">Пожалуйста, подождите</p>
            </div>

            <!-- Контейнер карты -->
            <div v-else ref="mapContainer" class="w-full h-full rounded-lg sm:rounded-xl md:rounded-2xl border-2 border-white shadow-lg"></div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, nextTick } from 'vue'

const isMapLoaded = ref(false)

const productTypes = ref([])
const constrTypes = ref([])

const filters = reactive({
    productType: '',
    constrTypeId: '',
})

const objects = ref([])

const mapContainer = ref(null)
const activeCard = ref(null)
let map = null
let placemarks = []

const getProductTypeName = id => productTypes.value.find(p => p.id == id)?.name || ''
const getConstrTypeName = id => constrTypes.value.find(c => c.id == id)?.name || ''



// 🚀 Загрузка фильтров
async function loadFilters() {
    const params = new URLSearchParams();
    if (filters.productType) params.append('productType', filters.productType)
    const res = await fetch(`http://127.0.0.1:8000/api/filters?${params.toString()}`)
    console.log(res)
    const data = await res.json()
    console.log(data)
    productTypes.value = data.productTypes
    constrTypes.value = data.constrTypes
}

// 🚀 Загрузка объявлений с фильтрами
async function loadAdvertisements() {
    const params = new URLSearchParams()
    if (filters.productType) params.append('productType', filters.productType)
    if (filters.constrTypeId) params.append('constrTypeId', filters.constrTypeId)

    const res = await fetch(`http://127.0.0.1:8000/api/advertisements?${params.toString()}`)
    const data = await res.json()
    objects.value = data

    if (map && isMapLoaded.value) {
        clearPlacemarks()
        renderPlacemarks()
    }
}

function resetFilters() {
    filters.productType = ''
    filters.constrTypeId = ''
    loadAdvertisements()
}

function applyFilters() {
    loadFilters()
    loadAdvertisements()
}

function loadYandexMap() {
    return new Promise((resolve, reject) => {
        if (window.ymaps) {
            window.ymaps.ready(resolve)
        } else {
            const script = document.createElement('script')
            script.src = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=c623fc99-6a74-48cf-94b5-b7cf1ccf7254'
            script.onload = () => {
                if (window.ymaps) window.ymaps.ready(resolve)
                else reject(new Error('Yandex Maps не загрузились'))
            }
            script.onerror = () => reject(new Error('Ошибка загрузки Яндекс.Карт'))
            document.head.appendChild(script)
        }
    })
}

onMounted(async () => {
    await loadFilters()
    await loadAdvertisements()
    await initMap()
})

async function initMap() {
    isMapLoaded.value = false
    await loadYandexMap()
    isMapLoaded.value = true
    await nextTick()

    map = new ymaps.Map(mapContainer.value, {
        center: [51.833437, 107.584547],
        zoom: 13,
        controls: ['zoomControl', 'typeSelector', 'fullscreenControl'],
        behaviors: ['default', 'scrollZoom']
    })

    renderPlacemarks()
}

function clearPlacemarks() {
    if (map && placemarks.length > 0) {
        placemarks.forEach(pm => map.geoObjects.remove(pm))
        placemarks = []
    }
}

function renderPlacemarks() {
    objects.value.forEach(obj => {
        if (!obj.location) return
        const coords = [obj.location.latitude, obj.location.longitude]

        const placemark = new ymaps.Placemark(coords, {
            balloonContent: `
  <div style="
    font-family: 'Inter', sans-serif;
    padding: 12px 16px;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    max-width: 280px;
  ">
    <div style="font-weight: 700; font-size: 16px; color: #111; margin-bottom: 8px;">
      📍 ${obj.address}
    </div>

    <div style="font-size: 14px; color: #444; line-height: 1.5;">
      <p style="margin: 4px 0;">
        🏭 <b>Тип продукции:</b> ${obj.type || '-'}
      </p>
      <p style="margin: 4px 0;">
        🏗 <b>Конструкция:</b> ${getConstrTypeName(obj.type?.id) || '—'}
      </p>
      <p style="margin: 4px 0;">
        📌 <b>Адрес:</b> ${obj.address || 'Не указан'}
      </p>
      <p style="margin: 4px 0">
           <b>Стороны</b> ${obj.sides[0] || ''} и ${obj.sides[1] || ''}
       </p>
    </div>

    <div style="margin-top: 10px; text-align: right;">
      <button style="
        background: linear-gradient(90deg,#ef4444,#dc2626);
        color: #fff;
        padding: 6px 12px;
        font-size: 13px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: 0.2s;
      " onmouseover="this.style.opacity=0.9" onmouseout="this.style.opacity=1">
        Подробнее →
      </button>
    </div>
  </div>
`


        }, { preset: 'islands#redIcon' })

        placemarks.push(placemark)
        map.geoObjects.add(placemark)
    })
}

</script>




<style scoped>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

* {
    transition-property: color, background-color, border-color, transform, box-shadow;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

.line-clamp-1 {
    overflow: hidden;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 1;
}

/* Адаптивные стили для селектов */
select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.2em 1.2em;
    padding-right: 2.5rem;
}

@media (max-width: 768px) {
    select {
        background-size: 1em 1em;
        padding-right: 2rem;
    }
}

/* Анимация спиннера */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
