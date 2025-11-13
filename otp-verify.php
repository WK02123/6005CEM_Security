<?php
session_start();
include "connection.php";
require_once __DIR__ . '/session_boot.php';

if (!isset($_SESSION['pending_email'], $_SESSION['pending_usertype'], $_SESSION['pending_purpose'])) {
  header('Location: login.php'); exit;
}
$email    = $_SESSION['pending_email'];
$usertype = $_SESSION['pending_usertype'];   // 'a','d','p'
$purpose  = $_SESSION['pending_purpose'];    // 'login','register'
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST'){
  $code = trim($_POST['otp'] ?? '');

  $st = $database->prepare("SELECT id, otp_hash, expires_at, attempts FROM user_otp
                            WHERE email=? AND usertype=? AND purpose=?
                            ORDER BY id DESC LIMIT 1");
  $st->bind_param("sss", $email, $usertype, $purpose);
  $st->execute(); $res = $st->get_result(); $row = $res->fetch_assoc(); $st->close();

  if(!$row){ $msg="No active OTP. Please resend."; }
  else if((int)$row['attempts']>=5){ $msg="Too many attempts. Resend a new code."; }
  else if(new DateTime() > new DateTime($row['expires_at'])){ $msg="Code expired. Resend a new code."; }
  else{
    if (password_verify($code, $row['otp_hash'])) {
      // success → clear OTP
      $del=$database->prepare("DELETE FROM user_otp WHERE id=?"); $del->bind_param("i",$row['id']); $del->execute(); $del->close();

      // finalize session
      $_SESSION['user']      = $email;
      $_SESSION['usertype']  = $usertype;
      $_SESSION['last_activity'] = time();
      if (!isset($_SESSION['username'])) $_SESSION['username'] = 'User';
      $_SESSION['__created'] = time();
      unset($_SESSION['pending_email'], $_SESSION['pending_usertype'], $_SESSION['pending_purpose']);

      // redirect by role
      if ($usertype==='a') header('Location: admin/index.php');
      elseif ($usertype==='d') header('Location: doctor/index.php');
      else header('Location: patient/index.php');
      exit;
    } else {
      $up=$database->prepare("UPDATE user_otp SET attempts=attempts+1 WHERE id=?");
      $up->bind_param("i",$row['id']); $up->execute(); $up->close();
      $msg="Invalid code.";
    }
  }
}

// resend
if (isset($_GET['resend']) && $_GET['resend']==='1'){
  require_once __DIR__.'/mail_otp.php';
  $otp = random_int(100000,999999);
  $hash = password_hash((string)$otp, PASSWORD_DEFAULT);
  $exp  = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

  $clean=$database->prepare("DELETE FROM user_otp WHERE email=? AND usertype=? AND purpose=?");
  $clean->bind_param("sss",$email,$usertype,$purpose); $clean->execute();

  $ins=$database->prepare("INSERT INTO user_otp(email,usertype,purpose,otp_hash,expires_at) VALUES(?,?,?,?,?)");
  $ins->bind_param("sssss",$email,$usertype,$purpose,$hash,$exp); $ins->execute();

  $msg = sendOtpMail($email,$otp,'eDoc') ? "New code sent." : "Could not send email.";
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Verify OTP</title>
<link rel="stylesheet" href="css/main.css"></head>
<body><center>
<div class="container" style="max-width:420px">
  <h2>Two-Factor Verification</h2>
  <p>We emailed a 6-digit code to <b><?=htmlspecialchars($email)?></b>.</p>
  <?php if($msg) echo '<p style="color:#c00">'.$msg.'</p>'; ?>
  <form method="post">
    <input class="input-text" name="otp" maxlength="6" pattern="\d{6}" placeholder="Enter 6-digit code" required>
    <br><br><button class="btn btn-primary">Verify</button>
  </form>
  <p><a href="?resend=1">Resend code</a> • <a href="logout.php">Cancel</a></p>
</div>
</center></body></html>
