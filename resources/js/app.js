import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);

window.Alpine = Alpine;
Alpine.start();

import Swiper from 'swiper';
import 'swiper/swiper-bundle.css';

document.addEventListener("DOMContentLoaded", function () {
    new Swiper(".dynamic-slider", {
        loop: true,
        autoplay: { delay: 3500 },
        pagination: { el: ".swiper-pagination", clickable: true },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
    });
});

import { createIcons, icons } from 'lucide';

document.addEventListener('DOMContentLoaded', () => {
    createIcons({icons});
});
