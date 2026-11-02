<div class="cols">
    <div class="panel">
        <h2>Versió instal·lada</h2>
        <p><strong>v<?= e(Updater::currentVersion()) ?></strong>
            <?php if ($c = Updater::installedCommit()): ?> · commit <code><?= e(substr($c, 0, 7)) ?></code><?php endif; ?>
            <?php if ($at = setting('installed_at')): ?><br><small class="muted">Actualitzada el <?= e(date('d/m/Y H:i', strtotime($at))) ?></small><?php endif; ?>
        </p>
        <p class="muted small">Origen: <code><?= e(Updater::repo()) ?></code> · branca <code><?= e(Updater::branch()) ?></code></p>
        <form method="post" action="<?= url('admin/updates/check') ?>"><?= csrf_field() ?><button class="btn">Comprovar actualitzacions</button></form>
    </div>
    <div class="panel">
        <h2>Darrera versió a GitHub</h2>
        <?php if ($last): ?>
            <p><strong><?= $last['version'] ? 'v' . e($last['version']) . ' · ' : '' ?><code><?= e($last['short']) ?></code></strong> <small class="muted"><?= e($last['date'] ? date('d/m/Y H:i', strtotime($last['date'])) : '') ?> · <?= e($last['author']) ?></small></p>
            <pre class="commit"><?= e($last['message']) ?></pre>
            <p class="muted small">Comprovat: <?= e(date('d/m/Y H:i', strtotime($last['checked_at']))) ?></p>
            <?php if ($last['sha'] !== Updater::installedCommit()): ?>
                <form method="post" action="<?= url('admin/updates/run') ?>" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Actualitzant... no tanquis la pàgina';return confirm('S\'actualitzarà la web amb la darrera versió de la branca <?= e(Updater::branch()) ?>. Es farà una còpia de seguretat del codi abans. Continuar?')">
                    <?= csrf_field() ?><button class="btn btn-primary">Actualitzar ara</button>
                </form>
            <?php else: ?><p class="badge badge-ok">Estàs al dia</p><?php endif; ?>
        <?php else: ?><p class="muted">Encara no s'ha comprovat.</p><?php endif; ?>
    </div>
</div>
<?php if ($log): ?>
<div class="panel"><h2>Resultat de l'actualització</h2><ol class="log"><?php foreach ($log as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ol></div>
<?php endif; ?>
<div class="panel">
    <h2>Base de dades</h2>
    <?php if ($pending): ?>
        <p>Migracions pendents: <code><?= e(implode(', ', $pending)) ?></code></p>
        <form method="post" action="<?= url('admin/updates/migrate') ?>"><?= csrf_field() ?><button class="btn btn-primary">Executar migracions</button></form>
    <?php else: ?><p class="badge badge-ok">L'esquema de la base de dades està al dia.</p><?php endif; ?>
</div>
<div class="panel">
    <h2>Còpies de seguretat del codi</h2>
    <p class="muted small">Abans de cada actualització es desa una còpia del codi (no inclou la base de dades ni les imatges pujades). Es conserven les 5 últimes. Es recomana fer també còpies de la base de dades des del panell del hosting.</p>
    <?php if (!$backups): ?><p class="muted">Cap còpia.</p><?php endif; ?>
    <table class="table"><tbody>
    <?php foreach ($backups as $b): ?>
        <tr><td><code><?= e($b['name']) ?></code></td><td><?= e($b['date']) ?></td><td><?= round($b['size'] / 1048576, 1) ?> MB</td>
            <td class="actions"><form method="post" action="<?= url('admin/updates/restore') ?>" onsubmit="return confirm('Restaurar aquesta còpia del codi?')"><?= csrf_field() ?><input type="hidden" name="backup" value="<?= e($b['name']) ?>"><button class="btn btn-small">Restaurar</button></form></td></tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
