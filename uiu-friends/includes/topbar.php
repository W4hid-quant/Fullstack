<?php /** Expects $u (current user) */ ?>
<header class="topbar">
  <div class="topbar-welcome">
    <img src="<?= e($u['avatar']) ?>" alt="">
    <div>
      <h1>Welcome back, <?= e(explode(' ', $u['full_name'])[0]) ?> (<?= e($u['department']) ?>, Batch <?= e($u['batch']) ?>)</h1>
      <div class="meta">
        <?= $u['uiu_id_verified'] ? '<span class="badge-verified"><i class="fa-solid fa-circle-check"></i> UIU ID Verified</span>' : '<span class="badge-pending"><i class="fa-solid fa-clock"></i> Verification Pending</span>' ?>
        <span class="trust-chip"><i class="fa-solid fa-star"></i> <?= number_format($u['trust_score'],1) ?> Trust Score</span>
      </div>
    </div>
  </div>
  <div class="topbar-actions">
    <form action="search.php" method="get" class="search-box" style="margin:0;">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" name="q" placeholder="Search loans &amp; campaigns..." value="<?= e($_GET['q'] ?? '') ?>">
    </form>
    <a href="messages.php" class="icon-btn"><i class="fa-regular fa-bell"></i><?php if (($unread ?? 0) > 0): ?><span class="dot"></span><?php endif; ?></a>
    <a href="security.php" class="icon-btn"><i class="fa-solid fa-gear"></i></a>
  </div>
</header>
