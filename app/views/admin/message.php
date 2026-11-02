<?php defined('BASE_PATH') || exit; ?>
<p><a href="<?= url('admin/messages') ?>">← Missatges</a></p>
<div class="panel message-view">
    <dl class="dl">
        <dt>Data</dt><dd><?= e(date('d/m/Y H:i', strtotime($m['created_at']))) ?></dd>
        <dt>Nom</dt><dd><strong><?= e($m['name']) ?></strong></dd>
        <dt>Correu</dt><dd><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></dd>
        <?php if ($m['phone']): ?><dt>Telèfon</dt><dd><a href="tel:<?= e(preg_replace('/\s+/', '', $m['phone'])) ?>"><?= e($m['phone']) ?></a></dd><?php endif; ?>
        <dt>Motiu</dt><dd><?= e($m['subject']) ?></dd>
        <dt>Enviat per correu</dt><dd><?= $m['email_sent'] ? 'Sí' : 'No (revisa la configuració de correu)' ?></dd>
    </dl>
    <div class="message-body"><?= nl2br(e($m['message'])) ?></div>
    <div class="form-actions">
        <a class="btn btn-primary" href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $m['subject'] . ' — ' . setting('site_name', 'AFA')) ?>">Respondre per correu</a>
        <form method="post" class="inline"><?= csrf_field() ?><button class="btn" name="action" value="unread">Marcar com a no llegit</button></form>
        <form method="post" class="inline" onsubmit="return confirm('Eliminar aquest missatge?')"><?= csrf_field() ?><button class="btn btn-danger" name="action" value="delete">Eliminar</button></form>
    </div>
</div>
