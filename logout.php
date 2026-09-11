<?php
// logout.php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = @session_get_cookie_params();
    if (is_array($params)) {
        @setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
}

unset($_COOKIE['varsaathi_remember']);
@setcookie('varsaathi_remember', '', time() - 36000, '/');

@session_unset();
if (session_status() === PHP_SESSION_ACTIVE) {
    @session_destroy();
}

header("Location: login.php");
exit;
