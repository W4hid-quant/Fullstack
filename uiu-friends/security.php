<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$u = current_user();
$active = 'security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_verification'])) {
    csrf_check();
    // Simulated document submission -> in a real system this would go to an admin review queue.
    flash('success', 'Your documents were submitted. An admin will verify your UIU ID shortly.');
    redirect('security.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    csrf_check();
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    if (!password_verify($current, $u['password_hash'])) {
        $pwError = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $pwError = 'New password must be at least 6 characters.';
    } else {
        $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        flash('success', 'Password updated successfully.');
        redirect('security.php');
    }
}

$page_title = 'Security & Verification';
include __DIR__ . '/includes/header.php';
?>
<div class="app-shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main-area">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="content-wrap">
      <div class="content-main">
        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

        <div class="card">
          <h3><i class="fa-solid fa-id-card"></i> UIU ID Verification</h3>
          <?php if ($u['uiu_id_verified']): ?>
            <div class="alert alert-success">Your UIU Student ID is verified. You have full access to lending, borrowing, and campaign features.</div>
          <?php else: ?>
            <div class="alert alert-info">Verification is pending. Upload your Student ID card and university email to get verified by an admin.</div>
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="submit_verification" value="1">
              <div class="form-group"><label>Upload Student ID (image/PDF)</label><input type="file" name="id_doc"></div>
              <button class="btn btn-primary" type="submit">Submit for Verification</button>
            </form>
          <?php endif; ?>
        </div>

        <div class="card">
          <h3>Verification Checklist</h3>
          <ul class="checklist">
            <li class="<?= $u['uiu_id_verified']?'':'pending' ?>"><i class="fa-solid fa-circle-<?= $u['uiu_id_verified']?'check':'exclamation' ?>"></i> UIU Student ID</li>
            <li class="<?= $u['email_verified']?'':'pending' ?>"><i class="fa-solid fa-circle-<?= $u['email_verified']?'check':'exclamation' ?>"></i> University Email</li>
            <li class="<?= $u['phone_verified']?'':'pending' ?>"><i class="fa-solid fa-circle-<?= $u['phone_verified']?'check':'exclamation' ?>"></i> Phone Number</li>
            <li class="<?= $u['payment_linked']?'':'pending' ?>"><i class="fa-solid fa-circle-<?= $u['payment_linked']?'check':'exclamation' ?>"></i> Payment Account Linked</li>
          </ul>
        </div>

        <div class="card">
          <h3><i class="fa-solid fa-key"></i> Change Password</h3>
          <?php if (!empty($pwError)): ?><div class="alert alert-error"><?= e($pwError) ?></div><?php endif; ?>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="change_password" value="1">
            <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
            <div class="form-group"><label>New Password</label><input type="password" name="new_password" required></div>
            <button class="btn btn-primary" type="submit">Update Password</button>
          </form>
        </div>
      </div>

      <div class="content-side">
        <div class="safety-box">
          <i class="fa-solid fa-shield-halved"></i>
          <h4>Stay Safe & Secure</h4>
          <p>We verify every student ID and use digital agreements to protect you from fraud. Never share OTPs, passwords, or bank PINs with anyone — including "lenders" or "borrowers" on this platform.</p>
        </div>
        <div class="card">
          <h3>Agreement Policy</h3>
          <p style="font-size:12.5px;color:var(--text-muted);">You accepted the UIU Friends Lender & Borrower Agreement Policy on <?= date('M d, Y', strtotime($u['created_at'])) ?>.</p>
          <a href="#" class="btn btn-outline btn-block btn-sm">Read Full Policy</a>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
