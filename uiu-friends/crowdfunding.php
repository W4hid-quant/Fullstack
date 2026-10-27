<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$u = current_user();
$active = 'crowdfunding.php';

$formError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_campaign'])) {
    csrf_check();
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $goal  = (float)($_POST['goal_amount'] ?? 0);
    $days  = (int)($_POST['days_left'] ?? 30);
    if ($title && $desc && $goal > 0) {
        $stmt = $pdo->prepare("INSERT INTO campaigns (organizer_id, title, description, goal_amount, days_left, admin_status) VALUES (?,?,?,?,?,'pending')");
        $stmt->execute([$u['id'], $title, $desc, $goal, $days]);
        flash('success', 'Your campaign was submitted and is pending admin verification.');
        redirect('crowdfunding.php');
    } else {
        $formError = 'Please fill in all required fields.';
    }
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT c.*, u.full_name, u.department, u.batch, u.uiu_id_verified
        FROM campaigns c JOIN users u ON u.id = c.organizer_id";
$params = [];
if ($q !== '') {
    $sql .= " WHERE (c.title LIKE ? OR c.description LIKE ? OR u.full_name LIKE ?)";
    $like = "%$q%";
    $params = [$like, $like, $like];
}
$sql .= " ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$campaigns = $stmt->fetchAll();

$page_title = 'Crowdfunding';
include __DIR__ . '/includes/header.php';
?>
<div class="app-shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main-area">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="content-wrap">
      <div class="content-main">
        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

        <?php if (isset($_GET['new'])): ?>
        <div class="card">
          <h3><i class="fa-solid fa-bullseye"></i> Start a Crowdfunding Campaign</h3>
          <?php if ($formError): ?><div class="alert alert-error"><?= e($formError) ?></div><?php endif; ?>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="create_campaign" value="1">
            <div class="form-group"><label>Campaign Title</label><input type="text" name="title" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" rows="4" required></textarea></div>
            <div class="form-row">
              <div class="form-group"><label>Goal Amount (৳)</label><input type="number" name="goal_amount" min="500" required></div>
              <div class="form-group"><label>Duration (days)</label><input type="number" name="days_left" value="30" min="1"></div>
            </div>
            <div class="alert alert-info">Your campaign will be reviewed and verified by an admin (ID + supporting documents) before appearing as "Verified".</div>
            <button class="btn btn-primary" type="submit">Submit Campaign for Review</button>
          </form>
        </div>
        <?php endif; ?>

        <div class="card">
          <form method="get" action="crowdfunding.php" style="display:flex;gap:10px;align-items:center;">
            <div class="search-box" style="flex:1;width:auto;">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search campaigns by title, description, or organizer...">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($q !== ''): ?><a href="crowdfunding.php" class="btn btn-outline">Clear</a><?php endif; ?>
          </form>
        </div>

        <div class="filter-tabs">
          <a href="crowdfunding.php" class="<?= $q === '' ? 'active' : '' ?>">All Campaigns</a>
          <a href="crowdfunding.php?new=1">+ Start a Campaign</a>
        </div>

        <?php if ($q !== ''): ?>
          <p style="font-size:13.5px;color:var(--text-muted);"><strong><?= count($campaigns) ?></strong> campaign<?= count($campaigns)===1?'':'s' ?> matching "<?= e($q) ?>"</p>
        <?php endif; ?>

        <?php if (!$campaigns): ?>
          <div class="card empty-state"><i class="fa-regular fa-folder-open"></i>
            <p><?= $q !== '' ? 'No campaigns matched your search.' : 'No campaigns yet.' ?></p>
          </div>
        <?php endif; ?>

        <?php foreach ($campaigns as $c): $pct = min(100, round($c['raised_amount'] / max(1,$c['goal_amount']) * 100)); ?>
          <div class="card campaign-mini">
            <div class="top">
              <span><?= $c['admin_status']==='verified' ? '<span class="badge-verified"><i class="fa-solid fa-circle-check"></i> Verified Campaign</span>' : ($c['admin_status']==='rejected' ? '<span style="color:#b91c1c;">Rejected</span>' : '<span class="badge-pending"><i class="fa-solid fa-clock"></i> Pending Verification</span>') ?></span>
              <span><?= time_ago($c['created_at']) ?></span>
            </div>
            <h4><?= e($c['title']) ?></h4>
            <div class="organizer">Organized by <?= e($c['full_name']) ?> · <?= e($c['department']) ?> '<?= substr($c['batch'],-2) ?></div>
            <p class="post-desc"><?= e(truncate_text($c['description'], 160)) ?></p>
            <div class="progress-bar"><div style="width:<?= $pct ?>%"></div></div>
            <div class="campaign-stats">
              <span><strong><?= taka($c['raised_amount']) ?></strong> raised of <?= taka($c['goal_amount']) ?></span>
              <span><?= $pct ?>% funded · <?= $c['days_left'] ?> days left</span>
            </div>
            <div class="post-actions">
              <a href="campaign_details.php?id=<?= $c['id'] ?>" class="btn btn-primary"><i class="fa-solid fa-heart"></i> View & Donate</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="content-side">
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
