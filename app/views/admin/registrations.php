<p><a href="<?= url('admin/r/activities') ?>">← Activitats</a> · <a href="<?= url('admin/r/activities/' . $a['id']) ?>">Editar activitat</a> · <a href="<?= url('activitats/' . $a['slug']) ?>" target="_blank">Veure a la web ↗</a></p>
<div class="stats">
    <div class="stat"><span>Participants</span><strong><?= (int)$summary['seats'] ?><?= $a['capacity'] ? ' / ' . (int)$a['capacity'] : '' ?></strong><small><?= (int)$summary['regs'] ?> inscripcions</small></div>
    <div class="stat"><span>Ingressos</span><strong><?= e(money((int)$summary['revenue'])) ?></strong></div>
    <div class="stat"><span>Entrades validades</span><strong><?= $checked ?></strong><small><a href="<?= url('admin/checkin') ?>">Obrir escàner</a></small></div>
    <div class="stat"><span>Data</span><strong class="sm"><?= e(fmt_date($a['starts_at'], true) ?: '—') ?></strong><small><?= status_badge($a['status']) ?></small></div>
</div>
<div class="toolbar">
    <div class="tabs">
        <?php foreach (['valid' => 'Confirmades', 'pending' => 'Pendents de pagament', 'cancelled' => 'Cancel·lades', 'expired' => 'Caducades', 'all' => 'Totes'] as $k => $l): ?>
            <a href="?status=<?= $k ?>" class="<?= $status === $k ? 'active' : '' ?>"><?= $l ?></a>
        <?php endforeach; ?>
    </div>
    <a class="btn" href="?status=<?= e($status) ?>&export=csv">Exportar CSV</a>
    <button class="btn" onclick="window.print()">Imprimir llista</button>
    <button class="btn btn-primary" onclick="document.getElementById('manual').hidden = !document.getElementById('manual').hidden">+ Inscripció manual</button>
</div>
<form method="post" class="panel" id="manual" hidden>
    <?= csrf_field() ?><input type="hidden" name="action" value="manual">
    <h2>Inscripció manual</h2>
    <div class="grid-fields">
        <div class="field"><label>Responsable *</label><input name="name" required></div>
        <div class="field"><label>Correu *</label><input type="email" name="email" required></div>
        <div class="field"><label>Telèfon</label><input name="phone"></div>
        <div class="field"><label>Import cobrat (€)</label><input name="amount" inputmode="decimal" value="0"></div>
    </div>
    <div class="field"><label>Participants * <small class="muted">(un per línia; opcionalment «Nom | Curs»)</small></label><textarea name="attendees" rows="3" required></textarea></div>
    <div class="field"><label>Observacions</label><input name="notes"></div>
    <label class="check"><input type="checkbox" name="is_member" value="1"> Família sòcia</label>
    <label class="check"><input type="checkbox" name="send" value="1" checked> Enviar entrades per correu</label>
    <button class="btn btn-primary">Afegir</button>
</form>
<div class="panel flush">
<table class="table list">
    <thead><tr><th>Inscripció</th><th>Participants</th><th>Responsable</th><th>Import</th><th>Estat</th><th></th></tr></thead>
    <tbody>
    <?php if (!$regs): ?><tr><td colspan="6" class="center muted">No hi ha inscripcions.</td></tr><?php endif; ?>
    <?php foreach ($regs as $r): ?>
        <tr>
            <td data-label="Inscripció"><code><?= e($r['code']) ?></code><br><small class="muted"><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></small></td>
            <td data-label="Participants">
                <?php foreach ($tickets[$r['id']] ?? [] as $t): ?>
                    <div><?= $t['checked_in_at'] ? '<span title="Validada ' . e($t['checked_in_at']) . '">✅</span>' : '▫️' ?> <a href="<?= url('entrada/' . $t['code']) ?>" target="_blank"><?= e($t['attendee_name']) ?></a><?= $t['attendee_course'] ? ' <small class="muted">(' . e($t['attendee_course']) . ')</small>' : '' ?></div>
                <?php endforeach; ?>
            </td>
            <td data-label="Responsable"><?= e($r['name']) ?><?= $r['is_member'] ? ' <span class="badge badge-ok">soci/a</span>' : '' ?><br><small class="muted"><?= e($r['email']) ?><?= $r['phone'] ? ' · ' . e($r['phone']) : '' ?></small><?= $r['notes'] ? '<br><small>📝 ' . e($r['notes']) . '</small>' : '' ?></td>
            <td data-label="Import" class="num"><?= $r['amount_cents'] ? e(money((int)$r['amount_cents'])) : 'Gratuïta' ?></td>
            <td data-label="Estat"><?= status_badge($r['status']) ?><?= $r['tickets_sent_at'] ? '<br><small class="muted">Enviades ' . e(date('d/m H:i', strtotime($r['tickets_sent_at']))) . '</small>' : '' ?></td>
            <td class="actions">
                <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="registration_id" value="<?= (int)$r['id'] ?>">
                    <?php if (in_array($r['status'], ['paid', 'free'], true)): ?>
                        <button class="btn btn-small" name="action" value="resend">Reenviar entrades</button>
                        <button class="btn btn-small btn-light" name="action" value="cancel" onclick="return confirm('Cancel·lar la inscripció? Les entrades deixaran de ser vàlides.')">Cancel·lar</button>
                    <?php elseif (in_array($r['status'], ['pending', 'expired', 'failed'], true)): ?>
                        <button class="btn btn-small" name="action" value="mark_paid" onclick="return confirm('Marcar com a pagada i enviar entrades?')">Marcar pagada</button>
                    <?php endif; ?>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
