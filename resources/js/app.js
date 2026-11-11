import './bootstrap';
import Swiper from 'swiper/bundle';
import 'swiper/css/bundle';

document.querySelectorAll('.swiper').forEach(element => {
    new Swiper(element, {
        speed: 400,
        autoplay: { delay: 3000, disableOnInteraction: false },
        spaceBetween: 10,
        pagination: { el: element.querySelector('.swiper-pagination'), clickable: true },
        navigation: { nextEl: element.querySelector('.swiper-button-next'), prevEl: element.querySelector('.swiper-button-prev') },
        breakpoints: {
            325: { slidesPerView: 2, spaceBetween: 20 },
            768: { slidesPerView: 4, spaceBetween: 40 },
            1024: { slidesPerView: 5, spaceBetween: 50 },
        },
    });
});
