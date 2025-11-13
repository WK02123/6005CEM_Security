<?php
// doctor/index.php
require_once __DIR__ . '/../auth_guard.php'; // login + idle-timeout
guard_role('d');                              // ⬅️ doctor-only

require_once __DIR__ . '/../connection.php'; // $database (mysqli)

date_default_timezone_set('Asia/Kolkata');
$today     = date('Y-m-d');
$useremail = $_SESSION['user'] ?? '';

// helper
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ---- fetch logged-in doctor ----
$stmt = $database->prepare("SELECT * FROM doctor WHERE docemail = ? LIMIT 1");
$stmt->bind_param('s', $useremail);
$stmt->execute();
$userrow   = $stmt->get_result();
$userfetch = $userrow->fetch_assoc();
$stmt->close();

$userid   = $userfetch['docid']   ?? null;
$username = $userfetch['docname'] ?? 'Doctor';

// KPIs (same as your original)
$patientrow     = $database->query("SELECT * FROM patient");
$doctorrow      = $database->query("SELECT * FROM doctor");
$appointmentrow = $database->query("SELECT * FROM appointment WHERE appodate >= '{$today}'");
$schedulerow    = $database->query("SELECT * FROM schedule WHERE scheduledate = '{$today}'");

// Upcoming (next 7 days) for THIS doctor
$nextweek = date("Y-m-d", strtotime("+1 week"));
if ($userid) {
    $q = "SELECT s.scheduleid, s.title, d.docname, s.scheduledate, s.scheduletime, s.nop
          FROM schedule s
          INNER JOIN doctor d ON s.docid = d.docid
          WHERE s.docid = ? AND s.scheduledate BETWEEN ? AND ?
          ORDER BY s.scheduledate DESC, s.scheduletime DESC";
    $up = $database->prepare($q);
    $up->bind_param('iss', $userid, $today, $nextweek);
    $up->execute();
    $upcoming = $up->get_result();
    $up->close();
} else {
    $upcoming = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
    <title>Dashboard</title>
    <style>
        .dashbord-tables,.doctor-heade{ animation: transitionIn-Y-over .5s; }
        .filter-container{ animation: transitionIn-Y-bottom .5s; }
        .sub-table,#anim{ animation: transitionIn-Y-bottom .5s; }
        .doctor-heade{ animation: transitionIn-Y-over .5s; }
    </style>
</head>
<body>
<div class="container">
    <div class="menu">
        <table class="menu-container" border="0">
            <tr>
                <td style="padding:10px" colspan="2">
                    <table border="0" class="profile-container">
                        <tr>
                            <td width="30%" style="padding-left:20px">
                                <img src="../img/user.png" alt="" width="100%" style="border-radius:50%">
                            </td>
                            <td style="padding:0;margin:0">
                                <p class="profile-title"><?php echo h(mb_strimwidth($username,0,13,'..')); ?></p>
                                <p class="profile-subtitle"><?php echo h(mb_strimwidth($useremail,0,22,'')); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <a href="../logout.php"><input type="button" value="Log out" class="logout-btn btn-primary-soft btn"></a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="menu-row">
                <td class="menu-btn menu-icon-dashbord menu-active menu-icon-dashbord-active">
                    <a href="index.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Dashboard</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-appoinment">
                    <a href="appointment.php" class="non-style-link-menu"><div><p class="menu-text">My Appointments</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-session">
                    <a href="schedule.php" class="non-style-link-menu"><div><p class="menu-text">My Sessions</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-patient">
                    <a href="patient.php" class="non-style-link-menu"><div><p class="menu-text">My Patients</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-settings">
                    <a href="settings.php" class="non-style-link-menu"><div><p class="menu-text">Settings</p></div></a>
                </td>
            </tr>
        </table>
    </div>

    <div class="dash-body" style="margin-top:15px">
        <table border="0" width="100%" style="border-spacing:0;margin:0;padding:0;">
            <tr>
                <td colspan="1" class="nav-bar">
                    <p style="font-size:23px;padding-left:12px;font-weight:600;margin-left:20px;">Dashboard</p>
                </td>
                <td width="25%"></td>
                <td width="15%">
                    <p style="font-size:14px;color:#777;padding:0;margin:0;text-align:right;">Today's Date</p>
                    <p class="heading-sub12" style="padding:0;margin:0;"><?php echo h($today); ?></p>
                </td>
                <td width="10%">
                    <button class="btn-label" style="display:flex;justify-content:center;align-items:center;"><img src="../img/calendar.svg" width="100%"></button>
                </td>
            </tr>

            <tr>
                <td colspan="4">
                    <center>
                        <table class="filter-container doctor-header" style="border:none;width:95%" border="0">
                            <tr>
                                <td>
                                    <h3>Welcome!</h3>
                                    <h1><?php echo h($username); ?>.</h1>
                                    <p>
                                        Thanks for joining with us. We are always trying to get you a complete service.<br>
                                        You can view your daily schedule, reach patients’ appointments at home!<br><br>
                                    </p>
                                    <a href="appointment.php" class="non-style-link">
                                        <button class="btn-primary btn" style="width:30%">View My Appointments</button>
                                    </a>
                                    <br><br>
                                </td>
                            </tr>
                        </table>
                    </center>
                </td>
            </tr>

            <tr>
                <td colspan="4">
                    <table border="0" width="100%">
                        <tr>
                            <td width="50%">
                                <center>
                                    <table class="filter-container" style="border:none;" border="0">
                                        <tr><td colspan="4"><p style="font-size:20px;font-weight:600;padding-left:12px;">Status</p></td></tr>
                                        <tr>
                                            <td style="width:25%;">
                                                <div class="dashboard-items" style="padding:20px;margin:auto;width:95%;display:flex">
                                                    <div>
                                                        <div class="h1-dashboard"><?php echo $doctorrow ? $doctorrow->num_rows : 0; ?></div><br>
                                                        <div class="h3-dashboard">All Doctors &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
                                                    </div>
                                                    <div class="btn-icon-back dashboard-icons" style="background-image:url('../img/icons/doctors-hover.svg');"></div>
                                                </div>
                                            </td>
                                            <td style="width:25%;">
                                                <div class="dashboard-items" style="padding:20px;margin:auto;width:95%;display:flex;">
                                                    <div>
                                                        <div class="h1-dashboard"><?php echo $patientrow ? $patientrow->num_rows : 0; ?></div><br>
                                                        <div class="h3-dashboard">All Patients &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
                                                    </div>
                                                    <div class="btn-icon-back dashboard-icons" style="background-image:url('../img/icons/patients-hover.svg');"></div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width:25%;">
                                                <div class="dashboard-items" style="padding:20px;margin:auto;width:95%;display:flex;">
                                                    <div>
                                                        <div class="h1-dashboard"><?php echo $appointmentrow ? $appointmentrow->num_rows : 0; ?></div><br>
                                                        <div class="h3-dashboard">NewBooking &nbsp;&nbsp;</div>
                                                    </div>
                                                    <div class="btn-icon-back dashboard-icons" style="margin-left:0;background-image:url('../img/icons/book-hover.svg');"></div>
                                                </div>
                                            </td>
                                            <td style="width:25%;">
                                                <div class="dashboard-items" style="padding:20px;margin:auto;width:95%;display:flex;padding-top:21px;padding-bottom:21px;">
                                                    <div>
                                                        <div class="h1-dashboard"><?php echo $schedulerow ? $schedulerow->num_rows : 0; ?></div><br>
                                                        <div class="h3-dashboard" style="font-size:15px">Today Sessions</div>
                                                    </div>
                                                    <div class="btn-icon-back dashboard-icons" style="background-image:url('../img/icons/session-iceblue.svg');"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </center>
                            </td>

                            <td>
                                <p id="anim" style="font-size:20px;font-weight:600;padding-left:40px;">Your Upcoming Sessions until Next week</p>
                                <center>
                                    <div class="abc scroll" style="height:250px;padding:0;margin:0;">
                                        <table width="85%" class="sub-table scrolldown" border="0">
                                            <thead>
                                                <tr>
                                                    <th class="table-headin">Session Title</th>
                                                    <th class="table-headin">Scheduled Date</th>
                                                    <th class="table-headin">Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            if (!$upcoming || $upcoming->num_rows === 0) {
                                                echo '<tr><td colspan="4">
                                                        <br><br><br><br>
                                                        <center>
                                                            <img src="../img/notfound.svg" width="25%"><br>
                                                            <p class="heading-main12" style="margin-left:45px;font-size:20px;color:rgb(49,49,49)">
                                                                We couldn\'t find anything related to your keywords!
                                                            </p>
                                                            <a class="non-style-link" href="schedule.php">
                                                                <button class="login-btn btn-primary-soft btn" style="display:flex;justify-content:center;align-items:center;margin-left:20px;">
                                                                    &nbsp; Show all Sessions &nbsp;
                                                                </button>
                                                            </a>
                                                        </center>
                                                        <br><br><br><br>
                                                     </td></tr>';
                                            } else {
                                                while ($row = $upcoming->fetch_assoc()) {
                                                    echo '<tr>
                                                            <td style="padding:20px;">&nbsp;'.h(mb_strimwidth($row['title'],0,30,'')).'</td>
                                                            <td style="padding:20px;font-size:13px;">'.h(substr($row['scheduledate'],0,10)).'</td>
                                                            <td style="text-align:center;">'.h(substr($row['scheduletime'],0,5)).'</td>
                                                          </tr>';
                                                }
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </center>
                            </td>
                        </tr>
                    </table>
                </td>
            <tr>
        </table>
    </div>
</div>
</body>
</html>
