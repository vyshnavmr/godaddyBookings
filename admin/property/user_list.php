<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

/* Only the main admin can view registered users - a manager
   account should never reach this even if they guess the URL */

if (isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1) {
    header("Location: ../dashboard.php");
    exit();
}

require_once "../../config/db.php";

/*====================================================
SEARCH & PAGINATION
====================================================*/

$searchUser = trim($_GET['search_user'] ?? "");

$perPage = 50;

$page = max(1, (int)($_GET['page'] ?? 1));


/*====================================================
USER LIST - with booking count per user
====================================================*/

$whereClause = " WHERE 1=1 ";

if ($searchUser != "") {

    $escaped = mysqli_real_escape_string($conn, $searchUser);

    $whereClause .= " AND (u.name LIKE '%$escaped%' OR u.email LIKE '%$escaped%' OR u.phone LIKE '%$escaped%')";

}


/*====================================================
COUNT + PAGINATION MATH
====================================================*/

$countResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users u" . $whereClause);

$totalUsers = (int)(mysqli_fetch_assoc($countResult)['total'] ?? 0);

$totalPages = max(1, (int)ceil($totalUsers / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

function buildUserPageUrl($pageNum) {

    $params = $_GET;

    $params['page'] = $pageNum;

    return "user_list.php?" . http_build_query($params);

}

$sql = "

SELECT

u.id,

u.name,

u.email,

u.phone,

u.created_at,

COUNT(b.id) AS booking_count

FROM users u

LEFT JOIN bookings b ON b.user_id = u.id

" . $whereClause . "

GROUP BY u.id ORDER BY u.created_at DESC

LIMIT $perPage OFFSET $offset

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
    .filters {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .filters input {
        flex: 1 1 320px;
        padding: 12px 16px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
    }

    .user-name-link {
        color: #4a6cf7;
        text-decoration: none;
        font-weight: 600;
    }

    .user-name-link:hover {
        text-decoration: underline;
    }

    .filters .btn-search {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 28px;
        border: none;
        border-radius: 8px;
        background: #4a6cf7;
        color: #fff;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .filters .btn-search:hover {
        background: #3a5ce0;
    }

    .user-cell {
        font-weight: 600;
        color: #333;
    }

    .booking-count-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        background: #eef2ff;
        color: #4a6cf7;
        font-size: 13px;
        font-weight: 600;
    }

    .booking-count-badge.zero {
        background: #f5f5f5;
        color: #999;
    }

    @media (max-width: 600px) {

        .filters {
            flex-direction: column;
            align-items: stretch;
        }

    }

        .copyable-value {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-copy {
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        padding: 4px;
        font-size: 13px;
        transition: color 0.15s ease;
    }

    .btn-copy:hover {
        color: #4a6cf7;
    }

    .btn-copy.copied {
        color: #1e7e42;
    }

    .no-value {
        color: #bbb;
    }

        .btn-reset-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #eef2ff;
        color: #4a6cf7;
        border: none;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
    }

    .btn-reset-link:hover {
        background: #dbe4ff;
    }

    .reset-link-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 20px;
    }

    .reset-link-modal {
        position: relative;
        background: #fff;
        border-radius: 14px;
        padding: 30px;
        max-width: 440px;
        width: 100%;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
    }

    .reset-link-modal h3 {
        font-size: 18px;
        margin-bottom: 10px;
        color: #333;
    }

    .reset-link-note {
        font-size: 13px;
        color: #666;
        margin-bottom: 18px;
    }

    .reset-link-box {
        display: flex;
        gap: 8px;
    }

    .reset-link-box input {
        flex: 1;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 13px;
        font-family: monospace;
    }

    .reset-link-box button {
        background: #4a6cf7;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 0 16px;
        cursor: pointer;
    }

</style>

<div class="container">

    <div class="page-header">

        <h1>Registered Users</h1>

    </div>

    <form method="GET" class="filters">

        <input
            type="text"
            name="search_user"
            placeholder="Search by name, email, or phone..."
            value="<?= htmlspecialchars($searchUser); ?>">

        <button type="submit" class="btn-search">

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

                    <th>Bookings</th>

                    <th>Joined</th>

                    <th>Action</th>

                </tr>

            </thead>

            <tbody>

                <?php if (mysqli_num_rows($result) > 0) { ?>

                    <?php while ($user = mysqli_fetch_assoc($result)) { ?>

                        <tr>

                            <td class="user-cell">

                                <a href="bookings.php?search_customer=<?= urlencode($user['name']); ?>" class="user-name-link">

                                    <?= htmlspecialchars($user['name']); ?>

                                </a>

                            </td>

                            <td>

                                <span class="copyable-value">

                                    <?= htmlspecialchars($user['email']); ?>

                                    <button type="button" class="btn-copy" data-copy-value="<?= htmlspecialchars($user['email']); ?>" aria-label="Copy email">

                                        <i class="fa-regular fa-copy"></i>

                                    </button>

                                </span>

                            </td>

                            <td>

                                <span class="copyable-value">

                                    <?= htmlspecialchars($user['phone']); ?>

                                    <button type="button" class="btn-copy" data-copy-value="<?= htmlspecialchars($user['phone']); ?>" aria-label="Copy phone number">

                                        <i class="fa-regular fa-copy"></i>

                                    </button>

                                </span>

                            </td>

                            <td>

                                <span class="booking-count-badge <?= $user['booking_count'] == 0 ? 'zero' : ''; ?>">

                                    <?= (int)$user['booking_count']; ?>

                                </span>

                            </td>

                            <td><?= date("d M Y", strtotime($user['created_at'])); ?></td>

                            <td>

                                <button type="button" class="btn-reset-link" data-user-id="<?= $user['id']; ?>">

                                    <i class="fa-solid fa-key"></i> Reset Link

                                </button>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>

                        <td colspan="6">

                            <div class="empty-state">

                                <i class="fa-solid fa-users"></i>

                                <h3>No Users Found</h3>

                                <p><?= $searchUser != "" ? 'Try a different search term.' : 'No one has signed up yet.'; ?></p>

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

                <a href="<?= buildUserPageUrl($page - 1); ?>" class="admin-pagination-btn">

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

                    <a href="<?= buildUserPageUrl(1); ?>" class="admin-pagination-number">1</a>

                    <?php if ($windowStart > 2) { ?>
                        <span class="admin-pagination-ellipsis">…</span>
                    <?php } ?>

                <?php } ?>

                <?php for ($p = $windowStart; $p <= $windowEnd; $p++) { ?>

                    <a href="<?= buildUserPageUrl($p); ?>" class="admin-pagination-number <?= ($p == $page) ? 'active' : ''; ?>"><?= $p; ?></a>

                <?php } ?>

                <?php if ($windowEnd < $totalPages) { ?>

                    <?php if ($windowEnd < $totalPages - 1) { ?>
                        <span class="admin-pagination-ellipsis">…</span>
                    <?php } ?>

                    <a href="<?= buildUserPageUrl($totalPages); ?>" class="admin-pagination-number"><?= $totalPages; ?></a>

                <?php } ?>

            </div>

            <?php if ($page < $totalPages) { ?>

                <a href="<?= buildUserPageUrl($page + 1); ?>" class="admin-pagination-btn">

                    Next <i class="fa-solid fa-chevron-right"></i>

                </a>

            <?php } else { ?>

                <span class="admin-pagination-btn admin-pagination-disabled">

                    Next <i class="fa-solid fa-chevron-right"></i>

                </span>

            <?php } ?>

        </div>

        <p class="admin-pagination-info">Page <?= $page; ?> of <?= $totalPages; ?> (<?= $totalUsers; ?> total users)</p>

    <?php } ?>

     <!-- ================= RESET LINK MODAL ================= -->

    <div class="reset-link-overlay" id="resetLinkOverlay" style="display:none;">

        <div class="reset-link-modal">

            <button type="button" class="export-modal-close" id="resetLinkClose" aria-label="Close">

                <i class="fa-solid fa-xmark"></i>

            </button>

            <h3>Password Reset Link</h3>

            <p class="reset-link-note">Share this link with the user. It expires at <strong id="resetLinkExpiry"></strong> (10 minutes from now).</p>

            <div class="reset-link-box">

                <input type="text" id="resetLinkInput" readonly>

                <button type="button" id="resetLinkCopyBtn">

                    <i class="fa-regular fa-copy"></i>

                </button>

            </div>

        </div>

    </div>

</div>

<script>
        document.querySelectorAll(".btn-copy").forEach(function (button) {

        button.addEventListener("click", function () {

            const value = this.dataset.copyValue;

            navigator.clipboard.writeText(value).then(() => {

                const icon = this.querySelector("i");

                this.classList.add("copied");

                icon.classList.remove("fa-copy");
                icon.classList.add("fa-check");

                setTimeout(() => {

                    this.classList.remove("copied");
                    icon.classList.remove("fa-check");
                    icon.classList.add("fa-copy");

                }, 1500);

            }).catch(() => {

                alert("Couldn't copy to clipboard. Please copy manually.");

            });

        });

    });

        document.querySelectorAll(".btn-reset-link").forEach(function (button) {

        button.addEventListener("click", function () {

            const userId = this.dataset.userId;

            fetch("generate_reset_link.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "user_id=" + encodeURIComponent(userId)
            })

                .then(res => res.json())

                .then(data => {

                    if (data.success) {

                        document.getElementById("resetLinkInput").value = data.link;
                        document.getElementById("resetLinkExpiry").textContent = data.expires_at;
                        document.getElementById("resetLinkOverlay").style.display = "flex";

                    } else {

                        alert(data.message || "Failed to generate link.");

                    }

                })

                .catch(() => alert("Failed to generate link. Please try again."));

        });

    });

    document.getElementById("resetLinkCopyBtn").addEventListener("click", function () {

        const input = document.getElementById("resetLinkInput");

        input.select();

        navigator.clipboard.writeText(input.value);

        this.innerHTML = '<i class="fa-solid fa-check"></i>';

        setTimeout(() => { this.innerHTML = '<i class="fa-regular fa-copy"></i>'; }, 1500);

    });

    document.getElementById("resetLinkClose").addEventListener("click", function () {

        document.getElementById("resetLinkOverlay").style.display = "none";

    });

</script>

</body>

</html>