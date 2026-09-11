/**
 * VARSAATHI Dating App - Custom VIP Premium Bottom Sheet Engine
 */

(function () {
  if (typeof window === 'undefined') return;

  window.openPremiumBottomSheet = function () {
    if (document.getElementById('varsaathiPremiumModal')) return;

    const modal = document.createElement('div');
    modal.id = 'varsaathiPremiumModal';
    modal.style.cssText = `
      position: fixed;
      inset: 0;
      z-index: 999999;
      background: rgba(0, 0, 0, 0.72);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      display: flex;
      align-items: flex-end;
      justify-content: center;
      padding: 0;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.35s ease;
    `;

    modal.innerHTML = `
      <div id="premiumModalCard" style="width: 100%; max-width: 440px; background: #FFFFFF; border-radius: 32px 32px 0 0; padding: 24px 20px calc(24px + env(safe-area-inset-bottom, 0px)) 20px; text-align: center; box-shadow: 0 -16px 50px rgba(0,0,0,0.3); transform: translateY(100%); transition: transform 0.38s cubic-bezier(0.175, 0.885, 0.32, 1.1); max-height: 90vh; overflow-y: auto;">
        
        <!-- Drag Pill Handle -->
        <div style="width: 40px; height: 4px; border-radius: 2px; background: #E5E5EA; margin: 0 auto 16px auto;"></div>
        
        <!-- Crown VIP Badge Header -->
        <div style="position: relative; display: inline-block; margin-bottom: 12px;">
          <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #FFD700 0%, #FF9500 50%, #C31F3A 100%); color: #FFF; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; box-shadow: 0 10px 28px rgba(255,165,0,0.45); margin: 0 auto;">
            👑
          </div>
          <span style="position: absolute; bottom: -4px; right: -6px; background: #C31F3A; color: #FFF; font-size: 0.65rem; font-weight: 900; padding: 2px 7px; border-radius: 10px; border: 2px solid #FFF;">VIP</span>
        </div>

        <h2 style="font-weight: 900; font-size: 1.45rem; color: #1C1C1E; margin-bottom: 4px; font-family: 'Outfit', sans-serif; letter-spacing: -0.5px;">
          VARSAATHI GOLD VIP
        </h2>
        <p style="font-size: 0.84rem; color: #8E8E93; font-weight: 600; margin-bottom: 18px;">
          Unlock Unlimited Likes, See Who Liked You & Direct Messages!
        </p>

        <!-- Feature Highlights Grid -->
        <div style="background: #F9F9FC; border-radius: 20px; padding: 14px 16px; margin-bottom: 20px; border: 1px solid #E5E5EA; text-align: left; display: grid; grid-template-columns: 1fr 1fr; gap: 10px 12px;">
          <div style="display: flex; align-items: center; gap: 8px; font-size: 0.78rem; font-weight: 800; color: #1C1C1E;">
            <i class="fa-solid fa-infinity" style="color: #C31F3A; font-size: 0.95rem;"></i> Unlimited Likes
          </div>
          <div style="display: flex; align-items: center; gap: 8px; font-size: 0.78rem; font-weight: 800; color: #1C1C1E;">
            <i class="fa-solid fa-eye" style="color: #AF52DE; font-size: 0.95rem;"></i> See Who Liked You
          </div>
          <div style="display: flex; align-items: center; gap: 8px; font-size: 0.78rem; font-weight: 800; color: #1C1C1E;">
            <i class="fa-solid fa-paper-plane" style="color: #34C759; font-size: 0.95rem;"></i> Direct Messages
          </div>
          <div style="display: flex; align-items: center; gap: 8px; font-size: 0.78rem; font-weight: 800; color: #1C1C1E;">
            <i class="fa-solid fa-bolt" style="color: #FF9500; font-size: 0.95rem;"></i> 5x Profile Boost
          </div>
        </div>

        <!-- Plan Selection Tier Cards -->
        <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 22px;" id="premiumPlansContainer">
          
          <!-- 12 Months (Best Value) -->
          <div class="premium-plan-card active" data-plan="12m" onclick="selectPremiumPlan(this)" style="position: relative; background: #FFF; border: 2px solid #C31F3A; border-radius: 18px; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; box-shadow: 0 4px 14px rgba(195,31,58,0.15); transition: all 0.2s ease;">
            <span style="position: absolute; top: -10px; right: 14px; background: linear-gradient(135deg, #FFD700 0%, #FF9500 100%); color: #000; font-size: 0.65rem; font-weight: 900; padding: 2px 9px; border-radius: 10px; letter-spacing: 0.5px;">🔥 MOST POPULAR • 70% OFF</span>
            <div style="text-align: left;">
              <div style="font-weight: 900; font-size: 0.98rem; color: #1C1C1E;">12 Months Pass</div>
              <div style="font-size: 0.74rem; color: #8E8E93; font-weight: 600;">Billed ₹2,388 yearly</div>
            </div>
            <div style="text-align: right;">
              <div style="font-weight: 900; font-size: 1.15rem; color: #C31F3A;">₹199<span style="font-size:0.75rem; font-weight:600;">/mo</span></div>
            </div>
          </div>

          <!-- 3 Months -->
          <div class="premium-plan-card" data-plan="3m" onclick="selectPremiumPlan(this)" style="position: relative; background: #F8F8FC; border: 2px solid transparent; border-radius: 18px; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: all 0.2s ease;">
            <div style="text-align: left;">
              <div style="font-weight: 900; font-size: 0.98rem; color: #1C1C1E;">3 Months Pass</div>
              <div style="font-size: 0.74rem; color: #8E8E93; font-weight: 600;">Billed ₹1,197 quarterly</div>
            </div>
            <div style="text-align: right;">
              <div style="font-weight: 900; font-size: 1.15rem; color: #1C1C1E;">₹399<span style="font-size:0.75rem; font-weight:600;">/mo</span></div>
            </div>
          </div>

          <!-- 1 Month -->
          <div class="premium-plan-card" data-plan="1m" onclick="selectPremiumPlan(this)" style="position: relative; background: #F8F8FC; border: 2px solid transparent; border-radius: 18px; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: all 0.2s ease;">
            <div style="text-align: left;">
              <div style="font-weight: 900; font-size: 0.98rem; color: #1C1C1E;">1 Month Pass</div>
              <div style="font-size: 0.74rem; color: #8E8E93; font-weight: 600;">Flexible monthly billing</div>
            </div>
            <div style="text-align: right;">
              <div style="font-weight: 900; font-size: 1.15rem; color: #1C1C1E;">₹599<span style="font-size:0.75rem; font-weight:600;">/mo</span></div>
            </div>
          </div>
        </div>

        <!-- Continue CTA Button -->
        <button id="premiumCheckoutBtn" onclick="checkoutPremiumPlan()" class="btn-block btn-primary" style="height: 52px; border-radius: 26px; font-weight: 900; font-size: 1.05rem; width: 100%; margin-bottom: 10px; background: linear-gradient(135deg, #FFD700 0%, #C31F3A 70%, #A8162E 100%); border: none; color: #FFF; box-shadow: 0 8px 24px rgba(195,31,58,0.4); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
          👑 Continue with Varsaathi VIP
        </button>

        <button onclick="closePremiumModal()" style="background: none; border: none; color: #8E8E93; font-weight: 700; font-size: 0.85rem; width: 100%; padding: 8px; cursor: pointer;">
          No Thanks, Stay Free
        </button>
      </div>
    `;

    document.body.appendChild(modal);

    setTimeout(() => {
      modal.style.opacity = '1';
      modal.style.pointerEvents = 'auto';
      const card = document.getElementById('premiumModalCard');
      if (card) card.style.transform = 'translateY(0)';
    }, 100);
  };

  window.selectPremiumPlan = function (cardEl) {
    const cards = document.querySelectorAll('.premium-plan-card');
    cards.forEach(c => {
      c.style.borderColor = 'transparent';
      c.style.background = '#F8F8FC';
      c.style.boxShadow = 'none';
    });

    cardEl.style.borderColor = '#C31F3A';
    cardEl.style.background = '#FFF';
    cardEl.style.boxShadow = '0 4px 14px rgba(195,31,58,0.15)';
  };

  window.checkoutPremiumPlan = function () {
    alert('🎉 Upgrade Successful! Welcome to VARSAATHI Gold VIP. All premium features unlocked!');
    closePremiumModal();
  };

  window.closePremiumModal = function () {
    const modal = document.getElementById('varsaathiPremiumModal');
    if (!modal) return;
    const card = document.getElementById('premiumModalCard');
    if (card) card.style.transform = 'translateY(100%)';
    modal.style.opacity = '0';
    modal.style.pointerEvents = 'none';
    setTimeout(() => modal.remove(), 350);
  };
})();
