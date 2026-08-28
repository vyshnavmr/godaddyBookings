<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once "../config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM admins WHERE email = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $email);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {

        $admin = mysqli_fetch_assoc($result);

        if (password_verify($password, $admin['password'])) {

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['is_manager'] = (int)$admin['is_manager'];

            /* A manager can be linked to multiple properties */

            $_SESSION['manager_property_ids'] = [];

            if ((int)$admin['is_manager'] === 1) {

                $propSql = "SELECT property_id FROM property_managers WHERE admin_id=?";

                $propStmt = mysqli_prepare($conn, $propSql);

                mysqli_stmt_bind_param($propStmt, "i", $admin['id']);

                mysqli_stmt_execute($propStmt);

                $propResult = mysqli_stmt_get_result($propStmt);

                while ($propRow = mysqli_fetch_assoc($propResult)) {

                    $_SESSION['manager_property_ids'][] = (int)$propRow['property_id'];

                }

            }

            header("Location: dashboard.php");
            exit();

        } else {

            $error = "Invalid Password.";

        }

    } else {

        $error = "Admin not found.";

    }

}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Login</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/login.css">

</head>

<body>

<div class="login-container">

    <div class="login-card">

        <h1>Godaddy Booking</h1>

        <h2>Admin Login</h2>

        <?php if($error != "") { ?>

            <div class="error">

                <?php echo $error; ?>

            </div>

        <?php } ?>

        <form method="POST">

            <input
                type="email"
                name="email"
                placeholder="Email Address"
                required>

            <input
                type="password"
                name="password"
                placeholder="Password"
                required>

            <button type="submit">

                Login

            </button>

        </form>

    </div>

</div>

</body>

</html>