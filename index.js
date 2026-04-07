// Slideshow
const slides = document.querySelectorAll('.slide');
const indicators = document.querySelectorAll('.indicator');
let current = 0;

function showSlide(index) {
    current = (index + slides.length) % slides.length;

    slides.forEach((slide, i) => {
        slide.style.transform = `translateX(${100 * (i - current)}%)`;
        slide.classList.remove('active');
        if (i === current) slide.classList.add('active');
    });

    indicators.forEach((dot, i) => {
        dot.classList.toggle('active', i === current);
    });
}

setInterval(() => showSlide(current + 1), 5000);

indicators.forEach(dot => {
    dot.addEventListener('click', () => showSlide(parseInt(dot.dataset.index)));
});

showSlide(0);