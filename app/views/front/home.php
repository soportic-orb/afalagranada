<?php $heroImg = setting('hero_image', ''); ?>
<section class="hero<?= $heroImg ? ' with-image' : '' ?>"<?= $heroImg ? ' style="--hero-img:url(\'' . e(upload_url($heroImg)) . '\')"' : '' ?>>
    <div class="container hero-inner">
        <div class="hero-text">
            <p class="eyebrow">AFA Escola Jacint Verdaguer · La Granada</p>
            <h1><?= e(setting('hero_title', 'Famílies que fan escola')) ?></h1>
            <p class="lead"><?= e(setting('hero_subtitle', 'Som l\'Associació de Famílies d\'Alumnes. Treballem plegats perquè els nostres infants gaudeixin d\'una escola més rica, oberta i compartida.')) ?></p>
            <div class="hero-actions">
                <?php $cta = setting('hero_cta_url', '/fes-te-soci'); ?>
                <a class="btn btn-accent btn-lg" href="<?= e(str_starts_with($cta, '/') ? url($cta) : $cta) ?>"><?= e(setting('hero_cta_label', 'Fes-te soci/a')) ?></a>
                <a class="btn btn-ghost btn-lg" href="<?= url('activitats') ?>">Veure activitats</a>
            </div>
        </div>
        <?php if (!$heroImg): ?>
        <div class="hero-art" aria-hidden="true">
            <img src="<?= asset('img/hero.svg') ?>" alt="">
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="section" id="que-es">
    <div class="container split">
        <div>
            <p class="eyebrow">Qui som</p>
            <h2><?= e(setting('about_title', 'Què és l\'AFA?')) ?></h2>
            <div class="prose">
                <?= setting('about_text', '<p>L\'AFA és l\'associació que agrupa les famílies dels alumnes de l\'escola. Som un espai de participació, voluntari i sense ànim de lucre, que col·labora amb l\'equip docent i l\'Ajuntament per millorar la vida a l\'escola.</p>') ?>
            </div>
        </div>
        <div class="about-media">
            <?php if ($img = setting('about_image')): ?>
                <img class="rounded shadow" src="<?= e(upload_url($img)) ?>" alt="">
            <?php else: ?>
                <div class="stat-cloud">
                    <div><?= icon('users', 'icon icon-lg') ?><strong>Famílies</strong><span>sòcies i voluntàries</span></div>
                    <div><?= icon('sun', 'icon icon-lg') ?><strong>Serveis</strong><span>acollida, menjador i extraescolars</span></div>
                    <div><?= icon('palette', 'icon icon-lg') ?><strong>Cultura</strong><span>festes i activitats per als infants</span></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($services): ?>
<section class="section tinted" id="que-fem">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow">Serveis</p>
            <h2><?= e(setting('services_title', 'Què fem des de l\'AFA')) ?></h2>
            <?php if ($t = setting('services_text')): ?><p class="lead"><?= e($t) ?></p><?php endif; ?>
        </div>
        <div class="grid grid-3 features">
            <?php foreach ($services as $b) { partial('block_card', ['b' => $b]); } ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($benefits): ?>
<section class="section" id="avantatges">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow">Fes-te soci/a</p>
            <h2><?= e(setting('member_title', 'Per què fer-se soci/a?')) ?></h2>
            <?php if ($t = setting('member_text')): ?><p class="lead"><?= e($t) ?></p><?php endif; ?>
        </div>
        <div class="grid grid-3 features benefits">
            <?php foreach ($benefits as $b) { partial('block_card', ['b' => $b]); } ?>
        </div>
        <div class="member-cta">
            <div>
                <h3>Uneix-te a l'AFA</h3>
                <p><?= setting('member_fee') ? 'Quota: <strong>' . e(setting('member_fee')) . '</strong>. ' : '' ?>L'alta i el pagament de la quota es fan des de l'app de l'AFA.</p>
            </div>
            <a class="btn btn-accent" href="<?= url('app') ?>">Com fer-se soci/a</a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($activities): ?>
<section class="section tinted">
    <div class="container">
        <div class="section-head row">
            <div><p class="eyebrow">Agenda</p><h2>Properes activitats</h2></div>
            <a class="link-arrow" href="<?= url('activitats') ?>">Totes les activitats →</a>
        </div>
        <div class="grid grid-3">
            <?php foreach ($activities as $a) { partial('activity_card', ['a' => $a]); } ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($campaigns): ?>
<section class="section">
    <div class="container">
        <div class="section-head row">
            <div><p class="eyebrow">Micromecenatge</p><h2>Campanyes en marxa</h2></div>
            <a class="link-arrow" href="<?= url('campanyes') ?>">Totes les campanyes →</a>
        </div>
        <div class="grid grid-3">
            <?php foreach ($campaigns as $c) { partial('campaign_card', ['c' => $c]); } ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section app-band">
    <div class="container split">
        <div>
            <p class="eyebrow light">L'app de l'AFA</p>
            <h2><?= e(setting('app_tagline', 'Tota la gestió de l\'AFA, al teu mòbil')) ?></h2>
            <p class="lead">Amb <strong><?= e(setting('app_name', 'Abaco')) ?></strong> pots gestionar la quota de soci/a, pagar serveis, inscriure't a activitats i rebre els avisos de l'AFA.</p>
            <?php partial('store_badges'); ?>
            <p><a class="link-arrow light" href="<?= url('app') ?>">Més informació sobre l'app →</a></p>
        </div>
        <div class="app-phone" aria-hidden="true"><img src="<?= setting('app_image') ? e(upload_url(setting('app_image'))) : asset('img/phone.svg') ?>" alt=""></div>
    </div>
</section>

<?php if ($posts): ?>
<section class="section">
    <div class="container">
        <div class="section-head row">
            <div><p class="eyebrow">Actualitat</p><h2>Notícies</h2></div>
            <a class="link-arrow" href="<?= url('noticies') ?>">Totes les notícies →</a>
        </div>
        <div class="grid grid-3">
            <?php foreach ($posts as $p): ?>
                <article class="card post-card">
                    <?php if ($p['image']): ?><a class="card-media" href="<?= url('noticies/' . $p['slug']) ?>"><img src="<?= e(upload_url($p['image'])) ?>" alt="" loading="lazy"></a><?php endif; ?>
                    <div class="card-body">
                        <time class="muted"><?= e(fmt_date($p['published_at'])) ?></time>
                        <h3><a href="<?= url('noticies/' . $p['slug']) ?>"><?= e($p['title']) ?></a></h3>
                        <p><?= e($p['excerpt'] ?: strip_to_text($p['content'], 140)) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
