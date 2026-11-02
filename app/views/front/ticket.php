<?php $valid = in_array($t['reg_status'], ['paid', 'free'], true); ?>
<section class="section">
    <div class="container narrow">
        <div class="ticket-full <?= $valid ? '' : 'invalid' ?>">
            <div class="ticket-head">
                <span class="eyebrow light">Entrada · AFA Jacint Verdaguer</span>
                <h1 class="h2"><?= e($t['activity_title']) ?></h1>
                <?php if ($t['starts_at']): ?><p><?= e(fmt_date($t['starts_at'], true)) ?><?= $t['location'] ? ' · ' . e($t['location']) : '' ?></p><?php endif; ?>
            </div>
            <div class="ticket-body">
                <img src="<?= url('entrada/' . $t['code'] . '/qr.svg') ?>" alt="Codi QR" width="240" height="240">
                <p class="attendee"><strong><?= e($t['attendee_name']) ?></strong><?= $t['attendee_course'] ? ' · ' . e($t['attendee_course']) : '' ?></p>
                <code><?= e($t['code']) ?></code>
                <?php if (!$valid): ?><div class="alert alert-error">Entrada no vàlida (<?= e(status_label($t['reg_status'])) ?>)</div><?php endif; ?>
                <?php if ($t['checked_in_at']): ?><p class="badge badge-warn">Validada el <?= e(date('d/m/Y H:i', strtotime($t['checked_in_at']))) ?></p><?php endif; ?>
            </div>
        </div>
        <?php if ($staff): ?>
            <div class="card pad staff-box">
                <h2 class="h3">Control d'accés</h2>
                <?php if ($valid && !$t['checked_in_at']): ?>
                    <button class="btn btn-accent btn-block btn-lg" data-checkin="<?= e($t['code']) ?>" data-csrf="<?= e(csrf_token()) ?>" data-url="<?= url('admin/checkin/validate') ?>">Validar entrada</button>
                    <p class="checkin-result"></p>
                <?php elseif ($t['checked_in_at']): ?>
                    <form method="post" action="<?= url('admin/checkin/undo') ?>"><?= csrf_field() ?><input type="hidden" name="code" value="<?= e($t['code']) ?>"><input type="hidden" name="back" value="1"><button class="btn btn-outline btn-block">Desfer validació</button></form>
                <?php endif; ?>
                <p><a href="<?= url('admin/checkin') ?>">Obrir l'escàner →</a></p>
            </div>
        <?php else: ?>
            <p class="center muted">Mostra aquest codi QR a l'entrada de l'activitat.</p>
        <?php endif; ?>
    </div>
</section>
