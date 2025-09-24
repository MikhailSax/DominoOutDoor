import './bootstrap.js';
import './styles/app.css';
import './js/header';
import './js/auth/register';

import Swiper from 'swiper';
import { Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

import { createApp } from 'vue';
import MapApp from './components/MapApp.vue';

// Функция инициализации Vue
function initVue() {
    const mapElement = document.getElementById('map-app');
    if (mapElement && !mapElement.__vue_app__) {
        const app = createApp(MapApp);
        app.mount('#map-app');
        mapElement.__vue_app__ = app; // Помечаем как инициализированный
    }
}

// Функция инициализации Swiper
function initSwiper() {
    const swiperElements = document.querySelectorAll('.default-carousel');
    swiperElements.forEach(element => {
        if (!element.__swiper_instance__) {
            const swiper = new Swiper(element, {
                modules: [Navigation, Pagination],
                speed: 400,
                spaceBetween: 100,
                navigation: {
                    nextEl: element.querySelector('.swiper-button-next'),
                    prevEl: element.querySelector('.swiper-button-prev'),
                },
                pagination: {
                    el: element.querySelector('.swiper-pagination'),
                    clickable: true,
                },
            });
            element.__swiper_instance__ = swiper;
        }
    });
}

// Обработчик загрузки страницы
function initPage() {
    initVue();
    initSwiper();
}

// Инициализация при полной загрузке
document.addEventListener('DOMContentLoaded', initPage);

// Инициализация при Turbo навигации
document.addEventListener('turbo:load', initPage);

// Очистка перед переходом (опционально)
document.addEventListener('turbo:before-render', () => {
    // Vue автоматически уничтожается при unmount
});
