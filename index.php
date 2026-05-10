<?php
session_start();
include 'includes/header.php';
?>

<div class="w-full flex justify-center px-4 pt-20 pb-10">

<?php
if ((isset($_SESSION["username"])) && ($_SESSION["isAdmin"] == 1)) {
    include 'components/admin/admin-home.php';
} else if (isset($_SESSION["username"])) {
    include 'components/user/user-home.php';
} else {
    include 'components/guest/welcome.php';
}
?>

</div>

<?php include 'includes/footer.php'; ?>