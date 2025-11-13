<?php
// patient/schedule.php
require_once __DIR__ . '/../auth_guard.php';   // enforces login + idle timeout
guard_role('p');

require_once __DIR__ . '/../connection.php';

date_default_timezone_set('Asia/Kolkata');
$today = date('Y-m-d');

$useremail = $_SESSION['user'] ?? '';

// small helper for safe HTML
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ---- fetch current patient profile (for sidebar) ----
$pf = null; $username = 'User'; $userid = null;
if ($st = $database->prepare("SELECT * FROM patient WHERE pemail = ? LIMIT 1")) {
    $st->bind_param('s', $useremail);
    $st->execute();
    $pf = $st->get_result()->fetch_assoc();
    $st->close();
    if ($pf) {
        $username = $pf['pname'] ?? 'User';
        $userid   = $pf['pid']   ?? null;
    }
}

// ---- build schedules query (list future sessions) ----
// default (all future)
$sql = "SELECT s.scheduleid, s.title, s.scheduledate, s.scheduletime, d.docname
        FROM schedule s
        LEFT JOIN doctor d ON d.docid = s.docid
        WHERE s.scheduledate >= ?
        ORDER BY s.scheduledate ASC, s.scheduletime ASC";
$params = [$today];
$types  = 's';

$insertkey = '';
$searchtype = "All";
$q = '';

// handle search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search']) && $_POST['search'] !== '') {
    $insertkey = trim($_POST['search']);
    $searchtype = "Search Result : ";
    $q = '"';

    // match by doctor name OR title OR date (YYYY-MM-DD), safe like
    $like = '%' . $insertkey . '%';

    $sql = "SELECT s.scheduleid, s.title, s.scheduledate, s.scheduletime, d.docname
            FROM schedule s
            LEFT JOIN doctor d ON d.docid = s.docid
            WHERE s.scheduledate >= ?
              AND (
                    d.docname LIKE ?
                 OR s.title   LIKE ?
                 OR s.scheduledate = ?
              )
            ORDER BY s.scheduledate ASC, s.scheduletime ASC";
    $params = [$today, $like, $like, $insertkey];
    $types  = 'ssss';
}

// run query
$result = null;
if ($st = $database->prepare($sql)) {
    $st->bind_param($types, ...$params);
    $st->execute();
    $result = $st->get_result();
    $st->close();
} else {
    die("SQL error: " . h($database->error));
}

// for datalist (doctor names + titles)
$listDoctors = $database->query("SELECT DISTINCT docname FROM doctor ORDER BY docname ASC");
$listTitles  = $database->query("SELECT DISTINCT title   FROM schedule ORDER BY title ASC");
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
    <title>Sessions</title>
    <style>
        .popup{ animation: transitionIn-Y-bottom 0.5s; }
        .sub-table{ animation: transitionIn-Y-bottom 0.5s; }
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
                <td class="menu-btn menu-icon-home">
                    <a href="index.php" class="non-style-link-menu"><div><p class="menu-text">Home</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-doctor">
                    <a href="doctors.php" class="non-style-link-menu"><div><p class="menu-text">All Doctors</p></div></a>
                </td>
            </tr>
            <tr class="menu-row">
                <td class="menu-btn menu-icon-session menu-active menu-icon-session-active">
                    <a href="schedule.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Scheduled Sessions</p></div></a>
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

    <div class="dash-body">
        <table border="0" width="100%" style="border-spacing:0;margin:0;padding:0;margin-top:25px;">
            <tr>
                <td width="13%">
                    <a href="schedule.php">
                        <button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px">
                            <font class="tn-in-text">Back</font>
                        </button>
                    </a>
                </td>
                <td>
                    <form action="" method="post" class="header-search">
                        <input type="search" name="search" class="input-text header-searchbar"
                               placeholder="Search Doctor name or Session Title or Date (YYYY-MM-DD)"
                               list="doctors" value="<?php echo h($insertkey); ?>">&nbsp;&nbsp;

                        <?php
                        echo '<datalist id="doctors">';
                        if ($listDoctors) {
                            while ($row = $listDoctors->fetch_assoc()) {
                                if (!empty($row['docname'])) echo "<option value='".h($row['docname'])."'></option>";
                            }
                        }
                        if ($listTitles) {
                            while ($row = $listTitles->fetch_assoc()) {
                                if (!empty($row['title'])) echo "<option value='".h($row['title'])."'></option>";
                            }
                        }
                        echo '</datalist>';
                        ?>

                        <input type="submit" value="Search" class="login-btn btn-primary btn"
                               style="padding-left:25px;padding-right:25px;padding-top:10px;padding-bottom:10px;">
                    </form>
                </td>
                <td width="15%">
                    <p style="font-size:14px;color:#777;padding:0;margin:0;text-align:right;">Today's Date</p>
                    <p class="heading-sub12" style="padding:0;margin:0;"><?php echo h($today); ?></p>
                </td>
                <td width="10%">
                    <button class="btn-label" style="display:flex;justify-content:center;align-items:center;">
                        <img src="../img/calendar.svg" width="100%">
                    </button>
                </td>
            </tr>

            <tr>
                <td colspan="4" style="padding-top:10px;width:100%;">
                    <p class="heading-main12" style="margin-left:45px;font-size:18px;color:#313131">
                        <?php echo h($searchtype) . " Sessions" . " (" . ($result ? $result->num_rows : 0) . ")"; ?>
                    </p>
                    <p class="heading-main12" style="margin-left:45px;font-size:22px;color:#313131">
                        <?php echo $q . h($insertkey) . $q; ?>
                    </p>
                </td>
            </tr>

            <tr>
                <td colspan="4">
                    <center>
                        <div class="abc scroll">
                            <table width="100%" class="sub-table scrolldown" border="0" style="padding:50px;border:none">
                                <tbody>
                                <?php
                                if (!$result || $result->num_rows === 0) {
                                    echo '<tr><td colspan="4">
                                            <br><br><br><br>
                                            <center>
                                                <img src="../img/notfound.svg" width="25%">
                                                <br>
                                                <p class="heading-main12" style="margin-left:45px;font-size:20px;color:#313131">
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
                                    // display in rows of 3 tiles
                                    $count = 0;
                                    echo "<tr>";
                                    while ($row = $result->fetch_assoc()) {
                                        $scheduleid   = $row['scheduleid'];
                                        $title        = $row['title'] ?? '';
                                        $docname      = $row['docname'] ?? 'Unknown Doctor';
                                        $scheduledate = $row['scheduledate'];
                                        $scheduletime = $row['scheduletime'];

                                        echo '
                                        <td style="width:25%;">
                                            <div class="dashboard-items search-items">
                                                <div style="width:100%">
                                                    <div class="h1-search">'.h(mb_strimwidth($title,0,21,'')).'</div><br>
                                                    <div class="h3-search">'.h(mb_strimwidth($docname,0,30,'')).'</div>
                                                    <div class="h4-search">'.h($scheduledate).'<br>
                                                        Starts: <b>@'.h(substr($scheduletime,0,5)).'</b> (24h)
                                                    </div>
                                                    <br>
                                                    <a href="booking.php?id='.h($scheduleid).'">
                                                        <button class="login-btn btn-primary-soft btn" style="padding-top:11px;padding-bottom:11px;width:100%">
                                                            <font class="tn-in-text">Book Now</font>
                                                        </button>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>';

                                        $count++;
                                        if ($count % 3 === 0) echo "</tr><tr>";
                                    }
                                    echo "</tr>";
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </center>
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
