<?php
session_start();
include 'includes/header.php';
?>

<?php
if (isset($_SESSION["username"]) && $_SESSION["isAdmin"] == 1) {
    include 'components/admin/admin-home.php';
} else if (isset($_SESSION["username"])) {
?>
    <div class="w-full flex justify-center px-4 pt-20 pb-10">
        <?php include 'components/user/user-home.php'; ?>
    </div>
<?php
} else {
    include 'components/guest/welcome.php';
}
?>

<?php include 'includes/footer.php'; ?>