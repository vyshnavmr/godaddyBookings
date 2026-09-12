<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once "../../config/db.php";

/*====================================================
MANAGER SCOPE - same pattern as bookings.php
====================================================*/

$isRestrictedManager = isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1;

$managerPropertyIds = $_SESSION['manager_property_ids'] ?? [];


/*====================================================
REVIEWS QUERY
====================================================*/

$sql = "

SELECT

r.*,

u.name AS reviewer_name,

p.title AS property_title,

p.property_code

FROM reviews r

INNER JOIN users u ON r.user_id = u.id

INNER JOIN properties p ON r.property_id = p.id

WHERE 1=1

";

if ($isRestrictedManager) {

    if (!empty($managerPropertyIds)) {

        $idList = implode(",", array_map("intval", $managerPropertyIds));

        $sql .= " AND r.property_id IN ($idList)";

    } else {

        $sql .= " AND r.property_id = 0";

    }

}

$sql .= " ORDER BY r.is_highlighted DESC, r.created_at DESC";

$result = mysqli_query($conn, $sql);


/*====================================================
RENDER PAGE
====================================================*/

$currentPage = "reviews";

include "../includes/header.php";

?>

<link rel="stylesheet" href="../assets/css/property-list.css">

<style>
    .review-comment-cell {
        max-width: 320px;
        white-space: normal;
        color: #555;
        font-size: 13px;
    }

    .review-stars i {
        color: #ddd;
        font-size: 13px;
    }

    .review-stars i.filled {
        color: #f5a623;
    }

    .highlight-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }

    .highlight-switch {
        position: relative;
        width: 42px;
        height: 22px;
        background: #ddd;
        border-radius: 20px;
        transition: background 0.2s ease;
        flex-shrink: 0;
    }

    .highlight-switch::after {
        content: "";
        position: absolute;
        top: 2px;
        left: 2px;
        width: 18px;
        height: 18px;
        background: #fff;
        border-radius: 50%;
        transition: transform 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }

    .highlight-toggle.on .highlight-switch {
        background: #4a6cf7;
    }

    .highlight-toggle.on .highlight-switch::after {
        transform: translateX(20px);
    }

    .highlight-toggle input {
        display: none;
    }

    .highlight-save-note {
        font-size: 11px;
        color: #1e7e42;
        margin-left: 8px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .highlight-save-note.show {
        opacity: 1;
    }

    .row-highlighted {
        background: #fff8e6;
    }
</style>

<div class="container">

    <div class="page-header">

        <h1>Reviews</h1>

    </div>

    <div class="table-container">

        <table>

            <thead>

                <tr>

                    <th>Reviewer</th>

                    <th>Property</th>

                    <th>Rating</th>

                    <th>Comment</th>

                    <th>Date</th>

                    <th>Highlighted</th>

                </tr>

            </thead>

            <tbody>

                <?php if (mysqli_num_rows($result) > 0) { ?>

                    <?php while ($review = mysqli_fetch_assoc($result)) { ?>

                        <tr class="<?= (int)$review['is_highlighted'] === 1 ? 'row-highlighted' : ''; ?>">

                            <td><?= htmlspecialchars($review['reviewer_name']); ?></td>

                            <td>

                                <div class="property-info">

                                    <h4><?= htmlspecialchars($review['property_title']); ?></h4>

                                    <small><?= htmlspecialchars($review['property_code']); ?></small>

                                </div>

                            </td>

                            <td>

                                <div class="review-stars">

                                    <?php for ($s = 1; $s <= 5; $s++) { ?>

                                        <i class="fa-solid fa-star <?= $s <= (int)$review['rating'] ? 'filled' : ''; ?>"></i>

                                    <?php } ?>

                                </div>

                            </td>

                            <td class="review-comment-cell">

                                <?= htmlspecialchars($review['comment'] ?: '—'); ?>

                            </td>

                            <td><?= date("d M Y", strtotime($review['created_at'])); ?></td>

                            <td>

                                <label class="highlight-toggle <?= (int)$review['is_highlighted'] === 1 ? 'on' : ''; ?>" data-review-id="<?= $review['id']; ?>">

                                    <input type="checkbox" <?= (int)$review['is_highlighted'] === 1 ? 'checked' : ''; ?>>

                                    <span class="highlight-switch"></span>

                                </label>

                                <span class="highlight-save-note">Updated</span>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>

                        <td colspan="6">

                            <div class="empty-state">

                                <i class="fa-solid fa-star-half-stroke"></i>

                                <h3>No Reviews Yet</h3>

                                <p>Reviews submitted by guests will appear here.</p>

                            </div>

                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

</div>

<script>
    document.querySelectorAll(".highlight-toggle").forEach(function (toggle) {

        toggle.addEventListener("click", function (e) {

            e.preventDefault();

            const reviewId = this.dataset.reviewId;
            const isCurrentlyOn = this.classList.contains("on");
            const newValue = isCurrentlyOn ? 0 : 1;
            const checkbox = this.querySelector("input");
            const note = this.parentElement.querySelector(".highlight-save-note");

            fetch("reviews_update_status.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "review_id=" + encodeURIComponent(reviewId) +
                      "&is_highlighted=" + encodeURIComponent(newValue)
            })

                .then(function (res) { return res.json(); })

                .then(function (data) {

                    if (data.success) {

                        toggle.classList.toggle("on");
                        checkbox.checked = !isCurrentlyOn;
                        toggle.closest("tr").classList.toggle("row-highlighted");

                        note.classList.add("show");

                        setTimeout(function () {
                            note.classList.remove("show");
                        }, 2000);

                    } else {

                        alert(data.message || "Failed to update.");

                    }

                })

                .catch(function () {

                    alert("Failed to update. Please try again.");

                });

        });

    });
</script>

</body>

</html>