<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

if (isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1) {
    header("Location: ../dashboard.php");
    exit();
}

require_once "../../config/db.php";

$sql = "

SELECT

d.id,

d.destination_name,

d.is_active,

(SELECT COUNT(*) FROM properties WHERE destination_id = d.id) AS property_count

FROM destinations d

ORDER BY d.destination_name ASC

";

$result = mysqli_query($conn, $sql);

$destinations = [];

while ($row = mysqli_fetch_assoc($result)) {

    $destinations[] = $row;

}

$currentPage = "destinations";

include "../includes/header.php";

?>

<link rel="stylesheet" href="../assets/css/property-list.css">

<style>
    .lookup-name-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .lookup-name-input {
        border: 1px solid transparent;
        background: transparent;
        font-weight: 600;
        color: #333;
        font-size: 14px;
        padding: 6px 8px;
        border-radius: 6px;
        width: 200px;
    }

    .lookup-name-input:focus {
        border-color: #4a6cf7;
        background: #fff;
        outline: none;
    }

    .btn-save-name {
        display: none;
        background: #4a6cf7;
        color: #fff;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
    }

    .active-toggle {
        position: relative;
        width: 42px;
        height: 22px;
        background: #ddd;
        border-radius: 20px;
        cursor: pointer;
        transition: background 0.2s ease;
        display: inline-block;
    }

    .active-toggle.on {
        background: #16a34a;
    }

    .active-toggle::after {
        content: "";
        position: absolute;
        top: 2px;
        left: 2px;
        width: 18px;
        height: 18px;
        background: #fff;
        border-radius: 50%;
        transition: transform 0.2s ease;
    }

    .active-toggle.on::after {
        transform: translateX(20px);
    }

    .merge-select {
        padding: 6px 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 12px;
        font-family: 'Poppins', sans-serif;
        max-width: 160px;
    }

    .btn-merge {
        background: #fdeceb;
        color: #c0392b;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
    }

    .btn-merge:hover {
        background: #fbd5d1;
    }

    .row-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .save-note {
        font-size: 11px;
        color: #1e7e42;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .save-note.show {
        opacity: 1;
    }
</style>

<div class="container">

    <div class="page-header">

        <h1>Destinations</h1>

    </div>

    <p style="color:#888; font-size:13px; margin-bottom:20px;">

        Rename a destination to fix a typo, deactivate one you no longer want shown, or merge a duplicate into the correct entry (this moves all its properties over, then removes the duplicate).

    </p>

    <div class="table-container">

        <table>

            <thead>

                <tr>

                    <th>Destination</th>

                    <th>Properties</th>

                    <th>Active</th>

                    <th>Merge Duplicate Into</th>

                </tr>

            </thead>

            <tbody>

                <?php foreach ($destinations as $dest) { ?>

                    <tr data-row-id="<?= $dest['id']; ?>">

                        <td>

                            <div class="lookup-name-cell">

                                <input type="text" class="lookup-name-input" value="<?= htmlspecialchars($dest['destination_name']); ?>" data-original="<?= htmlspecialchars($dest['destination_name']); ?>" data-id="<?= $dest['id']; ?>">

                                <button type="button" class="btn-save-name">Save</button>

                                <span class="save-note">Saved</span>

                            </div>

                        </td>

                        <td><?= (int)$dest['property_count']; ?></td>

                        <td>

                            <span class="active-toggle <?= (int)$dest['is_active'] === 1 ? 'on' : ''; ?>" data-id="<?= $dest['id']; ?>"></span>

                        </td>

                        <td>

                            <div class="row-actions">

                                <select class="merge-select" data-source-id="<?= $dest['id']; ?>">

                                    <option value="">Choose target...</option>

                                    <?php foreach ($destinations as $other) { ?>

                                        <?php if ($other['id'] != $dest['id']) { ?>

                                            <option value="<?= $other['id']; ?>"><?= htmlspecialchars($other['destination_name']); ?></option>

                                        <?php } ?>

                                    <?php } ?>

                                </select>

                                <button type="button" class="btn-merge" data-source-id="<?= $dest['id']; ?>" data-source-name="<?= htmlspecialchars($dest['destination_name']); ?>">

                                    Merge

                                </button>

                            </div>

                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

</div>

<script>

    /* Show Save button only once the text actually changes */

    document.querySelectorAll(".lookup-name-input").forEach(function (input) {

        input.addEventListener("input", function () {

            const saveBtn = this.closest(".lookup-name-cell").querySelector(".btn-save-name");

            saveBtn.style.display = (this.value !== this.dataset.original) ? "inline-block" : "none";

        });

    });

    document.querySelectorAll(".btn-save-name").forEach(function (button) {

        button.addEventListener("click", function () {

            const cell = this.closest(".lookup-name-cell");
            const input = cell.querySelector(".lookup-name-input");
            const note = cell.querySelector(".save-note");

            fetch("destinations_actions.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "action=rename&id=" + encodeURIComponent(input.dataset.id) + "&name=" + encodeURIComponent(input.value)
            })

                .then(res => res.json())

                .then(data => {

                    if (data.success) {

                        input.dataset.original = input.value;
                        this.style.display = "none";
                        note.classList.add("show");
                        setTimeout(() => note.classList.remove("show"), 2000);

                    } else {

                        alert(data.message || "Failed to rename.");

                    }

                })

                .catch(() => alert("Failed to rename. Please try again."));

        });

    });

    document.querySelectorAll(".active-toggle").forEach(function (toggle) {

        toggle.addEventListener("click", function () {

            const newValue = this.classList.contains("on") ? 0 : 1;

            fetch("destinations_actions.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "action=toggle_active&id=" + encodeURIComponent(this.dataset.id) + "&is_active=" + newValue
            })

                .then(res => res.json())

                .then(data => {

                    if (data.success) {

                        this.classList.toggle("on");

                    } else {

                        alert(data.message || "Failed to update.");

                    }

                })

                .catch(() => alert("Failed to update. Please try again."));

        });

    });

    document.querySelectorAll(".btn-merge").forEach(function (button) {

        button.addEventListener("click", function () {

            const sourceId = this.dataset.sourceId;
            const sourceName = this.dataset.sourceName;
            const select = document.querySelector('.merge-select[data-source-id="' + sourceId + '"]');
            const targetId = select.value;

            if (!targetId) {

                alert("Please choose a destination to merge into first.");

                return;

            }

            const targetName = select.options[select.selectedIndex].text;

            if (!confirm('Merge "' + sourceName + '" into "' + targetName + '"? All properties using "' + sourceName + '" will be moved to "' + targetName + '", and "' + sourceName + '" will be deleted. This cannot be undone.')) {

                return;

            }

            fetch("destinations_actions.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "action=merge&source_id=" + encodeURIComponent(sourceId) + "&target_id=" + encodeURIComponent(targetId)
            })

                .then(res => res.json())

                .then(data => {

                    if (data.success) {

                        document.querySelector('tr[data-row-id="' + sourceId + '"]').remove();

                    } else {

                        alert(data.message || "Failed to merge.");

                    }

                })

                .catch(() => alert("Failed to merge. Please try again."));

        });

    });

</script>

</body>

</html>