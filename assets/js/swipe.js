/**
 * Friendzy Dating App - Card Swiping Engine
 */

document.addEventListener('DOMContentLoaded', () => {
  const cardDeck = document.getElementById('cardDeck');
  if (!cardDeck) return;

  let isDragging = false;
  let startX = 0;
  let startY = 0;
  let currentX = 0;
  let currentY = 0;
  let activeCard = null;

  function initDeck() {
    const cards = cardDeck.querySelectorAll('.profile-card');
    cards.forEach((card, idx) => {
      card.style.zIndex = cards.length - idx;
      if (idx === 0) {
        card.style.transform = 'scale(1) translateY(0px)';
        bindCardEvents(card);
      } else if (idx === 1) {
        card.style.transform = 'scale(0.95) translateY(12px)';
      } else {
        card.style.transform = 'scale(0.9) translateY(24px)';
      }
    });

    if (cards.length === 0) {
      cardDeck.innerHTML = `
        <div style="text-align:center; padding: 60px 20px;" class="no-more-cards">
          <div style="font-size: 3rem; margin-bottom: 12px;">🌟</div>
          <h3 style="font-weight:800; margin-bottom:8px;">No More Profiles!</h3>
          <p style="color:var(--text-muted); font-size:0.85rem;">Adjust your filter preferences or check back soon for new matches nearby.</p>
          <button onclick="location.reload()" class="btn-block btn-transparent" style="margin-top:20px; background:var(--primary-gradient); color:#fff; border:none;">Refresh Feed</button>
        </div>
      `;
    }
  }

  function bindCardEvents(card) {
    activeCard = card;

    // Mouse Events
    card.addEventListener('mousedown', onDragStart);
    document.addEventListener('mousemove', onDragMove);
    document.addEventListener('mouseup', onDragEnd);

    // Touch Events
    card.addEventListener('touchstart', onDragStart, { passive: true });
    document.addEventListener('touchmove', onDragMove, { passive: true });
    document.addEventListener('touchend', onDragEnd);
  }

  function onDragStart(e) {
    if (!activeCard) return;
    isDragging = true;
    const pageX = e.type.includes('touch') ? e.touches[0].pageX : e.pageX;
    const pageY = e.type.includes('touch') ? e.touches[0].pageY : e.pageY;
    startX = pageX;
    startY = pageY;
    activeCard.style.transition = 'none';
  }

  function onDragMove(e) {
    if (!isDragging || !activeCard) return;
    const pageX = e.type.includes('touch') ? e.touches[0].pageX : e.pageX;
    const pageY = e.type.includes('touch') ? e.touches[0].pageY : e.pageY;

    currentX = pageX - startX;
    currentY = pageY - startY;

    const rotate = currentX * 0.08;
    activeCard.style.transform = `translate3d(${currentX}px, ${currentY}px, 0) rotate(${rotate}deg)`;

    // Update Stamp Opacities
    const likeStamp = activeCard.querySelector('.swipe-stamp.like');
    const dislikeStamp = activeCard.querySelector('.swipe-stamp.dislike');

    if (currentX > 20) {
      if (likeStamp) likeStamp.style.opacity = Math.min(currentX / 100, 1);
      if (dislikeStamp) dislikeStamp.style.opacity = 0;
    } else if (currentX < -20) {
      if (dislikeStamp) dislikeStamp.style.opacity = Math.min(Math.abs(currentX) / 100, 1);
      if (likeStamp) likeStamp.style.opacity = 0;
    } else {
      if (likeStamp) likeStamp.style.opacity = 0;
      if (dislikeStamp) dislikeStamp.style.opacity = 0;
    }
  }

  function onDragEnd() {
    if (!isDragging || !activeCard) return;
    isDragging = false;

    const threshold = 100;
    if (currentX > threshold) {
      swipeCard('like');
    } else if (currentX < -threshold) {
      swipeCard('dislike');
    } else {
      // Snap Back
      activeCard.style.transition = 'transform 0.3s ease';
      activeCard.style.transform = 'translate3d(0, 0, 0) rotate(0deg)';
      const stamps = activeCard.querySelectorAll('.swipe-stamp');
      stamps.forEach(s => s.style.opacity = 0);
    }

    currentX = 0;
    currentY = 0;
  }

  function swipeCard(direction) {
    if (!activeCard) return;
    const targetUserId = activeCard.dataset.userId;
    const targetName = activeCard.dataset.userName;
    const targetAvatar = activeCard.dataset.userAvatar;

    const moveX = direction === 'like' ? 500 : (direction === 'dislike' ? -500 : 0);
    const moveY = direction === 'superlike' ? -600 : 0;
    const rotate = direction === 'like' ? 25 : -25;

    activeCard.style.transition = 'transform 0.4s ease, opacity 0.4s ease';
    activeCard.style.transform = `translate3d(${moveX}px, ${moveY}px, 0) rotate(${rotate}deg)`;
    activeCard.style.opacity = '0';

    // Call Swipe API
    fetch('api/swipe.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ target_id: targetUserId, action: direction })
    })
    .then(res => res.json())
    .then(data => {
      if (data.matched) {
        showMatchModal(targetName, targetAvatar, data.match_id);
      }
    })
    .catch(err => console.error('Swipe API Error:', err));

    setTimeout(() => {
      activeCard.remove();
      initDeck();
    }, 350);
  }

  function showMatchModal(userName, userAvatar, matchId) {
    const modal = document.getElementById('matchModal');
    if (!modal) return;
    document.getElementById('matchedUserName').textContent = userName;
    document.getElementById('matchedUserAvatar').src = userAvatar;
    document.getElementById('matchedChatBtn').href = `chat.php?match_id=${matchId}`;
    modal.classList.add('active');
  }

  // Bind Control Buttons
  document.getElementById('btnDislike')?.addEventListener('click', () => swipeCard('dislike'));
  document.getElementById('btnLike')?.addEventListener('click', () => swipeCard('like'));
  document.getElementById('btnSuperlike')?.addEventListener('click', () => swipeCard('superlike'));
  document.getElementById('closeMatchModalBtn')?.addEventListener('click', () => {
    document.getElementById('matchModal')?.classList.remove('active');
  });

  initDeck();
});
