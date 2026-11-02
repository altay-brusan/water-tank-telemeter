<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect him to welcome page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
	if($_SESSION['cp']==1)
		header("location: dashboard_x.php");
	else
		header("location: dashboard.php");
	
	exit;
}

// Include config file
require_once "includes/config.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

	// Check if username is empty
	if(empty(trim($_POST["username"]))){
		$username_err = "Kullanıcıadı alanı boş bırakılamaz.";
	} else{
		$username = trim($_POST["username"]);
	}

	// Check if password is empty
	if(empty(trim($_POST["password"]))){
		$password_err = "Şifre alanı boş bırakılamaz.";
	} else{
		$password = trim($_POST["password"]);
	}

	// Validate credentials
	
	if(empty($username_err) && empty($password_err)){
		// Prepare a select statement
		
		$sql = "SELECT * FROM `water_users` AS `wu` WHERE `wu`.`user_email` = ".safe($username)." AND `wu`.`user_active`=1";
		
		if($query=$mysqli->query($sql)){
			
			if($query->num_rows == 1){
				
				$row=$query->fetch_assoc();

				if(password_verify($password, $row['user_password'])){
					// Password is correct, so start a new session
					session_start();
					// Store data in session variables
					$_SESSION["loggedin"] = true;
					$_SESSION["uid"] = $row['user_id'];
					$_SESSION["name"] = $row['user_name']." ".$row['user_lastname'];
					$_SESSION["cp"]=$row['user_companyid'];
					// Redirect user to welcome page
					$sql="UPDATE `water_users` AS `wu` SET `wu`.`user_lastlogin`=NOW() WHERE `wu`.`user_id`=".safe($row['user_id'])." LIMIT 1";
					$mysqli->query($sql);
// 					echo $sql;
					if($row['user_companyid']==1)
						header("location: dashboard_x.php");
					else
 						header("location: dashboard.php");
				}
				else{
					// Display an error message if password is not valid
					$password_err = "Geçersiz Kullanıcıadı/Şifre.";
				}
				
			}
			else{
				// Display an error message if username doesn't exist
				$password_err = "Geçersiz Kullanıcıadı/Şifre.";
			}
		}
		else{
			$password_err = "Oops! Birşeyler ters gitti. Lütfen tekrar deneyin.";
		}
	}
	// Close connection
	$mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Turhal Belediyesi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <style type="text/css">
      .bd-placeholder-img {
        font-size: 1.125rem;
        text-anchor: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
      }

      @media (min-width: 768px) {
        .bd-placeholder-img-lg {
          font-size: 3.5rem;
        }
      }
    </style>
	<link href="assets/css/signin.css" rel="stylesheet">
</head>
<body class="text-center">
	<form class="form-signin " action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
		<img class="mb-4" src="assets/img/logo.png" alt="" width="150" height="150">
		<!--<h1 class="h3 mb-3 font-weight-normal">Please sign in</h1>-->
		<label for="username" class="sr-only">Kullanıcıadı</label>
		<input type="email" name="username" id="username" class="form-control" placeholder="Kullanıcıadı" required autofocus>
		<span class="help-block text-danger"><?php echo $username_err; ?></span>
		<label for="password" class="sr-only">Şifre</label>
		<input type="password" name="password" id="password" class="form-control" placeholder="Şifre" required>
		<span class="help-block text-danger"><?php echo $password_err; ?></span>
		<button class="btn btn-lg btn-primary btn-block" type="submit">Giriş</button>
		<p class="mt-5 mb-3 text-muted">&copy; 2020 Turhal Belediyesi</p>
		<p class="mt-2 mb-3 text-muted">Developed by <a href="http://www.pievision.com" target="_blank"><img src="assets/img/pievision-logo.png?t=<?php echo date('His')?>"></a></p>
	</form>
</body>
</html>