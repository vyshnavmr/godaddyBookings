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
SEARCH & PAGINATION
====================================================*/

$search = trim($_GET['search'] ?? "");

$perPage = 50;

$page = max(1, (int)($_GET['page'] ?? 1));


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
MANAGER LIST - each with the properties they manage.
Search matches name/email/phone directly, or any of the
properties they're assigned to (via HAVING, since the
property list only exists after GROUP_CONCAT runs).
====================================================*/

$searchCondition = "";

if ($search !== "") {

    $escapedSearch = mysqli_real_escape_string($conn, $search);

    $searchCondition = "

    HAVING

        a.name LIKE '%$escapedSearch%'

        OR a.email LIKE '%$escapedSearch%'

        OR a.phone LIKE '%$escapedSearch%'

        OR property_pairs LIKE '%$escapedSearch%'

    ";

}

$baseSql = "

SELECT

a.id,

a.name,

a.email,

a.phone,

a.encrypted_password,

a.created_at,

GROUP_CONCAT(CONCAT(p.id, ':', p.title) SEPARATOR '||') AS property_pairs,

COUNT(pm.property_id) AS property_count

FROM admins a

LEFT JOIN property_managers pm ON pm.admin_id = a.id

LEFT JOIN properties p ON p.id = pm.property_id

WHERE a.is_manager = 1

GROUP BY a.id

" . $searchCondition;


/*====================================================
COUNT + PAGINATION - wrapping baseSql as a subquery since
it already has its own GROUP BY/HAVING, and MySQL doesn't
allow COUNT(*) with HAVING directly at the outer level.
====================================================*/

$countResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM ($baseSql) AS sub");

$totalManagers = (int)(mysqli_fetch_assoc($countResult)['total'] ?? 0);

