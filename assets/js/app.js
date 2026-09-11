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
