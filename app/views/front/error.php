<section class="page-hero small">
    <div class="container">
        <p class="eyebrow">Error <?= (int)$code ?></p>
        <h1><?= e($title) ?></h1>
        <p class="lead"><?= e($message ?: 'La pàgina que busques no existeix o s\'ha mogut.') ?></p>
        <a class="btn btn-primary" href="<?= url('/') ?>">Torna a l'inici</a>
    </div>
</section>
