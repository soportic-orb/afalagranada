<?php defined('BASE_PATH') || exit; ?>
<?php partial('shop_bar'); ?>
<section class="section">
    <div class="container narrow">
        <div class="card pad track-card">
            <h1 class="h2">Seguir la meva comanda</h1>
            <?php if ($sent): ?>
                <div class="alert alert-success">Si hi ha comandes fetes amb aquest correu, en uns minuts rebràs un missatge amb l'enllaç per consultar-les. Revisa també la carpeta de correu brossa.</div>
            <?php endif; ?>
            <p>Escriu el correu electrònic amb què vas fer la compra i t'enviarem un enllaç per accedir a les teves comandes, veure'n l'estat i gestionar-les.</p>
            <form method="post" action="<?= url('botiga/seguiment') ?>" class="form inline-track">
                <?= csrf_field() ?>
                <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                <input type="email" name="email" required placeholder="el-teu@correu.cat" autocomplete="email" aria-label="Correu electrònic">
                <button class="btn btn-primary" type="submit">Enviar l'enllaç</button>
            </form>
        </div>
    </div>
</section>
