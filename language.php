<?php
// language.php
require_once __DIR__ . '/config/db.php';
$css_version = filemtime(__DIR__ . '/assets/css/style.css');

$selected_lang = $_COOKIE['app_lang'] ?? 'English (USA)';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lang = $_POST['language'] ?? 'English (USA)';
    setcookie('app_lang', $lang, time() + (86400 * 30), "/");
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Select Language - VARSAATHI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= $css_version ?>">
  <style>
    .lang-container {
      display: flex;
      flex-direction: column;
      height: 100%;
      background: #FFF;
      padding: 20px;
    }
    .lang-header {
      margin-bottom: 20px;
    }
    .back-btn {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: #F4F4F5;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--ios-text);
      text-decoration: none;
      font-size: 1rem;
      margin-bottom: 16px;
    }
    .lang-title {
      font-size: 1.65rem;
      font-weight: 800;
      color: var(--ios-text);
      font-family: 'Plus Jakarta Sans', sans-serif;
      margin-bottom: 6px;
    }
    .lang-sub {
      font-size: 0.84rem;
      color: #71717A;
      line-height: 1.4;
    }
    .lang-list {
      flex: 1;
      overflow-y: auto;
      margin-bottom: 16px;
      padding-right: 4px;
    }
    .lang-card {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 16px;
      background: #F8F8FC;
      border-radius: 16px;
      border: 2px solid transparent;
      margin-bottom: 10px;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .lang-card.active {
      background: #FFF;
      border-color: var(--ios-pink);
      box-shadow: 0 4px 14px rgba(255, 45, 85, 0.1);
    }
    .lang-card-left {
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 700;
      font-size: 0.92rem;
      color: var(--ios-text);
    }
    .lang-flag {
      font-size: 1.35rem;
    }
    .lang-radio {
      width: 22px;
      height: 22px;
      border-radius: 50%;
      border: 2px solid #D4D4D8;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #FFF;
      font-size: 0.68rem;
    }
    .lang-card.active .lang-radio {
      background: var(--ios-pink);
      border-color: var(--ios-pink);
    }
    .lang-controls {
      display: flex;
      gap: 12px;
    }
    .btn-lang-skip {
      flex: 1;
      height: 48px;
      border-radius: 24px;
      background: #FDF0F6;
      color: var(--ios-pink);
      border: none;
      font-size: 0.95rem;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .btn-lang-continue {
      flex: 1;
      height: 48px;
      border-radius: 24px;
      background: var(--ios-gradient);
      color: #FFF;
      border: none;
      font-size: 0.95rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: var(--ios-glow);
    }
  </style>
</head>
<body>
  <div class="app-container">
    <div class="lang-container">
      <form action="language.php" method="POST" style="display: flex; flex-direction: column; height: 100%;">
        <div class="lang-header">
          <a href="javascript:history.back()" class="back-btn"><i class="fa-solid fa-chevron-left"></i></a>
          <h2 class="lang-title">Select Language 🇪🇺</h2>
          <p class="lang-sub">Please select your preferred language to facilitate communication.</p>
        </div>

        <div class="lang-list">
          <?php
          $languages = [
            ["name" => "English (USA)", "flag" => "🇺🇸"],
            ["name" => "Chinese (China)", "flag" => "🇨🇳"],
            ["name" => "Hindi (India)", "flag" => "🇮🇳"],
            ["name" => "Portuguese (Portugal)", "flag" => "🇵🇹"],
            ["name" => "Spanish (Spain)", "flag" => "🇪🇸"],
            ["name" => "Arabic (UAE)", "flag" => "🇦🇪"],
            ["name" => "French (France)", "flag" => "🇫🇷"],
            ["name" => "Russian (Russia)", "flag" => "🇷🇺"]
          ];

          foreach ($languages as $item):
            $isActive = ($item['name'] === $selected_lang);
          ?>
            <label class="lang-card <?= $isActive ? 'active' : '' ?>" onclick="selectLang(this)">
              <input type="radio" name="language" value="<?= $item['name'] ?>" <?= $isActive ? 'checked' : '' ?> style="display:none;">
              <div class="lang-card-left">
                <span class="lang-flag"><?= $item['flag'] ?></span>
                <span><?= $item['name'] ?></span>
              </div>
              <div class="lang-radio">
                <i class="fa-solid fa-check"></i>
              </div>
            </label>
          <?php endforeach; ?>
        </div>

        <div class="lang-controls">
          <a href="index.php" class="btn-lang-skip">Skip</a>
          <button type="submit" class="btn-lang-continue">Continue</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function selectLang(element) {
      document.querySelectorAll('.lang-card').forEach(c => c.classList.remove('active'));
      element.classList.add('active');
    }
  </script>
</body>
</html>
