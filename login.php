<?php
// ---------- login.php ----------
session_start();

date_default_timezone_set('Asia/Kolkata');
$_SESSION["date"] = date('Y-m-d');

include("connection.php"); // $database = new mysqli(...)
include __DIR__ . '/csrf.php'; // CSRF helper

$error = '<label for="promter" class="form-label">&nbsp;</label>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // === CSRF check ===
    if (!csrf_validate('login', $_POST['csrf'] ?? null)) {
        $error = '<label class="form-label" style="color:#f33;text-align:center;">Security check failed. Please refresh and try again.</label>';
    } else {
        $email = trim($_POST['useremail'] ?? '');
        $password = $_POST['userpassword'] ?? '';
        $errs = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errs[] = "Enter a valid email.";
        }
        if ($password === '') {
            $errs[] = "Password is required.";
        }

        if (empty($errs)) {
            // Step 1: Find user type
            $sqlType = "SELECT usertype FROM webuser WHERE email = ? LIMIT 1";
            if ($st = $database->prepare($sqlType)) {
                $st->bind_param("s", $email);
                $st->execute();
                $typeRes = $st->get_result();

                if ($typeRes && $typeRes->num_rows === 1) {
                    $row = $typeRes->fetch_assoc();
                    $usertype = $row['usertype']; // 'p', 'a', 'd'

                    // Step 2: Fetch login info by role
                    if ($usertype === 'p') {
                        $sqlUser = "SELECT ppassword AS pwd, pname AS name FROM patient WHERE pemail = ? LIMIT 1";
                    } elseif ($usertype === 'a') {
                        $sqlUser = "SELECT apassword AS pwd, aemail AS name FROM admin WHERE aemail = ? LIMIT 1";
                    } elseif ($usertype === 'd') {
                        $sqlUser = "SELECT docpassword AS pwd, docname AS name FROM doctor WHERE docemail = ? LIMIT 1";
                    } else {
                        $errs[] = "Unknown user type.";
                        $sqlUser = null;
                    }

                    if ($sqlUser) {
                        if ($st2 = $database->prepare($sqlUser)) {
                            $st2->bind_param("s", $email);
                            $st2->execute();
                            $res2 = $st2->get_result();

                            if ($res2 && $res2->num_rows === 1) {
                                $u = $res2->fetch_assoc();
                                $hash = $u['pwd'];

                                // Step 3: Verify password
                                if (is_string($hash) && password_verify($password, $hash)) {
                                    // Step 4: Generate OTP and send email
                                    require_once __DIR__ . '/mail_otp.php';

                                    $_SESSION['pending_email']    = $email;
                                    $_SESSION['pending_usertype'] = $usertype;
                                    $_SESSION['pending_purpose']  = 'login';
                                    $displayName = trim((string)($u['name'] ?? 'User'));
                                    $_SESSION['username'] = $displayName !== '' ? explode(' ', $displayName)[0] : 'User';

                                    $otp     = random_int(100000, 999999);
                                    $otpHash = password_hash((string)$otp, PASSWORD_DEFAULT);
                                    $exp     = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

                                    // Clear old OTPs
                                    if ($clean = $database->prepare("DELETE FROM user_otp WHERE email=? AND usertype=? AND purpose='login'")) {
                                        $clean->bind_param("ss", $email, $usertype);
                                        $clean->execute();
                                        $clean->close();
                                    }

                                    // Insert OTP
                                    if ($ins = $database->prepare("INSERT INTO user_otp (email, usertype, purpose, otp_hash, expires_at) VALUES (?,?,?,?,?)")) {
                                        $purpose = 'login';
                                        $ins->bind_param("sssss", $email, $usertype, $purpose, $otpHash, $exp);
                                        $ins->execute();
                                        $ins->close();

                                        // Send email
                                        if (!sendOtpMail($email, $otp, 'eDoc')) {
                                            $errs[] = "Could not send OTP email. Please try again.";
                                        } else {
                                            header('Location: otp-verify.php');
                                            exit;
                                        }
                                    } else {
                                        $errs[] = "Server error (OTP insert). Try again.";
                                    }
                                } else {
                                    $errs[] = "Wrong credentials: Invalid email or password.";
                                }
                            } else {
                                $errs[] = "Account not found.";
                            }
                            $st2->close();
                        } else {
                            $errs[] = "Server error. Please try again.";
                        }
                    }
                } else {
                    $errs[] = "No account found for this email.";
                }
                $st->close();
            } else {
                $errs[] = "Server error. Please try again.";
            }
        }

        if (!empty($errs)) {
            $error = '<label for="promter" class="form-label" style="color:rgb(255,62,62);text-align:center;">'
                   . implode('<br>', array_map('htmlspecialchars', $errs))
                   . '</label>';
        }
    }
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
    <link rel="stylesheet" href="css/login.css">
    <title>Login</title>
</head>
<body>
<center>
<div class="container">
    <table border="0" style="margin:0;padding:0;width:60%;">
        <tr><td><p class="header-text">Welcome Back!</p></td></tr>
        <div class="form-body">
            <tr><td><p class="sub-text">Login with your details to continue</p></td></tr>
            <?php if (!empty($_GET['expired'])): ?>
                <tr>
                <td>
                    <div style="color:#b91c1c;text-align:center;font-weight:500;margin:8px 0;">
                    ⚠️ Your session expired due to inactivity. Please log in again.
                    </div>
                </td>
                </tr>
                <?php endif; ?>

            <tr>
                <form action="" method="POST" novalidate>
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token('login')); ?>">
                    
                    <td class="label-td"><label for="useremail" class="form-label">Email: </label></td>
            </tr>
            <tr>
                <td class="label-td">
                    <input type="email" name="useremail" class="input-text" placeholder="Email Address" required>
                </td>
            </tr>
            <tr><td class="label-td"><label for="userpassword" class="form-label">Password: </label></td></tr>
            <tr>
                <td class="label-td">
                    <input type="password" name="userpassword" class="input-text" placeholder="Password" required>
                </td>
            </tr>
            <tr><td><br><?php echo $error; ?></td></tr>
            <tr><td><input type="submit" value="Login" class="login-btn btn-primary btn"></td></tr>
        </div>
        <tr>
            <td>
                <br>
                <label class="sub-text" style="font-weight:280;">Don't have an account&#63;</label>
                <a href="signup.php" class="hover-link1 non-style-link">Sign Up</a>
                <br><br><br>
            </td>
        </tr>
                </form>
    </table>
</div>
</center>
</body>
</html>

<script>
  setTimeout(() => {
    const msg = document.querySelector('.session-expired');
    if (msg) msg.style.display = 'none';
  }, 5000);
</script>
