<?php
session_start();
if(isset($_SESSION['admin'])){
    header('Location:  https://ipn.yourdomain.com/payments/payments/dashboard');
    die();
}

if(isset($_POST['password'])){
    if(!empty($_POST['password'])){
        if($_POST['password'] == "// your passwd here"){
            $_SESSION['admin'] = 1;
            header('Location:  https://ipn.yourdomain.com/payments/payments/dashboard');
            die();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Invalid password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BTC IPN Panel</title>
</head>
<body>
    <form method="POST">
        <input name="password" type="password" placeholder="Password"><br>
        <button type="submit">Login</button>
    </form>
<?php
if(isset($error)){
    echo '<br>' . $error;
}
?>
</body>
</html>