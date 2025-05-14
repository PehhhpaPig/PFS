<?php
session_start();
require_once 'securimage/securimage.php';

$securimage = new Securimage();

if ($securimage->check($_POST['captcha_code']) == false) {
    echo "<h3>⚠️ Incorrect CAPTCHA. Please try again.</h3>";
    echo "<a href='captcha_form.php'>Go Back</a>";
} else {
    echo "<h3>✅ CAPTCHA Passed. Form can now be processed.</h3>";
    echo "Welcome, " . htmlspecialchars($_POST['username']) . "!";
    // Here you could handle form logic, store in database, etc.
}
?>
