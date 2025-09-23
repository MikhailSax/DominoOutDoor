import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

import './js/header';
import './js/auth/register';

import Swiper from 'swiper';
import { Navigation, Pagination } from 'swiper/modules'; // Import modules as needed

//vue
import { createApp } from 'vue';
import MapApp from './components/MapApp.vue';

// Import Swiper styles (if not already handled in CSS)
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';


createApp(MapApp).mount('#map-app');

// Initialize Swiper
document.addEventListener('DOMContentLoaded', () => {
    const swiper = new Swiper('.default-carousel', {
        modules: [Navigation, Pagination], // Register modules
        speed: 400,
        spaceBetween: 100,
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        // ... other Swiper options
    });
});
