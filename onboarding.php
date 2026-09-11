<?php
// onboarding.php
require_once __DIR__ . '/config/db.php';
require_login();

$user_id = get_current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $birthdate = $_POST['birthdate'] ?? '2000-01-01';
    $occupation = trim($_POST['occupation'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $interests_arr = isset($_POST['interests']) ? $_POST['interests'] : ["Coffee", "Travel"];

    $stmt = $pdo->prepare("
        UPDATE users
        SET birthdate = :dob, occupation = :occ, bio = :bio, interests = :interests
        WHERE id = :u
    ");
    $stmt->execute([
        ':dob' => $birthdate,
        ':occ' => $occupation,
        ':bio' => $bio,
        ':interests' => json_encode($interests_arr),
        ':u' => $user_id
    ]);

    header("Location: index.php");
    exit;
}
$css_version = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#FFFFFF">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <title>Setup Profile - VARSAATHI</title>
  <!-- Google Fonts: Plus Jakarta Sans & Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
  <!-- FontAwesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= $css_version ?>">
</head>
<body>
  <div class="app-container">

    <div class="auth-container" style="overflow-y: auto;">
      <div style="text-align: center; margin-bottom: 20px;">
        <span style="background: rgba(255,45,85,0.1); color: var(--ios-pink); font-size: 0.75rem; font-weight: 800; padding: 4px 12px; border-radius: 20px;">STEP 2 OF 2</span>
        <h2 style="font-weight: 900; margin-top: 8px; color: var(--ios-text);">Complete Your Profile</h2>
        <p style="color: var(--ios-muted); font-size: 0.82rem; font-weight: 600;">Help others get to know you better</p>
      </div>

      <form action="onboarding.php" method="POST">
        <div class="form-group">
          <label class="form-label">Birthdate</label>
          <input type="date" name="birthdate" class="form-control" value="2000-01-01" required>
        </div>

        <div class="form-group">
          <label class="form-label">Occupation / Profession</label>
          <input type="text" name="occupation" class="form-control" placeholder="e.g. Graphic Designer, Student">
        </div>

        <div class="form-group">
          <label class="form-label">About Me (Bio)</label>
          <textarea name="bio" class="form-control" style="height: 90px; padding: 12px;" placeholder="Share your hobbies, favorite music, or ideal weekend date..."></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Select Interests</label>
          <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <?php
            $sample_tags = ["Music", "Coffee", "Travel", "Fitness", "Coding", "UI Design", "Yoga", "Movies", "Foodie", "Art", "Hiking", "Photography"];
            foreach ($sample_tags as $tag):
            ?>
              <label style="cursor: pointer;">
                <input type="checkbox" name="interests[]" value="<?= $tag ?>" style="display: none;" onchange="this.nextElementSibling.classList.toggle('active', this.checked)">
                <span class="tag-chip" style="display: inline-block; color: var(--ios-text); background: #FFF; border: 1px solid var(--border-subtle); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; transition: all 0.2s ease;">
                  <?= $tag ?>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <button type="submit" class="btn-block btn-primary" style="margin-top: 16px;">Finish & Start Swiping</button>
      </form>
    </div>
  </div>

  <style>
    .tag-chip.active {
      background: var(--ios-gradient) !important;
      color: #FFF !important;
      border-color: transparent !important;
    }
  </style>
</body>
</html>
