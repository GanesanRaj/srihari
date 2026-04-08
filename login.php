<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle login form submission
$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once __DIR__ . '/config/config.php';

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            // Authenticate against tbl_user
            $stmt = $pdo->prepare("SELECT * FROM `tbl_user` WHERE username = :username");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Check password (plain text comparison as per existing API)
                if ($password == $user['password']) {
                    // Set session variables
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_id'] = $user['user_id'] ?? $user['id'] ?? 0;
                    $_SESSION['role_id'] = $user['role_id'] ?? 0;
                    $_SESSION['user_type'] = (!empty($user['user_type']) ? $user['user_type'] : ($user['clientaccess'] == 1 ? 'client' : 'both'));
                    $_SESSION['client_ids'] = !empty($user['client_ids']) ? $user['client_ids'] : '';
                    $_SESSION['branch_ids'] = !empty($user['branch_ids']) ? $user['branch_ids'] : '';

                    // Fetch extended user details (Employee Name, Designation)
                    if (function_exists('fetch_user_extended_details')) {
                        $details = fetch_user_extended_details($user['user_id'], $user['username'], $user['role_id'], $_SESSION['user_type']);
                    } else {
                        $details = ['name' => $user['username'], 'designation' => 'User'];
                    }

                    $_SESSION['employee_name'] = $details['name'];
                    $_SESSION['designation'] = $details['designation'];

                    // Store device/browser information
                    $_SESSION['device_info'] = [
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                        'login_time' => date('Y-m-d H:i:s'),
                        'session_id' => session_id()
                    ];

                    // Redirect to dashboard
                    header("Location: index.php");
                    exit();
                } else {
                    $error = 'Invalid password.';
                }
            } else {
                $error = 'User credentials not found.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Sri Hari Agencies — Sign In</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="assets/Ganesan/srihari.png">
    <meta name="keywords" content="Sri Hari Agencies, eCommerce Shipping in India">
    <meta name="title" content="Sri Hari Agencies, Shipping and Courier Services in India." />
    <meta name="description" content="Sri Hari Agencies is leading logistics, shipping and courier solution provider in India." />
    <meta property="og:url" content="" />
    <meta property="og:image" content="assets/Ganesan/srihari.png" />
    <meta property="og:title" content="Sri Hari Agencies, Shipping and Courier Services in India." />
    <meta property="og:description" content="Sri Hari Agencies is leading logistics, shipping and courier solution provider in India." />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- jQuery -->
    <script src="assets/Ganesan/jquery-3.3.1.min.js"></script>

    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-45KP6Z41QY"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'G-45KP6Z41QY');
    </script>

    <style>
        /* =============================================
           CSS VARIABLES — WHITE THEME
        ============================================= */
        :root {
            --white:       #ffffff;
            --gray-50:     #f8fafc;
            --gray-100:    #f1f5f9;
            --gray-200:    #e2e8f0;
            --gray-300:    #cbd5e1;
            --gray-400:    #94a3b8;
            --gray-500:    #64748b;
            --gray-700:    #334155;
            --gray-900:    #0f172a;
            --amber-600:   #d97706;
            --amber-500:   #f59e0b;
            --amber-400:   #fbbf24;
            --amber-50:    #fffbeb;
            --red-50:      #fef2f2;
            --red-300:     #fca5a5;
            --red-500:     #ef4444;
            --shadow-sm:   0 1px 3px rgba(15,23,42,0.08), 0 1px 2px rgba(15,23,42,0.06);
            --shadow-md:   0 4px 16px rgba(15,23,42,0.10), 0 2px 6px rgba(15,23,42,0.06);
            --shadow-lg:   0 12px 40px rgba(15,23,42,0.13), 0 4px 12px rgba(15,23,42,0.07);
            --font-display: 'Playfair Display', Georgia, serif;
            --font-body:    'Outfit', sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            font-family: var(--font-body);
            background: var(--gray-100);
            overflow: hidden;
        }

        /* =============================================
           PAGE SHELL
        ============================================= */
        .login-page {
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            background: var(--white);
        }

        /* =============================================
           LEFT PANEL — 70%  (image + overlay brand)
        ============================================= */
        .brand-panel {
            width: 70%;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        /* Full-panel background image */
        .brand-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        /* Gradient overlay: transparent top → dark navy bottom */
        .brand-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to bottom,
                rgba(255,255,255,0.08)  0%,
                rgba(15, 23, 42, 0.18) 40%,
                rgba(15, 23, 42, 0.72) 72%,
                rgba(10, 15, 28, 0.94) 100%
            );
            pointer-events: none;
        }

        /* Top-left logo badge */
        .brand-badge {
            position: absolute;
            top: 32px;
            left: 36px;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 18px 10px 12px;
            background: rgba(255,255,255,0.92);
            border-radius: 50px;
            box-shadow: var(--shadow-md);
            opacity: 0;
            animation: fadeDown 0.7s ease 0.1s forwards;
        }

        .brand-badge img {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .badge-text {
            display: flex;
            flex-direction: column;
            line-height: 1;
        }

        .badge-name {
            font-family: var(--font-display);
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--gray-900);
        }

        .badge-sub {
            font-size: 0.58rem;
            font-weight: 600;
            color: var(--amber-600);
            letter-spacing: 0.22em;
            text-transform: uppercase;
            margin-top: 2px;
        }

        /* Bottom content over image */
        .brand-content {
            position: relative;
            z-index: 10;
            padding: 0 44px 40px;
            color: var(--white);
        }

        .brand-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--amber-400);
            letter-spacing: 0.28em;
            text-transform: uppercase;
            margin-bottom: 14px;
            opacity: 0;
            animation: fadeUp 0.7s ease 0.3s forwards;
        }

        .brand-eyebrow::before {
            content: '';
            display: inline-block;
            width: 24px;
            height: 2px;
            background: var(--amber-400);
            border-radius: 2px;
        }

        .brand-headline {
            font-family: var(--font-display);
            font-size: clamp(2rem, 3.2vw, 3rem);
            font-weight: 700;
            color: var(--white);
            line-height: 1.16;
            margin-bottom: 16px;
            opacity: 0;
            animation: fadeUp 0.7s ease 0.5s forwards;
            text-shadow: 0 2px 12px rgba(0,0,0,0.3);
        }

        .brand-headline em {
            font-style: italic;
            color: var(--amber-400);
        }

        .brand-tagline {
            font-size: 0.88rem;
            font-weight: 300;
            color: rgba(255,255,255,0.72);
            line-height: 1.7;
            max-width: 420px;
            margin-bottom: 28px;
            opacity: 0;
            animation: fadeUp 0.7s ease 0.65s forwards;
        }

        /* Stats bar */
        .stats-bar {
            display: flex;
            gap: 0;
            opacity: 0;
            animation: fadeUp 0.7s ease 0.8s forwards;
        }

        .stat-block {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 14px 24px;
            background: rgba(255,255,255,0.10);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.18);
        }

        .stat-block:first-child { border-radius: 12px 0 0 12px; }
        .stat-block:last-child  { border-radius: 0 12px 12px 0; }
        .stat-block + .stat-block { border-left: none; }

        .stat-num {
            font-family: var(--font-display);
            font-size: 1.45rem;
            font-weight: 700;
            color: var(--amber-400);
            line-height: 1;
        }

        .stat-lbl {
            font-size: 0.6rem;
            font-weight: 500;
            color: rgba(255,255,255,0.55);
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        /* =============================================
           RIGHT FORM PANEL — 30%
        ============================================= */
        .form-panel {
            width: 30%;
            background: var(--white);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: stretch;
            padding: 0;
            position: relative;
            border-left: 1px solid var(--gray-200);
            overflow-y: auto;
            box-shadow: -4px 0 24px rgba(15,23,42,0.06);
        }

        /* Amber accent top bar */
        .form-panel::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--amber-600), var(--amber-400));
            z-index: 1;
        }

        /* Form card — fills the entire panel */
        .form-card {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            width: 100%;
            padding: 52px 44px 44px;
            background: var(--white);
        }

        /* Form header */
        .form-top {
            text-align: center;
            margin-bottom: 32px;
            opacity: 0;
            animation: fadeDown 0.7s ease 0.2s forwards;
        }

        /* Big user icon circle */
        .form-icon-wrap {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--amber-600), var(--amber-400));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 6px 24px rgba(217,119,6,0.32);
        }

        .form-icon-wrap i {
            font-size: 2.4rem;
            color: var(--white);
        }

        .form-heading {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1.1;
            margin-bottom: 6px;
        }

        .form-sub {
            font-size: 0.88rem;
            font-weight: 300;
            color: var(--gray-400);
        }

        /* Error alert */
        .login-error {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            padding: 11px 13px;
            background: var(--red-50);
            border: 1px solid rgba(239,68,68,0.22);
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 0.79rem;
            color: #b91c1c;
            animation: fadeDown 0.3s ease;
        }

        .login-error i { color: var(--red-500); flex-shrink: 0; margin-top: 1px; }

        .err-close {
            margin-left: auto;
            background: none;
            border: none;
            color: #b91c1c;
            cursor: pointer;
            font-size: 1.1rem;
            line-height: 1;
            padding: 0;
            opacity: 0.6;
            transition: opacity 0.2s;
        }

        .err-close:hover { opacity: 1; }

        /* Form body */
        .login-form {
            opacity: 0;
            animation: fadeUp 0.7s ease 0.4s forwards;
        }

        .field-group { margin-bottom: 20px; }

        .field-lbl {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gray-500);
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .field-wrap { position: relative; }

        .f-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-300);
            font-size: 1.15rem;
            pointer-events: none;
            transition: color 0.22s;
            z-index: 2;
        }

        .field-input {
            width: 100%;
            padding: 15px 16px 15px 46px;
            background: var(--gray-50);
            border: 1.5px solid var(--gray-200);
            border-radius: 10px;
            color: var(--gray-900);
            font-family: var(--font-body);
            font-size: 1.05rem;
            font-weight: 400;
            outline: none;
            transition: border-color 0.22s, background 0.22s, box-shadow 0.22s;
            -webkit-appearance: none;
        }

        .field-input::placeholder { color: var(--gray-300); font-weight: 300; }

        .field-input:focus {
            border-color: var(--amber-500);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(245,158,11,0.12);
        }

        .field-wrap:focus-within .f-icon { color: var(--amber-500); }

        /* Password toggle */
        .pwd-toggle {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-300);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 0;
            transition: color 0.2s;
            z-index: 2;
        }

        .pwd-toggle:hover { color: var(--amber-500); }

        /* Remember / Forgot */
        .form-extras {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 4px 0 28px;
        }

        .remember-lbl {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.88rem;
            color: var(--gray-500);
            user-select: none;
        }

        .remember-lbl input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--amber-500);
            cursor: pointer;
        }

        .forgot-link {
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--amber-600);
            text-decoration: none;
            transition: color 0.2s;
        }

        .forgot-link:hover { color: var(--amber-500); }

        /* Login CTA button */
        .btn-login {
            width: 100%;
            padding: 15px 20px;
            background: linear-gradient(135deg, var(--amber-600), var(--amber-500));
            border: none;
            border-radius: 10px;
            color: var(--white);
            font-family: var(--font-body);
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: transform 0.18s, box-shadow 0.22s;
            margin-bottom: 0;
            box-shadow: 0 4px 18px rgba(217,119,6,0.32);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, transparent 55%);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(217,119,6,0.36);
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(217,119,6,0.18);
        }


        /* =============================================
           KEYFRAMES
        ============================================= */
        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* =============================================
           LOADER
        ============================================= */
        #page-loader {
            position: fixed;
            inset: 0;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.45s ease;
        }

        #page-loader.hidden { opacity: 0; pointer-events: none; }

        .loader-ring {
            width: 40px;
            height: 40px;
            border: 3px solid var(--gray-100);
            border-top-color: var(--amber-500);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* Scrollbar */
        .form-panel::-webkit-scrollbar { width: 4px; }
        .form-panel::-webkit-scrollbar-track { background: transparent; }
        .form-panel::-webkit-scrollbar-thumb { background: var(--gray-200); border-radius: 2px; }

        /* Responsive */
        @media (max-width: 860px) {
            .brand-panel { display: none; }
            .form-panel  { width: 100%; border-left: none; }
        }
    </style>
</head>

<body>

<!-- Loader -->
<div id="page-loader">
    <div class="loader-ring"></div>
</div>

<div class="login-page">

    <!-- ==========================================
         LEFT: IMAGE + BRAND OVERLAY  (70%)
    =========================================== -->
    <div class="brand-panel">

        <!-- Full-panel image -->
        <img src="assets/Ganesan/auth.jpg" alt="Sri Hari Agencies" class="brand-img">

        <!-- Gradient overlay -->
        <div class="brand-overlay"></div>

        <!-- Top-left logo badge -->
        <div class="brand-badge">
            <img src="assets/Ganesan/srihari.png" alt="Sri Hari Agencies logo">
            <div class="badge-text">
                <span class="badge-name">Sri Hari Agencies</span>
                <!--<span class="badge-sub">Logistics &amp; Courier</span>-->
            </div>
        </div>

        <!-- Bottom overlay content -->
        <div class="brand-content">
            <div class="brand-eyebrow">Trusted since inception</div>
            <h1 class="brand-headline">
                Legacy in Service, <em>Excellence</em><br>
                in Delivery.
            </h1>
            <p class="brand-tagline">
                India's preferred courier and logistics partner &mdash; connecting businesses and customers across 500+ cities with speed, reliability, and care.
            </p>

            <!-- Stats -->
            <div class="stats-bar">
                <div class="stat-block">
                    <span class="stat-num">10L+</span>
                    <span class="stat-lbl">Monthly Shipments</span>
                </div>
                <div class="stat-block">
                    <span class="stat-num">500+</span>
                    <span class="stat-lbl">Cities</span>
                </div>
                <div class="stat-block">
                    <span class="stat-num">99.2%</span>
                    <span class="stat-lbl">On-time Rate</span>
                </div>
                <div class="stat-block">
                    <span class="stat-num">15K+</span>
                    <span class="stat-lbl">Active Clients</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         RIGHT: FORM PANEL  (30%)
    =========================================== -->
    <div class="form-panel">

        <div class="form-card">

            <div class="form-top">
                <!-- Big icon -->
                <div class="form-icon-wrap">
                    <i class="fa fa-users"></i>
                </div>
                <h2 class="form-heading">Sign In</h2>
                <p class="form-sub">Access your logistics dashboard</p>
            </div>

            <!-- Error -->
            <div class="userContent">
                <?php if (!empty($error)): ?>
                    <div class="login-error">
                        <i class="fa fa-circle-exclamation"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                        <button class="err-close" onclick="this.closest('.login-error').remove()" aria-label="Close">&times;</button>
                    </div>
                <?php endif; ?>
            </div>

            <form class="login-form" action="" autocomplete="off" method="post" accept-charset="utf-8">

                <!-- Username -->
                <div class="field-group">
                    <label class="field-lbl" for="login-username">Username</label>
                    <div class="field-wrap">
                        <i class="fa fa-user f-icon"></i>
                        <input
                            type="text"
                            id="login-username"
                            name="username"
                            class="field-input"
                            placeholder="Username"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            required
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="field-group">
                    <label class="field-lbl" for="password">Password</label>
                    <div class="field-wrap">
                        <i class="fa fa-lock f-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="field-input"
                            placeholder="Password"
                            required
                        >
                        <button type="button" class="pwd-toggle" id="pwdToggle" aria-label="Toggle password">
                            <i class="fa fa-eye" id="pwdIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember + Forgot -->
                <div class="form-extras">
                    <label class="remember-lbl">
                        <input type="checkbox" name="remember">
                        Remember me
                    </label>
                    <a href="" class="forgot-link">Forgot Password?</a>
                </div>

                <!-- CTA -->
                <button type="submit" class="btn-login">
                    LOGIN
                </button>

            </form>
        </div>
    </div>
</div>

<script>
    // Hide loader
    window.addEventListener('load', function () {
        setTimeout(function () {
            document.getElementById('page-loader').classList.add('hidden');
        }, 300);
    });

    // Password toggle
    document.getElementById('pwdToggle').addEventListener('click', function () {
        var input = document.getElementById('password');
        var icon  = document.getElementById('pwdIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
</script>

<!-- Security & input-validation scripts (preserved) -->
<script language="javascript">
    function checkInput(ob) {
        var invalidChars = /[^0-9.]/gi;
        if (invalidChars.test(ob.value)) { ob.value = ob.value.replace(invalidChars, ""); }
    }
    function removeSpace(ob) {
        var invalidChars = /\s/g;
        if (invalidChars.test(ob.value)) { ob.value = ob.value.replace(invalidChars, ""); }
    }
    function checkNumber(ob) {
        var invalidChars = /[^0-9.,]/gi;
        if (invalidChars.test(ob.value)) { ob.value = ob.value.replace(invalidChars, ""); }
    }
    var csrfName = 'csrf_trux', csrfHash = 'cf959232b9ae36e57d8ffabdd529f5e8';
    document.addEventListener("contextmenu", function (e) { e.preventDefault(); }, false);
    document.onkeydown = function (e) {
        if (e.ctrlKey && e.keyCode === 85) { return false; }
        if (e.shiftKey && (e.which == 188 || e.which == 190)) { e.preventDefault(); }
    };
    $(function () {
        $('input').on("keydown", function (e) {
            if (e.shiftKey && (e.which == 188 || e.which == 190)) { e.preventDefault(); }
        });
    });
    $(document).on('input', 'input, textarea', function (e) {
        if (e.originalEvent.inputType == 'insertFromPaste') {
            var regex = new RegExp("^[a-zA-Z0-9@. ]+$");
            if (!regex.test($(this).val())) {
                alert('Only english language characters accepted.');
                $(this).val($(this).val().replace(/[^A-Za-z0-9@. ]/g, '').trim());
            }
        }
    });
</script>
<script>
    $(document).ready(function () {
        var invalidChars = /[&\%#;]/;
        $('input').keyup(function () {
            if (invalidChars.test($(this).val())) {
                var str = $(this).val();
                $(this).val(str.replace(invalidChars, ""));
                alert("These 5 special characters(& '/' % # ;) are not allowed.");
            }
        });
    });
</script>

</body>
</html>
