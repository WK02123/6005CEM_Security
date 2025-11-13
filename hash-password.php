<?php
include("connection.php"); // ensure $database is your mysqli connection

// ===== Admin passwords =====
$res = $database->query("SELECT aemail, apassword FROM admin");
while ($row = $res->fetch_assoc()) {
    $email = $row['aemail'];
    $pwd   = $row['apassword'];

    // Only hash if not already hashed (bcrypt hashes start with $2y$ or $2b$)
    if (strncmp($pwd, '$2', 2) !== 0) {
        $hashed = password_hash($pwd, PASSWORD_DEFAULT);

        $st = $database->prepare("UPDATE admin SET apassword=? WHERE aemail=?");
        $st->bind_param("ss", $hashed, $email);
        $st->execute();
        $st->close();

        echo "✅ Hashed admin: $email<br>";
    }
}

// ===== Doctor passwords =====
$res2 = $database->query("SELECT docemail, docpassword FROM doctor");
while ($row = $res2->fetch_assoc()) {
    $email = $row['docemail'];
    $pwd   = $row['docpassword'];

    if (strncmp($pwd, '$2', 2) !== 0) {
        $hashed = password_hash($pwd, PASSWORD_DEFAULT);

        $st = $database->prepare("UPDATE doctor SET docpassword=? WHERE docemail=?");
        $st->bind_param("ss", $hashed, $email);
        $st->execute();
        $st->close();

        echo "✅ Hashed doctor: $email<br>";
    }
}

// ===== Patient passwords =====
$res3 = $database->query("SELECT pemail, ppassword FROM patient");
while ($row = $res3->fetch_assoc()) {
    $email = $row['pemail'];
    $pwd   = $row['ppassword'];

    if (strncmp($pwd, '$2', 2) !== 0) {
        $hashed = password_hash($pwd, PASSWORD_DEFAULT);

        $st = $database->prepare("UPDATE patient SET ppassword=? WHERE pemail=?");
        $st->bind_param("ss", $hashed, $email);
        $st->execute();
        $st->close();

        echo "✅ Hashed patient: $email<br>";
    }
}
echo "<br>All done!";
?>
