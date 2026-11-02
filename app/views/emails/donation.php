<h2 style="margin:0 0 12px;color:#078838;">Moltes gràcies<?= $d['donor_name'] ? ', ' . e($d['donor_name']) : '' ?>!</h2>
<p>Hem rebut correctament la teva aportació a la campanya <strong><?= e($d['campaign_title']) ?></strong>.</p>
<table role="presentation" cellpadding="8" cellspacing="0" style="background:#f4f5f5;border-radius:10px;margin:16px 0;width:100%;">
<tr><td>Import</td><td align="right"><strong><?= e(money((int)$d['amount_cents'])) ?></strong></td></tr>
<tr><td>Data</td><td align="right"><?= e(date('d/m/Y H:i', strtotime($d['paid_at'] ?: 'now'))) ?></td></tr>
<tr><td>Referència</td><td align="right">DON-<?= (int)$d['id'] ?></td></tr>
</table>
<p>Gràcies a aportacions com la teva podem fer realitat projectes per a tots els infants de l'escola.</p>
<p><a href="<?= e(abs_url('campanyes/' . $d['campaign_slug'])) ?>" style="display:inline-block;background:#fbdc08;color:#1d1d1b;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:bold;">Segueix la campanya</a></p>
