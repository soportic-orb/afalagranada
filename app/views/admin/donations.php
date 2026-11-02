<?php defined('BASE_PATH') || exit; ?>
<p><a href="<?= url('admin/r/campaigns') ?>">← Campanyes</a> · <a href="<?= url('admin/r/campaigns/' . $c['id']) ?>">Editar campanya</a> · <a href="<?= url('campanyes/' . $c['slug']) ?>" target="_blank">Veure a la web ↗</a></p>
<div class="panel"><?php partial('progress', ['stats' => $stats]); ?></div>
<div class="toolbar">
    <div class="tabs">
        <?php foreach (['paid' => 'Pagades', 'pending' => 'Pendents', 'expired' => 'Caducades', 'refunded' => 'Retornades', 'all' => 'Totes'] as $k => $l): ?>
            <a href="?status=<?= $k ?>" class="<?= $status === $k ? 'active' : '' ?>"><?= $l ?></a>
        <?php endforeach; ?>
    </div>
    <a class="btn" href="?status=<?= e($status) ?>&export=csv">Exportar CSV</a>
    <button class="btn btn-primary" onclick="document.getElementById('manual').hidden = !document.getElementById('manual').hidden">+ Registrar donació manual</button>
</div>
<form method="post" class="panel" id="manual" hidden>
    <?= csrf_field() ?><input type="hidden" name="action" value="manual">
    <h2>Donació fora de la web</h2>
    <div class="grid-fields">
        <div class="field"><label>Import (€) *</label><input name="amount" required inputmode="decimal"></div>
        <div class="field"><label>Mètode</label><select name="method"><option value="cash">Efectiu</option><option value="transfer">Transferència</option></select></div>
        <div class="field"><label>Nom</label><input name="name"></div>
        <div class="field"><label>Correu</label><input type="email" name="email"></div>
    </div>
    <div class="field"><label>Missatge</label><input name="message"></div>
    <label class="check"><input type="checkbox" name="anonymous" value="1"> Anònim</label>
    <button class="btn btn-primary">Registrar</button>
</form>
<div class="panel flush">
<table class="table list">
    <thead><tr><th>Data</th><th>Donant</th><th>Import</th><th>Missatge</th><th>Estat</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="6" class="center muted">No hi ha donacions.</td></tr><?php endif; ?>
    <?php foreach ($rows as $d): ?>
        <tr>
            <td data-label="Data"><?= e(date('d/m/Y H:i', strtotime($d['paid_at'] ?: $d['created_at']))) ?></td>
            <td data-label="Donant"><?= e($d['donor_name'] ?: '—') ?><?= $d['anonymous'] ? ' <span class="badge badge-muted">anònim</span>' : '' ?><br><small class="muted"><?= e($d['donor_email']) ?></small></td>
            <td data-label="Import" class="num"><strong><?= e(money((int)$d['amount_cents'])) ?></strong><br><small class="muted"><?= e($d['method']) ?></small></td>
            <td data-label="Missatge"><?= e($d['message']) ?></td>
            <td data-label="Estat"><?= status_badge($d['status']) ?></td>
            <td class="actions">
                <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="donation_id" value="<?= (int)$d['id'] ?>">
                    <?php if ($d['status'] === 'paid'): ?>
                        <?php if ($d['donor_email']): ?><button class="btn btn-small" name="action" value="resend">Reenviar agraïment</button><?php endif; ?>
                        <input type="hidden" name="status" value="refunded"><button class="btn btn-small btn-light" name="action" value="status" onclick="return confirm('Marcar com a retornada? (El retorn s\'ha de fer a Stripe)')">Marcar retornada</button>
                    <?php elseif (in_array($d['status'], ['pending', 'expired'], true)): ?>
                        <input type="hidden" name="status" value="paid"><button class="btn btn-small" name="action" value="status" onclick="return confirm('Marcar com a pagada manualment?')">Marcar pagada</button>
                    <?php endif; ?>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
