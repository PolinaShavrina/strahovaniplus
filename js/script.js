const slider = document.querySelector('.slider');
const prevButton = document.querySelector('.prev-button');
const nextButton = document.querySelector('.next-button');
const slides = Array.from(slider.querySelectorAll('.cards'));
const slideCount = slides.length;
let slideIndex = 0;

prevButton.addEventListener('click', showPreviousSlide);
nextButton.addEventListener('click', showNextSlide);

function showPreviousSlide() {
  slideIndex = (slideIndex - 1 + slideCount) % slideCount;
  updateSlider();
}


function showNextSlide() {
  slideIndex = (slideIndex + 1) % slideCount;
  updateSlider();
}

function updateSlider() {
    slides.forEach((slides, index)=> {
        if (index == slideIndex) {
            slides.style.display = 'block';
        } else {
            slides.style.display = 'none';
        }
    });
}

updateSlider();