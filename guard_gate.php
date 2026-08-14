<?php
require 'config.php';
requireLogin();
requireRole('guard');

$headcount = getTodayHeadcount();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Gate Entry - Kantayoktok Sporting and Cockpit Arena</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="guard.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>🚪 Gate Entry</h1>
                <p class="user-info">Logged in as: <strong><?php echo $_SESSION['full_name']; ?></strong></p>
            </div>
            <div class="header-actions">
                <button type="button" class="refresh-btn" onclick="window.location.reload()">↻ Refresh</button>
                <a href="logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </div>

        <div class="big-counter">
            <div class="counter-label">People Inside Now</div>
            <div class="counter-value" id="counter"><?php echo $headcount['inside']; ?></div>
        </div>

        <div class="action-controls">
            <div class="quantity-row">
                <label class="quantity-label" for="quantity">Count</label>
                <input class="quantity-input" id="quantity" type="number" min="1" step="1" value="1">
            </div>
            <p class="helper-text">Use this for groups, re-entries, or exits. The app will ask for confirmation before saving.</p>
        </div>

        <div class="buttons">
            <button class="btn btn-enter action-btn" onclick="openConfirmModal('entry')">➕<br>First Entry</button>
            <button class="btn btn-reentry action-btn" onclick="openConfirmModal('reentry')">🔁<br>Re-entry</button>
            <button class="btn btn-exit action-btn" onclick="openConfirmModal('exit')">➖<br>Exit</button>
        </div>

        <div class="stats">
            <div class="stat-box">
                <div class="stat-label">First Entries</div>
                <div class="stat-value" id="entries"><?php echo $headcount['entries']; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Re-entries</div>
                <div class="stat-value" id="reentries"><?php echo $headcount['reentries']; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Exited</div>
                <div class="stat-value" id="exits"><?php echo $headcount['exits']; ?></div>
            </div>
        </div>

        <div id="confirmModal" class="modal-backdrop" style="display:none;">
            <div class="modal-card">
                <h3 id="modalTitle">Confirm action</h3>
                <p id="modalMessage">1 person will ENTER?</p>
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-secondary" onclick="closeConfirmModal()">Cancel</button>
                    <button type="button" class="modal-btn modal-btn-primary" id="modalConfirmBtn" onclick="confirmPendingAction()">Confirm</button>
                </div>
            </div>
        </div>

    </div>

    <script>
    let pendingAction = null;
    let isSubmitting = false;

    function setBusyState(isBusy) {
        const buttons = document.querySelectorAll('.action-btn');
        buttons.forEach((button) => {
            button.disabled = isBusy;
        });

        const quantityInput = document.getElementById('quantity');
        if (quantityInput) {
            quantityInput.disabled = isBusy;
        }
    }

    function openConfirmModal(action) {
        const quantity = Math.max(1, parseInt(document.getElementById('quantity').value || '1', 10) || 1);
        const actionLabel = action === 'entry' ? 'ENTER' : action === 'reentry' ? 'RE-ENTER' : 'EXIT';
        const noun = quantity === 1 ? 'person' : 'people';
        pendingAction = { action, quantity };

        document.getElementById('modalTitle').textContent = 'Confirm action';
        document.getElementById('modalMessage').textContent = `${quantity} ${noun} will ${actionLabel}?`;
        document.getElementById('confirmModal').style.display = 'flex';
    }

    function closeConfirmModal() {
        pendingAction = null;
        document.getElementById('confirmModal').style.display = 'none';
    }

    async function confirmPendingAction() {
        if (!pendingAction || isSubmitting) return;

        const { action, quantity } = pendingAction;
        closeConfirmModal();
        await submitAttendanceAction(action, quantity);
    }

    async function submitAttendanceAction(action, quantity) {
        if (isSubmitting) return;

        isSubmitting = true;
        setBusyState(true);

        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=add_entry&type=${action}&quantity=${encodeURIComponent(quantity)}`
            });

            const data = await response.json();
            if (data.success) {
                document.getElementById('counter').textContent = data.inside;
                document.getElementById('entries').textContent = data.entries;
                document.getElementById('reentries').textContent = data.reentries;
                document.getElementById('exits').textContent = data.exits;

                const counter = document.querySelector('.big-counter');
                const colorMap = {
                    entry: 'linear-gradient(135deg, #28a745 0%, #218838 100%)',
                    reentry: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                    exit: 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)'
                };
                counter.style.background = colorMap[action] || colorMap.entry;
                setTimeout(() => {
                    counter.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                }, 350);
            } else {
                alert(data.error || 'Unable to record this action.');
            }
        } catch (error) {
            console.error('Gate action failed', error);
            alert('The request could not be completed. Please try again.');
        } finally {
            isSubmitting = false;
            setBusyState(false);
        }
    }
    </script>
</body>
</html>