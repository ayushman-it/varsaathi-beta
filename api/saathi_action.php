<?php
// api/saathi_action.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/flags.php';

if (!is_logged_in()) {
    json_response(['error' => 'Unauthorized'], 401);
}

$user_id = get_current_user_id();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? '';

$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :u");
$user_stmt->execute([':u' => $user_id]);
$user = $user_stmt->fetch();
$saathi = get_saathi_profile($user_id);

switch ($action) {
    case 'save_profile_step':
        $step = (int)($input['step'] ?? 1);

        if ($step === 1) {
            // Update birthdate in users table if provided
            if (!empty($input['birthdate'])) {
                $u_dob_stmt = $pdo->prepare("UPDATE users SET birthdate = :dob WHERE id = :u");
                $u_dob_stmt->execute([':dob' => trim($input['birthdate']), ':u' => $user_id]);
            }

            // Basic & Physical Details
            $stmt = $pdo->prepare("UPDATE saathi_profiles SET headline = :h, created_by = :cb, marital_status = :ms, have_children = :hc, height_cm = :ht, weight_kg = :wt, body_type = :bt, complexion = :cx, mother_tongue = :mt, languages_spoken = :ls, state = :st, country = :cn WHERE user_id = :u");
            $stmt->execute([
                ':h' => trim($input['headline'] ?? ''),
                ':cb' => trim($input['created_by'] ?? 'Self'),
                ':ms' => trim($input['marital_status'] ?? 'Never Married'),
                ':hc' => trim($input['have_children'] ?? 'No'),
                ':ht' => (int)($input['height_cm'] ?? 165),
                ':wt' => !empty($input['weight_kg']) ? (int)$input['weight_kg'] : null,
                ':bt' => trim($input['body_type'] ?? 'Average'),
                ':cx' => trim($input['complexion'] ?? 'Fair'),
                ':mt' => trim($input['mother_tongue'] ?? 'Hindi'),
                ':ls' => trim($input['languages_spoken'] ?? 'Hindi, English'),
                ':st' => trim($input['state'] ?? 'Chhattisgarh'),
                ':cn' => trim($input['country'] ?? 'India'),
                ':u' => $user_id
            ]);
        } elseif ($step === 2) {
            // About Me & Bio
            $bio = trim($input['bio'] ?? '');
            $user_upd = $pdo->prepare("UPDATE users SET bio = :b WHERE id = :u");
            $user_upd->execute([':b' => $bio, ':u' => $user_id]);
        } elseif ($step === 3) {
            // Education & Career
            $stmt = $pdo->prepare("UPDATE saathi_profiles SET highest_qualification = :hq, degree = :deg, specialization = :sp, college = :col, occupation_type = :ot, company_name = :cn, annual_income = :inc WHERE user_id = :u");
            $stmt->execute([
                ':hq' => trim($input['highest_qualification'] ?? 'Bachelor\'s Degree'),
                ':deg' => trim($input['degree'] ?? ''),
                ':sp' => trim($input['specialization'] ?? ''),
                ':col' => trim($input['college'] ?? ''),
                ':ot' => trim($input['occupation_type'] ?? 'Private Job'),
                ':cn' => trim($input['company_name'] ?? ''),
                ':inc' => trim($input['annual_income'] ?? 'Prefer not to say'),
                ':u' => $user_id
            ]);
            if (!empty($input['occupation'])) {
                $user_occ = $pdo->prepare("UPDATE users SET occupation = :occ WHERE id = :u");
                $user_occ->execute([':occ' => trim($input['occupation']), ':u' => $user_id]);
            }
        } elseif ($step === 4) {
            // Religion, Community & Kundli
            $stmt = $pdo->prepare("UPDATE saathi_profiles SET religion = :rel, caste_community = :comm, sub_caste = :sub, gotra = :gotra, manglik_status = :mngl, rashi = :rashi, nakshatra = :nak, birth_time = :bt, birth_place = :bp WHERE user_id = :u");
            $stmt->execute([
                ':rel' => trim($input['religion'] ?? ''),
                ':comm' => trim($input['caste_community'] ?? ''),
                ':sub' => trim($input['sub_caste'] ?? ''),
                ':gotra' => trim($input['gotra'] ?? ''),
                ':mngl' => trim($input['manglik_status'] ?? 'Prefer not to say'),
                ':rashi' => trim($input['rashi'] ?? ''),
                ':nak' => trim($input['nakshatra'] ?? ''),
                ':bt' => trim($input['birth_time'] ?? ''),
                ':bp' => trim($input['birth_place'] ?? ''),
                ':u' => $user_id
            ]);
        } elseif ($step === 5) {
            // Family & Lifestyle
            $stmt = $pdo->prepare("UPDATE saathi_profiles SET family_type = :ft, family_values = :fv, family_status = :fs, family_location = :fl, father_occupation = :fo, mother_occupation = :mo, brothers_count = :bc, brothers_married = :bm, sisters_count = :sc, sisters_married = :sm, own_house = :oh, own_car = :oc, diet = :diet, smoking = :smk, drinking = :drk, fitness = :fit WHERE user_id = :u");
            $stmt->execute([
                ':ft' => trim($input['family_type'] ?? 'Nuclear Family'),
                ':fv' => trim($input['family_values'] ?? 'Moderate'),
                ':fs' => trim($input['family_status'] ?? 'Middle Class'),
                ':fl' => trim($input['family_location'] ?? ''),
                ':fo' => trim($input['father_occupation'] ?? ''),
                ':mo' => trim($input['mother_occupation'] ?? ''),
                ':bc' => (int)($input['brothers_count'] ?? 0),
                ':bm' => (int)($input['brothers_married'] ?? 0),
                ':sc' => (int)($input['sisters_count'] ?? 0),
                ':sm' => (int)($input['sisters_married'] ?? 0),
                ':oh' => trim($input['own_house'] ?? 'No'),
                ':oc' => trim($input['own_car'] ?? 'No'),
                ':diet' => trim($input['diet'] ?? 'Vegetarian'),
                ':smk' => trim($input['smoking'] ?? 'No'),
                ':drk' => trim($input['drinking'] ?? 'No'),
                ':fit' => trim($input['fitness'] ?? 'Occasionally'),
                ':u' => $user_id
            ]);
        } elseif ($step === 6) {
            // Partner Expectations
            $stmt = $pdo->prepare("UPDATE saathi_profiles SET partner_min_age = :pmin, partner_max_age = :pmax, partner_marital_status = :pms, partner_religion = :prel, partner_community = :pcomm, partner_education = :ped, partner_occupation = :pocc, partner_location = :ploc, marriage_timeline = :mt, partner_expectations = :exp WHERE user_id = :u");
            $stmt->execute([
                ':pmin' => (int)($input['partner_min_age'] ?? 18),
                ':pmax' => (int)($input['partner_max_age'] ?? 45),
                ':pms' => trim($input['partner_marital_status'] ?? 'Any'),
                ':prel' => trim($input['partner_religion'] ?? 'Any'),
                ':pcomm' => trim($input['partner_community'] ?? 'Any'),
                ':ped' => trim($input['partner_education'] ?? 'Any'),
                ':pocc' => trim($input['partner_occupation'] ?? 'Any'),
                ':ploc' => trim($input['partner_location'] ?? 'Any'),
                ':mt' => trim($input['marriage_timeline'] ?? 'Within 1 Year'),
                ':exp' => trim($input['partner_expectations'] ?? ''),
                ':u' => $user_id
            ]);
        }

        // Recalculate completion percentage & auto-publish
        $refreshed_sp = get_saathi_profile($user_id);
        $refreshed_u = $pdo->query("SELECT * FROM users WHERE id = {$user_id}")->fetch();
        $new_pct = calculate_saathi_completion($refreshed_sp, $refreshed_u);

        $upd_pct = $pdo->prepare("UPDATE saathi_profiles SET completion_pct = :p, status = 'published', is_published = 1 WHERE user_id = :u");
        $upd_pct->execute([':p' => $new_pct, ':u' => $user_id]);

        json_response([
            'success' => true,
            'message' => 'Step saved successfully',
            'completion_pct' => $new_pct
        ]);
        break;

    case 'update_privacy':
        $privacy = [
            'show_income' => !empty($input['show_income']),
            'show_religion' => !empty($input['show_religion']),
            'show_community' => !empty($input['show_community']),
            'show_kundli' => !empty($input['show_kundli']),
            'show_family' => !empty($input['show_family'])
        ];
        $stmt = $pdo->prepare("UPDATE saathi_profiles SET privacy_json = :pj WHERE user_id = :u");
        $stmt->execute([':pj' => json_encode($privacy), ':u' => $user_id]);

        json_response(['success' => true, 'message' => 'Privacy settings updated']);
        break;

    case 'toggle_pause':
        $new_pause = !empty($input['is_paused']) ? 1 : 0;
        $status = $new_pause ? 'paused' : 'published';
        $stmt = $pdo->prepare("UPDATE saathi_profiles SET is_paused = :p, status = :s WHERE user_id = :u");
        $stmt->execute([':p' => $new_pause, ':s' => $status, ':u' => $user_id]);

        json_response(['success' => true, 'is_paused' => (bool)$new_pause, 'message' => $new_pause ? 'Saathi Profile Paused' : 'Saathi Profile Resumed']);
        break;

    case 'send_interest':
    case 'favorite':
    case 'like':
        $target_id = (int)($input['target_id'] ?? 0);
        if ($target_id <= 0 || $target_id === $user_id) {
            json_response(['error' => 'Invalid target user'], 400);
        }

        // Save or update swipe like in DB
        $swipe_stmt = $pdo->prepare("INSERT INTO swipes (swiper_id, target_id, swipe_type) VALUES (:s, :t, 'like') ON DUPLICATE KEY UPDATE swipe_type = 'like'");
        $swipe_stmt->execute([':s' => $user_id, ':t' => $target_id]);

        // Check if mutual like exists
        $check_stmt = $pdo->prepare("SELECT id FROM swipes WHERE swiper_id = :t AND target_id = :s AND swipe_type = 'like'");
        $check_stmt->execute([':t' => $target_id, ':s' => $user_id]);

        $match_id = null;
        $is_mutual = (bool)$check_stmt->fetch();
        $initial_status = $is_mutual ? 'accepted' : 'pending';

        $match_stmt = $pdo->prepare("INSERT INTO matches (user1_id, user2_id, status, requested_by) VALUES (LEAST(:u1, :u2), GREATEST(:u1, :u2), :st, :req) ON DUPLICATE KEY UPDATE status = IF(status = 'accepted', 'accepted', VALUES(status))");
        $match_stmt->execute([':u1' => $user_id, ':u2' => $target_id, ':st' => $initial_status, ':req' => $user_id]);

        $m_id_stmt = $pdo->prepare("SELECT id FROM matches WHERE (user1_id = :u1 AND user2_id = :u2) OR (user1_id = :u2 AND user2_id = :u1)");
        $m_id_stmt->execute([':u1' => $user_id, ':u2' => $target_id]);
        $match_id = (int)$m_id_stmt->fetchColumn();

        // Send FCM Push Notification to target user
        try {
            require_once __DIR__ . '/../includes/fcm_helper.php';
            $sender_name = $user['full_name'] ?? 'Someone';
            $notif_title = $is_mutual ? "It's a Match! 💕" : "New Matrimonial Interest! 💕";
            $notif_body = $is_mutual 
                ? "$sender_name matched with you! Tap to start chatting and calling." 
                : "$sender_name expressed interest in your matrimonial profile! Tap to view.";

            send_fcm_notification(
                $target_id,
                $notif_title,
                $notif_body,
                [
                    'type' => $is_mutual ? 'match' : 'like',
                    'match_id' => $match_id,
                    'url' => $is_mutual ? "chat.php?match_id=$match_id" : "matches.php"
                ]
            );
        } catch (Exception $fcm_err) {}

        json_response([
            'success' => true,
            'is_match' => $is_mutual,
            'match_id' => $match_id,
            'message' => $is_mutual ? 'It\'s a Match! You can now chat and video call.' : 'Matrimonial Interest sent successfully!'
        ]);
        break;

    case 'pass':
    case 'dislike':
        $target_id = (int)($input['target_id'] ?? 0);
        if ($target_id > 0 && $target_id !== $user_id) {
            $swipe_stmt = $pdo->prepare("INSERT INTO swipes (swiper_id, target_id, swipe_type) VALUES (:s, :t, 'dislike') ON DUPLICATE KEY UPDATE swipe_type = 'dislike'");
            $swipe_stmt->execute([':s' => $user_id, ':t' => $target_id]);
        }
        json_response(['success' => true, 'message' => 'Passed candidate']);
        break;

    case 'accept_request':
        $match_id = (int)($input['match_id'] ?? 0);
        if ($match_id > 0) {
            $stmt = $pdo->prepare("UPDATE matches SET status = 'accepted' WHERE id = :m AND (user1_id = :u OR user2_id = :u)");
            $stmt->execute([':m' => $match_id, ':u' => $user_id]);
            json_response(['success' => true, 'message' => 'Connection request accepted! You can now start chatting.']);
        }
        json_response(['error' => 'Invalid match ID'], 400);
        break;

    case 'reject_request':
        $match_id = (int)($input['match_id'] ?? 0);
        if ($match_id > 0) {
            $stmt = $pdo->prepare("UPDATE matches SET status = 'rejected' WHERE id = :m AND (user1_id = :u OR user2_id = :u)");
            $stmt->execute([':m' => $match_id, ':u' => $user_id]);
            json_response(['success' => true, 'message' => 'Connection request declined.']);
        }
        json_response(['error' => 'Invalid match ID'], 400);
        break;

    default:
        json_response(['error' => 'Invalid action'], 400);
}
