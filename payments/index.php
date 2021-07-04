<?php
session_start();
if(!isset($_SESSION['admin'])){
    header('Location: https://ipn.yourdomain.com/payments/login.php');
    die();
} else {
    header('Location: https://ipn.youredomain.com/payments/dashboard');
    die();
}
?>