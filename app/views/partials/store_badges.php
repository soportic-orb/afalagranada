<?php defined('BASE_PATH') || exit; ?>
<div class="store-badges">
    <?php if ($u = setting('app_ios_url')): ?>
        <a class="store-badge" href="<?= e($u) ?>" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M16.4 12.6c0-2.6 2.1-3.8 2.2-3.9-1.2-1.8-3.1-2-3.7-2-1.6-.2-3.1.9-3.9.9-.8 0-2-.9-3.4-.9-1.7 0-3.3 1-4.2 2.6-1.8 3.1-.5 7.7 1.3 10.2.9 1.2 1.9 2.6 3.2 2.6 1.3-.1 1.8-.8 3.3-.8s2 .8 3.4.8c1.4 0 2.3-1.3 3.1-2.5 1-1.4 1.4-2.8 1.4-2.9-.1 0-2.7-1-2.7-4.1zM13.9 5c.7-.9 1.2-2 1-3.2-1 0-2.3.7-3 1.6-.7.8-1.2 2-1.1 3.1 1.2.1 2.3-.6 3.1-1.5z"/></svg>
            <span><small>Descarrega-la a</small>App Store</span>
        </a>
    <?php endif; ?>
    <?php if ($u = setting('app_android_url')): ?>
        <a class="store-badge" href="<?= e($u) ?>" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3.6 1.8c-.3.3-.4.7-.4 1.2v18c0 .5.1.9.4 1.2l10-10.2zM14.7 13.1l2.9 2.9-11.7 6.7c-.4.2-.8.3-1.1.2zM18.9 9.2 21 10.4c.9.5.9 1.8 0 2.4l-2.1 1.2-3.1-3.1zM4.8 1c.3 0 .7 0 1.1.3l11.7 6.7-2.9 2.9z"/></svg>
            <span><small>Disponible a</small>Google Play</span>
        </a>
    <?php endif; ?>
</div>
