<?php
// onboarding-steps.php
require_once __DIR__ . '/config/db.php';

$step = (int)($_GET['step'] ?? 1);
if ($step < 1) $step = 1;
if ($step > 5) $step = 5;

$css_version = time();
$error = '';
$user_id = get_current_user_id();
$current_user = null;

if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :u");
    $stmt->execute([':u' => $user_id]);
    $current_user = $stmt->fetch();
}

// Process Step Forms with Real Database Persistence
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $gender = strtolower($_POST['gender'] ?? 'female');

        if (empty($full_name)) {
            $error = 'Please enter your full name.';
        } elseif (!$user_id && (empty($email) || empty($password))) {
            $error = 'Please provide an email and password.';
        } else {
            if ($user_id) {
                // Update existing logged in user
                $u_stmt = $pdo->prepare("UPDATE users SET full_name = :fn, gender = :gen WHERE id = :u");
                $u_stmt->execute([':fn' => $full_name, ':gen' => $gender, ':u' => $user_id]);
            } else {
                // Check if email already exists
                $chk = $pdo->prepare("SELECT id FROM users WHERE email = :e");
                $chk->execute([':e' => $email]);
                $existing = $chk->fetch();

                if ($existing) {
                    login_user_session($existing['id']);
                    $user_id = $existing['id'];
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $ins = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, gender, avatar_url) VALUES (:fn, :e, :p, :g, :av)");
                    $ins->execute([
                        ':fn' => $full_name,
                        ':e' => $email,
                        ':p' => $hash,
                        ':g' => $gender,
                        ':av' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80'
                    ]);
                    $user_id = $pdo->lastInsertId();
                    login_user_session($user_id);

                    // Insert default user preferences
                    $pref_ins = $pdo->prepare("INSERT INTO user_preferences (user_id, min_age, max_age, max_distance, gender_preference) VALUES (:u, 18, 35, 50, 'everyone')");
                    $pref_ins->execute([':u' => $user_id]);
                }
            }
            header("Location: onboarding-steps.php?step=2");
            exit;
        }

    } elseif ($step === 2) {
        // Sexual Orientation & Looking For
        $orientations = $_POST['orientations'] ?? ['Straight'];
        $looking_for = strtolower($_POST['looking_for'] ?? 'everyone');

        if ($user_id) {
            $u_stmt = $pdo->prepare("UPDATE users SET looking_for = :lf WHERE id = :u");
            $u_stmt->execute([':lf' => $looking_for, ':u' => $user_id]);
        }
        header("Location: onboarding-steps.php?step=3");
        exit;

    } elseif ($step === 3) {
        // Passions & Interests
        $interests = $_POST['interests'] ?? ['Coffee', 'Travel', 'Music'];

        if ($user_id) {
            $u_stmt = $pdo->prepare("UPDATE users SET interests = :interests WHERE id = :u");
            $u_stmt->execute([':interests' => json_encode($interests), ':u' => $user_id]);
        }
        header("Location: onboarding-steps.php?step=4");
        exit;

    } elseif ($step === 4) {
        // Multi-Photo Upload & Avatar Selection
        $uploaded_photos = [];
        $avatar_url = $_POST['preset_avatar'] ?? '';

        // Check if multiple files were uploaded
        if (!empty($_FILES['avatar_files']['name'][0])) {
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $count = count($_FILES['avatar_files']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['avatar_files']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['avatar_files']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                        $new_name = 'photo_' . $user_id . '_' . time() . '_' . $i . '.' . $ext;
                        $target_path = $upload_dir . $new_name;
                        if (move_uploaded_file($_FILES['avatar_files']['tmp_name'][$i], $target_path)) {
                            $uploaded_photos[] = 'uploads/' . $new_name;
                        }
                    }
                }
            }
        }

        if (empty($avatar_url) && !empty($uploaded_photos)) {
            $avatar_url = $uploaded_photos[0];
        }

        if (empty($avatar_url)) {
            $avatar_url = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80';
        }

        if (empty($uploaded_photos)) {
            $uploaded_photos = [$avatar_url];
        }

        if ($user_id) {
            $u_stmt = $pdo->prepare("UPDATE users SET avatar_url = :av, photos = :p WHERE id = :u");
            $u_stmt->execute([':av' => $avatar_url, ':p' => json_encode($uploaded_photos), ':u' => $user_id]);
        }
        header("Location: onboarding-steps.php?step=5");
        exit;

    } elseif ($step === 5) {
        // Match Preferences & Finish
        $max_distance = (int)($_POST['max_distance'] ?? 50);
        $min_age = (int)($_POST['min_age'] ?? 18);
        $max_age = (int)($_POST['max_age'] ?? 35);
        $gender_pref = $_POST['gender_preference'] ?? 'everyone';

        if ($user_id) {
            $pref_stmt = $pdo->prepare("
                INSERT INTO user_preferences (user_id, min_age, max_age, max_distance, gender_preference)
                VALUES (:u, :min_a, :max_a, :max_d, :gp)
                ON DUPLICATE KEY UPDATE min_age = VALUES(min_age), max_age = VALUES(max_age), max_distance = VALUES(max_distance), gender_preference = VALUES(gender_preference)
            ");
            $pref_stmt->execute([
                ':u' => $user_id,
                ':min_a' => $min_age,
                ':max_a' => $max_age,
                ':max_d' => $max_distance,
                ':gp' => $gender_pref
            ]);

            // Broadcast FCM Push Notification for New Profile Registered
            try {
                require_once __DIR__ . '/includes/fcm_helper.php';
                $nu_stmt = $pdo->prepare("SELECT full_name, birthdate, location_city FROM users WHERE id = :u LIMIT 1");
                $nu_stmt->execute([':u' => $user_id]);
                $new_user = $nu_stmt->fetch();
                if ($new_user) {
                    $name = ucwords(strtolower(trim($new_user['full_name'])));
                    $age = calculate_age($new_user['birthdate']);
                    $city = (!empty($new_user['location_city']) && strpos(strtolower($new_user['location_city']), 'san francisco') === false) ? $new_user['location_city'] : 'India';

                    $recip_stmt = $pdo->prepare("SELECT id FROM users WHERE id != :nu AND fcm_token IS NOT NULL AND CHAR_LENGTH(fcm_token) > 10 LIMIT 30");
                    $recip_stmt->execute([':nu' => $user_id]);
                    $recipients = $recip_stmt->fetchAll(PDO::FETCH_COLUMN);

                    foreach ($recipients as $target_id) {
                        send_fcm_notification(
                            $target_id,
                            "New Profile Joined Varsaathi! ✨",
                            "$name ($age yrs, $city) just joined Varsaathi! Tap to view their profile.",
                            [
                                'type' => 'new_profile',
                                'target_id' => $user_id,
                                'url' => "saathi-profile.php?id=$user_id"
                            ]
                        );
                    }
                }
            } catch (Exception $e) {}
        }
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#FFFFFF">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <title>Onboarding Step <?= $step ?> - VARSAATHI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= $css_version ?>">
  <style>
    .step-container {
      display: flex;
      flex-direction: column;
      height: 100%;
      background: #FFF;
      padding: 16px 20px calc(16px + env(safe-area-inset-bottom, 0px)) 20px;
      justify-content: space-between;
      overflow: hidden;
    }
    .step-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
      position: relative;
      flex-shrink: 0;
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
    }
    .progress-bar-wrap {
      width: 150px;
      height: 8px;
      background: #F4F4F6;
      border-radius: 4px;
      overflow: hidden;
      position: relative;
    }
    .progress-bar-fill {
      height: 100%;
      background: linear-gradient(90deg, #FF2D55 0%, #AF52DE 100%);
      border-radius: 4px;
      transition: width 0.3s ease;
    }
    .progress-badge {
      position: absolute;
      top: -20px;
      left: 50%;
      transform: translateX(-50%);
      background: var(--ios-pink);
      color: #FFF;
      font-size: 0.65rem;
      font-weight: 800;
      padding: 2px 10px;
      border-radius: 10px;
    }
    .step-title {
      font-size: 1.75rem;
      font-weight: 800;
      color: var(--ios-text);
      font-family: 'Plus Jakarta Sans', sans-serif;
      margin-bottom: 6px;
      line-height: 1.2;
    }
    .step-sub {
      font-size: 0.85rem;
      color: #71717A;
      margin-bottom: 16px;
    }
    .option-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
      flex: 1;
      min-height: 0;
      overflow-y: auto;
      margin-bottom: 12px;
      padding-right: 2px;
      -webkit-overflow-scrolling: touch;
    }
    .option-card {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      background: #F8F8FC;
      border-radius: 18px;
      border: 2px solid transparent;
      cursor: pointer;
      font-weight: 700;
      font-size: 0.95rem;
      color: var(--ios-text);
      transition: all 0.2s ease;
      user-select: none;
    }
    .option-card.active {
      background: #FFF;
      border-color: var(--ios-pink);
      box-shadow: 0 4px 16px rgba(255, 45, 85, 0.1);
    }
    .option-radio {
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
    .option-card.active .option-radio {
      background: var(--ios-pink);
      border-color: var(--ios-pink);
    }
    .btn-continue {
      width: 100%;
      height: 52px;
      border-radius: 26px;
      background: linear-gradient(135deg, #FF2D55 0%, #E024BE 100%);
      color: #FFF;
      font-size: 1.05rem;
      font-weight: 800;
      border: none;
      cursor: pointer;
      box-shadow: 0 6px 20px rgba(255, 45, 85, 0.35);
      flex-shrink: 0;
      margin-top: 8px;
    }
  </style>
</head>
<body>
  <div class="app-container">
    <div class="step-container">
      <form action="onboarding-steps.php?step=<?= $step ?>" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; height: 100%;">
        
        <!-- Top Navigation & Progress Bar -->
        <div class="step-top">
          <a href="<?= ($step > 1) ? 'onboarding-steps.php?step=' . ($step - 1) : 'auth-choice.php' ?>" class="back-btn">
            <i class="fa-solid fa-chevron-left"></i>
          </a>
          <div style="position: relative;">
            <div class="progress-badge"><?= $step ?>/5</div>
            <div class="progress-bar-wrap">
              <div class="progress-bar-fill" style="width: <?= ($step / 5) * 100 ?>%;"></div>
            </div>
          </div>
          <div style="width: 36px;"></div>
        </div>

        <?php if (!empty($error)): ?>
          <div style="background: #FFE5E5; color: #FF2D55; padding: 10px 14px; border-radius: 12px; font-size: 0.84rem; font-weight: 700; margin-bottom: 14px; text-align: center;">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
          <!-- Step 1: Account Setup -->
          <div>
            <h2 class="step-title">Create Your Varsaathi Profile</h2>
            <p class="step-sub">Enter your details to register in the Varsaathi database.</p>
          </div>
          <div class="option-list">
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input type="text" name="full_name" class="form-control" placeholder="e.g. Jessica Alba" required value="<?= htmlspecialchars($current_user['full_name'] ?? '') ?>">
            </div>

            <?php if (!$user_id): ?>
              <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="your.email@domain.com" required>
              </div>

              <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
              </div>
            <?php endif; ?>

            <div class="form-group">
              <label class="form-label">I Identify As</label>
              <div style="display: flex; gap: 10px;">
                <label class="option-card active" style="flex:1; justify-content:center;">
                  <input type="radio" name="gender" value="female" checked style="display:none;" onchange="updateGenderCards()"> Female
                </label>
                <label class="option-card" style="flex:1; justify-content:center;">
                  <input type="radio" name="gender" value="male" style="display:none;" onchange="updateGenderCards()"> Male
                </label>
              </div>
            </div>
          </div>

        <?php elseif ($step === 2): ?>
          <!-- Step 2: Sexual Orientation & Preferences (Matching Image 5) -->
          <div>
            <h2 class="step-title">My Sexual Orientation Is</h2>
            <p class="step-sub">Select your orientation to save in your profile</p>
          </div>
          <div class="option-list">
            <?php
            $orientations = ["Straight", "Gay", "Lesbian", "Bisexual", "Asexual", "Demisexual", "Pansexual"];
            foreach ($orientations as $index => $opt):
              $isSel = ($index === 0);
            ?>
              <label class="option-card <?= $isSel ? 'active' : '' ?>">
                <input type="checkbox" name="orientations[]" value="<?= $opt ?>" <?= $isSel ? 'checked' : '' ?> style="display:none;" onchange="this.closest('.option-card').classList.toggle('active', this.checked)">
                <span><?= $opt ?></span>
                <div class="option-radio"><i class="fa-solid fa-check"></i></div>
              </label>
            <?php endforeach; ?>

            <div style="margin-top: 14px;">
              <label class="form-label">Looking To Meet</label>
              <select name="looking_for" class="form-control">
                <option value="male">Men</option>
                <option value="female">Women</option>
                <option value="everyone" selected>Everyone</option>
              </select>
            </div>
          </div>

        <?php elseif ($step === 3): ?>
          <!-- Step 3: Passions & Interests -->
          <div>
            <h2 class="step-title">What Are Your Passions?</h2>
            <p class="step-sub">Select interests to save to the database.</p>
          </div>
          <div class="option-list">
            <?php
            $passions = ["Coffee Lover", "World Traveler", "Live Music", "Fitness & Gym", "Foodie", "Art & Design", "Photography", "Movies & Series"];
            foreach ($passions as $index => $opt):
              $isSel = ($index < 3);
            ?>
              <label class="option-card <?= $isSel ? 'active' : '' ?>">
                <input type="checkbox" name="interests[]" value="<?= $opt ?>" <?= $isSel ? 'checked' : '' ?> style="display:none;" onchange="this.closest('.option-card').classList.toggle('active', this.checked)">
                <span><?= $opt ?></span>
                <div class="option-radio"><i class="fa-solid fa-check"></i></div>
              </label>
            <?php endforeach; ?>
          </div>

        <?php elseif ($step === 4): ?>
          <!-- Step 4: Profile Photos -->
          <div>
            <h2 class="step-title">Upload Profile Photo</h2>
            <p class="step-sub">Choose a photo or upload your image file.</p>
          </div>
          <div class="option-list" style="align-items: center; justify-content: center; gap: 16px;">
            
            <label style="
              width: 160px;
              height: 160px;
              border-radius: 50%;
              border: 4px dashed var(--ios-pink);
              display: flex;
              flex-direction: column;
              align-items: center;
              justify-content: center;
              background: #FDF0F6;
              color: var(--ios-pink);
              cursor: pointer;
              overflow: hidden;
              position: relative;
            ">
              <input type="file" name="avatar_files[]" multiple accept="image/*" style="display:none;" onchange="previewFile(this)">
              <img id="avatarPreview" src="<?= htmlspecialchars($current_user['avatar_url'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80') ?>" style="width:100%; height:100%; object-fit:cover; display:block;">
              <div style="position:absolute; bottom:6px; background:rgba(0,0,0,0.6); color:#FFF; font-size:0.68rem; padding:2px 8px; border-radius:10px; font-weight:700;">Tap to Change</div>
            </label>

            <div style="width: 100%;">
              <label class="form-label" style="text-align:center;">Or Select Preset Avatar</label>
              <div style="display: flex; gap: 10px; justify-content: center;">
                <?php
                $presets = [
                  "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80",
                  "https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=600&q=80",
                  "https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=600&q=80",
                  "https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=600&q=80"
                ];
                foreach ($presets as $pUrl):
                ?>
                  <img src="<?= $pUrl ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; cursor: pointer; border: 2px solid transparent;" onclick="selectPreset('<?= $pUrl ?>', this)">
                <?php endforeach; ?>
              </div>
              <input type="hidden" name="preset_avatar" id="presetAvatarInput" value="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80">
            </div>

          </div>

        <?php else: ?>
          <!-- Step 5: Distance & Finish -->
          <div>
            <h2 class="step-title">Set Match Preferences</h2>
            <p class="step-sub">Save distance & age settings to database.</p>
          </div>
          <div class="option-list">
            <div class="form-group">
              <label class="form-label">Maximum Distance (<span id="distVal">50</span> km)</label>
              <input type="range" name="max_distance" class="form-control" min="1" max="100" value="50" style="height: 10px; padding: 0;" oninput="document.getElementById('distVal').textContent = this.value">
            </div>
            <div class="form-group">
              <label class="form-label">Minimum Age</label>
              <input type="number" name="min_age" class="form-control" value="18" min="18" max="99">
            </div>
            <div class="form-group">
              <label class="form-label">Maximum Age</label>
              <input type="number" name="max_age" class="form-control" value="35" min="18" max="99">
            </div>
            <div class="form-group">
              <label class="form-label">Match Gender Preference</label>
              <select name="gender_preference" class="form-control">
                <option value="everyone" selected>Everyone</option>
                <option value="female">Women</option>
                <option value="male">Men</option>
              </select>
            </div>
          </div>
        <?php endif; ?>

        <!-- Bottom Action Button -->
        <button type="submit" class="btn-continue">
          <?= ($step === 5) ? 'Save & Explore Varsaathi' : 'Continue' ?>
        </button>

      </form>
    </div>
  </div>

  <script>
    function updateGenderCards() {
      document.querySelectorAll('input[name="gender"]').forEach(r => {
        r.closest('.option-card').classList.toggle('active', r.checked);
      });
    }

    function previewFile(input) {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('avatarPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
      }
    }

    function selectPreset(url, imgElement) {
      document.getElementById('avatarPreview').src = url;
      document.getElementById('presetAvatarInput').value = url;
    }
  </script>
</body>
</html>
