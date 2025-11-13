<?php
// patient/index.php
require_once __DIR__ . '/../auth_guard.php'; // login + idle-timeout guard
guard_role('p');                              // ⬅️ patient-only

require_once __DIR__ . '/../connection.php'; // $database (mysqli)

date_default_timezone_set('Asia/Kolkata');
$today     = date('Y-m-d');
$useremail = $_SESSION['user'] ?? '';

// helper
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// fetch logged-in patient
$sql  = "SELECT * FROM patient WHERE pemail = ? LIMIT 1";
$stmt = $database->prepare($sql);
$stmt->bind_param('s', $useremail);
$stmt->execute();
$userrow   = $stmt->get_result();
$userfetch = $userrow->fetch_assoc();
$stmt->close();

$userid   = $userfetch['pid']   ?? null;
$username = $userfetch['pname'] ?? 'User';

// quick KPI queries (same as your original)
$patientrow     = $database->query("SELECT * FROM patient");
$doctorrow      = $database->query("SELECT * FROM doctor");
$appointmentrow = $database->query("SELECT * FROM appointment WHERE appodate >= '{$today}'");
$schedulerow    = $database->query("SELECT * FROM schedule WHERE scheduledate = '{$today}'");
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
        .dashbord-tables{ animation: transitionIn-Y-over .5s; }
        .filter-container{ animation: transitionIn-Y-bottom .5s; }
        .sub-table,.anime{ animation: transitionIn-Y-bottom .5s; }
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
                <td class="menu-btn menu-icon-home menu-active menu-icon-home-active">
                    <a href="index.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Home</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-doctor">
                    <a href="doctors.php" class="non-style-link-menu"><div><p class="menu-text">All Doctors</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-session">
                    <a href="schedule.php" class="non-style-link-menu"><div><p class="menu-text">Scheduled Sessions</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-appoinment">
                    <a href="appointment.php" class="non-style-link-menu"><div><p class="menu-text">My Bookings</p></div></a>
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
                <td colspan="1" class="nav-bar"><p style="font-size:23px;padding-left:12px;font-weight:600;margin-left:20px;">Home</p></td>
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
                        <table class="filter-container doctor-header patient-header" style="border:none;width:95%" border="0">
                            <tr>
                                <td>
                                    <h3>Welcome!</h3>
                                    <h1><?php echo h($username); ?>.</h1>
                                    <p>
                                        Haven't any idea about doctors? no problem let's jumping to 
                                        <a href="doctors.php" class="non-style-link"><b>"All Doctors"</b></a> section or 
                                        <a href="schedule.php" class="non-style-link"><b>"Sessions"</b></a><br>
                                        Track your past and future appointments history.<br>
                                        Also find out the expected arrival time of your doctor or medical consultant.<br><br>
                                    </p>

                                    <h3>Channel a Doctor Here</h3>
                                    <form action="schedule.php" method="post" style="display:flex">
                                        <input type="search" name="search" class="input-text" placeholder="Search Doctor and We will Find The Session Available" list="doctors" style="width:45%;">&nbsp;&nbsp;

                                        <?php
                                        echo '<datalist id="doctors">';
                                        $list11 = $database->query("SELECT docname, docemail FROM doctor");
                                        if ($list11) {
                                            while ($row00 = $list11->fetch_assoc()) {
                                                $d = $row00['docname'] ?? '';
                                                if ($d !== '') echo "<option value='".h($d)."'></option>";
                                            }
                                        }
                                        echo '</datalist>';
                                        ?>

                                        <input type="submit" value="Search" class="login-btn btn-primary btn" style="padding-left:25px;padding-right:25px;padding-top:10px;padding-bottom:10px;">
                                    </form>
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
                                <p style="font-size:20px;font-weight:600;padding-left:40px;" class="anime">Your Upcoming Booking</p>
                                <center>
                                    <div class="abc scroll" style="height:250px;padding:0;margin:0;">
                                        <table width="85%" class="sub-table scrolldown" border="0">
                                            <thead>
                                                <tr>
                                                    <th class="table-headin">Appoint. Number</th>
                                                    <th class="table-headin">Session Title</th>
                                                    <th class="table-headin">Doctor</th>
                                                    <th class="table-headin">Sheduled Date & Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            if ($userid) {
                                                $q = "SELECT s.scheduleid, s.title, s.scheduledate, s.scheduletime,
                                                             a.apponum, d.docname
                                                      FROM schedule s
                                                      INNER JOIN appointment a ON s.scheduleid = a.scheduleid
                                                      INNER JOIN patient p     ON p.pid        = a.pid
                                                      INNER JOIN doctor d      ON d.docid      = s.docid
                                                      WHERE p.pid = ?
                                                        AND s.scheduledate >= ?
                                                      ORDER BY s.scheduledate ASC, s.scheduletime ASC";
                                                $st2 = $database->prepare($q);
                                                $st2->bind_param('is', $userid, $today);
                                                $st2->execute();
                                                $result = $st2->get_result();
                                                $st2->close();
                                            } else {
                                                $result = false;
                                            }

                                            if (!$result || $result->num_rows === 0) {
                                                echo '<tr><td colspan="4">
                                                        <br><br><br><br>
                                                        <center>
                                                            <img src="../img/notfound.svg" width="25%"><br>
                                                            <p class="heading-main12" style="margin-left:45px;font-size:20px;color:rgb(49,49,49)">Nothing to show here!</p>
                                                            <a class="non-style-link" href="schedule.php">
                                                                <button class="login-btn btn-primary-soft btn" style="display:flex;justify-content:center;align-items:center;margin-left:20px;">
                                                                    &nbsp; Channel a Doctor &nbsp;
                                                                </button>
                                                            </a>
                                                        </center>
                                                        <br><br><br><br>
                                                     </td></tr>';
                                            } else {
                                                while ($row = $result->fetch_assoc()) {
                                                    echo '<tr>
                                                            <td style="padding:30px;font-size:25px;font-weight:700;">&nbsp;'.h($row['apponum']).'</td>
                                                            <td style="padding:20px;">&nbsp;'.h(mb_strimwidth($row['title'],0,30,'')).'</td>
                                                            <td>'.h(mb_strimwidth($row['docname'],0,20,'')).'</td>
                                                            <td style="text-align:center;">'.h(substr($row['scheduledate'],0,10)).' '.h(substr($row['scheduletime'],0,5)).'</td>
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
            </tr>
        </table>
    </div>
</div>
</body>
</html>
