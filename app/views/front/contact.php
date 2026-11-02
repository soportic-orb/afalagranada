<?php defined('BASE_PATH') || exit; ?>
<?php $subjects = contact_subjects(); $sel = old('subject', 'general'); ?>
<section class="page-hero small<?= hero_bg('contact') ?>">
    <div class="container">
        <p class="eyebrow">Parlem</p>
        <h1>Contacte</h1>
        <p class="lead"><?= e(setting('contact_intro', 'Tens dubtes, suggeriments o vols col·laborar amb l\'AFA? Escriu-nos i et respondrem tan aviat com puguem.')) ?></p>
    </div>
</section>
<section class="section">
    <div class="container layout-aside contact-layout">
        <div class="card pad" id="formulari">
            <h2 class="h3">Envia'ns un missatge</h2>
            <form method="post" action="<?= url('contacte') ?>" class="form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="ts" value="<?= e($formTs) ?>">
                <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                <div class="grid-2">
                    <div class="field"><label for="c-name">Nom i cognoms *</label><input id="c-name" name="name" required maxlength="150" value="<?= e(old('name')) ?>" autocomplete="name"></div>
                    <div class="field"><label for="c-email">Correu electrònic *</label><input id="c-email" type="email" name="email" required value="<?= e(old('email')) ?>" autocomplete="email"></div>
                    <div class="field"><label for="c-phone">Telèfon <small>(opcional)</small></label><input id="c-phone" type="tel" name="phone" maxlength="40" value="<?= e(old('phone')) ?>" autocomplete="tel"></div>
                    <div class="field"><label for="c-subject">Motiu</label>
                        <select id="c-subject" name="subject">
                            <?php foreach ($subjects as $k => $l): ?><option value="<?= e($k) ?>" <?= $sel === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="field"><label for="c-message">Missatge *</label><textarea id="c-message" name="message" rows="7" required minlength="10" maxlength="5000"><?= e(old('message')) ?></textarea></div>
                <label class="check"><input type="checkbox" name="accept" value="1" required> He llegit i accepto la <a href="<?= e(url(ltrim(setting('privacy_url', '/privacitat'), '/'))) ?>" target="_blank">política de privacitat</a></label>
                <button class="btn btn-primary btn-lg" type="submit"><?= icon('mail', 'icon icon-sm') ?> Enviar missatge</button>
            </form>
        </div>
        <aside>
            <div class="card pad contact-info">
                <h2 class="h3">On som</h2>
                <ul class="meta big">
                    <li><?= icon('pin', 'icon icon-sm') ?> <span><?= e(setting('contact_address', 'Escola Jacint Verdaguer, La Granada (Alt Penedès)')) ?></span></li>
                    <?php if ($m = setting('contact_email')): ?><li><?= icon('mail', 'icon icon-sm') ?> <a href="mailto:<?= e($m) ?>"><?= e($m) ?></a></li><?php endif; ?>
                    <?php if ($p = setting('contact_phone')): ?><li><?= icon('phone', 'icon icon-sm') ?> <a href="tel:<?= e(preg_replace('/\s+/', '', $p)) ?>"><?= e($p) ?></a></li><?php endif; ?>
                </ul>
            </div>
            <div class="card pad contact-app">
                <h2 class="h3">Ets soci/a?</h2>
                <p>Per a gestions de quota, serveis i inscripcions a extraescolars, fes servir l'app <strong><?= e(setting('app_name', 'Abaco')) ?></strong>.</p>
                <a class="link-arrow" href="<?= url('aplicacio') ?>">Més informació sobre l'app →</a>
            </div>
        </aside>
    </div>
</section>
