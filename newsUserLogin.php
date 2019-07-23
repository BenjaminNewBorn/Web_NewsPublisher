<?php
session_start();
session_unset();




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UserLogin</title>
    <link rel="stylesheet" type="text/css" href="newsWeb.css">
</head>
<body>
<h2>Please Login:</h2><br>
<form id="userform" name="userinput" action="<?php echo htmlentities($_SERVER['PHP_SELF']);?>" method="post">
    UserName:<input type="text" name="username"/><br>
    PassWord:<input type="password" name="password"/><br>
    <input type="hidden" name="_submitted" value="1">
    <input type="submit" name="submit" value="Login"/>
    <input type="submit" name="submit" value="Register">
</form>

<?php

if(!isset($_POST['username'],$_POST['password'])) {
    if(isset($_POST['_submitted'])) {
        printf("Neither username or Password can be empty");
    }
}
else{

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    //validate the username
    if( !preg_match('/^[\w_\-]+$/', $username) ){
        echo "Invalid username";
        exit;
    }
    if( !preg_match('/^([a-zA-Z0-9]|[_]){5,17}$/', $password) ){
        echo "Invalid Password<br>The password should have 6 - 18 characters and only include letters, numbers and '_'";
        exit;
    }

    //connect the database
    require 'database.php';

    if($_POST['submit'] == "Login") { //Login
        //get password via query
        $stmt = $mysqli->prepare("select count(*),id, password from users where name = ?");
        if(!$stmt) {
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->bind_result($cnt,$user_id, $pwd_hash);
        $stmt->fetch();

        //check if password is correct
        $pwd_guess = $_POST['password'];
        if($cnt == 1 && password_verify($pwd_guess, $pwd_hash)) {

            $_SESSION['user_id'] = $user_id;
            $_SESSION['login'] = true;
            $_SESSION['username'] = $username;
            $stmt->close();
            $_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(32));
            header("Location:newsList.php");
            exit();
        }else {
            echo "Please check username and password\n";
            $stmt->close();
        }
    }elseif($_POST['submit'] == 'Register') { // Register
        $pwd_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $mysqli->prepare("insert into users (name, password) values (?,?)");
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        $stmt->bind_param('ss', $username, $pwd_hash);
        $stmt->execute();
        if($stmt->affected_rows == 1) {
            printf("Register successfully! Please login");
        } else {
            printf("Query Failed: %s\n", $mysqli->error);
        }

        $stmt->close();
    }
}

?>



</body>
</html>