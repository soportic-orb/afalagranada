<?php defined('BASE_PATH') || exit; ?>
<?php
$suggested = array_values(array_filter(array_map(fn($v) => to_cents(trim($v)), explode(',', (string)($c['suggested_amounts'] ?: '10,20,50,100'))), fn($v) => $v > 0));
$selected = old('amount', (string)($suggested[1] ?? $suggested[0] ?? 2000));
?>
<section class="page-hero small<?= hero_bg('campaigns') ?>">
    <div class="container">
        <p class="eyebrow"><a href="<?= url('campanyes') ?>">← Campanyes</a></p>
        <h1><?= e($c['title']) ?></h1>
        <?php if ($c['summary']): ?><p class="lead"><?= e($c['summary']) ?></p><?php endif; ?>
    </div>
</section>
<section class="section">
    <div class="container layout-aside">
        <div>
            <?php if ($c['image']): ?><img class="rounded cover" src="<?= e(upload_url($c['image'])) ?>" alt=""><?php endif; ?>
            <div class="prose"><?= $c['description'] ?></div>
            <?php if ($images): ?>
                <h2>Galeria</h2>
                <div class="gallery">
                    <?php foreach ($images as $img): ?>
                        <figure>
                            <a href="<?= e(upload_url($img['path'])) ?>" target="_blank"><img src="<?= e(upload_url($img['path'])) ?>" alt="<?= e($img['caption']) ?>" loading="lazy"></a>
                            <?php if ($img['caption']): ?><figcaption><?= e($img['caption']) ?></figcaption><?php endif; ?>
                        </figure>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($donors): ?>
                <h2>Darreres aportacions</h2>
                <ul class="donor-list">
                    <?php foreach ($donors as $d): ?>
                        <li>
                            <div class="avatar"><?= e(mb_strtoupper(mb_substr($d['anonymous'] || !$d['donor_name'] ? 'A' : $d['donor_name'], 0, 1))) ?></div>
                            <div>
                                <strong><?= e($d['anonymous'] || !$d['donor_name'] ? 'Anònim' : $d['donor_name']) ?></strong>
                                <span class="muted"> · <?= e(money((int)$d['amount_cents'])) ?> · <?= e(fmt_date($d['paid_at'])) ?></span>
                                <?php if ($d['message']): ?><p>“<?= e($d['message']) ?>”</p><?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <aside class="sticky">
            <div class="card pad" id="donar">
                <?php partial('progress', ['stats' => $stats]); ?>
                <?php if ($open): ?>
                    <?php if (input('cancel')): ?><div class="alert alert-info">Has cancel·lat el pagament. Pots tornar-ho a provar quan vulguis.</div><?php endif; ?>
                    <form method="post" action="<?= url('campanyes/' . $c['slug'] . '/donar') ?>" class="form donate-form">
                        <?= csrf_field() ?>
                        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                        <fieldset>
                            <legend>Tria l'import</legend>
                            <div class="amounts">
                                <?php foreach ($suggested as $s): ?>
                                    <label class="amount"><input type="radio" name="amount" value="<?= e(cents_to_input($s)) ?>" <?= $selected === cents_to_input($s) ? 'checked' : '' ?>><span><?= e(money($s, false)) ?></span></label>
                                <?php endforeach; ?>
                                <label class="amount"><input type="radio" name="amount" value="other" <?= $selected === 'other' ? 'checked' : '' ?>><span>Altre</span></label>
                            </div>
                            <div class="field other-amount">
                                <label for="amount_other">Import (€)</label>
                                <input type="text" inputmode="decimal" id="amount_other" name="amount_other" value="<?= e(old('amount_other')) ?>" placeholder="Mínim <?= e(money(max(100, (int)$c['min_donation_cents']))) ?>">
                            </div>
                        </fieldset>
                        <div class="field"><label for="d-name">Nom</label><input id="d-name" name="name" value="<?= e(old('name')) ?>" autocomplete="name"></div>
                        <div class="field"><label for="d-email">Correu electrònic *</label><input id="d-email" type="email" name="email" required value="<?= e(old('email')) ?>" autocomplete="email"></div>
                        <div class="field"><label for="d-msg">Missatge (opcional)</label><textarea id="d-msg" name="message" rows="2" maxlength="500"><?= e(old('message')) ?></textarea></div>
                        <label class="check"><input type="checkbox" name="anonymous" value="1" <?= old('anonymous') ? 'checked' : '' ?>> No mostrar el meu nom públicament</label>
                        <button class="btn btn-red btn-block btn-lg" type="submit"><?= icon('heart', 'icon icon-sm') ?> Fer la donació</button>
                        <p class="secure"><?= icon('shield', 'icon icon-sm') ?> Pagament segur amb targeta, Apple Pay o Google Pay mitjançant Stripe.</p>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">Aquesta campanya ja no accepta donacions. Moltes gràcies a totes les persones que hi heu col·laborat!</div>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</section>
