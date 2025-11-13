<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


function sendOtpMail($toEmail, $otp, $appName='eDoc'){
  $mail = new PHPMailer(true);
  try{
     $mail->isSMTP();
      $mail->Host       = $_ENV['MAILTRAP_HOST'];
      $mail->SMTPAuth   = true;
      $mail->Username   = $_ENV['MAILTRAP_USERNAME'];
      $mail->Password   = $_ENV['MAILTRAP_PASSWORD'];
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port       = (int)$_ENV['MAILTRAP_PORT'];
      $mail->setFrom($_ENV['MAILTRAP_FROM'], $_ENV['MAILTRAP_FROM_NAME']);
      $mail->addAddress($toEmail);

      $mail->isHTML(true);
      $mail->Subject = "$appName - OTP Code";
      $mail->Body    = "<p>Your code:</p><h2>$otp</h2><p>Expires in 10 minutes.</p>";
      $mail->AltBody = "Your code is $otp";

      $mail->send();
    return true;
  }catch(Exception $e){
    error_log('OTP mail error: '.$m->ErrorInfo);
    return false;
  }
}
