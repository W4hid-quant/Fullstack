<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$u = current_user();
$active = 'crowdfunding.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT c.*, u.full_name, u.department, u.batch, u.uiu_id_verified
    FROM campaigns c JOIN users u ON u.id = c.organizer_id WHERE c.id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) { http_response_code(404); die('Campaign not found.'); }

$formError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['donate'])) {
    csrf_check();
    $amount = (float)($_POST['amount'] ?? 0);
    $anon   = isset($_POST['anonymous']) ? 1 : 0;
    if ($amount >= 50) {
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO donations (campaign_id, donor_id, amount, anonymous, txn_ref) VALUES (?,?,?,?,?)")
            ->execute([$id, $u['id'], $amount, $anon, gen_txn_ref()]);
        $pdo->prepare("UPDATE campaigns SET raised_amount = raised_amount + ? WHERE id=?")->execute([$amount, $id]);
        $pdo->commit();
        flash('success', 'Thank you! Your donation of ' . taka($amount) . ' was received.');
        redirect('campaign_details.php?id=' . $id . '#ledger');
    } else {
        $formError = 'Minimum donation amount is ৳50.';
    }
}

$stmt->execute([$id]);
$c = $stmt->fetch();
$pct = min(100, round($c['raised_amount'] / max(1,$c['goal_amount']) * 100));

$donStmt = $pdo->prepare("SELECT d.*, u.full_name FROM donations d JOIN users u ON u.id = d.donor_id WHERE d.campaign_id=? ORDER BY d.created_at DESC");
$donStmt->execute([$id]);
$donations = $donStmt->fetchAll();

$page_title = $c['title'];
include __DIR__ . '/includes/header.php';
?>
<div class="app-shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main-area">
    <div class="page-header">
      <a href="crowdfunding.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i></a>
      <h1>Campaign Details</h1>
    </div>

    <div class="detail-wrap">
      <div class="detail-main">
        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

        <div class="post-image">
          <img src="<?= e($c['image'] ?: 'https://images.unsplash.com/photo-1554774853-b415df9eeb92?auto=format&fit=crop&w=900&q=60') ?>" alt="">
          <span class="image-badge <?= $c['admin_status']!=='verified'?'pending':'' ?>"><?= $c['admin_status']==='verified' ? 'Verified Campaign' : 'Pending Verification' ?></span>
        </div>

        <div class="card">
          <h2 style="margin:0 0 8px;font-size:20px;"><?= e($c['title']) ?></h2>
          <p style="font-size:13.5px;color:var(--text-muted);margin-bottom:16px;">Organized by <strong><?= e($c['full_name']) ?></strong> · <?= e($c['department']) ?> '<?= substr($c['batch'],-2) ?> <?= $c['uiu_id_verified'] ? '<span class="badge-verified"><i class="fa-solid fa-circle-check"></i> UIU ID Verified</span>' : '' ?></p>
          <p class="post-desc"><?= nl2br(e($c['description'])) ?></p>

          <div class="card" style="background:var(--bg);border:none;">
            <h3 style="font-size:14px;"><i class="fa-solid fa-file-shield"></i> Authenticity Verification</h3>
            <div class="tag-row">
              <span class="tag"><i class="fa-solid fa-id-card"></i> UIU Student ID <?= $c['id_verified'] ? '✓' : '…' ?></span>
              <span class="tag"><i class="fa-solid fa-file-medical"></i> Supporting Documents <?= $c['docs_verified'] ? '✓' : '…' ?></span>
              <span class="tag"><i class="fa-solid fa-user-shield"></i> Admin Panel <?= $c['admin_status']==='verified' ? 'Approved ✓' : 'Reviewing' ?></span>
            </div>
          </div>
        </div>

        <div class="card" id="ledger">
          <h3><i class="fa-solid fa-list"></i> Public Donation Ledger (Transparency)</h3>
          <table class="simple-table">
            <tr><th>Donor</th><th>Amount</th><th>Date</th><th>Txn ID</th></tr>
            <?php foreach (array_slice($donations,0,25) as $d): ?>
              <tr>
                <td><?= $d['anonymous'] ? 'Anonymous Donor' : e($d['full_name']) ?></td>
                <td><?= taka($d['amount']) ?></td>
                <td><?= date('M d, Y', strtotime($d['created_at'])) ?></td>
                <td style="color:var(--text-muted);"><?= e($d['txn_ref']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$donations): ?><tr><td colspan="4" style="color:var(--text-muted);">No donations yet — be the first!</td></tr><?php endif; ?>
          </table>
          <?php if (count($donations) > 25): ?><p style="text-align:center;margin-top:12px;"><a href="#" style="color:var(--orange);font-weight:600;">View all <?= count($donations) ?> donations</a></p><?php endif; ?>
        </div>
      </div>

      <div class="detail-side">
        <div class="card">
          <div style="font-size:26px;font-weight:800;"><?= taka($c['raised_amount']) ?></div>
          <div style="font-size:13px;color:var(--text-muted);margin-bottom:14px;">raised of <?= taka($c['goal_amount']) ?></div>
          <div class="progress-bar"><div style="width:<?= $pct ?>%"></div></div>
          <div class="campaign-stats"><span><?= count($donations) ?> donors</span><span><?= $pct ?>% funded · <?= $c['days_left'] ?> days left</span></div>

          <?php if ($formError): ?><div class="alert alert-error"><?= e($formError) ?></div><?php endif; ?>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="donate" value="1">
            <div class="toggle-group">
              <input type="radio" name="preset" value="500" id="p1" onclick="document.getElementById('amt').value=500"><label for="p1">৳500</label>
              <input type="radio" name="preset" value="1000" id="p2" checked onclick="document.getElementById('amt').value=1000"><label for="p2">৳1,000</label>
              <input type="radio" name="preset" value="2500" id="p3" onclick="document.getElementById('amt').value=2500"><label for="p3">৳2,500</label>
            </div>
            <div class="form-group"><input type="number" id="amt" name="amount" value="1000" min="50" placeholder="Custom amount"></div>
            <div class="form-group"><label style="display:flex;align-items:center;gap:8px;font-weight:500;"><input type="checkbox" name="anonymous" style="width:auto;"> Donate anonymously</label></div>
            <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-heart"></i> Donate Now</button>
          </form>
          <p style="font-size:11px;color:var(--text-muted);text-align:center;margin-top:10px;">Processed securely via bKash / Nagad / Card gateway</p>
        </div>

        <div class="safety-box">
          <i class="fa-solid fa-shield-halved"></i>
          <h4>Verified & Transparent</h4>
          <p>Every campaign is ID-checked and admin-reviewed before publishing. All donations are logged on a public ledger so funds can never be misused unnoticed.</p>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
