<?php
// admin/index.php
require_once __DIR__ . '/../auth_guard.php'; // login + idle-timeout guard
guard_role('a');                              // ⬅️ admin-only

require_once __DIR__ . '/../connection.php'; // $database (mysqli)

date_default_timezone_set('Asia/Kolkata');
$today = date('Y-m-d');

// helper
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// (Optional) show the signed-in admin email on the sidebar
$adminEmail = $_SESSION['user'] ?? 'admin@edoc.com';

// KPIs
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
        .sub-table{ animation: transitionIn-Y-bottom .5s; }
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
                                <p class="profile-title">Administrator</p>
                                <p class="profile-subtitle"><?php echo h($adminEmail); ?></p>
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
                <td class="menu-btn menu-icon-doctor">
                    <a href="doctors.php" class="non-style-link-menu"><div><p class="menu-text">Doctors</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-schedule">
                    <a href="schedule.php" class="non-style-link-menu"><div><p class="menu-text">Schedule</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-appoinment">
                    <a href="appointment.php" class="non-style-link-menu"><div><p class="menu-text">Appointment</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-patient">
                    <a href="patient.php" class="non-style-link-menu"><div><p class="menu-text">Patients</p></div></a>
                </td>
            </tr>
        </table>
    </div>

    <div class="dash-body" style="margin-top:15px">
        <table border="0" width="100%" style="border-spacing:0;margin:0;padding:0;">
            <tr>
                <td colspan="2" class="nav-bar">
                    <form action="doctors.php" method="post" class="header-search">
                        <input type="search" name="search" class="input-text header-searchbar" placeholder="Search Doctor name or Email" list="doctors">&nbsp;&nbsp;
                        <?php
                        echo '<datalist id="doctors">';
                        $list11 = $database->query("SELECT docname, docemail FROM doctor");
                        if ($list11) {
                            while ($r = $list11->fetch_assoc()) {
                                $d = $r['docname']  ?? '';
                                $c = $r['docemail'] ?? '';
                                if ($d !== '') echo "<option value='".h($d)."'></option>";
                                if ($c !== '') echo "<option value='".h($c)."'></option>";
                            }
                        }
                        echo '</datalist>';
                        ?>
                        <input type="submit" value="Search" class="login-btn btn-primary-soft btn" style="padding-left:25px;padding-right:25px;padding-top:10px;padding-bottom:10px;">
                    </form>
                </td>
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
                        <table class="filter-container" style="border:none;" border="0">
                            <tr><td colspan="4"><p style="font-size:20px;font-weight:600;padding-left:12px;">Status</p></td></tr>
                            <tr>
                                <td style="width:25%;">
                                    <div class="dashboard-items" style="padding:20px;margin:auto;width:95%;display:flex">
                                        <div>
                                            <div class="h1-dashboard"><?php echo $doctorrow ? $doctorrow->num_rows : 0; ?></div><br>
                                            <div class="h3-dashboard">Doctors &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
                                        </div>
                                        <div class="btn-icon-back dashboard-icons" style="background-image:url('../img/icons/doctors-hover.svg');"></div>
                                    </div>
                                </td>
                                <td style="width:25%;">
                                    <div class="dashboard-items" style="padding:20px;margin:auto;width:95%;display:flex;">
                                        <div>
                                            <div class="h1-dashboard"><?php echo $patientrow ? $patientrow->num_rows : 0; ?></div><br>
                                            <div class="h3-dashboard">Patients &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
                                        </div>
                                        <div class="btn-icon-back dashboard-icons" style="background-image:url('../img/icons/patients-hover.svg');"></div>
                                    </div>
                                </td>
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
                                    <div class="dashboard-items" style="padding:20px;margin:auto;width:95%;display:flex;padding-top:26px;padding-bottom:26px;">
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
            </tr>

            <tr>
                <td colspan="4">
                    <table width="100%" border="0" class="dashbord-tables">
                        <tr>
                            <td>
                                <p style="padding:10px 0 0 48px;font-size:23px;font-weight:700;color:var(--primarycolor);">
                                    Upcoming Appointments until Next <?php echo h(date("l", strtotime("+1 week"))); ?>
                                </p>
                                <p style="padding-bottom:19px;padding-left:50px;font-size:15px;font-weight:500;color:#212529e3;line-height:20px;">
                                    Here's quick access to upcoming appointments until 7 days.<br>
                                    More details available in @Appointment section.
                                </p>
                            </td>
                            <td>
                                <p style="text-align:right;padding:10px 48px 0 0;font-size:23px;font-weight:700;color:var(--primarycolor);">
                                    Upcoming Sessions until Next <?php echo h(date("l", strtotime("+1 week"))); ?>
                                </p>
                                <p style="padding-bottom:19px;text-align:right;padding-right:50px;font-size:15px;font-weight:500;color:#212529e3;line-height:20px;">
                                    Here's quick access to sessions scheduled within 7 days.<br>
                                    Add/Remove and more features in @Schedule section.
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td width="50%">
                                <center>
                                    <div class="abc scroll" style="height:200px;">
                                        <table width="85%" class="sub-table scrolldown" border="0">
                                            <thead>
                                                <tr>
                                                    <th class="table-headin" style="font-size:12px;">Appointment number</th>
                                                    <th class="table-headin">Patient name</th>
                                                    <th class="table-headin">Doctor</th>
                                                    <th class="table-headin">Session</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $nextweek = date("Y-m-d", strtotime("+1 week"));
                                            $sqlmain = "SELECT a.appoid, s.scheduleid, s.title, d.docname, p.pname,
                                                               s.scheduledate, s.scheduletime, a.apponum, a.appodate
                                                        FROM schedule s
                                                        INNER JOIN appointment a ON s.scheduleid = a.scheduleid
                                                        INNER JOIN patient p     ON p.pid        = a.pid
                                                        INNER JOIN doctor d      ON s.docid      = d.docid
                                                        WHERE s.scheduledate BETWEEN '{$today}' AND '{$nextweek}'
                                                        ORDER BY s.scheduledate DESC";
                                            $result = $database->query($sqlmain);

                                            if (!$result || $result->num_rows === 0) {
                                                echo '<tr><td colspan="4">
                                                        <br><br><br><br>
                                                        <center>
                                                            <img src="../img/notfound.svg" width="25%"><br>
                                                            <p class="heading-main12" style="margin-left:45px;font-size:20px;color:rgb(49,49,49)">We couldn\'t find anything!</p>
                                                            <a class="non-style-link" href="appointment.php">
                                                                <button class="login-btn btn-primary-soft btn" style="display:flex;justify-content:center;align-items:center;margin-left:20px;">
                                                                    &nbsp; Show all Appointments &nbsp;
                                                                </button>
                                                            </a>
                                                        </center>
                                                        <br><br><br><br>
                                                     </td></tr>';
                                            } else {
                                                while ($row = $result->fetch_assoc()) {
                                                    echo '<tr>
                                                            <td style="text-align:center;font-size:23px;font-weight:500;color:var(--btnnicetext);padding:20px;">'
                                                            . h($row['apponum']) . '</td>
                                                            <td style="font-weight:600;">&nbsp;' . h(mb_strimwidth($row['pname'],0,25,'')) . '</td>
                                                            <td style="font-weight:600;">&nbsp;' . h(mb_strimwidth($row['docname'],0,25,'')) . '</td>
                                                            <td>' . h(mb_strimwidth($row['title'],0,15,'')) . '</td>
                                                          </tr>';
                                                }
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </center>
                            </td>

                            <td width="50%" style="padding:0;">
                                <center>
                                    <div class="abc scroll" style="height:200px;padding:0;margin:0;">
                                        <table width="85%" class="sub-table scrolldown" border="0">
                                            <thead>
                                                <tr>
                                                    <th class="table-headin">Session Title</th>
                                                    <th class="table-headin">Doctor</th>
                                                    <th class="table-headin">Scheduled Date & Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $sqlmain = "SELECT s.scheduleid, s.title, d.docname, s.scheduledate, s.scheduletime, s.nop
                                                        FROM schedule s
                                                        INNER JOIN doctor d ON s.docid = d.docid
                                                        WHERE s.scheduledate BETWEEN '{$today}' AND '{$nextweek}'
                                                        ORDER BY s.scheduledate DESC";
                                            $result = $database->query($sqlmain);

                                            if (!$result || $result->num_rows === 0) {
                                                echo '<tr><td colspan="4">
                                                        <br><br><br><br>
                                                        <center>
                                                            <img src="../img/notfound.svg" width="25%"><br>
                                                            <p class="heading-main12" style="margin-left:45px;font-size:20px;color:rgb(49,49,49)">We couldn\'t find anything!</p>
                                                            <a class="non-style-link" href="schedule.php">
                                                                <button class="login-btn btn-primary-soft btn" style="display:flex;justify-content:center;align-items:center;margin-left:20px;">
                                                                    &nbsp; Show all Sessions &nbsp;
                                                                </button>
                                                            </a>
                                                        </center>
                                                        <br><br><br><br>
                                                     </td></tr>';
                                            } else {
                                                while ($row = $result->fetch_assoc()) {
                                                    echo '<tr>
                                                            <td style="padding:20px;">&nbsp;' . h(mb_strimwidth($row['title'],0,30,'')) . '</td>
                                                            <td>' . h(mb_strimwidth($row['docname'],0,20,'')) . '</td>
                                                            <td style="text-align:center;">' . h(substr($row['scheduledate'],0,10)) . ' ' . h(substr($row['scheduletime'],0,5)) . '</td>
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

                        <tr>
                            <td>
                                <center><a href="appointment.php" class="non-style-link"><button class="btn-primary btn" style="width:85%">Show all Appointments</button></a></center>
                            </td>
                            <td>
                                <center><a href="schedule.php" class="non-style-link"><button class="btn-primary btn" style="width:85%">Show all Sessions</button></a></center>
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
