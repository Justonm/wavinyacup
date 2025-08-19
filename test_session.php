<?php
session_start();

if (!isset($_SESSION['test_var'])) {
    $_SESSION['test_var'] = 'hello';
    echo 'Session variable set. Refresh the page.';
} else {
    echo 'Session variable found! Value: ' . $_SESSION['test_var'];
}
?>