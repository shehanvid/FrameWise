<?php
session_start();
include 'includes/header.php';
?>

<div class="w-full max-w-4xl mx-auto px-4 sm:px-6 mt-6 md:mt-10">

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