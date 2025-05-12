<?php
declare(strict_types=1)
start_secure_session();
require_once 'securimage/securimage.php';
function json_out(array $arr, int $http = 200): never {
        http_response_code($http);
        echo json_encode($arr); exit;
    }
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
