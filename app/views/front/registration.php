<section class="page-hero small center">
    <div class="container narrow">
        <?php if (in_array($reg['status'], ['paid', 'free'], true)): ?>
            <div class="big-icon ok"><?= icon('check', 'icon icon-xl') ?></div>
            <h1>Inscripció confirmada!</h1>
            <p class="lead">T'hem enviat les entrades a <strong><?= e($reg['email']) ?></strong>. També les pots descarregar o mostrar des d'aquí el dia de l'activitat.</p>
        <?php elseif ($reg['status'] === 'pending'): ?>
            <h1>Pagament pendent</h1>
            <p class="lead">Encara no hem rebut la confirmació del pagament. Si l'has completat, rebràs les entrades per correu en uns minuts.</p>
        <?php else: ?>
            <h1>Inscripció <?= e(mb_strtolower(status_label($reg['status']))) ?></h1>
            <p class="lead">Aquesta inscripció no està activa. Si creus que és un error, contacta amb l'AFA.</p>
        <?php endif; ?>
    </div>
</section>
<section class="section">
    <div class="container narrow">
        <div class="card pad">
            <h2 class="h3"><?= e($reg['activity_title']) ?></h2>
            <ul class="meta">
                <?php if ($reg['starts_at']): ?><li><?= icon('calendar', 'icon icon-sm') ?> <?= e(fmt_date($reg['starts_at'], true)) ?></li><?php endif; ?>
                <?php if ($reg['location']): ?><li><?= icon('pin', 'icon icon-sm') ?> <?= e($reg['location']) ?></li><?php endif; ?>
                <li><?= icon('tag', 'icon icon-sm') ?> Inscripció <?= e($reg['code']) ?> · <?= (int)$reg['quantity'] ?> participant(s) · <?= $reg['amount_cents'] ? e(money((int)$reg['amount_cents'])) : 'Gratuïta' ?></li>
            </ul>
        </div>
        <div class="tickets">
            <?php foreach ($tickets as $t): ?>
                <a class="ticket" href="<?= url('entrada/' . $t['code']) ?>">
                    <img src="<?= url('entrada/' . $t['code'] . '/qr.svg') ?>" alt="Codi QR de l'entrada" width="140" height="140">
                    <div>
                        <strong><?= e($t['attendee_name']) ?></strong>
                        <?php if ($t['attendee_course']): ?><span><?= e($t['attendee_course']) ?></span><?php endif; ?>
                        <code><?= e($t['code']) ?></code>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
