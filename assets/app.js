import './bootstrap.js';
import './styles/app.css';
import './js/header';
import './js/auth/register';

import Swiper from 'swiper';
import { Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

// Vue
import { createApp } from 'vue';
import App from './components/App.vue';

// Простая инициализация Vue
function initVue() {
    const mapElement = document.getElementById('map-app');
    if (mapElement && !mapElement._vueApp) {
        try {
            const app = createApp(App, {
                filtersUrl: mapElement.dataset.filtersUrl || '/api/filters',
                advertisementsUrl: mapElement.dataset.advertisementsUrl || '/api/advertisements',
                productRequestsUrl: mapElement.dataset.productRequestsUrl || '/product-requests',
            });
            app.mount('#map-app');
            mapElement._vueApp = app;
        } catch (error) {
            console.error('Vue app mounting error:', error);
        }
    }
}

// Инициализация Swiper
function initSwiper() {
    const swiperElements = document.querySelectorAll('.default-carousel');
    swiperElements.forEach((element) => {
        if (!element._swiper) {
            try {
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
                element._swiper = swiper;
            } catch (error) {
                console.error('Swiper initialization error:', error);
            }
        }
    });
}

// Основная функция инициализации
function initApp() {
    initVue();
    initSwiper();
}

// Обработчики событий
document.addEventListener('DOMContentLoaded', initApp);
document.addEventListener('turbo:load', initApp);

// Очистка перед переходом Turbo
document.addEventListener('turbo:before-render', () => {
    const mapElement = document.getElementById('map-app');
    if (mapElement && mapElement._vueApp) {
        mapElement._vueApp.unmount();
        mapElement._vueApp = null;
    }
});
