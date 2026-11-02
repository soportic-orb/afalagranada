<?php defined('BASE_PATH') || exit; ?>
<section class="section sent-section">
    <canvas class="confetti-canvas" data-confetti aria-hidden="true"></canvas>
    <div class="container narrow center sent-box">
        <div class="sent-emoji" role="img" aria-label="Celebració">🎉</div>
        <h1>Missatge rebut correctament</h1>
        <p class="lead">Et donarem resposta el més aviat possible.</p>
        <div class="sent-actions">
            <a class="btn btn-primary btn-lg" href="<?= url('/') ?>">Torna a l'inici</a>
            <a class="btn btn-ghost btn-lg" href="<?= url('activitats') ?>">Veure activitats</a>
        </div>
    </div>
</section>
