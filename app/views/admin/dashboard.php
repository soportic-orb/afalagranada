<?php foreach ($warnings as $w): ?><div class="alert alert-warn"><?= $w ?></div><?php endforeach; ?>
<div class="stats">
    <div class="stat"><span>Recaptat en campanyes</span><strong><?= e(money($stats['donations_total'])) ?></strong><small><?= (int)$stats['donations_count'] ?> donacions</small></div>
    <div class="stat"><span>Inscripcions a activitats</span><strong><?= (int)$stats['registrations_count'] ?></strong><small>participants · <?= e(money($stats['registrations_total'])) ?></small></div>
    <div class="stat"><span>Campanyes actives</span><strong><?= (int)$stats['active_campaigns'] ?></strong><small><a href="<?= url('admin/r/campaigns') ?>">Gestionar</a></small></div>
    <div class="stat"><span>Properes activitats</span><strong><?= (int)$stats['upcoming_activities'] ?></strong><small><a href="<?= url('admin/r/activities') ?>">Gestionar</a></small></div>
</div>
<div class="quick">
    <a class="btn btn-primary" href="<?= url('admin/r/activities/new') ?>">+ Nova activitat</a>
    <a class="btn btn-primary" href="<?= url('admin/r/campaigns/new') ?>">+ Nova campanya</a>
    <a class="btn" href="<?= url('admin/r/posts/new') ?>">+ Nova notícia</a>
    <a class="btn" href="<?= url('admin/r/pages/new') ?>">+ Nova pàgina</a>
    <a class="btn" href="<?= url('admin/checkin') ?>">Escàner QR</a>
</div>
<div class="cols">
    <div class="panel">
        <h2>Darreres donacions</h2>
        <?php if (!$lastDonations): ?><p class="muted">Encara no n'hi ha.</p><?php endif; ?>
        <table class="table"><tbody>
        <?php foreach ($lastDonations as $d): ?>
            <tr><td><?= e($d['donor_name'] ?: 'Anònim') ?><br><small class="muted"><?= e($d['campaign']) ?></small></td><td class="num"><?= e(money((int)$d['amount_cents'])) ?><br><small class="muted"><?= e(date('d/m H:i', strtotime($d['paid_at']))) ?></small></td></tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
    <div class="panel">
        <h2>Darreres inscripcions</h2>
        <?php if (!$lastRegs): ?><p class="muted">Encara no n'hi ha.</p><?php endif; ?>
        <table class="table"><tbody>
        <?php foreach ($lastRegs as $r): ?>
            <tr><td><?= e($r['name']) ?><br><small class="muted"><?= e($r['activity']) ?></small></td><td class="num"><?= (int)$r['quantity'] ?> pers.<br><small class="muted"><?= e(date('d/m H:i', strtotime($r['created_at']))) ?></small></td></tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
</div>
