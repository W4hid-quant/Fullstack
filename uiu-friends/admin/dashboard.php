<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$admin = current_admin();
$active = 'dashboard.php';

$totalUsers = $pdo->query("SELECT COUNT(*) c FROM users WHERE role='user'")->fetch()['c'];
$verifiedUsers = $pdo->query("SELECT COUNT(*) c FROM users WHERE role='user' AND uiu_id_verified=1")->fetch()['c'];
$totalLoanPosts = $pdo->query("SELECT COUNT(*) c FROM loan_posts")->fetch()['c'];
$pendingLoanPosts = $pdo->query("SELECT COUNT(*) c FROM loan_posts WHERE admin_status='pending'")->fetch()['c'];
$totalCampaigns = $pdo->query("SELECT COUNT(*) c FROM campaigns")->fetch()['c'];
$pendingCampaigns = $pdo->query("SELECT COUNT(*) c FROM campaigns WHERE admin_status='pending'")->fetch()['c'];
$totalVolume = $pdo->query("SELECT COALESCE(SUM(amount),0) s FROM transactions")->fetch()['s'];
$totalDonations = $pdo->query("SELECT COALESCE(SUM(amount),0) s FROM donations")->fetch()['s'];
$openReports = $pdo->query("SELECT COUNT(*) c FROM reports WHERE status='open'")->fetch()['c'];

$recentUsers = $pdo->query("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentTx = $pdo->query("SELECT t.*, u1.full_name payer, u2.full_name payee FROM transactions t
    JOIN users u1 ON u1.id=t.payer_id JOIN users u2 ON u2.id=t.payee_id ORDER BY t.created_at DESC LIMIT 6")->fetchAll();

$page_title = 'Admin Dashboard';
$base = '../';
$use_admin_css = true;
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="admin-main">
    <div class="admin-topbar">
      <h1>Dashboard</h1>
      <div class="mini-user"><img src="<?= e($admin['avatar']) ?>"><span><?= e($admin['full_name']) ?></span></div>
    </div>
    <div class="admin-content">

      <div class="kpi-row">
        <div class="kpi-card"><div class="icon" style="background:#fff3e8;color:#f97316;"><i class="fa-solid fa-users"></i></div><div class="num"><?= $totalUsers ?></div><div class="lbl"><?= $verifiedUsers ?> verified students</div></div>
        <div class="kpi-card"><div class="icon" style="background:#eff6ff;color:#2563eb;"><i class="fa-solid fa-hand-holding-dollar"></i></div><div class="num"><?= $totalLoanPosts ?></div><div class="lbl"><?= $pendingLoanPosts ?> pending review</div></div>
        <div class="kpi-card"><div class="icon" style="background:#f0fdf4;color:#16a34a;"><i class="fa-solid fa-bullseye"></i></div><div class="num"><?= $totalCampaigns ?></div><div class="lbl"><?= $pendingCampaigns ?> pending verification</div></div>
        <div class="kpi-card"><div class="icon" style="background:#fef2f2;color:#dc2626;"><i class="fa-solid fa-flag"></i></div><div class="num"><?= $openReports ?></div><div class="lbl">open reports</div></div>
      </div>

      <div class="kpi-row" style="grid-template-columns:1fr 1fr;">
        <div class="kpi-card"><div class="icon" style="background:#fff3e8;color:#f97316;"><i class="fa-solid fa-sack-dollar"></i></div><div class="num"><?= taka($totalVolume) ?></div><div class="lbl">Total loan transaction volume</div></div>
        <div class="kpi-card"><div class="icon" style="background:#fdf2f8;color:#db2777;"><i class="fa-solid fa-heart"></i></div><div class="num"><?= taka($totalDonations) ?></div><div class="lbl">Total crowdfunding raised</div></div>
      </div>

      <div class="admin-table-card" style="margin-bottom:20px;">
        <div class="head"><h3>Recently Joined Students</h3><a href="users.php" class="link-btn">View all →</a></div>
        <table class="admin-table">
          <tr><th>Student</th><th>Department</th><th>Trust Score</th><th>Verification</th><th>Credentials</th></tr>
          <?php foreach ($recentUsers as $ru): ?>
          <tr>
            <td><a href="view_user.php?id=<?= $ru['id'] ?>"><div class="mini-user"><img src="<?= e($ru['avatar']) ?>"><div><div class="n"><?= e($ru['full_name']) ?></div><div class="s"><?= e($ru['email']) ?></div></div></div></a></td>
            <td><?= e($ru['department']) ?> '<?= substr($ru['batch'],-2) ?></td>
            <td><i class="fa-solid fa-star" style="color:#f97316;"></i> <?= number_format($ru['trust_score'],1) ?></td>
            <td><?= $ru['uiu_id_verified'] ? '<span class="pill pill-green">Verified</span>' : '<span class="pill pill-orange">Pending</span>' ?></td>
            <td><a href="view_user.php?id=<?= $ru['id'] ?>" class="link-btn">Review</a></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>

      <div class="admin-table-card">
        <div class="head"><h3>Recent Transactions</h3><a href="transactions.php" class="link-btn">View all →</a></div>
        <table class="admin-table">
          <tr><th>Txn Ref</th><th>Payer → Payee</th><th>Amount</th><th>Method</th><th>Date</th></tr>
          <?php foreach ($recentTx as $t): ?>
          <tr>
            <td><?= e($t['txn_ref']) ?></td>
            <td><?= e($t['payer']) ?> → <?= e($t['payee']) ?></td>
            <td><?= taka($t['amount']) ?></td>
            <td><span class="pill pill-gray"><?= ucfirst($t['payment_method']) ?></span></td>
            <td><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$recentTx): ?><tr><td colspan="5" style="color:var(--text-muted);">No transactions yet.</td></tr><?php endif; ?>
        </table>
      </div>

    </div>
  </div>
</div>
</body>
</html>
