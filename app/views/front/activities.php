<section class="page-hero small">
    <div class="container">
        <p class="eyebrow">Agenda</p>
        <h1>Activitats</h1>
        <p class="lead">Festes, tallers, sortides i activitats culturals per als infants i les famílies. Inscriu-t'hi en línia i rep les entrades al correu.</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <?php if (!$upcoming): ?><p class="empty">Ara mateix no hi ha activitats programades. Torna aviat!</p><?php endif; ?>
        <div class="grid grid-3">
            <?php foreach ($upcoming as $a) { partial('activity_card', ['a' => $a]); } ?>
        </div>
        <?php if ($past): ?>
            <h2 class="mt">Activitats anteriors</h2>
            <div class="grid grid-3 past">
                <?php foreach ($past as $a) { partial('activity_card', ['a' => $a]); } ?>
            </div>
        <?php endif; ?>
    </div>
</section>
