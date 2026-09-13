/**
 * VARSAATHI Dating App - iOS Navigation & Drawer Engine
 */

document.addEventListener('DOMContentLoaded', () => {
  // Smooth Preloader Fadeout
  const loader = document.getElementById('pageLoader');
  if (loader) {
    setTimeout(() => {
      loader.style.opacity = '0';
      setTimeout(() => loader.style.display = 'none', 350);
    }, 500);
  }

  // Sidebar Drawer Handlers
  const openSidebarBtn = document.getElementById('openSidebarBtn');
  const sidebarOverlay = document.getElementById('sidebarOverlay');

  if (openSidebarBtn && sidebarOverlay) {
    openSidebarBtn.addEventListener('click', () => {
      sidebarOverlay.classList.add('active');
    });

    sidebarOverlay.addEventListener('click', (e) => {
      if (e.target === sidebarOverlay) {
        sidebarOverlay.classList.remove('active');
      }
    });
  }

  // Filter Drawer Toggles
  const filterBtn = document.getElementById('filterBtn');
  const filterDrawerModal = document.getElementById('filterDrawerModal');
  const closeFilterBtn = document.getElementById('closeFilterBtn');

  if (filterBtn && filterDrawerModal) {
    filterBtn.addEventListener('click', () => filterDrawerModal.classList.add('active'));
  }
  if (closeFilterBtn && filterDrawerModal) {
    closeFilterBtn.addEventListener('click', () => filterDrawerModal.classList.remove('active'));
  }

  // Close Match Modal
  const closeMatchModalBtn = document.getElementById('closeMatchModalBtn');
  const matchModal = document.getElementById('matchModal');
  if (closeMatchModalBtn && matchModal) {
    closeMatchModalBtn.addEventListener('click', () => matchModal.classList.remove('active'));
  }

  // Filter Sliders Live Update
  const distanceRange = document.getElementById('distanceRange');
  const distanceVal = document.getElementById('distanceVal');
  if (distanceRange && distanceVal) {
    distanceRange.addEventListener('input', (e) => {
      distanceVal.textContent = e.target.value + ' km';
    });
  }

  const ageMin = document.getElementById('ageMin');
  const ageMax = document.getElementById('ageMax');
  const ageVal = document.getElementById('ageVal');
  if (ageMin && ageMax && ageVal) {
    const updateAgeText = () => {
      ageVal.textContent = `${ageMin.value} - ${ageMax.value}`;
    };
    ageMin.addEventListener('input', updateAgeText);
    ageMax.addEventListener('input', updateAgeText);
  }
});

// Global Cute Floating Heart Particles Explosion Trigger
window.triggerCuteLikeAnimation = function (element, event) {
  if (!element && !event) return;

  const rect = element ? element.getBoundingClientRect() : null;
  const centerX = event && event.clientX ? event.clientX : (rect ? rect.left + rect.width / 2 : window.innerWidth / 2);
  const centerY = event && event.clientY ? event.clientY : (rect ? rect.top + rect.height / 2 : window.innerHeight / 2);

  const heartEmojis = ['💕', '💖', '💗', '💓', '✨', '🌸', '❤️'];

  for (let i = 0; i < 9; i++) {
    const particle = document.createElement('div');
    particle.className = 'cute-heart-particle';
    particle.textContent = heartEmojis[Math.floor(Math.random() * heartEmojis.length)];

    const tx = (Math.random() - 0.5) * 160;
    const ty = - (Math.random() * 110 + 30);
    const rot = (Math.random() - 0.5) * 60;

    particle.style.left = `${centerX}px`;
    particle.style.top = `${centerY}px`;
    particle.style.setProperty('--tx', `${tx}px`);
    particle.style.setProperty('--ty', `${ty}px`);
    particle.style.setProperty('--rot', `${rot}deg`);

    document.body.appendChild(particle);

    setTimeout(() => particle.remove(), 1200);
  }
// Global Bouncy 3D Heart Bubble Burst Trigger for Cards
window.triggerBouncyHeartBubble = function (cardOrElement, event) {
  let targetContainer = document.body;
  let posX = window.innerWidth / 2;
  let posY = window.innerHeight / 2;

  if (cardOrElement) {
    if (cardOrElement.classList && cardOrElement.classList.contains('carousel-card-item')) {
      targetContainer = cardOrElement;
    } else if (cardOrElement.closest && cardOrElement.closest('.carousel-card-item')) {
      targetContainer = cardOrElement.closest('.carousel-card-item');
    } else if (cardOrElement.closest && cardOrElement.closest('.saathi-hero-card')) {
      targetContainer = cardOrElement.closest('.saathi-hero-card');
    } else if (cardOrElement.getBoundingClientRect) {
      const rect = cardOrElement.getBoundingClientRect();
      posX = rect.left + rect.width / 2;
      posY = rect.top + rect.height / 2;
    }
  }

  // Create main bouncy 3D glass bubble
  const bubbleWrap = document.createElement('div');
  bubbleWrap.className = 'bouncy-heart-bubble-wrap';
  
  if (targetContainer !== document.body) {
    bubbleWrap.style.position = 'absolute';
    bubbleWrap.style.top = '45%';
    bubbleWrap.style.left = '50%';
  } else {
    bubbleWrap.style.position = 'fixed';
    bubbleWrap.style.left = `${posX}px`;
    bubbleWrap.style.top = `${posY}px`;
  }

  bubbleWrap.innerHTML = `<div class="bouncy-heart-bubble">💖</div>`;
  targetContainer.appendChild(bubbleWrap);

  // Also trigger surrounding particle burst
  if (typeof window.triggerCuteLikeAnimation === 'function') {
    window.triggerCuteLikeAnimation(cardOrElement, event);
  }

  setTimeout(() => bubbleWrap.remove(), 1150);
};