$totalPages = max(1, (int)ceil($totalManagers / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

function buildManagerPageUrl($pageNum) {

    $params = $_GET;

    $params['page'] = $pageNum;

    return "manager_list.php?" . http_build_query($params);

}

$sql = $baseSql . " ORDER BY a.name ASC LIMIT $perPage OFFSET $offset";

$result = mysqli_query($conn, $sql);


/*====================================================
RENDER PAGE
====================================================*/

$currentPage = "managers";

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

        .unlink-panel-row td {
        background: #f8fafc;
        padding: 18px 20px;
    }

    .unlink-panel {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .unlink-select-all {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        cursor: pointer;
    }

    .unlink-checklist {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .unlink-checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1px solid #ddd;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 13px;
        cursor: pointer;
    }

        .unlink-actions {
        display: flex;
        gap: 10px;
    }

    .btn-cancel-unlink {
        background: #fff;
        color: #555;
        border: 1px solid #ddd;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-cancel-unlink:hover {
        background: #f5f5f5;
    }

    .btn-confirm-unlink {
        align-self: flex-start;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #c0392b;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-confirm-unlink:hover {
        background: #a5321f;
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

    <form method="GET" class="filters" style="margin-bottom:20px;">

        <input type="text" name="search" placeholder="Search by name, email, phone, or property..." value="<?= htmlspecialchars($search); ?>" style="flex:1; min-width:280px;">

        <button type="submit">

            <i class="fa-solid fa-magnifying-glass"></i>

            Search

        </button>

    </form>

    <div class="table-container">

        <table>

            <thead>

                <tr>

                    <th>Name</th>

                    <th>Email</th>

                    <th>Phone</th>

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

                        $managerProperties = [];

                        if (!empty($manager['property_pairs'])) {

                            foreach (explode('||', $manager['property_pairs']) as $pair) {

                                $parts = explode(':', $pair, 2);

                                if (count($parts) === 2) {

                                    $managerProperties[] = ['id' => (int)$parts[0], 'title' => $parts[1]];

                                }

                            }

                        }

                        ?>

                        <tr>

                            <td class="manager-cell"><?= htmlspecialchars($manager['name']); ?></td>

                            <td><?= htmlspecialchars($manager['email']); ?></td>

                            <td><?= !empty($manager['phone']) ? htmlspecialchars($manager['phone']) : '<span class="no-properties">—</span>'; ?></td>

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

                                <?php if (!empty($managerProperties)) { ?>

                                    <span class="property-tags"><?= htmlspecialchars(implode(', ', array_column($managerProperties, 'title'))); ?></span>

                                <?php } else { ?>

                                    <span class="no-properties">No properties assigned</span>

                                <?php } ?>

                            </td>

                            <td><?= date("d M Y", strtotime($manager['created_at'])); ?></td>

                            <td>

                                <?php if (!empty($managerProperties)) { ?>

                                    <button type="button" class="btn-remove unlink-toggle" data-target="unlinkPanel<?= $manager['id']; ?>">

                                        <i class="fa-solid fa-link-slash"></i> Unlink

                                    </button>

                                <?php } else { ?>

                                    <span class="no-properties">—</span>

                                <?php } ?>

                            </td>

                        </tr>

                        <?php if (!empty($managerProperties)) { ?>

                            <tr class="unlink-panel-row" id="unlinkPanel<?= $manager['id']; ?>" style="display:none;">

                                <td colspan="7">

                                    <form action="manager_delete.php" method="POST" class="unlink-panel" onsubmit="return confirm('Unlink the selected propert' + (this.querySelectorAll('input[name=\'property_ids[]\']:checked').length > 1 ? 'ies' : 'y') + ' from this manager?');">

                                        <input type="hidden" name="admin_id" value="<?= $manager['id']; ?>">

                                        <label class="unlink-select-all">

                                            <input type="checkbox" class="select-all-checkbox">

                                            <strong>Select All</strong>

                                        </label>

                                        <div class="unlink-checklist">

                                            <?php foreach ($managerProperties as $mp) { ?>

                                                <label class="unlink-checkbox-item">

                                                    <input type="checkbox" name="property_ids[]" value="<?= $mp['id']; ?>">

                                                    <?= htmlspecialchars($mp['title']); ?>

                                                </label>

                                            <?php } ?>

                                        </div>

                                        <div class="unlink-actions">

                                            <button type="submit" class="btn-confirm-unlink">

                                                <i class="fa-solid fa-link-slash"></i> Unlink Selected

                                            </button>

                                            <button type="button" class="btn-cancel-unlink" data-target="unlinkPanel<?= $manager['id']; ?>">

                                                Cancel

                                            </button>

                                        </div>

                                    </form>

                                </td>

                            </tr>

                        <?php } ?>

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

    <?php if ($totalPages > 1) { ?>

        <div class="admin-pagination">

            <?php if ($page > 1) { ?>

                <a href="<?= buildManagerPageUrl($page - 1); ?>" class="admin-pagination-btn">

                    <i class="fa-solid fa-chevron-left"></i> Previous

                </a>

            <?php } else { ?>

                <span class="admin-pagination-btn admin-pagination-disabled">

                    <i class="fa-solid fa-chevron-left"></i> Previous

                </span>

            <?php } ?>

            <div class="admin-pagination-numbers">

                <?php

                $windowStart = max(1, $page - 2);

                $windowEnd = min($totalPages, $page + 2);

                if ($windowStart > 1) { ?>

                    <a href="<?= buildManagerPageUrl(1); ?>" class="admin-pagination-number">1</a>

                    <?php if ($windowStart > 2) { ?>
                        <span class="admin-pagination-ellipsis">…</span>
                    <?php } ?>

                <?php } ?>

                <?php for ($p = $windowStart; $p <= $windowEnd; $p++) { ?>

                    <a href="<?= buildManagerPageUrl($p); ?>" class="admin-pagination-number <?= ($p == $page) ? 'active' : ''; ?>"><?= $p; ?></a>

                <?php } ?>

                <?php if ($windowEnd < $totalPages) { ?>

                    <?php if ($windowEnd < $totalPages - 1) { ?>
                        <span class="admin-pagination-ellipsis">…</span>
                    <?php } ?>

                    <a href="<?= buildManagerPageUrl($totalPages); ?>" class="admin-pagination-number"><?= $totalPages; ?></a>

                <?php } ?>

            </div>

            <?php if ($page < $totalPages) { ?>

                <a href="<?= buildManagerPageUrl($page + 1); ?>" class="admin-pagination-btn">

                    Next <i class="fa-solid fa-chevron-right"></i>

                </a>

            <?php } else { ?>

                <span class="admin-pagination-btn admin-pagination-disabled">

                    Next <i class="fa-solid fa-chevron-right"></i>

                </span>

            <?php } ?>

        </div>

        <p class="admin-pagination-info">Page <?= $page; ?> of <?= $totalPages; ?> (<?= $totalManagers; ?> total managers)</p>

    <?php } ?>

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

        document.querySelectorAll(".unlink-toggle").forEach(function (button) {

        button.addEventListener("click", function () {

            const panel = document.getElementById(this.dataset.target);

            panel.style.display = (panel.style.display === "none") ? "table-row" : "none";

        });

    });

    document.querySelectorAll(".select-all-checkbox").forEach(function (selectAll) {

        selectAll.addEventListener("change", function () {

            const checklist = this.closest("form").querySelectorAll('input[name="property_ids[]"]');

            checklist.forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });

        });

    });

        document.querySelectorAll(".btn-cancel-unlink").forEach(function (button) {

        button.addEventListener("click", function () {

            const panel = document.getElementById(this.dataset.target);

            panel.style.display = "none";

            /* Reset the checkboxes so reopening the panel later
               doesn't show a stale selection from last time */

            panel.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
                checkbox.checked = false;
            });

        });

    });

</script>

</body>

</html>