<?php
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }

  $timeout = 15 * 60;

  if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: profile.php");
    exit;
  }

  $_SESSION['last_activity'] = time();
?>
