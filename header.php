<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? '';
$username = $user['username'] ?? 'User';
$role = $user['pos_name'] ?? 'Staff';

$unreadCount = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE staff_id = ? AND is_read = FALSE");
    $stmt->execute([$_SESSION['user_id']]);
    $unreadCount = (int)$stmt->fetchColumn();
}
?>
<div class="top-header">
    <div class="header-title">
        <h4><?php echo htmlspecialchars($pageTitle); ?></h4>
        <p><?php echo htmlspecialchars($pageSubtitle); ?></p>
    </div>
    <div class="header-right">
        <button class="header-bell-btn" id="notificationBellBtn" data-bs-toggle="modal" data-bs-target="#notificationsModal" title="Notifications">
            🔔
            <?php if ($unreadCount > 0): ?>
                <span class="notification-badge" id="notificationBadge"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span>
            <?php endif; ?>
        </button>
        <div class="header-user">
            <div style="text-align: right;">
                <div style="font-weight: 600; font-size: 0.9rem; color: #fff;"><?php echo htmlspecialchars($username); ?></div>
                <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($role); ?></div>
            </div>
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($username); ?>&background=8a2be2&color=fff" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%;">
        </div>
    </div>
</div>

<!-- Notifications Modal -->
<div class="modal fade" id="notificationsModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable" style="max-width: 500px;">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header border-secondary d-flex justify-content-between align-items-center">
        <h5 class="modal-title">🔔 Notifications</h5>
        <div>
          <button type="button" class="btn btn-sm btn-outline-light me-2" id="markAllReadBtn">Mark all as read</button>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>
      <div class="modal-body p-0" id="notificationsContainer">
        <div class="text-center text-muted py-4">Loading notifications...</div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bellBtn = document.getElementById('notificationBellBtn');
    const notificationsContainer = document.getElementById('notificationsContainer');
    const markAllReadBtn = document.getElementById('markAllReadBtn');

    function loadNotifications() {
        fetch('api_notifications.php?action=list')
            .then(r => r.json())
            .then(data => {
                if (data.notifications.length === 0) {
                    notificationsContainer.innerHTML = '<div class="text-center text-muted py-4">No notifications</div>';
                } else {
                    notificationsContainer.innerHTML = data.notifications.map(n => `
                        <div class="notification-item border-bottom border-secondary p-3 ${n.is_read ? '' : 'bg-dark bg-opacity-50'}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold">${n.type === 'SP' ? '⚠️' : '💰'} ${escapeHtml(n.title)}</h6>
                                    <p class="mb-2 text-light small">${escapeHtml(n.message)}</p>
                                    <small class="text-muted">${new Date(n.created_at).toLocaleString()}</small>
                                </div>
                                ${!n.is_read ? `<button class="btn btn-sm btn-outline-light ms-2 mark-read-btn" data-id="${n.id}">✓</button>` : ''}
                            </div>
                        </div>
                    `).join('');

                    document.querySelectorAll('.mark-read-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const id = this.dataset.id;
                            fetch('api_notifications.php?action=mark_read', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'id=' + id
                            }).then(() => loadNotifications());
                        });
                    });
                }

                const badge = document.getElementById('notificationBadge');
                if (data.unreadCount > 0) {
                    if (!badge) {
                        bellBtn.innerHTML = '🔔 <span class="notification-badge" id="notificationBadge">' + (data.unreadCount > 9 ? '9+' : data.unreadCount) + '</span>';
                    } else {
                        badge.textContent = data.unreadCount > 9 ? '9+' : data.unreadCount;
                    }
                } else {
                    if (badge) badge.remove();
                }
            });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    document.getElementById('notificationsModal').addEventListener('show.bs.modal', loadNotifications);

    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            fetch('api_notifications.php?action=mark_all_read', {
                method: 'POST'
            }).then(() => loadNotifications());
        });
    }

    loadNotifications();
    setInterval(loadNotifications, 30000); // Refresh every 30 seconds
});
</script>
