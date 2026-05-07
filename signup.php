<html>
    <head>  
  
    </head>
    <body>
   
 
    <body>
    <link rel="stylesheet" href="assets/css/signup.css">


        
    <section>
        <?php 
        session_start();
        if(isset($_GET["error"])) 
        {
            if(($_GET["error"] == "invalidEmail")||($_GET["error"] == "emailtaken"))
            {
                echo '<div class="form-box">
            <div class="form-value">
                <form action="includes/signup.inc.php" method="post">
                    <h2>Register</h2>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="text" name="name" value="'.$_SESSION["name"].'" required>
                        <label for="">Name</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="text" name="email"  required>
                        <label for="">Email</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="text" name="uid" value="'.$_SESSION["uid"].'" required>
                        <label for="">User Name</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" name="pwd" value="'.$_SESSION["pwd"].'" required>
                        <label for="">Password</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" name="pwdrepeat" value="'.$_SESSION["pwdrepeat"].'" required>
                        <label for="">Repeat Password</label>
                    </div>
                    <button name="submit" type="submit">Sign Up</button>';

            }
            else if(($_GET["error"] == "invalidUid")||($_GET["error"] == "usernametaken"))
            {
                echo '<div class="form-box">
            <div class="form-value">
                <form action="includes/signup.inc.php" method="post">
                    <h2>Register</h2>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="text" name="name" value="'.$_SESSION["name"].'" required>
                        <label for="">Name</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="text" name="email"  value="'.$_SESSION["email"].'" required>
                        <label for="">Email</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="text" name="uid" required>
                        <label for="">User Name</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" name="pwd" value="'.$_SESSION["pwd"].'" required>
                        <label for="">Password</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" name="pwdrepeat" value="'.$_SESSION["pwdrepeat"].'" required>
                        <label for="">Repeat Password</label>
                    </div>
                    <button name="submit" type="submit">Sign Up</button>';
            }
            else if($_GET["error"] == "passwordnotmatch")
            {
                echo '<div class="form-box">
            <div class="form-value">
                <form action="includes/signup.inc.php" method="post">
                    <h2>Register</h2>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="text" name="name" value="'.$_SESSION["name"].'" required>
                        <label for="">Name</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="text" name="email"  value="'.$_SESSION["email"].'" required>
                        <label for="">Email</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="text" name="uid" value="'.$_SESSION["uid"].'" required>
                        <label for="">User Name</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" name="pwd" required>
                        <label for="">Password</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" name="pwdrepeat" required>
                        <label for="">Repeat Password</label>
                    </div>
                    <button name="submit" type="submit">Sign Up</button>';
            }
            
            
        }
        else{
            echo '<div class="form-box">
            <div class="form-value">
                <form action="includes/signup.inc.php" method="post">
                    <h2>Register</h2>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="text" name="name" required>
                        <label for="">Name</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="text" name="email"  required>
                        <label for="">Email</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="text" name="uid"  required>
                        <label for="">User Name</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" name="pwd"  required>
                        <label for="">Password</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" name="pwdrepeat"  required>
                        <label for="">Repeat Password</label>
                    </div>
                    <button name="submit" type="submit">Sign Up</button>';
        }
        ?>



        
                    <?php
                    if (isset ($_GET["error"])){
                        if($_GET["error"] == "emptyinput"){
                            echo'<div class="error">Fill in the all Fields</div>';
                        } else if($_GET["error"] == "invalidUid"){
                            echo'<div class="error">Invalid Username</div>';
                        } else if($_GET["error"] == "invalidEmail"){
                            echo'<div class="error">Invalid Email</div>';
                        } else if($_GET["error"] == "passwordnotmatch"){
                            echo'<div class="error">Password does not match </div>';
                        } else if($_GET["error"] == "stmtfailed"){
                            echo'<div class="error">Something Went Wrong!</div>';
                        }  else if($_GET["error"] == "none"){
                            echo'<div class="error">Account Created</div>';
                        } else if($_GET["error"] == "usernametaken"){
                            echo'<div class="error">Username Alrady in Use!</div>';
                        }else if($_GET["error"] == "emailtaken"){
                            echo'<div class="error">Email Alrady in Use!</div>';
                        }
                    }
                    ?>
                    <div class="register">
                        <p>Do you have a account <a href="login.php">LogIn</a></p>
                    </div>
                </form>
                
            </div>
        </div>
    </section>
                </body></html>