<?php defined('BASE_PATH') || exit; ?>
<p><a href="<?= url('admin/r/products') ?>">← Productes</a> · <a href="<?= url('admin/r/products/' . $p['id']) ?>">Editar producte</a> · <a href="<?= url('botiga/producte/' . $p['slug']) ?>" target="_blank">Veure a la botiga ↗</a></p>
<form method="post" class="panel">
    <?= csrf_field() ?><input type="hidden" name="action" value="save">
    <?php if (!$variants): ?>
        <h2>Estoc del producte</h2>
        <div class="field" style="max-width:220px"><label>Unitats disponibles</label><input type="number" name="product_stock" value="<?= e($p['stock']) ?>" placeholder="Il·limitat"><small class="help">En blanc = il·limitat.</small></div>
        <p class="muted small">Si el producte té talles, colors o altres opcions, afegeix-les a sota: cada opció tindrà el seu propi estoc.</p>
    <?php else: ?>
        <h2><?= e($p['variant_label'] ?: 'Opcions') ?></h2>
        <table class="table">
            <thead><tr><th>Nom</th><th>Estoc</th><th>Ordre</th><th>Activa</th><th>Venudes</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($variants as $v): $sold = (int)db()->value("SELECT COALESCE(SUM(i.quantity),0) FROM order_items i JOIN orders o ON o.id = i.order_id WHERE i.variant_id = ? AND o.payment_status IN ('paid','partially_refunded')", [$v['id']]); ?>
                <tr>
                    <td><input name="v[<?= (int)$v['id'] ?>][name]" value="<?= e($v['name']) ?>" required></td>
                    <td style="width:130px"><input type="number" name="v[<?= (int)$v['id'] ?>][stock]" value="<?= e($v['stock']) ?>" placeholder="Il·limitat"></td>
                    <td style="width:90px"><input type="number" name="v[<?= (int)$v['id'] ?>][sort]" value="<?= (int)$v['sort_order'] ?>"></td>
                    <td><input type="checkbox" name="v[<?= (int)$v['id'] ?>][active]" value="1" <?= $v['active'] ? 'checked' : '' ?>></td>
                    <td><?= $sold ?></td>
                    <td class="actions"><button class="btn btn-small btn-danger" form="del-<?= (int)$v['id'] ?>">Eliminar</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <div class="field">
        <label>Afegir opcions <small class="muted">(una per línia, opcionalment «Nom | estoc», p. ex. «M | 12»)</small></label>
        <textarea name="new" rows="3" placeholder="S | 10&#10;M | 15&#10;L | 8"></textarea>
    </div>
    <button class="btn btn-primary">Desar</button>
</form>
<?php foreach ($variants as $v): ?>
    <form id="del-<?= (int)$v['id'] ?>" method="post" onsubmit="return confirm('Eliminar aquesta opció?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="variant_id" value="<?= (int)$v['id'] ?>"></form>
<?php endforeach; ?>
