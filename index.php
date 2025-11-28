<?php
// index.php - Main Application

session_start();

// Include all necessary class files
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Ticket.php';
require_once 'classes/TicketResponse.php';

// Initialize database connection and objects
$database = new Database();
$db = $database->getConnection(); // Establish database connection

$user = new User($db); // User object
$ticket = new Ticket($db); // Ticket object
$response = new TicketResponse($db); // TicketResponse object

// Determine the action based on GET parameter, default to 'home'
$action = isset($_GET['action']) ? $_GET['action'] : 'home';

// Handle different actions using a switch statement for routing
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
        if(!isset($_SESSION['user_id'])) {
            header("Location: ?action=login");
            exit();
        }

        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $ticket_id = $ticket->create($_SESSION['user_id'], $_POST['judul'], $_POST['deskripsi'], $_POST['kategori'], $_POST['prioritas']);
            if($ticket_id) {
                header("Location: ?action=view_ticket&id=" . $ticket_id);
                exit();
            } else {
                $error = "Gagal membuat tiket!";
            }
        }
        break;

    case 'add_response':
        if(!isset($_SESSION['user_id'])) {
            header("Location: ?action=login");
            exit();
        }

        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            if($response->create($_POST['ticket_id'], $_SESSION['user_id'], $_POST['response'])) {
                header("Location: ?action=view_ticket&id=" . $_POST['ticket_id']);
                exit();
            }
        }
        break;

    case 'update_status':
        if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
            header("Location: ?action=login");
            exit();
        }

        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            if($ticket->updateStatus($_POST['ticket_id'], $_POST['status'])) {
                header("Location: ?action=view_ticket&id=" . $_POST['ticket_id']);
                exit();
            }
        }
        break;

    case 'dashboard':
        if(!isset($_SESSION['user_id'])) {
            header("Location: ?action=login");
            exit();
        }
        $stmt = $ticket->getAll($_SESSION['user_id'], $_SESSION['user_role']);
        break;

    case 'view_ticket':
        if(!isset($_SESSION['user_id'])) {
            header("Location: ?action=login");
            exit();
        }

        $ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $ticket_data = $ticket->getById($ticket_id);

        if(!$ticket_data) {
            $ticket_not_found = true;
            break;
        }

        if($_SESSION['user_role'] != 'admin' && $ticket_data['user_id'] != $_SESSION['user_id']) {
            $access_denied = true;
            break;
        }

        $responses = $response->getByTicketId($ticket_id);
        break;

    case 'home':
    default:
        break;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT Cipta Hospital INA - Sistem Ticketing</title>
    <style>
        /* General Reset and Base Styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', Arial, sans-serif; /* Using Inter for a modern look */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); /* Purple gradient */
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Align to top */
            padding: 20px 0;
            color: #333; /* Default text color */
        }
        .container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            animation: fadeIn 0.8s ease-out; /* Simple fade-in animation */
        }

        /* Header Styling */
        .header {
            background: rgba(255,255,255,0.15); /* Slightly less opaque */
            backdrop-filter: blur(12px); /* Stronger blur effect */
            -webkit-backdrop-filter: blur(12px); /* For Safari support */
            padding: 25px;
            border-radius: 20px; /* More rounded */
            margin-bottom: 30px;
            color: white;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); /* Deeper shadow */
            border: 1px solid rgba(255,255,255,0.3); /* Subtle border */
        }
        .header h1 {
            font-size: 2.8em; /* Larger title */
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3); /* Text shadow for better readability */
        }
        .header p {
            font-size: 1.3em; /* Larger subtitle */
            opacity: 0.95; /* More opaque */
        }

        /* Navigation Styling */
        .nav {
            display: flex;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
            gap: 15px;
            margin-top: 25px;
            justify-content: center; /* Center align nav items */
        }
        .nav a, .btn {
            background: rgba(255,255,255,0.25); /* Slightly more opaque for buttons */
            color: white;
            padding: 14px 28px; /* Larger padding */
            text-decoration: none;
            border-radius: 30px; /* More rounded */
            border: none;
            cursor: pointer;
            transition: all 0.3s ease; /* Smooth transition */
            font-weight: bold;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .nav a:hover, .btn:hover {
            background: rgba(255,255,255,0.4);
            transform: translateY(-3px) scale(1.02); /* More pronounced hover effect */
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }

        /* Card Styling */
        .card {
            background: rgba(255,255,255,0.98); /* Almost opaque white */
            padding: 35px;
            border-radius: 20px; /* More rounded corners */
            margin-bottom: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15); /* Deeper shadow for cards */
            border: 1px solid rgba(0,0,0,0.05); /* Very light border */
        }
        .card h2 {
            font-size: 2em;
            color: #4a5568; /* Darker grey heading */
            margin-bottom: 25px;
            text-align: center;
        }

        /* Form Styling */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
            color: #4a5568;
            font-size: 1.1em;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 14px; /* Larger padding for inputs */
            border: 2px solid #cbd5e0; /* Lighter grey border */
            border-radius: 10px; /* More rounded */
            font-size: 1.05em; /* Slightly larger font */
            background-color: #f7fafc; /* Light background */
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea; /* Highlight color on focus */
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3); /* Subtle glow on focus */
        }
        textarea { resize: vertical; } /* Allow vertical resizing for textareas */

        /* Ticket Item Styling */
        .ticket-item {
            border: 1px solid #e2e8f0; /* Light grey border */
            padding: 25px;
            margin-bottom: 18px;
            border-radius: 12px; /* More rounded */
            background: #ffffff; /* Pure white background */
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); /* Soft shadow */
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .ticket-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .ticket-item h4 {
            font-size: 1.4em;
            color: #2d3748;
            margin-bottom: 8px;
        }
        .ticket-item p {
            color: #555;
            line-height: 1.6;
        }
        .ticket-item > div:first-child { /* Flex for title and status */
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        /* Status and Priority Styling */
        .ticket-status {
            padding: 6px 18px;
            border-radius: 25px;
            color: white;
            font-size: 0.85em; /* Slightly smaller */
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .status-open { background: #28a745; } /* Green */
        .status-in-progress { background: #ffc107; color: #333; } /* Yellow-orange */
        .status-closed { background: #dc3545; } /* Red */

        .priority-high { color: #dc3545; font-weight: bold; }
        .priority-medium { color: #ffc107; font-weight: bold; }
        .priority-low { color: #28a745; font-weight: bold; }

        /* Response Item Styling */
        .response-item {
            background: #f0f4f7; /* Lighter grey-blue */
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            border-left: 5px solid #667eea; /* Highlight border */
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .response-item strong {
            color: #2d3748;
            font-size: 1.1em;
        }
        .response-item span {
            font-size: 0.9em;
            color: #777;
        }
        .response-item p {
            margin-top: 10px;
            line-height: 1.6;
            color: #444;
        }

        /* Alert Messages (Error/Success) */
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        /* Utility Styles */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .stats {
            display: flex;
            flex-wrap: wrap; /* Allow wrapping */
            justify-content: space-around;
            background: rgba(255,255,255,0.15);
            padding: 25px;
            border-radius: 20px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .stat-item {
            text-align: center;
            flex: 1 1 150px; /* Flexible item size */
            padding: 10px;
        }
        .stat-number {
            font-size: 2.5em; /* Larger numbers */
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }
        .stat-label {
            opacity: 0.9;
            font-size: 1.1em;
            margin-top: 5px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .header h1 { font-size: 2em; }
            .header p { font-size: 1em; }
            .nav { flex-direction: column; align-items: stretch; } /* Stack nav items */
            .nav a, .btn { padding: 12px 20px; }
            .card { padding: 25px; border-radius: 15px; }
            .card h2 { font-size: 1.7em; margin-bottom: 20px; }
            .ticket-item { padding: 20px; border-radius: 10px; }
            .stats { flex-direction: column; } /* Stack stats on small screens */
            .stat-number { font-size: 2em; }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PT Cipta Hospital INA</h1>
            <p>Layanan Dukungan Teknis Online</p>
            <div class="nav">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="?action=dashboard">Dashboard</a>
                    <a href="?action=create_ticket">Buat Tiket</a>
                    <a href="?action=logout">Logout (<?php echo htmlspecialchars($_SESSION['user_nama']); ?>)</a>
                <?php else: ?>
                    <a href="?action=home">Home</a>
                    <a href="?action=login">Login</a>
                    <a href="?action=register">Daftar</a>
                <?php endif; ?>
            </div>
        </div>

        <?php
        switch($action) {
            case 'home':
                ?>
                <div class="card">
                    <h2>Selamat Datang di Sistem Ticketing</h2>
                    <p style="margin: 20px 0;">PT Cipta Hospital INA adalah perusahaan teknologi kesehatan yang berdedikasi untuk memberikan solusi digital terbaik bagi rumah sakit dan fasilitas kesehatan di Indonesia.</p>

                    <p style="margin: 20px 0;">Sistem ticketing kami dirancang untuk memudahkan komunikasi antara klien dan tim support kami. Dengan sistem yang user-friendly dan responsif, kami berkomitmen memberikan pelayanan terbaik untuk menyelesaikan setiap permasalahan teknis yang Anda hadapi.</p>

                    <h3>Fitur Utama:</h3>
                    <ul style="margin: 20px 0; padding-left: 30px; list-style-type: disc; color: #555;">
                        <li>Sistem tiket terintegrasi untuk tracking permasalahan</li>
                        <li>Response time yang cepat dari tim support</li>
                        <li>Kategori dan prioritas tiket yang jelas</li>
                        <li>Riwayat komunikasi yang lengkap</li>
                        <li>Dashboard monitoring untuk admin</li>
                    </ul>

                    <div style="margin-top: 30px; text-align: center;">
                        <a href="?action=register" class="btn">Mulai Sekarang</a>
                    </div>
                </div>
                <?php
                break;

            case 'register':
                ?>
                <div class="card">
                    <h2>Daftar Akun Baru</h2>
                    <?php if(isset($error)): ?>
                        <div class="error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if(isset($success)): ?>
                        <div class="success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label for="nama">Nama Lengkap:</label>
                            <input type="text" id="nama" name="nama" required>
                        </div>
                        <div class="form-group">
                            <label for="email_reg">Email:</label>
                            <input type="email" id="email_reg" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password_reg">Password:</label>
                            <input type="password" id="password_reg" name="password" required>
                        </div>
                        <button type="submit" class="btn">Daftar</button>
                    </form>
                </div>
                <?php
                break;

            case 'login':
                ?>
                <div class="card">
                    <h2>Login</h2>
                    <?php if(isset($error)): ?>
                        <div class="error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label for="email_login">Email:</label>
                            <input type="email" id="email_login" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password_login">Password:</label>
                            <input type="password" id="password_login" name="password" required>
                        </div>
                        <button type="submit" class="btn">Login</button>
                    </form>
                </div>
                <?php
                break;

            case 'dashboard':
                ?>
                <div class="card">
                    <h2>Dashboard Tiket</h2>
                    <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['user_nama']); ?>!</p>

                    <div style="margin: 20px 0; text-align: center;">
                        <a href="?action=create_ticket" class="btn">Buat Tiket Baru</a>
                    </div>

                    <h3>Daftar Tiket:</h3>
                    <?php
                    if ($stmt->rowCount() > 0) {
                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            $status_class = strtolower(str_replace(' ', '-', $row['status']));
                            $priority_class = strtolower($row['prioritas']);
                            ?>
                            <div class="ticket-item">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <h4><?php echo htmlspecialchars($row['judul']); ?></h4>
                                    <span class="ticket-status status-<?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </div>

                                <p><?php echo htmlspecialchars(substr($row['deskripsi'], 0, 150)) . (strlen($row['deskripsi']) > 150 ? '...' : ''); ?></p>

                                <div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 15px; font-size: 14px; color: #666;">
                                    <span>Kategori: <?php echo htmlspecialchars($row['kategori']); ?></span>
                                    <span class="priority-<?php echo $priority_class; ?>">
                                        Prioritas: <?php echo htmlspecialchars($row['prioritas']); ?>
                                    </span>
                                    <span>Dibuat: <?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></span>
                                    <?php if($_SESSION['user_role'] == 'admin' && isset($row['user_nama'])): ?>
                                        <span>User: <?php echo htmlspecialchars($row['user_nama']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div style="margin-top: 15px; text-align: right;">
                                    <a href="?action=view_ticket&id=<?php echo htmlspecialchars($row['id']); ?>" class="btn">Lihat Detail</a>
                                </div>
                            </div>
                        <?php endwhile;
                    } else {
                        echo "<p>Belum ada tiket yang dibuat.</p>";
                    }
                    ?>
                </div>
                <?php
                break;

            case 'create_ticket':
                ?>
                <div class="card">
                    <h2>Buat Tiket Baru</h2>
                    <?php if(isset($error)): ?>
                        <div class="error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label for="judul">Judul Tiket:</label>
                            <input type="text" id="judul" name="judul" required>
                        </div>

                        <div class="form-group">
                            <label for="kategori">Kategori:</label>
                            <select id="kategori" name="kategori" required>
                                <option value="">Pilih Kategori</option>
                                <option value="Technical Support">Technical Support</option>
                                <option value="Bug Report">Bug Report</option>
                                <option value="Feature Request">Feature Request</option>
                                <option value="Account Issue">Account Issue</option>
                                <option value="General Inquiry">General Inquiry</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="prioritas">Prioritas:</label>
                            <select id="prioritas" name="prioritas" required>
                                <option value="">Pilih Prioritas</option>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="deskripsi">Deskripsi:</label>
                            <textarea id="deskripsi" name="deskripsi" rows="8" required placeholder="Jelaskan permasalahan Anda secara detail..."></textarea>
                        </div>

                        <button type="submit" class="btn">Buat Tiket</button>
                    </form>
                </div>
                <?php
                break;

            case 'view_ticket':
                if (isset($ticket_not_found) && $ticket_not_found) {
                    echo "<div class='card error'><h2>Tiket tidak ditemukan!</h2><p>Tiket dengan ID ini mungkin tidak ada atau telah dihapus.</p></div>";
                    break;
                }
                if (isset($access_denied) && $access_denied) {
                    echo "<div class='card error'><h2>Akses Ditolak!</h2><p>Anda tidak memiliki izin untuk melihat tiket ini.</p></div>";
                    break;
                }

                $status_class = strtolower(str_replace(' ', '-', $ticket_data['status']));
                $priority_class = strtolower($ticket_data['prioritas']);
                ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2><?php echo htmlspecialchars($ticket_data['judul']); ?></h2>
                        <span class="ticket-status status-<?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($ticket_data['status']); ?>
                        </span>
                    </div>

                    <div style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 20px; font-size: 14px; color: #666;">
                        <span>ID: #<?php echo htmlspecialchars($ticket_data['id']); ?></span>
                        <span>Kategori: <?php echo htmlspecialchars($ticket_data['kategori']); ?></span>
                        <span class="priority-<?php echo $priority_class; ?>">
                            Prioritas: <?php echo htmlspecialchars($ticket_data['prioritas']); ?>
                        </span>
                        <span>Dibuat: <?php echo date('d/m/Y H:i', strtotime($ticket_data['created_at'])); ?></span>
                        <?php if(isset($ticket_data['user_nama'])): ?>
                            <span>Oleh: <?php echo htmlspecialchars($ticket_data['user_nama']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div style="background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 30px; border: 1px solid #e0e0e0;">
                        <h4>Deskripsi Tiket:</h4>
                        <p style="margin-top: 15px; white-space: pre-wrap; word-wrap: break-word; line-height: 1.7;"><?php echo htmlspecialchars($ticket_data['deskripsi']); ?></p>
                    </div>

                    <?php if($_SESSION['user_role'] == 'admin'): ?>
                        <div style="margin-bottom: 30px; background: #e6e6fa; padding: 20px; border-radius: 10px; border: 1px solid #d0d0f0;">
                            <h4>Update Status Tiket:</h4>
                            <form method="POST" action="?action=update_status" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center; margin-top: 15px;">
                                <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket_id); ?>">
                                <select name="status" style="flex-grow: 1; min-width: 150px;">
                                    <option value="Open" <?php echo $ticket_data['status'] == 'Open' ? 'selected' : ''; ?>>Open</option>
                                    <option value="In Progress" <?php echo $ticket_data['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Closed" <?php echo $ticket_data['status'] == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                                <button type="submit" class="btn" style="background: #4CAF50;">Update Status</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <h4>Riwayat Komunikasi:</h4>
                    <div style="margin: 20px 0;">
                        <?php
                        if ($responses->rowCount() > 0) {
                            while($resp = $responses->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="response-item">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <strong><?php echo htmlspecialchars($resp['user_nama']); ?></strong>
                                        <span style="font-size: 12px; color: #666;">
                                            <?php echo date('d/m/Y H:i', strtotime($resp['created_at'])); ?>
                                        </span>
                                    </div>
                                    <p style="white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($resp['response']); ?></p>
                                </div>
                            <?php endwhile;
                        } else {
                            echo "<p>Belum ada komunikasi untuk tiket ini.</p>";
                        }
                        ?>
                    </div>

                    <?php if($ticket_data['status'] != 'Closed'): ?>
                        <div style="background: #f0f8ff; padding: 20px; border-radius: 10px; border: 1px solid #e0f0ff;">
                            <h4>Tambah Respon:</h4>
                            <form method="POST" action="?action=add_response">
                                <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket_id); ?>">
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <textarea name="response" rows="6" required placeholder="Tulis respon Anda..."></textarea>
                                </div>
                                <button type="submit" class="btn">Kirim Respon</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
                break;
        }
        ?>
    </div>
</body>
</html>