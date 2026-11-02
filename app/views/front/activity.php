<?php defined('BASE_PATH') || exit; ?>
<?php
$max = max(1, (int)$a['max_per_registration']);
if ($state['left'] !== null) { $max = max(1, min($max, $state['left'])); }
?>
<section class="page-hero small<?= hero_bg('activities') ?>">
    <div class="container">
        <p class="eyebrow"><a href="<?= url('activitats') ?>">← Activitats</a></p>
        <h1><?= e($a['title']) ?></h1>
        <?php if ($a['summary']): ?><p class="lead"><?= e($a['summary']) ?></p><?php endif; ?>
    </div>
</section>
<section class="section">
    <div class="container layout-aside">
        <div>
            <?php if ($a['image']): ?><img class="rounded cover" src="<?= e(upload_url($a['image'])) ?>" alt=""><?php endif; ?>
            <div class="prose"><?= $a['description'] ?></div>
        </div>
        <aside class="sticky">
            <div class="card pad">
                <ul class="meta big">
                    <?php if ($a['starts_at']): ?><li><?= icon('calendar', 'icon icon-sm') ?> <span><?= e(fmt_date($a['starts_at'], true)) ?><?= $a['ends_at'] ? '<br><small>fins ' . e(date('d/m/Y', strtotime($a['ends_at'])) === date('d/m/Y', strtotime($a['starts_at'])) ? date('H:i', strtotime($a['ends_at'])) . ' h' : fmt_date($a['ends_at'], true)) . '</small>' : '' ?></span></li><?php endif; ?>
                    <?php if ($a['location']): ?><li><?= icon('pin', 'icon icon-sm') ?> <span><?= e($a['location']) ?></span></li><?php endif; ?>
                    <li><?= icon('tag', 'icon icon-sm') ?> <span><?= $a['price_cents'] ? e(money((int)$a['price_cents'])) . ' per participant' . ($a['member_price_cents'] !== null ? '<br><small>Socis/es AFA: ' . e(money((int)$a['member_price_cents'])) . '</small>' : '') : 'Activitat gratuïta' ?></span></li>
                    <?php if ($state['left'] !== null): ?><li><?= icon('users', 'icon icon-sm') ?> <span><?= (int)$state['left'] ?> places disponibles</span></li><?php endif; ?>
                </ul>
            </div>
            <div class="card pad" id="inscripcio">
                <h2 class="h3">Inscripció</h2>
                <?php if (!$state['open']): ?>
                    <div class="alert alert-info"><?= e($state['reason'] ?: 'Inscripcions no disponibles.') ?></div>
                <?php else: ?>
                    <?php if (input('cancel')): ?><div class="alert alert-info">Has cancel·lat el pagament. La inscripció no s'ha completat.</div><?php endif; ?>
                    <form method="post" action="<?= url('activitats/' . $a['slug'] . '/inscripcio') ?>" class="form" id="reg-form"
                          data-price="<?= (int)$a['price_cents'] ?>" data-member-price="<?= $a['member_price_cents'] === null ? '' : (int)$a['member_price_cents'] ?>" data-max="<?= $max ?>">
                        <?= csrf_field() ?>
                        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                        <fieldset>
                            <legend>Participants</legend>
                            <div class="attendees">
                                <div class="attendee">
                                    <input name="attendee[]" placeholder="Nom i cognoms" required aria-label="Nom del participant">
                                    <?php if ($a['ask_course']): ?><input name="course[]" placeholder="Curs (ex. 3r)" class="course" aria-label="Curs"><?php endif; ?>
                                    <button type="button" class="remove" aria-label="Treure participant">×</button>
                                </div>
                            </div>
                            <?php if ($max > 1): ?><button type="button" class="btn btn-small btn-outline add-attendee">+ Afegir participant</button><?php endif; ?>
                        </fieldset>
                        <fieldset>
                            <legend>Persona responsable</legend>
                            <div class="field"><label for="r-name">Nom i cognoms *</label><input id="r-name" name="name" required value="<?= e(old('name')) ?>" autocomplete="name"></div>
                            <div class="field"><label for="r-email">Correu electrònic *</label><input id="r-email" type="email" name="email" required value="<?= e(old('email')) ?>" autocomplete="email"><small>Hi rebràs les entrades amb el codi QR.</small></div>
                            <div class="field"><label for="r-phone">Telèfon</label><input id="r-phone" type="tel" name="phone" value="<?= e(old('phone')) ?>" autocomplete="tel"></div>
                            <?php if ($a['member_price_cents'] !== null): ?>
                                <label class="check"><input type="checkbox" name="is_member" value="1" <?= old('is_member') ? 'checked' : '' ?>> Som família sòcia de l'AFA</label>
                            <?php endif; ?>
                            <div class="field"><label for="r-notes">Observacions (al·lèrgies, etc.)</label><textarea id="r-notes" name="notes" rows="2"><?= e(old('notes')) ?></textarea></div>
                            <label class="check"><input type="checkbox" name="accept" value="1" required> Accepto la <a href="<?= e(url(ltrim(setting('privacy_url', '/privacitat'), '/'))) ?>" target="_blank">política de privacitat</a></label>
                        </fieldset>
                        <div class="total">Total: <strong data-total><?= e(money((int)$a['price_cents'])) ?></strong></div>
                        <button class="btn btn-accent btn-block btn-lg" type="submit"><?= $a['price_cents'] || $a['member_price_cents'] ? 'Continuar amb el pagament' : 'Confirmar inscripció' ?></button>
                        <?php if ($a['price_cents']): ?><p class="secure"><?= icon('shield', 'icon icon-sm') ?> Pagament segur amb Stripe.</p><?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</section>
