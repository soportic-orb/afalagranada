<?php defined('BASE_PATH') || exit; ?>
<div class="checkin">
    <div class="panel">
        <div class="field">
            <label for="ci-activity">Activitat</label>
            <select id="ci-activity">
                <option value="0">Qualsevol activitat</option>
                <?php foreach ($activities as $act): ?><option value="<?= (int)$act['id'] ?>"><?= e($act['title']) ?><?= $act['starts_at'] ? ' — ' . e(date('d/m/Y', strtotime($act['starts_at']))) : '' ?></option><?php endforeach; ?>
            </select>
        </div>
        <div id="reader" class="reader"></div>
        <div class="row-gap">
            <button class="btn btn-primary" id="ci-start">Activar càmera</button>
            <button class="btn" id="ci-stop" hidden>Aturar</button>
        </div>
        <form id="ci-manual" class="inline-form">
            <input id="ci-code" placeholder="O introdueix el codi de l'entrada" autocomplete="off">
            <button class="btn">Validar</button>
        </form>
    </div>
    <div id="ci-result" class="ci-result" hidden></div>
    <div class="panel"><h2>Historial d'aquesta sessió</h2><ul id="ci-history" class="ci-history"></ul></div>
</div>
<script>window.CHECKIN = {url: <?= json_encode(url('admin/checkin/validate')) ?>};</script>
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="<?= asset('js/checkin.js') ?>" defer></script>
