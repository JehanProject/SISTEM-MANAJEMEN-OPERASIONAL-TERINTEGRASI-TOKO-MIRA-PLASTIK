<?php
$errors = $errors ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#235a43">
    <title>Siapkan akun | Mira Plastik</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/mira.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body class="auth-page">
    <div class="auth-shell">
        <aside class="auth-brand-panel">
            <a class="auth-brand" href="/setup" aria-label="Mira Plastik">
                <span class="brand-mark">M<span>.</span></span>
                <span class="brand-copy"><strong>Mira Plastik</strong><small>OPERASIONAL TOKO</small></span>
            </a>
            <div class="auth-brand-copy">
                <p class="auth-overline">TOKO MIRA PLASTIK · CABANG UTAMA</p>
                <h2>Mulai dari<br>akun pemilik.</h2>
            </div>
            <div class="auth-scene" aria-hidden="true">
                <div class="scene-frame scene-frame-back"></div>
                <div class="scene-frame scene-frame-front"><span>M</span></div>
                <div class="scene-accent"></div>
                <div class="scene-caption">TOKO MIRA<br>SEJAK 2026</div>
            </div>
            <div class="auth-brand-footer"><span>PENYIAPAN AKUN</span><span>01 / 01</span></div>
        </aside>
        <main class="auth-content">
            <div class="auth-form-wrap">
                <p class="auth-kicker">PENYIAPAN PERTAMA</p>
                <h1>Buat akun pemilik</h1>
                <p class="auth-intro">Akun ini digunakan untuk masuk ke sistem operasional toko.</p>

                <?php if (! empty($setupLocked)): ?>
                    <div class="auth-alert" role="alert">Setup terkunci. Atur <code>auth.setupToken</code> minimal 32 karakter di file <code>.env</code> untuk melanjutkan.</div>
                <?php else: ?>
                <?php if (! empty($error)): ?>
                    <div class="auth-alert" role="alert"><?= esc($error) ?></div>
                <?php endif ?>

                <form class="auth-form" action="/setup" method="post">
                    <?= csrf_field() ?>
                    <div class="auth-field">
                        <label for="setup_token">Token setup</label>
                        <input id="setup_token" name="setup_token" type="password" autocomplete="off" maxlength="256" required>
                    </div>
                    <div class="auth-field">
                        <label for="name">Nama pemilik</label>
                        <input id="name" name="name" type="text" value="<?= esc(old('name') ?? '') ?>" autocomplete="name" maxlength="80" required>
                        <?php if (isset($errors['name'])): ?><small class="auth-field-error"><?= esc($errors['name']) ?></small><?php endif ?>
                    </div>
                    <div class="auth-field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="<?= esc(old('email') ?? '') ?>" autocomplete="email" maxlength="254" required>
                        <?php if (isset($errors['email'])): ?><small class="auth-field-error"><?= esc($errors['email']) ?></small><?php endif ?>
                    </div>
                    <div class="auth-field">
                        <label for="password">Kata sandi <span>minimal 8 karakter</span></label>
                        <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="200" required>
                        <?php if (isset($errors['password'])): ?><small class="auth-field-error"><?= esc($errors['password']) ?></small><?php endif ?>
                    </div>
                    <div class="auth-field">
                        <label for="password_confirm">Ulangi kata sandi</label>
                        <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="8" maxlength="200" required>
                        <?php if (isset($errors['password_confirm'])): ?><small class="auth-field-error"><?= esc($errors['password_confirm']) ?></small><?php endif ?>
                    </div>
                    <button class="auth-submit" type="submit">Buat akun & masuk <span aria-hidden="true">→</span></button>
                </form>
                <?php endif ?>
                <p class="auth-footnote">MIRA PLASTIK <span>/</span> SISTEM OPERASIONAL</p>
            </div>
        </main>
    </div>
</body>
</html>