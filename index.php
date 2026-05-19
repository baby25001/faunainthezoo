<?php
require_once 'includes/auth.php';

if (isLoggedIn()) {
    if (getRole() === 'zookeeper') {
        header('Location: schedule.php');
    } else {
        header('Location: animals.php');
    }
} else {
    header('Location: login.php');
}
exit;
?>