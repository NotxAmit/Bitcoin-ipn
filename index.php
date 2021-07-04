<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
header("Access-Control-Allow-Origin: *");
if(isset($_GET['price'], $_GET['steamid']) AND is_numeric($_GET['price']) AND is_numeric($_GET['steamid']) AND $_GET['price'] >= 100){
    include('inc/conn.php');
    $usd = $_GET['price'] / 100;
    $btc = file_get_contents("https://blockchain.info/tobtc?currency=USD&value=" . $usd);
    $steamid = $_GET['steamid'];
    $gen = exec('python3 gen.py'); 
    $public_address = explode("|", $gen)[0];
    $private_address = explode("|", $gen)[1];
    $timestamp = time();

    $insert = $conn->prepare('INSERT INTO invoices (steamid, public_key, private_key, amount, credits, paid, timestamp) VALUES ((:steamid), (:public), (:private), (:amount), (:credits), "0", (:time))');
    $insert->bindParam(':steamid', $steamid);
    $insert->bindParam(':public', $public_address);
    $insert->bindParam(':private', $private_address);
    $insert->bindParam(':amount', $btc);
    $insert->bindParam(':credits', $_GET['price']);
    $insert->bindParam(':time', $timestamp);
    $insert->execute();

    $id = $conn->lastInsertId();
    header('Location:  https://ipn.yourdomain.com/?i=' . $id);
    die();
} elseif(isset($_GET['i']) AND is_numeric($_GET['i'])) {
    include('inc/conn.php');
    $invoice = $conn->prepare('SELECT * FROM invoices WHERE id = (:id)');
    $invoice->bindParam(':id', $_GET['i']);
    $invoice->execute();
    $invoice_output= $invoice->fetch();

    if(isset($_GET['cancel'])){
        $cancel = $conn->prepare('UPDATE invoices SET paid = "2" WHERE id = (:id)');
        $cancel->bindParam(':id', $_GET['i']);
        $cancel->execute();
        header('Location: https://yourdomainx.com/user/deposit');
        die();
    }

    if(isset($_GET['check'])){
        $get = file_get_contents("https://blockchain.info/q/addressbalance/" . $invoice_output['public_key'] . "?confirmations=1");
        if($invoice_output['amount'] == number_format(($get)*(pow(10, -8)), 8, '.', '')){
            if($invoice_output['paid'] == 0){
                $update_invoice = $conn->prepare('UPDATE invoices SET paid = "1" WHERE id = (:id)');
                $update_invoice->bindParam(':id', $_GET['i']);
                $update_invoice->execute();

                $add_balance = $conn->prepare('INSERT INTO `wallet_change` (user, `change`, reason, transaction_date) VALUES ((:user), (:change), "DEPOSIT", NULL)');
                $add_balance->bindParam(":user", $invoice_output['steamid']);
                $add_balance->bindParam(":change", $invoice_output['credits']);
                $add_balance->execute();

                $update_wallet = $conn->prepare('UPDATE users SET wallet = wallet + (:credits) WHERE steamid = (:steamid)');
                $update_wallet->bindParam(':credits', $invoice_output['credits']);
                $update_wallet->bindParam(':steamid', $invoice_output['steamid']);
                $update_wallet->execute();
            }
            echo "OK";
        } else {
            echo "NOPE";
        }
        die();
    }

    if($invoice_output['paid'] == 0){
        echo '<body onload="setInterval(check, 10000);"><link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.4.1/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css">';
        echo '<style>
            body {
                background-color: #1C1F29;
            }
            .card {
                background-color: #282C33;
                margin: 0 auto; /* Added */
                float: none; /* Added */
                margin-bottom: 10px; /* Added */
                width: 30rem !important;
        }</style>
        <br><div align="center" class="card" style="width: 25rem;">';
        $amount = $invoice_output['amount'];
        echo '<br><h5><span style="color:white">Send</span> ' . '<span style="color:orange">' . $amount . " BTC" . '</span></h5><br>';
        echo '<img src="https://chart.apis.google.com/chart?cht=qr&chs=300x300&choe=UTF-8&chl='.urlencode("bitcoin:" . $invoice_output['public_key'] . "?amount=" . $amount).'"/>';
        echo '<br><h5><span style="color:white">To Address:</span> <br>' . '<span style="color:white">' . $invoice_output['public_key'] . '</span>' . '</h5><br>';
	
        echo '<button onclick="location.href = \'https://ipn.yourdomain.com/?i='.$_GET['i'].'&cancel\';" class="btn btn-danger"><i class="fas fa-window-close"></i> Cancel Payment</button>';
        echo '<br><div style="background-color: #FF6700 !important; border-color: #FF6700 !important; color: white !important; margin-bottom: 0 !important;" class="alert alert-warning">0/1 confirmation received.<br>DO NOT CLOSE OUT OF THIS TAB!<br>You will be redirected automatically to the website.</div></div></body>';
    } else {
        header('Location: https://yourdomain.com/');
        die();
    }
} else {
    header('Location: https://yourdomain.com/user/deposit');
    die();
}
?>
<script>
function check() {
    var obj;
    fetch('https://ipn.yourdomain.com/?i=<?php echo $_GET['i'] ?>&check')
    .then(res => {
        res.text().then(function(text){
            if(text == "OK"){
                window.location = 'https://yourdomain.com';
            }
        })
    })
}
</script>
