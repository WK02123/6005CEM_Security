<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/animations.css">  
    <link rel="stylesheet" href="css/main.css">  
    <link rel="stylesheet" href="css/signup.css">
    <title>Sign Up</title>
</head>
<body>
<?php
session_start();

$_SESSION["user"] = "";
$_SESSION["usertype"] = "";

date_default_timezone_set('Asia/Kolkata');
$_SESSION["date"] = date('Y-m-d');

$error = '';

function clean($v){ return trim($v ?? ''); }
function valid_dob($dob){
    $ts = strtotime($dob);
    if ($ts === false || $ts > time()) return false;
    $age = floor((time() - $ts) / (365.25*24*3600));
    return $age >= 13;
}
function valid_nic($nic){ return preg_match('/^[A-Za-z0-9\-]{5,20}$/', $nic); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname   = clean($_POST['fname'] ?? '');
    $lname   = clean($_POST['lname'] ?? '');
    $address = clean($_POST['address'] ?? '');
    $nic     = clean($_POST['nic'] ?? '');
    $dob     = clean($_POST['dob'] ?? '');

    $errors = [];
    if ($fname === '' || $lname === '' || $address === '' || $nic === '' || $dob === '') {
        $errors[] = "All fields are required.";
    }
    if ($dob && !valid_dob($dob)) {
        $errors[] = "Invalid date of birth (must be at least 13 years old and not in the future).";
    }
    if ($nic && !valid_nic($nic)) {
        $errors[] = "Invalid NIC format.";
    }

    if (empty($errors)) {
        $_SESSION["personal"] = [
            'fname'   => $fname,
            'lname'   => $lname,
            'address' => $address,
            'nic'     => $nic,
            'dob'     => $dob
        ];
        header("location: create-account.php");
        exit;
    } else {
        $error = '<label for="promter" class="form-label" style="color:rgb(255,62,62);text-align:center;">'
               . implode('<br>', array_map('htmlspecialchars', $errors))
               . '</label>';
    }
}
?>

<center>
<div class="container">
    <table border="0">
        <tr>
            <td colspan="2">
                <p class="header-text">Let's Get Started</p>
                <p class="sub-text">Add Your Personal Details to Continue</p>
            </td>
        </tr>
        <tr>
            <form action="" method="POST">
                <td class="label-td" colspan="2">
                    <label for="name" class="form-label">Name: </label>
                </td>
        </tr>
        <tr>
            <td class="label-td">
                <input type="text" name="fname" class="input-text" placeholder="First Name" required>
            </td>
            <td class="label-td">
                <input type="text" name="lname" class="input-text" placeholder="Last Name" required>
            </td>
        </tr>
        <tr>
            <td class="label-td" colspan="2">
                <label for="address" class="form-label">Address: </label>
            </td>
        </tr>
        <tr>
            <td class="label-td" colspan="2">
                <input type="text" name="address" class="input-text" placeholder="Address" required>
            </td>
        </tr>
        <tr>
            <td class="label-td" colspan="2">
                <label for="nic" class="form-label">NIC: </label>
            </td>
        </tr>
        <tr>
            <td class="label-td" colspan="2">
                <input type="text" name="nic" class="input-text" placeholder="NIC Number" required>
            </td>
        </tr>
        <tr>
            <td class="label-td" colspan="2">
                <label for="dob" class="form-label">Date of Birth: </label>
            </td>
        </tr>
        <tr>
            <td class="label-td" colspan="2">
                <input type="date" name="dob" class="input-text" required>
            </td>
        </tr>

        <tr><td class="label-td" colspan="2"><?php echo $error; ?></td></tr>

        <tr>
            <td><input type="reset" value="Reset" class="login-btn btn-primary-soft btn"></td>
            <td><input type="submit" value="Next" class="login-btn btn-primary btn"></td>
        </tr>
        <tr>
            <td colspan="2">
                <br>
                <label class="sub-text" style="font-weight: 280;">Already have an account&#63; </label>
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
