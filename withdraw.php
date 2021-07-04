<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
function validate($addr){
    if(strlen($addr) <= 36 AND strlen($addr) > 25){
        if(mb_substr($addr, 0, 1, "UTF-8") == "1" OR mb_substr($addr, 0, 1, "UTF-8") == "3" OR mb_substr($addr, 0, 1, "UTF-8") == "b"){
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

if(isset($_GET['steamid'], $_GET['address'], $_GET['price']) AND is_numeric($_GET['price'])){
    if($_GET['price'] >= 500){ 
        if(validate($_GET['address'])){
            $steamid = $_GET['steamid'];
            $address = $_GET['address'];
            $credits = $_GET['price'];

            $final = "-" . $credits;

            include('inc/conn.php');
            $user = $conn->prepare('SELECT * FROM users WHERE steamid = (:steamid)');
            $user->bindParam(':steamid', $steamid);
            $user->execute();
            $user_out = $user->fetch();

            if(!empty($user_out)){
                if($user_out['banned'] == "0"){
                    if($user_out['total_bet'] >= $credits){
                        if($user_out['wallet'] >= $credits){
                            $balance = $conn->prepare('INSERT INTO `wallet_change` (user, `change`, reason, transaction_date) VALUES ((:user), (:change), "WITHDRAW", NULL)');
                            $balance->bindParam(":user", $steamid);
                            $balance->bindParam(":change", $final);
                            $balance->execute();

                            $final_balance = $conn->prepare('UPDATE users SET wallet = wallet - (:credits) WHERE id = (:id)');
                            $final_balance->bindParam(':credits', $credits);
                            $final_balance->bindParam(':id', $user_out['id']);
                            $final_balance->execute();
                            
                            $final_totalbet = $conn->prepare('UPDATE users SET total_bet = total_bet - (:credits) WHERE id = (:id)');
                            $final_totalbet->bindParam(':credits', $credits);
                            $final_totalbet->bindParam(':id', $user_out['id']);
                            $final_totalbet->execute();

                            $fcredits = $credits - 100;
                            $btc = file_get_contents("https://blockchain.info/tobtc?currency=USD&value=" . $fcredits / 100);
                            $timestamp = time();

                            $insert = $conn->prepare('INSERT INTO withdraw (steamid, address, amount, timestamp, type) VALUES ((:steamid), (:address), (:amount), (:timestamp), "0")');
                            $insert->bindParam(':steamid', $steamid);
                            $insert->bindParam(':address', $address);
                            $insert->bindParam(':amount', $btc);
                            $insert->bindParam(':timestamp', $timestamp);
                            $insert->execute();

                            header('Location: http://yourdomain.com/user/withdraw?success');
                            die();
                        } else {
                            header('Location: http://yourdomain.com/user/withdraw?notenough');
                            die();
                        }
                    } else {
                        header('Location: http://yourdomain.com/user/withdraw?beterror');
                        die();
                    }
                } else {
                    header('Location: http://yourdomain.com/user/withdraw');
                    die();
                }
            } else {
                header('Location: http://yourdomain.com/user/withdraw');
                die();
            }
        } else {
            header('Location: http://yourdomain.com/user/withdraw?adderror');
            die();   
        }
    } else {
        header('Location: http://yourdomain.com/user/withdraw?minerror');
        die();    
    }
} else {
    header('Location: http://yourdomain.com/user/withdraw');
    die();
}
?>