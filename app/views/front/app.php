<section class="page-hero app-hero">
    <div class="container split">
        <div>
            <p class="eyebrow">L'app de l'AFA</p>
            <h1><?= e(setting('app_name', 'Abaco')) ?></h1>
            <p class="lead"><?= e(setting('app_tagline', 'Tota la gestió de l\'AFA, al teu mòbil')) ?></p>
            <?php partial('store_badges'); ?>
            <?php if ($w = setting('app_web_url')): ?><p><a class="link-arrow" href="<?= e($w) ?>" target="_blank" rel="noopener">Accedeix a la versió web →</a></p><?php endif; ?>
        </div>
        <div class="app-phone"><img src="<?= setting('app_image') ? e(upload_url(setting('app_image'))) : asset('img/phone.svg') ?>" alt="Captura de l'app"></div>
    </div>
</section>
<section class="section">
    <div class="container narrow prose">
        <?= setting('app_intro', '<p>Les famílies sòcies de l\'AFA utilitzem l\'app <strong>Abaco</strong> per a la gestió del dia a dia: pagament de la quota, contractació de serveis, inscripcions a extraescolars i comunicacions.</p>') ?>
    </div>
</section>
<?php if ($features): ?>
<section class="section tinted">
    <div class="container">
        <div class="section-head"><h2>Què pots fer amb l'app?</h2></div>
        <div class="grid grid-3 features">
            <?php foreach ($features as $b) { partial('block_card', ['b' => $b]); } ?>
        </div>
    </div>
</section>
<?php endif; ?>
<?php if ($help = setting('app_help')): ?>
<section class="section">
    <div class="container narrow prose">
        <h2>Com començar</h2>
        <?= $help ?>
    </div>
</section>
<?php endif; ?>
