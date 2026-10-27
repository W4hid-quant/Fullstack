<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$admin = current_admin();
$active = 'reports.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['report_id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['open','reviewing','resolved']) ? $_POST['status'] : 'reviewing';
    $pdo->prepare("UPDATE reports SET status=? WHERE id=?")->execute([$status, $id]);
    redirect('reports.php');
}

$reports = $pdo->query("SELECT r.*, u.full_name, u.email, u.avatar FROM reports r JOIN users u ON u.id=r.reporter_id ORDER BY r.created_at DESC")->fetchAll();
$disputedAgreements = $pdo->query("SELECT a.*, bu.full_name borrower_name, lu.full_name lender_name FROM agreements a
    JOIN users bu ON bu.id=a.borrower_id JOIN users lu ON lu.id=a.lender_id WHERE a.status='disputed'")->fetchAll();

$page_title = 'Reports & Disputes';
$base = '../';
$use_admin_css = true;
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="admin-main">
    <div class="admin-topbar"><h1>Reports & Disputes</h1><div class="mini-user"><img src="<?= e($admin['avatar']) ?>"><span><?= e($admin['full_name']) ?></span></div></div>
    <div class="admin-content">

      <div class="admin-table-card" style="margin-bottom:22px;">
        <div class="head"><h3>Reported Conversations (<?= count($reports) ?>)</h3></div>
        <table class="admin-table">
          <tr><th>Reported By</th><th>Reason</th><th>Status</th><th>Date</th><th>Actions</th></tr>
          <?php foreach ($reports as $r): ?>
          <tr>
            <td><div class="mini-user"><img src="<?= e($r['avatar']) ?>"><div><div class="n"><?= e($r['full_name']) ?></div><div class="s"><?= e($r['email']) ?></div></div></div></td>
            <td><?= e($r['reason']) ?></td>
            <td><?php $m=['open'=>'pill-red','reviewing'=>'pill-orange','resolved'=>'pill-green'][$r['status']]; echo "<span class=\"pill $m\">".ucfirst($r['status'])."</span>"; ?></td>
            <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
            <td class="table-actions">
              <?php if ($r['status'] !== 'reviewing'): ?><form method="post" class="inline"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="report_id" value="<?= $r['id'] ?>"><input type="hidden" name="status" value="reviewing"><button class="link-btn" type="submit">Mark Reviewing</button></form><?php endif; ?>
              <?php if ($r['status'] !== 'resolved'): ?><form method="post" class="inline"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="report_id" value="<?= $r['id'] ?>"><input type="hidden" name="status" value="resolved"><button class="link-btn" type="submit">Resolve</button></form><?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$reports): ?><tr><td colspan="5" style="color:var(--text-muted);">No reports filed.</td></tr><?php endif; ?>
        </table>
      </div>

      <div class="admin-table-card">
        <div class="head"><h3>Disputed Agreements (<?= count($disputedAgreements) ?>)</h3></div>
        <table class="admin-table">
          <tr><th>Borrower</th><th>Lender</th><th>Principal</th><th>Status</th></tr>
          <?php foreach ($disputedAgreements as $a): ?>
          <tr><td><?= e($a['borrower_name']) ?></td><td><?= e($a['lender_name']) ?></td><td><?= taka($a['principal']) ?></td><td><span class="pill pill-red">Disputed</span></td></tr>
          <?php endforeach; ?>
          <?php if (!$disputedAgreements): ?><tr><td colspan="4" style="color:var(--text-muted);">No disputed agreements. 🎉</td></tr><?php endif; ?>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>
