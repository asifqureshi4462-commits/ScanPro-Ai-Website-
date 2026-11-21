<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth/admin_auth.php';

startAdminSession();
applyAdminSecurityHeaders();

if (getAuthenticatedAdmin() !== null) {
    header('Location: ' . getAdminRootUrlPath() . '/index.php', true, 302);
    exit;
}

// Redirect to one-time setup only if no admin exists yet and setup isn't locked
if (!isAdminSetupLockedOnDisk()) {
    try {
        $pdo = getDbConnection();
        if (countAdminAccounts($pdo) === 0 && is_file(__DIR__ . '/setup.php')) {
            header('Location: ' . getAdminRootUrlPath() . '/setup.php', true, 302);
            exit;
        }
    } catch (Throwable $e) {
        error_log('[ScanPro Admin Login Setup Check] ' . $e->getMessage());
    }
}

$flashes = consumeAdminFlashes();
$csrfToken = getAdminCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <title>Admin Sign In | ScanPro AI Control Center</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0B0B0F;
            color: #F8FAFC;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 16px;
            background-image:
                radial-gradient(circle at 20% 20%, rgba(34, 211, 238, 0.11), transparent 42%),
                radial-gradient(circle at 80% 80%, rgba(37, 99, 235, 0.10), transparent 42%);
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #17171D;
            border: 1px solid rgba(34, 211, 238, 0.25);
            border-radius: 18px;
            padding: 30px 24px;
            box-shadow: 0 24px 56px rgba(0, 0, 0, 0.70);
        }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 22px; }
        .brand-mark {
            width: 44px; height: 44px; border-radius: 12px;
            background: linear-gradient(135deg, #22D3EE, #16C7B5);
            color: #061B32; display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
        .brand-name { font-size: 1.15rem; font-weight: 800; color: #FFFFFF; }
        .brand-sub { font-size: 0.72rem; letter-spacing: 1.2px; text-transform: uppercase; color: #22D3EE; font-weight: 700; margin-top: 2px; }
        h1 { font-size: 1.4rem; margin-bottom: 6px; }
        .subtitle { font-size: 0.88rem; color: #94A3B8; margin-bottom: 22px; }
        .alert {
            padding: 12px 14px; border-radius: 10px; font-size: 0.86rem; margin-bottom: 16px;
            display: flex; align-items: flex-start; gap: 10px; line-height: 1.45;
        }
        .alert-error { background: rgba(239, 68, 68, 0.14); border: 1px solid rgba(239, 68, 68, 0.42); color: #FECACA; }
        .alert-warning { background: rgba(245, 158, 11, 0.14); border: 1px solid rgba(245, 158, 11, 0.40); color: #FDE68A; }
        .alert-success { background: rgba(16, 185, 129, 0.14); border: 1px solid rgba(16, 185, 129, 0.40); color: #A7F3D0; }
        .alert-info { background: rgba(34, 211, 238, 0.12); border: 1px solid rgba(34, 211, 238, 0.35); color: #CFFAFE; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 0.84rem; font-weight: 600; color: #E2E8F0; margin-bottom: 7px; }
        .input-wrap { position: relative; }
        input {
            width: 100%; padding: 12px 14px; border-radius: 10px; font-size: 16px;
            background: #0B0B0F; border: 1px solid rgba(255,255,255,0.12); color: #FFFFFF;
            outline: none; transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        input:focus { border-color: #22D3EE; box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.18); }
        .pwd-input { padding-right: 44px; }
        .toggle-btn {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            background: transparent; border: none; color: #94A3B8; padding: 8px; cursor: pointer;
        }
        .toggle-btn:hover { color: #22D3EE; }
        .btn-login {
            width: 100%; margin-top: 6px; padding: 13px 20px; border: none; border-radius: 11px;
            background: linear-gradient(135deg, #22D3EE, #16C7B5); color: #061B32;
            font-weight: 800; font-size: 0.96rem; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            transition: opacity 0.2s ease, transform 0.15s ease;
        }
        .btn-login:hover:not(:disabled) { opacity: 0.94; transform: translateY(-1px); }
        .btn-login:disabled { opacity: 0.65; cursor: progress; }
        .footer-note {
            margin-top: 20px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.08);
            display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: #64748B;
        }
        .footer-note a { color: #22D3EE; text-decoration: none; font-weight: 600; }
        .footer-note a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<main class="login-card">
    <div class="brand">
        <div class="brand-mark"><i class="fa-solid fa-sliders" aria-hidden="true"></i></div>
        <div>
            <div class="brand-name">ScanPro AI</div>
            <div class="brand-sub">Unified Control Center</div>
        </div>
    </div>

    <h1>Administrator Sign In</h1>
    <p class="subtitle">Authorized personnel only. All sign-in attempts are audited.</p>

    <div id="loginAlerts" aria-live="polite">
        <?php foreach ($flashes as $flash): ?>
            <?php $ftype = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info'; ?>
            <div class="alert alert-<?= e($ftype) ?>" role="alert">
                <i class="fa-solid fa-circle-info" aria-hidden="true" style="margin-top:2px;"></i>
                <div><?= e($flash['message']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <form id="adminLoginForm" action="../backend/api/admin/auth.php" method="POST" novalidate>
        <?= adminCsrfInput() ?>
        <input type="hidden" name="action" value="login">

        <div class="form-group">
            <label for="adminIdentity">Username or Email</label>
            <input
                type="text"
                id="adminIdentity"
                name="identity"
                required
                maxlength="190"
                autocomplete="username"
                placeholder="Enter admin username or email"
            >
        </div>

        <div class="form-group">
            <label for="adminPassword">Password</label>
            <div class="input-wrap">
                <input
                    type="password"
                    id="adminPassword"
                    name="password"
                    class="pwd-input"
                    required
                    autocomplete="current-password"
                    placeholder="Enter admin password"
                >
                <button type="button" class="toggle-btn" id="toggleAdminPwd" aria-label="Show password">
                    <i class="fa-regular fa-eye" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <button type="submit" id="adminLoginBtn" class="btn-login">
            <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
            <span>Sign In to Control Center</span>
        </button>
    </form>

    <div class="footer-note">
        <span><i class="fa-solid fa-lock" aria-hidden="true"></i> Isolated Session</span>
        <a href="../frontend/index.php">&larr; Back to Website</a>
    </div>
</main>

<script>
(function () {
    const form = document.getElementById('adminLoginForm');
    const alerts = document.getElementById('loginAlerts');
    const btn = document.getElementById('adminLoginBtn');
    const toggle = document.getElementById('toggleAdminPwd');
    const pwdInput = document.getElementById('adminPassword');

    if (toggle && pwdInput) {
        toggle.addEventListener('click', function () {
            const show = pwdInput.type === 'password';
            pwdInput.type = show ? 'text' : 'password';
            const icon = toggle.querySelector('i');
            if (icon) icon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        });
    }

    function showError(msg) {
        alerts.innerHTML = '';
        const div = document.createElement('div');
        div.className = 'alert alert-error';
        div.setAttribute('role', 'alert');
        div.innerHTML = '<i class="fa-solid fa-circle-exclamation" aria-hidden="true" style="margin-top:2px;"></i>';
        const txt = document.createElement('div');
        txt.textContent = msg;
        div.appendChild(txt);
        alerts.appendChild(div);
    }

    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const identity = document.getElementById('adminIdentity').value.trim();
            const password = pwdInput.value;
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '';

            if (!identity || !password) {
                showError('Please enter your admin username/email and password.');
                return;
            }

            btn.disabled = true;
            btn.querySelector('span').textContent = 'Verifying...';

            try {
                const res = await fetch(form.getAttribute('action'), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': csrf
                    },
                    body: JSON.stringify({ action: 'login', identity, password, csrf_token: csrf })
                });

                let data = null;
                try { data = await res.json(); } catch (err) {}

                if (!data) {
                    showError('Unexpected server response. Please reload and try again.');
                    btn.disabled = false;
                    btn.querySelector('span').textContent = 'Sign In to Control Center';
                    return;
                }

                if (!res.ok || !data.success) {
                    showError(data.message || 'Invalid credentials.');
                    btn.disabled = false;
                    btn.querySelector('span').textContent = 'Sign In to Control Center';
                    return;
                }

                window.location.href = data.redirect || 'index.php';
            } catch (err) {
                showError('Network error while connecting to server.');
                btn.disabled = false;
                btn.querySelector('span').textContent = 'Sign In to Control Center';
            }
        });
    }
})();
</script>
</body>
</html>