<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css"/>
    
</head>

<body>
    
    
    <link rel="stylesheet" href="assets/css/login-style.css">
    <section>
        <div class="form-box">
            <div class="form-value">
                <form action="includes/login.inc.php" method="post">
                    <h2>Login</h2>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="text" name="uid" required>
                        <label for="">Email or Username</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" name="pwd" required>
                        <label for="">Password</label>
                    </div>
                    
                    <button name="submit" type="submit">Log in</button>
                    <?php
                    if (isset ($_GET["error"])){
                        if($_GET["error"] == "emptyinput"){
                            echo'<div class="error">Fill in the all Fields</div>';
                        } else if($_GET["error"] == "wronglogin"){
                            echo'<div class="error">Invalid details</div>';
                        }
                         else if($_GET["error"] == "stmtfailed"){
                            echo'<div class="error">Something Went Wrong!</div>';
                        }  else if($_GET["error"] == "none"){
                            echo'<div class="error">Account Created</div>';
                        }
                        
                    }
                    ?>
                    <div class="register">
                        <p>Don't have a account <a href="signup.php">Register</a></p>
                    </div>
                </form>
            </div>
        </div>
        
    
   
</body>

</html>