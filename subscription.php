<?php
// subscription.php - Varsaathi Prime & Subscription Plans (Matching Image 2 Mockup)
require_once __DIR__ . '/config/db.php';
require_login();

$active_tab = 'profile';
$css_version = time();
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<header class="app-header">
  <a href="javascript:history.back()" class="icon-btn" title="Back" style="width: 36px; height: 36px; background: #F8F8FA; border: 1px solid #E5E5EA;">
    <i class="fa-solid fa-arrow-left" style="font-size: 0.95rem;"></i>
  </a>

  <div class="header-title" style="font-size: 1.4rem; font-weight: 300; color: #1C1C1E; letter-spacing: -0.5px; font-family: system-ui, -apple-system, sans-serif;">
    Subscription Plans
  </div>

  <div class="header-actions" style="width: 36px;"></div>
</header>

<main class="app-body" style="padding: 16px 16px 90px 16px; background: #FAF9FC;">

  <div style="text-align: center; margin-bottom: 20px;">
    <span style="font-size: 0.72rem; font-weight: 800; background: #FFF0F4; color: #C31F3A; padding: 4px 12px; border-radius: 12px; border: 1px solid rgba(195,31,58,0.15);">
      🎉 100% FREE - SPECIAL LAUNCH OFFER
    </span>
    <h2 style="font-size: 1.2rem; font-weight: 800; color: #1C1C1E; margin-top: 8px; margin-bottom: 4px;">Select Plan to continue</h2>
    <p style="font-size: 0.8rem; color: #8E8E93;">All premium features are completely free for all Chourasiya Samaj members!</p>
  </div>

  <div style="display: flex; flex-direction: column; gap: 16px;">
    
    <!-- 1. Basic Plan -->
    <div style="background: linear-gradient(135deg, #E02847 0%, #C31F3A 100%); border-radius: 24px; padding: 20px; color: #FFFFFF; position: relative; overflow: hidden; box-shadow: 0 10px 28px rgba(195,31,58,0.3);">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px;">
        <div>
          <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 2px;">Basic Plan</h3>
          <span style="font-size: 0.75rem; opacity: 0.9;">Standard Access</span>
        </div>
        <div style="text-align: right;">
          <div style="font-size: 0.85rem; text-decoration: line-through; opacity: 0.75;">₹499</div>
          <div style="font-size: 1.4rem; font-weight: 900;">FREE <span style="font-size: 0.85rem; font-weight: 700;">₹0</span></div>
        </div>
      </div>

      <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.84rem; font-weight: 600; margin-bottom: 18px;">
        <div style="display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-circle-check"></i> Direct Messaging enabled</div>
        <div style="display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-circle-check"></i> 100 Profile Visit per day</div>
        <div style="display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-circle-check"></i> Access for 30 Days</div>
      </div>

      <button onclick="activateFreePlan('Basic Plan')" style="width: 100%; height: 44px; border-radius: 22px; border: none; background: #FFFFFF; color: #C31F3A; font-weight: 800; font-size: 0.88rem; cursor: pointer; box-shadow: 0 4px 14px rgba(0,0,0,0.15);">
        ACTIVATE FOR FREE
      </button>
    </div>

    <!-- 2. Budget Plan -->
    <div style="background: linear-gradient(135deg, #E02847 0%, #A8162E 100%); border-radius: 24px; padding: 20px; color: #FFFFFF; position: relative; overflow: hidden; box-shadow: 0 10px 28px rgba(195,31,58,0.3);">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px;">
        <div>
          <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 2px;">Budget Plan</h3>
          <span style="font-size: 0.75rem; opacity: 0.9;">Popular Choice</span>
        </div>
        <div style="text-align: right;">
          <div style="font-size: 0.85rem; text-decoration: line-through; opacity: 0.75;">₹999</div>
          <div style="font-size: 1.4rem; font-weight: 900;">FREE <span style="font-size: 0.85rem; font-weight: 700;">₹0</span></div>
        </div>
      </div>

      <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.84rem; font-weight: 600; margin-bottom: 18px;">
        <div style="display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-circle-check"></i> Unlimited Direct Messaging</div>
        <div style="display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-circle-check"></i> Unlimited Profile Visit</div>
        <div style="display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-circle-check"></i> Access for 30 Days</div>
      </div>

      <button onclick="activateFreePlan('Budget Plan')" style="width: 100%; height: 44px; border-radius: 22px; border: none; background: #FFFFFF; color: #C31F3A; font-weight: 800; font-size: 0.88rem; cursor: pointer; box-shadow: 0 4px 14px rgba(0,0,0,0.15);">
        ACTIVATE FOR FREE
      </button>
    </div>

    <!-- 3. Prime Premium Plan -->
    <div style="background: linear-gradient(135deg, #C31F3A 0%, #7E0D1F 100%); border-radius: 24px; padding: 20px; color: #FFFFFF; position: relative; overflow: hidden; box-shadow: 0 12px 32px rgba(195,31,58,0.4); border: 2px solid rgba(255,255,255,0.3);">
      <div style="position: absolute; top: 12px; right: 12px; background: #FFD700; color: #000; font-weight: 900; font-size: 0.65rem; padding: 3px 10px; border-radius: 10px; text-transform: uppercase;">
        PRIME PRO
      </div>

      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px;">
        <div>
          <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 2px; display: flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-crown" style="color: #FFD700;"></i> Prime Plan
          </h3>
          <span style="font-size: 0.75rem; opacity: 0.9;">Ultimate Matrimonial Package</span>
        </div>
        <div style="text-align: right;">
          <div style="font-size: 0.85rem; text-decoration: line-through; opacity: 0.75;">₹1,499</div>
          <div style="font-size: 1.4rem; font-weight: 900; color: #FFD700;">FREE <span style="font-size: 0.85rem; font-weight: 700; color: #FFF;">₹0</span></div>
        </div>
      </div>

      <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.84rem; font-weight: 600; margin-bottom: 18px;">
        <div style="display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-circle-check" style="color: #FFD700;"></i> Unlimited Direct Messaging & Calls</div>
        <div style="display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-circle-check" style="color: #FFD700;"></i> Unlimited Profile Visit</div>
        <div style="display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-circle-check" style="color: #FFD700;"></i> Top Profile Priority Boost</div>
        <div style="display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-circle-check" style="color: #FFD700;"></i> Access for 60 Days</div>
      </div>

      <button onclick="activateFreePlan('Varsaathi Prime Plan')" style="width: 100%; height: 44px; border-radius: 22px; border: none; background: #FFD700; color: #1C1C1E; font-weight: 900; font-size: 0.88rem; cursor: pointer; box-shadow: 0 4px 16px rgba(255,215,0,0.4);">
        ACTIVATE PRIME FOR FREE
      </button>
    </div>

  </div>

</main>

<script>
function activateFreePlan(planName) {
  alert(`👑 ${planName} Activated Successfully!\nAll features are 100% free under Varsaathi Launch Offer.`);
}
</script>

<?php 
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
