<?php
$errors = $errors ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#235a43">
    <title>Masuk | Mira Plastik</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/mira.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body class="auth-page">
    <div class="auth-shell">
        <aside class="auth-brand-panel">
            <a class="auth-brand" href="/login" aria-label="Mira Plastik">
                <span class="brand-mark">M<span>.</span></span>
                <span class="brand-copy"><strong>Mira Plastik</strong><small>OPERASIONAL TOKO</small></span>
            </a>
            <div class="auth-brand-copy">
                <p class="auth-overline">TOKO MIRA PLASTIK · CABANG UTAMA</p>
                <h2>Selamat<br>datang kembali.</h2>
            </div>
            <div class="auth-scene" aria-hidden="true">
                <div class="scene-frame scene-frame-back"></div>
                <div class="scene-frame scene-frame-front"><span>M</span></div>
                <div class="scene-accent"></div>
                <div class="scene-caption">TOKO MIRA<br>SEJAK 2026</div>
            </div>
            <div class="auth-brand-footer"><span>RUANG KERJA</span><span>01 / 01</span></div>
        </aside>
        <main class="auth-content">
            <div class="auth-form-wrap">
                <p class="auth-kicker">AKSES PEMILIK</p>
                <h1>Masuk ke akun Anda</h1>
                <p class="auth-intro">Gunakan email dan kata sandi akun pemilik toko.</p>

                <?php if (! empty($error)): ?>
                    <div class="auth-alert" role="alert"><?= esc($error) ?></div>
                <?php endif ?>

                <form class="auth-form" action="/login" method="post">
                    <?= csrf_field() ?>
                    <div class="auth-field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="<?= esc(old('email') ?? '') ?>" autocomplete="username" maxlength="254" required>
                        <?php if (isset($errors['email'])): ?><small class="auth-field-error"><?= esc($errors['email']) ?></small><?php endif ?>
                    </div>
                    <div class="auth-field">
                        <label for="password">Kata sandi</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" maxlength="200" required>
                        <?php if (isset($errors['password'])): ?><small class="auth-field-error"><?= esc($errors['password']) ?></small><?php endif ?>
                    </div>
                    <button class="auth-submit" type="submit">Masuk <span aria-hidden="true">→</span></button>
                </form>
                <p class="auth-footnote">MIRA PLASTIK <span>/</span> SISTEM OPERASIONAL</p>
            </div>
        </main>
    </div>
</body>
</html>