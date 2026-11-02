<?php defined('BASE_PATH') || exit; ?>
<div class="stats">
    <div class="stat"><span>Vendes netes</span><strong><?= e(money((int)$stats['revenue'])) ?></strong><small><?= (int)$stats['orders'] ?> comandes pagades</small></div>
    <div class="stat"><span>Per preparar</span><strong><?= (int)$stats['to_prepare'] ?></strong><small>en estat «Rebuda»</small></div>
    <div class="stat"><span>En preparació</span><strong><?= (int)$stats['preparing'] ?></strong></div>
    <div class="stat"><span>Botiga</span><strong class="sm"><?= Shop::enabled() ? '<span class="badge badge-ok">Activa</span>' : '<span class="badge badge-muted">Desactivada</span>' ?></strong><small><a href="<?= url('admin/shop-settings') ?>">Configurar</a></small></div>
</div>
<div class="toolbar">
    <form class="search" method="get"><input type="hidden" name="status" value="<?= e($status) ?>"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Número, nom o correu..."></form>
    <div class="tabs">
        <?php foreach (['open' => 'Pendents de servir', 'received' => 'Rebudes', 'preparing' => 'En preparació', 'shipped' => 'Enviades', 'cancelled' => 'Cancel·lades', 'returned' => 'Devolucions', 'all' => 'Totes'] as $k => $l): ?>
            <a href="?status=<?= $k ?>" class="<?= $status === $k ? 'active' : '' ?>"><?= $l ?></a>
        <?php endforeach; ?>
    </div>
    <a class="btn" href="?status=<?= e($status) ?>&q=<?= e(urlencode($q)) ?>&export=csv">Exportar CSV</a>
</div>
<div class="panel flush">
<table class="table list">
    <thead><tr><th>Comanda</th><th>Client</th><th>Lliurament</th><th>Import</th><th>Pagament</th><th>Estat</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="7" class="center muted">No hi ha comandes.</td></tr><?php endif; ?>
    <?php foreach ($rows as $o): ?>
        <tr>
            <td data-label="Comanda"><a href="<?= url('admin/orders/' . $o['id']) ?>"><strong><?= e($o['number']) ?></strong></a><br><small class="muted"><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></small></td>
            <td data-label="Client"><?= e($o['name']) ?><br><small class="muted"><?= e($o['email']) ?></small></td>
            <td data-label="Lliurament"><?= $o['delivery_method'] === 'shipping' ? 'Enviament' : 'Recollida' ?><br><small class="muted"><?= (int)$o['units'] ?> unitats</small></td>
            <td data-label="Import" class="num"><strong><?= e(money((int)$o['total_cents'])) ?></strong><?= (int)$o['refunded_cents'] ? '<br><small class="muted">−' . e(money((int)$o['refunded_cents'])) . '</small>' : '' ?></td>
            <td data-label="Pagament"><?= e(Shop::paymentLabel($o['payment_status'])) ?></td>
            <td data-label="Estat"><?= Shop::statusBadge($o['status']) ?></td>
            <td class="actions"><a class="btn btn-small" href="<?= url('admin/orders/' . $o['id']) ?>">Gestionar</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
