<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$admin = current_admin();
$active = 'campaigns.php';

$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['verify'])) $pdo->prepare("UPDATE campaigns SET admin_status='verified', id_verified=1, docs_verified=1 WHERE id=?")->execute([$id]);
    if (isset($_POST['reject']))  $pdo->prepare("UPDATE campaigns SET admin_status='rejected' WHERE id=?")->execute([$id]);
    if (isset($_POST['toggle_id_doc'])) {
        $col = $_POST['toggle_id_doc'] === 'id' ? 'id_verified' : 'docs_verified';
        $pdo->prepare("UPDATE campaigns SET $col = 1 - $col WHERE id=?")->execute([$id]);
    }
    flash('success', 'Campaign updated.');
    redirect('view_campaign.php?id=' . $id);
}

$stmt = $pdo->prepare("SELECT c.*, u.id uid, u.full_name, u.email, u.student_id, u.department, u.batch,
        u.phone, u.avatar, u.trust_score, u.uiu_id_verified, u.status user_status, u.created_at joined
    FROM campaigns c JOIN users u ON u.id = c.organizer_id WHERE c.id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) { http_response_code(404); die('Campaign not found.'); }

$don = $pdo->prepare("SELECT d.*, u.full_name, u.email FROM donations d JOIN users u ON u.id=d.donor_id
    WHERE d.campaign_id=? ORDER BY d.created_at DESC");
$don->execute([$id]);
$donations = $don->fetchAll();

$pct = min(100, round($c['raised_amount'] / max(1, $c['goal_amount']) * 100));

$page_title = 'Campaign #' . $id;
$base = '../';
$use_admin_css = true;
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="admin-main">
    <div class="admin-topbar">
      <h1><a href="campaigns.php" style="color:var(--text-muted);"><i class="fa-solid fa-arrow-left"></i></a> &nbsp;Campaign #<?= $id ?></h1>
      <div class="table-actions">
        <?php if ($c['admin_status'] !== 'verified'): ?>
          <form method="post" class="inline"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="verify" value="1"><button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-circle-check"></i> Verify Campaign</button></form>
        <?php endif; ?>
        <?php if ($c['admin_status'] !== 'rejected'): ?>
          <form method="post" class="inline"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="reject" value="1"><button class="btn btn-outline btn-sm" type="submit" style="color:#dc2626;"><i class="fa-solid fa-ban"></i> Reject</button></form>
        <?php endif; ?>
      </div>
    </div>

    <div class="admin-content">
      <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

      <div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;">
        <div style="flex:1;min-width:340px;display:flex;flex-direction:column;gap:20px;">

          <div class="admin-table-card">
            <div class="head"><h3>Campaign Content (as students see it)</h3>
              <?php
                $m = ['verified'=>'pill-green','pending'=>'pill-orange','rejected'=>'pill-red'][$c['admin_status']];
                echo "<span class=\"pill $m\">" . ucfirst($c['admin_status']) . "</span>";
              ?>
            </div>
            <?php if ($c['image']): ?>
              <img src="<?= e($c['image']) ?>" alt="" style="width:100%;height:220px;object-fit:cover;">
            <?php endif; ?>
            <div style="padding:20px;">
              <h2 style="margin:0 0 10px;font-size:19px;"><?= e($c['title']) ?></h2>
              <p class="post-desc"><?= nl2br(e($c['description'])) ?></p>

              <div class="progress-bar"><div style="width:<?= $pct ?>%"></div></div>
              <div class="campaign-stats">
                <span><strong><?= taka($c['raised_amount']) ?></strong> raised of <?= taka($c['goal_amount']) ?></span>
                <span><?= $pct ?>% funded · <?= count($donations) ?> donors · <?= $c['days_left'] ?> days left</span>
              </div>
              <p style="font-size:12px;color:var(--text-muted);margin:0;">Created <?= date('M d, Y \a\t h:i A', strtotime($c['created_at'])) ?></p>
            </div>
          </div>

          <div class="admin-table-card">
            <div class="head"><h3>Authenticity Checks</h3></div>
            <table class="admin-table">
              <tr><th>Check</th><th>Status</th><th>Action</th></tr>
              <tr>
                <td>Organizer UIU Student ID</td>
                <td><span class="pill <?= $c['id_verified']?'pill-green':'pill-orange' ?>"><?= $c['id_verified']?'Verified':'Not checked' ?></span></td>
                <td><form method="post" class="inline"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="toggle_id_doc" value="id"><button class="link-btn" type="submit">Toggle</button></form></td>
              </tr>
              <tr>
                <td>Supporting Documents</td>
                <td><span class="pill <?= $c['docs_verified']?'pill-green':'pill-orange' ?>"><?= $c['docs_verified']?'Verified':'Not checked' ?></span></td>
                <td><form method="post" class="inline"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="toggle_id_doc" value="docs"><button class="link-btn" type="submit">Toggle</button></form></td>
              </tr>
            </table>
          </div>

          <div class="admin-table-card">
            <div class="head"><h3>Donation Ledger (<?= count($donations) ?>)</h3>
              <span style="font-size:12.5px;color:var(--text-muted);">Total: <strong><?= taka($c['raised_amount']) ?></strong></span>
            </div>
            <table class="admin-table">
              <tr><th>Donor</th><th>Public Name</th><th>Amount</th><th>Txn Ref</th><th>Date</th></tr>
              <?php foreach ($donations as $d): ?>
              <tr>
                <td><div class="mini-user"><div><div class="n"><?= e($d['full_name']) ?></div><div class="s"><?= e($d['email']) ?></div></div></div></td>
                <td><?= $d['anonymous'] ? '<span class="pill pill-gray">Anonymous</span>' : e($d['full_name']) ?></td>
                <td><?= taka($d['amount']) ?></td>
                <td style="color:var(--text-muted);"><?= e($d['txn_ref']) ?></td>
                <td><?= date('M d, Y h:i A', strtotime($d['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$donations): ?><tr><td colspan="5" style="color:var(--text-muted);">No donations yet.</td></tr><?php endif; ?>
            </table>
          </div>
        </div>

        <div style="width:320px;flex-shrink:0;display:flex;flex-direction:column;gap:20px;">
          <div class="admin-table-card">
            <div class="head"><h3>Organized By</h3></div>
            <div style="padding:18px;">
              <div class="mini-user" style="margin-bottom:14px;">
                <img src="<?= e($c['avatar']) ?>" style="width:46px;height:46px;">
                <div><div class="n" style="font-size:14px;"><?= e($c['full_name']) ?></div><div class="s"><?= e($c['email']) ?></div></div>
              </div>
              <div class="info-row"><span>Student ID</span><span class="val"><?= e($c['student_id']) ?></span></div>
              <div class="info-row"><span>Department</span><span class="val"><?= e($c['department']) ?> · '<?= substr($c['batch'],-2) ?></span></div>
              <div class="info-row"><span>Phone</span><span class="val"><?= e($c['phone'] ?: '—') ?></span></div>
              <div class="info-row"><span>Trust Score</span><span class="val orange"><?= number_format($c['trust_score'],1) ?> / 5</span></div>
              <div class="info-row"><span>UIU ID</span><span class="val <?= $c['uiu_id_verified']?'green':'' ?>"><?= $c['uiu_id_verified'] ? 'Verified' : 'Not verified' ?></span></div>
              <div class="info-row"><span>Account</span><span class="val"><?= ucfirst($c['user_status']) ?></span></div>
              <a href="view_user.php?id=<?= $c['uid'] ?>" class="btn btn-primary btn-block btn-sm" style="margin-top:14px;">
                <i class="fa-solid fa-id-card"></i> View Full Credentials
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
