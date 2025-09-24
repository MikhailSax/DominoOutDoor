<template>
    <div class="flex flex-col md:flex-row min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 text-gray-800">
        <!-- Левая панель -->
        <div class="w-full md:w-96 h-auto md:h-screen flex flex-col">
            <!-- Контент с скроллом -->
            <div class=" md:p-6 overflow-y-auto bg-white shadow-lg md:shadow-xl border-b md:border-r border-gray-200 flex-1">
                <!-- Заголовок -->
                <div class="mb-6 md:mb-8">
                    <h2 class="text-xl md:text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                        Каталог конструкций
                    </h2>
                    <p class="text-xs md:text-sm text-gray-500 mt-1">Выберите подходящий вариант</p>
                </div>

                <!-- Фильтры -->
                <div class="bg-gradient-to-br from-gray-50 to-white p-4 md:p-5 rounded-xl md:rounded-2xl shadow-sm border border-gray-100 mb-4 md:mb-6 space-y-3 md:space-y-4">
                    <!-- Типы продукции -->
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-700">Тип продукции</label>
                        <div class="relative">
                            <select
                                v-model="filters.productType"
                                class="w-full px-3 md:px-4 py-2 md:py-3 text-sm md:text-base border-2 border-gray-200 rounded-lg md:rounded-xl focus:ring-2 focus:ring-red-300 focus:border-red-400 appearance-none bg-white cursor-pointer"
                            >
                                <option value="">Все типы продукции</option>
                                <option v-for="pt in productTypes" :key="pt.id" :value="pt.id">{{ pt.name }}</option>
                            </select>
                            <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Тип конструкции -->
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-700">Тип конструкции</label>
                        <div class="relative">
                            <select
                                v-model="filters.constrTypeId"
                                class="w-full px-3 md:px-4 py-2 md:py-3 text-sm md:text-base border-2 border-gray-200 rounded-lg md:rounded-xl focus:ring-2 focus:ring-red-300 focus:border-red-400 appearance-none bg-white cursor-pointer"
                            >
                                <option value="">Все типы конструкций</option>
                                <option v-for="c in constrTypes" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                            <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Район -->
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-700">Район</label>
                        <div class="relative">
                            <select
                                v-model="filters.areaId"
                                class="w-full px-3 md:px-4 py-2 md:py-3 text-sm md:text-base border-2 border-gray-200 rounded-lg md:rounded-xl focus:ring-2 focus:ring-red-300 focus:border-red-400 appearance-none bg-white cursor-pointer"
                            >
                                <option value="">Все районы</option>
                                <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </select>
                            <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Статистика фильтров -->
                    <div v-if="filteredObjects.length !== objects.length"
                         class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-2 md:p-3">
                        <div class="flex items-center justify-between text-xs md:text-sm">
                            <span class="text-green-700 font-medium">Найдено объектов:</span>
                            <span class="bg-green-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                                {{ filteredObjects.length }} из {{ objects.length }}
                            </span>
                        </div>
                    </div>

                    <!-- Кнопки управления -->
                    <div class="flex gap-2 md:gap-3">
                        <button
                            @click="resetFilters"
                            class="flex-1 px-3 md:px-4 py-2 md:py-3 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 rounded-lg md:rounded-xl font-medium md:font-semibold hover:from-gray-200 hover:to-gray-300 transition-all duration-200 transform hover:scale-[1.02] border-2 border-gray-100 shadow-sm flex items-center justify-center gap-1 md:gap-2 text-xs md:text-sm"
                        >
                            <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Сбросить
                        </button>
                        <button
                            @click="applyFilters"
                            class="flex-1 px-3 md:px-4 py-2 md:py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg md:rounded-xl font-medium md:font-semibold hover:from-red-600 hover:to-red-700 transition-all duration-200 transform hover:scale-[1.02] shadow-lg flex items-center justify-center gap-1 md:gap-2 text-xs md:text-sm"
                        >
                            <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Применить
                        </button>
                    </div>
                </div>

                <!-- Карточки -->
                <div class="space-y-3 md:space-y-4">
                    <div
                        v-for="item in filteredObjects"
                        :key="item.id"
                        @click="focusOnMap(item)"
                        :class="[
                            'bg-white border-2 border-gray-100 rounded-xl md:rounded-2xl p-3 md:p-5 shadow-sm hover:shadow-lg md:hover:shadow-2xl transition-all duration-300 cursor-pointer transform hover:scale-[1.01] md:hover:scale-[1.02] group',
                            activeCard === item.id ? 'border-red-400 shadow-lg ring-1 md:ring-2 ring-red-100' : 'hover:border-red-200'
                        ]"
                    >
                        <div class="flex justify-between items-start mb-2 md:mb-3">
                            <div class="flex-1">
                                <h3 class="text-base md:text-lg font-bold text-gray-900 group-hover:text-red-600 transition-colors line-clamp-1">
                                    {{ item.name }}
                                </h3>
                                <div class="flex flex-wrap gap-1 mt-1 md:mt-2">
                                    <span class="px-1.5 py-0.5 md:px-2 md:py-1 bg-blue-50 text-blue-600 text-xs rounded-full">
                                        {{ getProductTypeName(item.productType) }}
                                    </span>
                                    <span class="px-1.5 py-0.5 md:px-2 md:py-1 bg-green-50 text-green-600 text-xs rounded-full">
                                        {{ getConstrTypeName(item.constrTypeId) }}
                                    </span>
                                    <span class="px-1.5 py-0.5 md:px-2 md:py-1 bg-purple-50 text-purple-600 text-xs rounded-full">
                                        {{ getAreaName(item.areaId) }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right ml-2">
                                <div class="bg-gradient-to-r from-red-500 to-red-600 text-white px-2 py-1 md:px-3 md:py-2 rounded-lg md:rounded-xl shadow-lg">
                                    <p class="text-sm md:text-lg font-bold">{{ item.price.toLocaleString() }} ₽</p>
                                    <p class="text-xs opacity-90">в месяц</p>
                                </div>
                            </div>
                        </div>

                        <!-- Дополнительная информация -->
                        <div class="flex items-center justify-between text-xs text-gray-500 border-t border-gray-100 pt-2 md:pt-3">
                            <span class="flex items-center line-clamp-1">
                                📍 {{ item.address || 'Адрес не указан' }}
                            </span>
                            <span class="flex items-center bg-gray-50 px-1.5 py-0.5 md:px-2 md:py-1 rounded-full whitespace-nowrap">
                                ⭐ {{ (Math.random() * 0.5 + 4.3).toFixed(1) }}
                            </span>
                        </div>
                    </div>

                    <!-- Сообщение если ничего не найдено -->
                    <div v-if="filteredObjects.length === 0"
                         class="text-center py-6 md:py-8 bg-gradient-to-br from-gray-50 to-white rounded-xl md:rounded-2xl border-2 border-dashed border-gray-200">
                        <div class="text-gray-400 mb-2">
                            <svg class="w-8 h-8 md:w-12 md:h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-base md:text-lg font-semibold text-gray-600 mb-1">Ничего не найдено</h3>
                        <p class="text-xs md:text-sm text-gray-500">Попробуйте изменить параметры фильтрации</p>
                        <button @click="resetFilters"
                                class="mt-2 md:mt-3 px-3 py-1.5 md:px-4 md:py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg text-xs md:text-sm font-medium hover:from-red-600 hover:to-red-700 transition-all">
                            Сбросить фильтры
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Карта -->
        <div class="flex-1 h-64 md:h-screen p-2 md:p-6">
            <div ref="mapContainer" class="w-full h-full rounded-lg md:rounded-2xl border-2 border-white shadow-lg md:shadow-2xl"></div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'

const productTypes = [
    { id: 'prod1', name: 'Билборд' },
    { id: 'prod2', name: 'Цифровой билборд' },
    { id: 'prod3', name: 'Ситиборд' },
    { id: 'prod4', name: 'Скроллер' },
    { id: 'prod5', name: 'Суперсайт' },
    { id: 'prod6', name: 'Брандмауэр' }
]

const constrTypes = [
    { id: '16', name: 'Статичная' },
    { id: '17', name: 'Цифровая' },
    { id: '18', name: 'С подсветкой' },
    { id: '19', name: 'Ротационная' },
    { id: '20', name: 'Двусторонняя' }
]

const areas = [
    { id: '1', name: 'Советский' },
    { id: '2', name: 'Октябрьский' },
    { id: '3', name: 'Железнодорожный' },
    { id: '4', name: 'Центральный' },
    { id: '5', name: 'Пригородный' }
]

const filters = reactive({
    productType: '',
    constrTypeId: '',
    areaId: ''
})

const objects = []
const baseLat = 51.833437
const baseLon = 107.584547

// Генерация данных
for (let i = 1; i <= 50; i++) {
    objects.push({
        id: i,
        name: `Рекламная конструкция #${i}`,
        productType: productTypes[Math.floor(Math.random() * productTypes.length)].id,
        constrTypeId: constrTypes[Math.floor(Math.random() * constrTypes.length)].id,
        areaId: areas[Math.floor(Math.random() * areas.length)].id,
        coords: [
            +(baseLat + Math.random() * 0.03 - 0.015).toFixed(6),
            +(baseLon + Math.random() * 0.03 - 0.015).toFixed(6)
        ],
        price: Math.floor(Math.random() * 50000 + 15000),
        address: `ул. ${['Ленина', 'Советская', 'Мира', 'Центральная', 'Гагарина'][Math.floor(Math.random() * 5)]}, ${Math.floor(Math.random() * 100) + 1}`
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
const activeCard = ref(null)
let map = null
let placemarks = []

const getProductTypeName = id => productTypes.find(p => p.id === id)?.name || ''
const getConstrTypeName = id => constrTypes.find(c => c.id === id)?.name || ''
const getAreaName = id => areas.find(a => a.id === id)?.name || ''

const focusOnMap = (item) => {
    activeCard.value = item.id

    if (map) {
        map.setCenter(item.coords, 15, { duration: 300 })

        const placemark = placemarks.find(pm => {
            const coords = pm.geometry.getCoordinates()
            return coords[0] === item.coords[0] && coords[1] === item.coords[1]
        })

        if (placemark) {
            placemarks.forEach(pm => pm.balloon.close())
            setTimeout(() => {
                placemark.balloon.open(item.coords)
            }, 350)
        }
    }
}

const resetFilters = () => {
    filters.productType = ''
    filters.constrTypeId = ''
    filters.areaId = ''
    activeCard.value = null
}

const applyFilters = () => {
    console.log('Фильтры применены:', filters)
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
        activeCard.value = null
    }
})

const clearPlacemarks = () => {
    placemarks.forEach(pm => map.geoObjects.remove(pm))
    placemarks = []
}

const renderPlacemarks = () => {
    filteredObjects.value.forEach(obj => {
        const placemark = new ymaps.Placemark(obj.coords, {
            balloonContentHeader: `
                <div style="font-size:16px; font-weight:bold; color:#333; margin-bottom:8px;">
                    ${obj.name}
                </div>
            `,
            balloonContentBody: `
                <div style="font-size:13px; color:#333; line-height:1.4;">
                    <div style="margin-bottom:6px;">
                        <span style="font-weight:600; color:#666;">Тип продукции:</span>
                        <span style="color:#dc2626;">${getProductTypeName(obj.productType)}</span>
                    </div>
                    <div style="margin-bottom:6px;">
                        <span style="font-weight:600; color:#666;">Конструкция:</span>
                        ${getConstrTypeName(obj.constrTypeId)}
                    </div>
                    <div style="margin-bottom:6px;">
                        <span style="font-weight:600; color:#666;">Район:</span>
                        ${getAreaName(obj.areaId)}
                    </div>
                    <div style="margin-bottom:6px;">
                        <span style="font-weight:600; color:#666;">Адрес:</span>
                        ${obj.address}
                    </div>
                    <div style="margin-top:10px; padding-top:10px; border-top:1px solid #eee;">
                        <span style="font-size:14px; font-weight:bold; color:#dc2626;">
                            ${obj.price.toLocaleString()} ₽/мес
                        </span>
                    </div>
                </div>
            `,
            balloonContentFooter: `
                <div style="margin-top:8px; text-align:center;">
                    <button onclick="alert('Запрос на аренду отправлен!')"
                            style="background:linear-gradient(45deg, #dc2626, #ef4444); color:white; border:none; padding:6px 12px; border-radius:5px; cursor:pointer; font-weight:600; font-size:12px;">
                        📞 Забронировать
                    </button>
                </div>
            `
        }, {
            preset: 'islands#darkBlueDotIcon',
            balloonCloseButton: true,
            hideIconOnBalloonOpen: false
        })

        placemark.events.add('click', function() {
            activeCard.value = obj.id
        })

        map.geoObjects.add(placemark)
        placemarks.push(placemark)
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
</style>
