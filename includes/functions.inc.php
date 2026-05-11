<?php
function emptyInputSignup($name, $email, $username, $pwd, $pwdRepeat){
    $result;
    if(empty($name) || empty($email) || empty($username) ||  empty($pwd) || empty($pwdRepeat)){
        $result = true;
    } else {
        $result = false;
    }
    return $result;
}

function invalidUid($username){
    $result;
    if(!preg_match("/^[a-zA-Z0-9]*$/", $username)){
        $result = true;
    } else {
        $result = false;
    }
    return $result;
}

function invalidEmail($email){
    $result;
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $result = true;
    } else {
        $result = false;
    }
    return $result;
}

function pwdMathch($pwd, $pwdRepeat){
    $result;
    if($pwd !== $pwdRepeat){
        $result = true;
    } else {
        $result = false;
    }
    return $result;
}

function uidExists($conn, $username){
    $sql ="SELECT * FROM users WHERE usersUid = ?";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)){
        header("Location:../signup.php?error=stmtfailed");
        exit();
    }
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $resultData = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($resultData)){
        return $row;
    } else {
        return false;
    }

    mysqli_stmt_close($stmt);
}
function emailExists($conn, $email){
    $sql ="SELECT * FROM users WHERE usersEmail = ?";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)){
        header("Location:../signup.php?error=stmtfailed");
        exit();
    }
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $resultData = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($resultData)){
        return $row;
    } else {
        return false;
    }

    mysqli_stmt_close($stmt);
}

function createUser($conn, $name, $email, $username, $pwd){
    $sql = "INSERT INTO users (usersName, usersEmail, usersUid, usersPwd) VALUES (?,?,?,?);";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)){
        header("Location:../signup.php?error=stmtfailed");
        exit();
    }
    $hashedPwd = password_hash($pwd, PASSWORD_DEFAULT);
    mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $username, $hashedPwd);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    // Unset the session variables
    unset($_SESSION["name"]);
    unset($_SESSION["email"]);
    unset($_SESSION["uid"]);
    unset($_SESSION["pwd"]);
    unset($_SESSION["pwdrepeat"]);
    header("Location:../login.php?error=none");
    exit();
}


function emptyInputLogin($username, $pwd){
    $result;
    if(empty($username) ||  empty($pwd)){
        $result = true;
    } else {
        $result = false;
    }
    return $result;
}

function loginUser($conn, $username, $pwd){
    $uidExists = uidExists($conn, $username, $username);
    if ($uidExists === false) {
        header("Location:../login.php?error=wronglogin");
        exit();
    }
    $pwdHashed = $uidExists["usersPwd"];
    $checkPwd = password_verify($pwd, $pwdHashed);

    if($checkPwd === false) {
        header("Location:../login.php?error=wronglogin");
        exit();
    } else if ($checkPwd === true) {
        session_start();
        $_SESSION["userid"] = $uidExists["usersId"];
        $_SESSION["useruid"] = $uidExists["usersUid"];
        $_SESSION["username"] = $uidExists["usersName"];
        $_SESSION["isAdmin"] = $uidExists["isAdmin"];
        $_SESSION["email"]= $uidExists["usersEmail"];
        $_SESSION["usersImg"]=$uidExists["usersImg"]; // Store isAdmin status in session
        header("Location:../index.php");
        exit();
    }
}


/*function loginUser($conn, $username, $pwd){
    $uidExists = uidExists($conn, $username, $username);
    if ($uidExists === false) {
        header("Location:../login.php?error=wronglogin");
        exit();
    }
    $pwdHashed = $uidExists["usersPwd"];
    $checkPwd = password_verify($pwd, $pwdHashed);

    if($checkPwd === false) {
        header("Location:../login.php?error=wronglogin");
        exit();
    } else if ($checkPwd === true) {
        session_start();
        $_SESSION["userid"] = $uidExists["usersId"];
        $_SESSION["useruid"] = $uidExists["usersUid"];
        $_SESSION["username"] = $uidExists["usersName"];
        header("Location:http://localhost/FilmFocus/index.php#reviews");
        exit();
    }
}*/





//FUNCTIONS THAT I USED IN INDEX.PHP





if((isset($_SESSION["username"]))&&($_SESSION["isAdmin"]==1))
{  
    
}
else if (isset($_SESSION["username"]))
{
    
} 


function getShootTypes($conn) {
    $result = mysqli_query($conn, "SELECT * FROM shoot_types ORDER BY id");
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getShotList($conn, $shoot_type_value) {
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, "SELECT s.item FROM shot_list_items s JOIN shoot_types t ON s.shoot_type_id = t.id WHERE t.value = ? ORDER BY s.sort_order");
    mysqli_stmt_bind_param($stmt, "s", $shoot_type_value);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    return array_column($rows, 'item');
}

function getMoods($conn) {
    $result = mysqli_query($conn, "SELECT * FROM moods ORDER BY id");
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getMoodLighting($conn, $mood_value) {
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, "SELECT lighting_recommendation FROM moods WHERE value = ?");
    mysqli_stmt_bind_param($stmt, "s", $mood_value);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $row['lighting_recommendation'] ?? 'Set lighting to complement your chosen mood.';
}

function getSwatches($conn) {
    $result = mysqli_query($conn, "SELECT * FROM outfit_swatches ORDER BY sort_order");
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getLookupOptions($conn, $category) {
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, "SELECT * FROM lookup_options WHERE category = ? ORDER BY sort_order");
    mysqli_stmt_bind_param($stmt, "s", $category);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

function getEquipment($conn) {
    $result = mysqli_query($conn, "SELECT * FROM equipment_options ORDER BY id");
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}