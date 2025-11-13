<?php
// ---------- create-account.php ----------
session_start();

date_default_timezone_set('Asia/Kolkata');
$_SESSION["date"] = date('Y-m-d');

// Clear any existing authenticated state
$_SESSION["user"] = "";
$_SESSION["usertype"] = "";

include "connection.php";     // $database = new mysqli(...)
include __DIR__ . "/csrf.php"; // CSRF helper

$error  = '';
$errors = [];

/* ---------- Helpers ---------- */
function clean($v){ return trim($v ?? ''); }
function valid_phone_my($tel){ return preg_match('/^0\d{9,10}$/', $tel); }
function valid_dob($dob){
    $ts = strtotime($dob);
    if ($ts === false || $ts > time()) return false;
    $age = floor((time() - $ts) / (365.25*24*3600));
    return $age >= 13;
}
function valid_nic($nic){ return preg_match('/^[A-Za-z0-9\-]{5,20}$/', $nic); }
/* Strong password: 8–72 chars, at least 1 lower, 1 upper, 1 digit, 1 symbol, no spaces */
function valid_password($pwd){
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s])[^\s]{8,72}$/', $pwd) === 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ===== CSRF ===== */
    if (!csrf_validate('register', $_POST['csrf'] ?? null)) {
        $errors[] = "Security check failed. Please refresh and submit again.";
    } else {

        // These 5 usually come from previous step (sign_up.php) stored in session
        $fname   = clean($_SESSION['personal']['fname']   ?? '');
        $lname   = clean($_SESSION['personal']['lname']   ?? '');
        $address = clean($_SESSION['personal']['address'] ?? '');
        $nic     = clean($_SESSION['personal']['nic']     ?? '');
        $dob     = clean($_SESSION['personal']['dob']     ?? '');
        $name    = trim("$fname $lname");

        // From this form
        $email       = clean($_POST['newemail'] ?? '');
        $tele        = clean($_POST['tele'] ?? '');
        $newpassword = $_POST['newpassword'] ?? '';
        $cpassword   = $_POST['cpassword'] ?? '';

        // Required checks
        if ($fname === '' || $lname === '' || $address === '' || $nic === '' || $dob === '') {
            $errors[] = "Please complete your personal details first.";
        }
        if ($email === '')        $errors[] = "Email is required.";
        if ($newpassword === '')  $errors[] = "Password is required.";
        if ($cpassword === '')    $errors[] = "Confirm Password is required.";

        // Format checks
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        if ($tele !== '' && !valid_phone_my($tele)) {
            $errors[] = "Invalid phone number. Use 10–11 digits starting with 0 (e.g., 0123456789).";
        }
        if ($dob && !valid_dob($dob)) {
            $errors[] = "Invalid date of birth (must be at least 13 years old and not in the future).";
        }
        if ($nic && !valid_nic($nic)) {
            $errors[] = "Invalid NIC format.";
        }
        if ($newpassword !== $cpassword) {
            $errors[] = "Password confirmation does not match.";
        }
        if ($newpassword && !valid_password($newpassword)) {
            $errors[] = "Password must be 8–72 chars, include uppercase, lowercase, number, and symbol, and contain no spaces.";
        }

        // Duplicate email check
        if (empty($errors)) {
            $sqlCheck = "SELECT 1 FROM webuser WHERE email = ? LIMIT 1";
            if ($stmt = $database->prepare($sqlCheck)) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows === 1) {
                    $errors[] = "Already have an account for this Email address.";
                }
                $stmt->close();
            } else {
                $errors[] = "Server error (prepare). Please try again.";
            }
        }

        if (empty($errors)) {
            $hashedPassword = password_hash($newpassword, PASSWORD_DEFAULT);

            $database->begin_transaction();
            try {
                // Insert patient
                $sqlPatient = "INSERT INTO patient (pemail, pname, ppassword, paddress, pnic, pdob, ptel)
                               VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt1 = $database->prepare($sqlPatient);
                if (!$stmt1) throw new Exception("Prepare patient failed");
                $stmt1->bind_param("sssssss", $email, $name, $hashedPassword, $address, $nic, $dob, $tele);
                if (!$stmt1->execute()) throw new Exception("Insert patient failed");
                $stmt1->close();

                // Insert webuser entry
                $sqlWebuser = "INSERT INTO webuser (email, usertype) VALUES (?, 'p')";
                $stmt2 = $database->prepare($sqlWebuser);
                if (!$stmt2) throw new Exception("Prepare webuser failed");
                $stmt2->bind_param("s", $email);
                if (!$stmt2->execute()) throw new Exception("Insert webuser failed");
                $stmt2->close();

                $database->commit();

                // ===== Send OTP (purpose=register) =====
                require_once __DIR__ . '/mail_otp.php';

                $_SESSION["pending_email"]    = $email;
                $_SESSION["pending_usertype"] = "p";
                $_SESSION["pending_purpose"]  = "register";
                $_SESSION["username"]         = $fname ?: 'User';

                $otp     = random_int(100000, 999999);
                $otpHash = password_hash((string)$otp, PASSWORD_DEFAULT);
                $exp     = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

                // Remove any previous register OTPs
                if ($clean = $database->prepare("DELETE FROM user_otp WHERE email=? AND usertype='p' AND purpose='register'")) {
                    $clean->bind_param("s", $email);
                    $clean->execute();
                    $clean->close();
                }

                // Insert OTP
                if ($ins = $database->prepare("INSERT INTO user_otp (email, usertype, purpose, otp_hash, expires_at) VALUES (?,?,?,?,?)")) {
                    $purpose = 'register';
                    $usertype = 'p';
                    $ins->bind_param("sssss", $email, $usertype, $purpose, $otpHash, $exp);
                    $ins->execute();
                    $ins->close();

                    if (!sendOtpMail($email, $otp, 'eDoc')) {
                        $errors[] = "Account created but OTP email failed to send. Try logging in and click Resend.";
                    } else {
                        header('Location: otp-verify.php'); exit;
                    }
                } else {
                    $errors[] = "Account created but OTP setup failed. Try logging in and click Resend.";
                }

            } catch (Throwable $e) {
                $database->rollback();
                $errors[] = "Something went wrong while creating the account. Please try again.";
            }
        }
    }

    $error = empty($errors)
        ? '<label for="promter" class="form-label"></label>'
        : '<label for="promter" class="form-label" style="color:rgb(255,62,62);text-align:center;">'
            . implode('<br>', array_map('htmlspecialchars', $errors))
            . '</label>';
} else {
    $error = '<label for="promter" class="form-label"></label>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/animations.css">  
    <link rel="stylesheet" href="css/main.css">  
    <link rel="stylesheet" href="css/signup.css">
    <title>Create Account</title>
    <style>.container{ animation: transitionIn-X 0.5s; }</style>
</head>
<body>
<center>
<div class="container">
    <table border="0" style="width:69%;">
        <tr>
            <td colspan="2">
                <p class="header-text">Let's Get Started</p>
                <p class="sub-text">It's Okey, Now Create User Account.</p>
            </td>
        </tr>
        <tr>
            <form action="" method="POST" novalidate>
                <!-- CSRF token -->
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token('register')); ?>">

                <td class="label-td" colspan="2">
                    <label for="newemail" class="form-label">Email: </label>
                </td>
        </tr>
        <tr>
            <td class="label-td" colspan="2">
                <input type="email" name="newemail" class="input-text" placeholder="Email Address" required>
            </td>
        </tr>
        <tr>
            <td class="label-td" colspan="2">
                <label for="tele" class="form-label">Mobile Number: </label>
            </td>
        </tr>
        <tr>
            <td class="label-td" colspan="2">
                <input type="tel" name="tele" class="input-text" placeholder="ex: 0123456789"
                       pattern="0\d{9,10}" title="Start with 0 and use 10–11 digits">
            </td>
        </tr>
        <tr>
            <td class="label-td" colspan="2">
                <label for="newpassword" class="form-label">Create New Password: </label>
            </td>
        </tr>
        <tr>
            <td class="label-td" colspan="2">
                <input type="password" name="newpassword" class="input-text"
                       placeholder="New Password" required minlength="8" maxlength="72"
                       pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s])[^\s]{8,72}$"
                       title="8–72 chars, with uppercase, lowercase, number, symbol; no spaces.">
            </td>
        </tr>
        <tr>
            <td class="label-td" colspan="2">
                <label for="cpassword" class="form-label">Confirm Password: </label>
            </td>
        </tr>
        <tr>
            <td class="label-td" colspan="2">
                <input type="password" name="cpassword" class="input-text" placeholder="Confirm Password" required>
            </td>
        </tr>

        <tr><td colspan="2"><?php echo $error; ?></td></tr>

        <tr>
            <td><input type="reset" value="Reset" class="login-btn btn-primary-soft btn"></td>
            <td><input type="submit" value="Sign Up" class="login-btn btn-primary btn"></td>
        </tr>
        <tr>
            <td colspan="2">
                <br>
                <label class="sub-text" style="font-weight:280;">Already have an account&#63;</label>
                <a href="login.php" class="hover-link1 non-style-link">Login</a>
                <br><br><br>
            </td>
        </tr>
            </form>
        </tr>
    </table>
</div>
</center>
</body>
</html>
