<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

/* Only the main admin manages managers - a manager account
   should never reach this page even if they guess the URL */

if (isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1) {
    header("Location: ../dashboard.php");
    exit();
}

require_once "../../config/db.php";

require_once "../../config/crypto.php";


/*====================================================
FLASH MESSAGES
====================================================*/

$successMessage = null;
$errorMessage = null;

if (isset($_SESSION['manager_success'])) {
    $successMessage = $_SESSION['manager_success'];
    unset($_SESSION['manager_success']);
}

if (isset($_SESSION['manager_error'])) {
    $errorMessage = $_SESSION['manager_error'];
    unset($_SESSION['manager_error']);
}


/*====================================================
MANAGER LIST - each with the properties they manage
====================================================*/

$sql = "

SELECT

a.id,

a.name,

a.email,

a.encrypted_password,

a.created_at,

GROUP_CONCAT(p.title SEPARATOR ', ') AS property_titles,

COUNT(pm.property_id) AS property_count

FROM admins a

LEFT JOIN property_managers pm ON pm.admin_id = a.id

LEFT JOIN properties p ON p.id = pm.property_id

WHERE a.is_manager = 1

GROUP BY a.id

ORDER BY a.name ASC

";

$result = mysqli_query($conn, $sql);


/*====================================================
RENDER PAGE
====================================================*/

$currentPage = "users";

include "../includes/header.php";

?>

<link rel="stylesheet" href="../assets/css/property-list.css">

<style>
    .alert {
        padding: 14px 18px;
        border-radius: 8px;
        font-size: 14px;
        margin-bottom: 20px;
    }

    .alert.success {
        background: #e7f8ee;
        color: #1e7e42;
    }

    .alert.error {
        background: #fdeceb;
        color: #c0392b;
    }

    .manager-cell {
        font-weight: 600;
        color: #333;
    }

    .property-tags {
        color: #666;
        font-size: 13px;
    }

    .no-properties {
        color: #aaa;
        font-style: italic;
        font-size: 13px;
    }

    .password-cell {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .password-mask {
        font-family: monospace;
        letter-spacing: 2px;
        color: #888;
    }

    .btn-reveal {
        background: none;
        border: none;
        color: #4a6cf7;
        cursor: pointer;
        font-size: 12px;
        padding: 4px 8px;
    }

    .btn-reveal:hover {
        text-decoration: underline;
    }

    .btn-remove {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: none;
        border: none;
        color: #c0392b;
        cursor: pointer;
        font-size: 13px;
        padding: 6px 10px;
    }

    .btn-remove:hover {
        text-decoration: underline;
    }
</style>

<div class="container">

    <div class="page-header">

        <h1>Property Managers</h1>

    </div>

    <?php if ($successMessage) { ?>

        <div class="alert success"><?= htmlspecialchars($successMessage); ?></div>

    <?php } ?>

    <?php if ($errorMessage) { ?>

        <div class="alert error"><?= htmlspecialchars($errorMessage); ?></div>

    <?php } ?>

    <p style="color:#888; font-size:13px; margin-bottom:20px;">

        Managers are created automatically when you add a Manager Email while adding or editing a property. This page lists existing managers and the properties they're linked to.

    </p>

    <div class="table-container">

        <table>

            <thead>

                <tr>

                    <th>Name</th>

                    <th>Email</th>

                    <th>Password</th>

                    <th>Properties Managed</th>

                    <th>Added</th>

                    <th>Action</th>

                </tr>

            </thead>

            <tbody>

                <?php if (mysqli_num_rows($result) > 0) { ?>

                    <?php while ($manager = mysqli_fetch_assoc($result)) { ?>

                        <?php

                        $plainPassword = "";

                        if (!empty($manager['encrypted_password'])) {

                            $plainPassword = decryptManagerPassword($manager['encrypted_password']);

                        }

                        ?>

                        <tr>

                            <td class="manager-cell"><?= htmlspecialchars($manager['name']); ?></td>

                            <td><?= htmlspecialchars($manager['email']); ?></td>

                            <td>

                                <?php if ($plainPassword) { ?>

                                    <div class="password-cell">

                                        <span class="password-mask" data-password="<?= htmlspecialchars($plainPassword); ?>">••••••••</span>

                                        <button type="button" class="btn-reveal">Show</button>

                                    </div>

                                <?php } else { ?>

                                    <span class="no-properties">—</span>

                                <?php } ?>

                            </td>

                            <td>

                                <?php if ($manager['property_count'] > 0) { ?>

                                    <span class="property-tags"><?= htmlspecialchars($manager['property_titles']); ?></span>

                                <?php } else { ?>

                                    <span class="no-properties">No properties assigned</span>

                                <?php } ?>

                            </td>

                            <td><?= date("d M Y", strtotime($manager['created_at'])); ?></td>

                            <td>

                                <form action="manager_delete.php" method="POST" onsubmit="return confirm('Remove this manager account? This cannot be undone.');">

                                    <input type="hidden" name="admin_id" value="<?= $manager['id']; ?>">

                                    <button type="submit" class="btn-remove">

                                        <i class="fa-solid fa-trash"></i> Remove

                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>

                        <td colspan="6">

                            <div class="empty-state">

                                <i class="fa-solid fa-users"></i>

                                <h3>No Managers Yet</h3>

                                <p>Add a Manager Email while creating or editing a property to create one.</p>

                            </div>

                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

</div>

<script>
    document.querySelectorAll(".btn-reveal").forEach(function (button) {

        button.addEventListener("click", function () {

            const mask = this.previousElementSibling;
            const isHidden = mask.textContent === "••••••••";

            mask.textContent = isHidden ? mask.dataset.password : "••••••••";

            this.textContent = isHidden ? "Hide" : "Show";

        });

    });
</script>

</body>

</html>