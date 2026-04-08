// SLIDES
const slides = document.querySelectorAll('.slide');
const indicators = document.querySelectorAll('.indicator');
let current = 0;

function showSlide(index) {
    current = (index + slides.length) % slides.length;

    slides.forEach((slide, i) => {
        slide.style.transform = `translateX(${100 * (i - current)}%)`;
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


// LOGIN MODAL
function openLogin() {
    document.getElementById("loginModal").classList.add("active");
    document.body.classList.add("modal-open");
}

function closeLogin() {
    document.getElementById("loginModal").classList.remove("active");
    document.body.classList.remove("modal-open");
}

// SHOW / HIDE PASSWORD
function togglePassword() {
    const pass = document.getElementById("password");
    pass.type = pass.type === "password" ? "text" : "password";
}