<?php
// index.php - Main Application

// 1. Optimasi Session & Error Reporting
ini_set('display_errors', 0); // Sembunyikan error PHP kasar di production
ini_set('session.cookie_lifetime', 86400);
ini_set('session.gc_maxlifetime', 86400);
session_start();

// 2. Load Class dari folder 'libs'
require_once __DIR__ . '/libs/Database.php';
require_once __DIR__ . '/libs/User.php';
require_once __DIR__ . '/libs/Ticket.php';
require_once __DIR__ . '/libs/TicketResponse.php';

// Initialize database
$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$ticket = new Ticket($db);
$response = new TicketResponse($db);

$action = isset($_GET['action']) ? $_GET['action'] : 'home';

// --- LOGIC PHP (ROUTING) ---
switch($action) {
    case 'register':
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            if($user->register($_POST['nama'], $_POST['email'], $_POST['password'])) {
                $success = "Registrasi berhasil! Silakan login.";
            } else {
                $error = "Registrasi gagal! Email mungkin sudah terdaftar.";
            }
        }
        break;
    case 'login':
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            if($user->login($_POST['email'], $_POST['password'])) {
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_nama'] = $user->nama;
                $_SESSION['user_email'] = $user->email;
                $_SESSION['user_role'] = $user->role;
                header("Location: ?action=dashboard");
                exit();
            } else {
                $error = "Email atau password salah!";
            }
        }
        break;
    case 'logout':
        session_destroy();
        header("Location: ?action=home");
        exit();
        break;
    case 'create_ticket':
        if(!isset($_SESSION['user_id'])) { header("Location: ?action=login"); exit(); }
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $ticket_id = $ticket->create($_SESSION['user_id'], $_POST['judul'], $_POST['deskripsi'], $_POST['kategori'], $_POST['prioritas']);
            if($ticket_id) { header("Location: ?action=view_ticket&id=" . $ticket_id); exit(); } 
            else { $error = "Gagal membuat tiket!"; }
        }
        break;
    case 'add_response':
        if(!isset($_SESSION['user_id'])) { header("Location: ?action=login"); exit(); }
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            if($response->create($_POST['ticket_id'], $_SESSION['user_id'], $_POST['response'])) {
                header("Location: ?action=view_ticket&id=" . $_POST['ticket_id']); exit();
            }
        }
        break;
    case 'update_status':
        if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') { header("Location: ?action=login"); exit(); }
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            if($ticket->updateStatus($_POST['ticket_id'], $_POST['status'])) {
                header("Location: ?action=view_ticket&id=" . $_POST['ticket_id']); exit();
            }
        }
        break;
    case 'dashboard':
        if(!isset($_SESSION['user_id'])) { header("Location: ?action=login"); exit(); }
        $stmt = $ticket->getAll($_SESSION['user_id'], $_SESSION['user_role']);
        break;
    case 'view_ticket':
        if(!isset($_SESSION['user_id'])) { header("Location: ?action=login"); exit(); }
        $ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $ticket_data = $ticket->getById($ticket_id);
        if(!$ticket_data) { $ticket_not_found = true; break; }
        if($_SESSION['user_role'] != 'admin' && $ticket_data['user_id'] != $_SESSION['user_id']) { $access_denied = true; break; }
        $responses = $response->getByTicketId($ticket_id);
        break;
    case 'home': default: break;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT Cipta Hospital INA - Sistem Ticketing</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* --- CSS VARIABLES FOR THEME --- */
        :root {
            /* Light Mode (Original) */
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-bg: rgba(255, 255, 255, 0.98);
            --text-main: #333;
            --text-muted: #555;
            --header-bg: rgba(255, 255, 255, 0.15);
            --header-text: white;
            --input-bg: #f7fafc;
            --input-border: #cbd5e0;
            --btn-bg: rgba(255,255,255,0.25);
            --btn-hover: rgba(255,255,255,0.4);
            --shadow-color: rgba(0,0,0,0.15);
            --response-bg: #f0f4f7;
            --response-text: #444;
        }

        [data-theme="dark"] {
            /* Dark Mode */
            --bg-gradient: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            --card-bg: rgba(30, 41, 59, 0.95);
            --text-main: #e2e8f0;
            --text-muted: #94a3b8;
            --header-bg: rgba(0, 0, 0, 0.3);
            --header-text: #f8fafc;
            --input-bg: #334155;
            --input-border: #475569;
            --btn-bg: rgba(255,255,255,0.1);
            --btn-hover: rgba(255,255,255,0.2);
            --shadow-color: rgba(0,0,0,0.4);
            --response-bg: #334155;
            --response-text: #cbd5e0;
        }

        /* General Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px 0;
            color: var(--text-main);
            transition: background 0.5s ease;
        }
        .container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            animation: fadeIn 0.6s ease-out;
        }

        /* Header & Utility Bar */
        .header {
            background: var(--header-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 30px;
            color: var(--header-text);
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.1);
            position: relative;
        }

        /* Top Bar for Clock & Switch */
        .top-bar {
            position: absolute;
            top: 20px;
            right: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .clock {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 0.9em;
            background: rgba(0,0,0,0.2);
            padding: 5px 10px;
            border-radius: 8px;
        }
        .theme-toggle {
            cursor: pointer;
            background: rgba(0,0,0,0.2);
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 1.2em;
            transition: transform 0.2s;
        }
        .theme-toggle:hover { transform: scale(1.1); }
        
        /* Mobile adjustment for top bar */
        @media (max-width: 768px) {
            .top-bar {
                position: static;
                justify-content: center;
                margin-bottom: 15px;
            }
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .header p { font-size: 1.1em; opacity: 0.9; }

        /* Nav */
        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }
        .nav a, .btn {
            background: var(--btn-bg);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .nav a:hover, .btn:hover {
            background: var(--btn-hover);
            transform: translateY(-2px);
        }

        /* Card */
        .card {
            background: var(--card-bg);
            padding: 35px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 10px 40px var(--shadow-color);
            border: 1px solid rgba(255,255,255,0.05);
            color: var(--text-main);
        }
        .card h2 {
            font-size: 1.8em;
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 2px solid rgba(0,0,0,0.05);
            padding-bottom: 15px;
        }
        
        /* Forms */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-main);
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--input-border);
            border-radius: 10px;
            font-size: 1em;
            background-color: var(--input-bg);
            color: var(--text-main);
            transition: 0.3s;
        }
        .form-group input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }

        /* Lists */
        .ticket-item {
            border: 1px solid var(--input-border);
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 12px;
            background: var(--card-bg); 
            /* Note: card inside card uses same bg in this system, 
               but opacity makes it slightly visible or flat */
        }
        .ticket-item p { color: var(--text-muted); }
        .ticket-item h4 { color: var(--text-main); margin-bottom: 5px; }

        /* Status Badges */
        .ticket-status { padding: 4px 12px; border-radius: 15px; color: white; font-size: 0.8em; font-weight: bold; }
        .status-open { background: #28a745; }
        .status-in-progress { background: #ffc107; color: #333; }
        .status-closed { background: #dc3545; }

        /* Responses */
        .response-item {
            background: var(--response-bg);
            padding: 15px;
            margin: 15px 0;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .response-item strong { color: var(--text-main); }
        .response-item p { color: var(--response-text); }
        .response-item span { color: var(--text-muted); font-size: 0.85em; }

        /* Alerts */
        .error { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f87171; }
        .success { background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #4ade80; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="top-bar">
                <div id="clock" class="clock">00:00:00</div>
                <button onclick="toggleTheme()" class="theme-toggle" id="themeBtn" title="Ganti Mode">🌙</button>
            </div>

            <h1>PT Cipta Hospital INA</h1>
            <p>Layanan Dukungan Teknis Online</p>
            
            <div class="nav">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="?action=dashboard">Dashboard</a>
                    <a href="?action=create_ticket">Buat Tiket</a>
                    <a href="?action=logout" style="background: rgba(220, 53, 69, 0.8);">Logout</a>
                <?php else: ?>
                    <a href="?action=home">Home</a>
                    <a href="?action=login">Login</a>
                    <a href="?action=register">Daftar</a>
                <?php endif; ?>
            </div>
        </div>

        <?php switch($action) { 
            case 'home': ?>
                <div class="card" style="text-align: center;">
                    <h2>Selamat Datang</h2>
                    <p style="font-size: 1.1em; line-height: 1.6; color: var(--text-muted);">
                        Sistem ticketing <strong>PT Cipta Hospital INA</strong> dirancang untuk mempercepat penanganan masalah teknis di fasilitas kesehatan Anda.
                    </p>
                    <div style="margin-top: 30px;">
                        <a href="?action=login" class="btn" style="background: #667eea; padding: 15px 40px;">Mulai Sekarang</a>
                    </div>
                </div>
            <?php break; 

            case 'login': ?>
                <div class="card" style="max-width: 500px; margin: 0 auto;">
                    <h2>Login Sistem</h2>
                    <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
                    <form method="POST">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required placeholder="email@contoh.com">
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" required>
                        </div>
                        <button type="submit" class="btn" style="width: 100%; background: #667eea;">Masuk</button>
                    </form>
                    <p style="text-align: center; margin-top: 15px; font-size: 0.9em;">Belum punya akun? <a href="?action=register" style="color: #667eea;">Daftar disini</a></p>
                </div>
            <?php break;

            case 'register': ?>
                <div class="card" style="max-width: 500px; margin: 0 auto;">
                    <h2>Daftar Akun</h2>
                    <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
                    <?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>
                    <form method="POST">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" required>
                        </div>
                        <button type="submit" class="btn" style="width: 100%; background: #667eea;">Daftar</button>
                    </form>
                </div>
            <?php break;

            case 'dashboard': ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2>Dashboard <?php echo htmlspecialchars($_SESSION['user_nama']); ?></h2>
                        <a href="?action=create_ticket" class="btn" style="background: #28a745; font-size: 0.9em;">+ Tiket Baru</a>
                    </div>
                    
                    <?php if ($stmt->rowCount() > 0): ?>
                        <div style="display: grid; gap: 15px;">
                            <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
                                $stClass = 'status-' . strtolower(str_replace(' ', '-', $row['status'])); ?>
                                <div class="ticket-item">
                                    <div style="display: flex; justify-content: space-between;">
                                        <h4><?php echo htmlspecialchars($row['judul']); ?></h4>
                                        <span class="ticket-status <?php echo $stClass; ?>"><?php echo $row['status']; ?></span>
                                    </div>
                                    <p style="margin: 10px 0; font-size: 0.95em;"><?php echo htmlspecialchars(substr($row['deskripsi'], 0, 100)) . '...'; ?></p>
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85em; color: var(--text-muted);">
                                        <span>Prioritas: <strong><?php echo $row['prioritas']; ?></strong></span>
                                        <a href="?action=view_ticket&id=<?php echo $row['id']; ?>" style="color: #667eea; font-weight: bold; text-decoration: none;">Lihat Detail &rarr;</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--text-muted);">Belum ada tiket yang dibuat.</p>
                    <?php endif; ?>
                </div>
            <?php break;

            case 'create_ticket': ?>
                <div class="card" style="max-width: 800px; margin: 0 auto;">
                    <h2>Buat Tiket Baru</h2>
                    <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
                    <form method="POST">
                        <div class="form-group">
                            <label>Judul</label>
                            <input type="text" name="judul" required>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label>Kategori</label>
                                <select name="kategori" required>
                                    <option value="Technical Support">Technical Support</option>
                                    <option value="Bug Report">Bug Report</option>
                                    <option value="Feature Request">Fitur Baru</option>
                                    <option value="General Inquiry">Umum</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Prioritas</label>
                                <select name="prioritas" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Deskripsi</label>
                            <textarea name="deskripsi" rows="6" required></textarea>
                        </div>
                        <div style="text-align: right;">
                            <a href="?action=dashboard" class="btn" style="background: #6c757d; margin-right: 10px;">Batal</a>
                            <button type="submit" class="btn" style="background: #667eea;">Kirim Tiket</button>
                        </div>
                    </form>
                </div>
            <?php break;

            case 'view_ticket': 
                if (isset($ticket_not_found)) echo "<div class='card error'>Tiket tidak ditemukan.</div>";
                elseif (isset($access_denied)) echo "<div class='card error'>Akses ditolak.</div>";
                else { $stClass = 'status-' . strtolower(str_replace(' ', '-', $ticket_data['status'])); ?>
                <div class="card">
                    <div style="border-bottom: 1px solid rgba(0,0,0,0.1); padding-bottom: 20px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <h2 style="text-align: left; border: none; margin: 0; padding: 0;">#<?php echo $ticket_data['id']; ?>: <?php echo htmlspecialchars($ticket_data['judul']); ?></h2>
                            <span class="ticket-status <?php echo $stClass; ?>" style="font-size: 1em; padding: 8px 15px;"><?php echo $ticket_data['status']; ?></span>
                        </div>
                        <p style="margin-top: 10px; color: var(--text-muted); font-size: 0.9em;">
                            Oleh <strong><?php echo htmlspecialchars($ticket_data['user_nama']); ?></strong> pada <?php echo date('d M Y, H:i', strtotime($ticket_data['created_at'])); ?>
                        </p>
                    </div>

                    <div style="background: var(--input-bg); padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                        <p style="white-space: pre-wrap; line-height: 1.6;"><?php echo htmlspecialchars($ticket_data['deskripsi']); ?></p>
                    </div>

                    <?php if($_SESSION['user_role'] == 'admin'): ?>
                        <div style="background: rgba(102, 126, 234, 0.1); padding: 15px; border-radius: 10px; margin-bottom: 30px;">
                            <form method="POST" action="?action=update_status" style="display: flex; align-items: center; gap: 10px;">
                                <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
                                <strong style="color: var(--text-main);">Update Status:</strong>
                                <select name="status" style="flex: 1; margin: 0;">
                                    <option value="Open" <?php echo $ticket_data['status']=='Open'?'selected':''; ?>>Open</option>
                                    <option value="In Progress" <?php echo $ticket_data['status']=='In Progress'?'selected':''; ?>>In Progress</option>
                                    <option value="Closed" <?php echo $ticket_data['status']=='Closed'?'selected':''; ?>>Closed</option>
                                </select>
                                <button type="submit" class="btn" style="background: #28a745; padding: 10px 20px;">Simpan</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <h3 style="margin-bottom: 20px;">Diskusi</h3>
                    <div style="margin-bottom: 30px;">
                        <?php if ($responses->rowCount() > 0): 
                            while($resp = $responses->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="response-item">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <strong><?php echo htmlspecialchars($resp['user_nama']); ?></strong>
                                    <span><?php echo date('d/m H:i', strtotime($resp['created_at'])); ?></span>
                                </div>
                                <p><?php echo htmlspecialchars($resp['response']); ?></p>
                            </div>
                        <?php endwhile; else: ?>
                            <p style="color: var(--text-muted); font-style: italic;">Belum ada balasan.</p>
                        <?php endif; ?>
                    </div>

                    <?php if($ticket_data['status'] != 'Closed'): ?>
                        <form method="POST" action="?action=add_response">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
                            <div class="form-group">
                                <textarea name="response" rows="3" required placeholder="Tulis balasan Anda..."></textarea>
                            </div>
                            <button type="submit" class="btn" style="background: #667eea;">Kirim Balasan</button>
                        </form>
                    <?php else: ?>
                        <div style="padding: 15px; background: #eee; text-align: center; border-radius: 8px; color: #555;">Tiket telah ditutup.</div>
                    <?php endif; ?>
                </div>
            <?php } break; 
        } ?>
    </div>

    <script>
        // 1. Digital Clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', { hour12: false });
            document.getElementById('clock').textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock(); // Run immediately

        // 2. Dark Mode Toggle
        const themeBtn = document.getElementById('themeBtn');
        const html = document.documentElement;
        
        // Cek Local Storage (Apakah user pernah pilih mode?)
        if (localStorage.getItem('theme') === 'dark') {
            html.setAttribute('data-theme', 'dark');
            themeBtn.textContent = '☀️';
        }

        function toggleTheme() {
            if (html.getAttribute('data-theme') === 'dark') {
                html.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                themeBtn.textContent = '🌙';
            } else {
                html.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                themeBtn.textContent = '☀️';
            }
        }
    </script>
</body>
</html>
