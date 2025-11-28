<?php
// index.php - Main Application

// 1. Optimasi Session Serverless
ini_set('session.cookie_lifetime', 86400);
ini_set('session.gc_maxlifetime', 86400);
session_start();

// 2. Load Class dari folder 'libs'
require_once __DIR__ . '/libs/Database.php';
require_once __DIR__ . '/libs/User.php';
require_once __DIR__ . '/libs/Ticket.php';
require_once __DIR__ . '/libs/TicketResponse.php';

// Initialize database objects
$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$ticket = new Ticket($db);
$response = new TicketResponse($db);

$action = isset($_GET['action']) ? $_GET['action'] : 'home';

// LOGIC PHP (TIDAK BERUBAH DARI SEBELUMNYA)
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
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Ticketing - PT Cipta Hospital INA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full flex flex-col">

    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="?action=home" class="flex-shrink-0 flex items-center gap-2">
                        <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold">C</div>
                        <span class="font-bold text-xl text-gray-800 tracking-tight">CiptaHelpdesk</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="?action=dashboard" class="text-gray-600 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition">Dashboard</a>
                        <a href="?action=create_ticket" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition shadow-sm">Buat Tiket</a>
                        <a href="?action=logout" class="text-gray-500 hover:text-red-600 px-3 py-2 rounded-md text-sm font-medium transition">Logout</a>
                    <?php else: ?>
                        <a href="?action=login" class="text-gray-600 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition">Masuk</a>
                        <a href="?action=register" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition shadow-sm">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow">
        <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
            
            <?php if(isset($error)): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0"><span class="text-red-500 font-bold">!</span></div>
                        <div class="ml-3"><p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if(isset($success)): ?>
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0"><span class="text-green-500 font-bold">✓</span></div>
                        <div class="ml-3"><p class="text-sm text-green-700"><?php echo htmlspecialchars($success); ?></p></div>
                    </div>
                </div>
            <?php endif; ?>


            <?php switch($action) { 
                case 'home': ?>
                    <div class="text-center py-16 lg:py-24">
                        <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                            <span class="block">Layanan Dukungan Teknis</span>
                            <span class="block text-indigo-600">PT Cipta Hospital INA</span>
                        </h1>
                        <p class="mt-3 max-w-md mx-auto text-base text-gray-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                            Solusi digital terbaik untuk fasilitas kesehatan. Laporkan kendala teknis Anda, tim kami siap membantu 24/7 dengan respons cepat dan solusi tepat.
                        </p>
                        <div class="mt-10 flex justify-center gap-4">
                            <a href="?action=register" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 md:py-4 md:text-lg md:px-10 shadow-lg hover:shadow-xl transition">
                                Mulai Sekarang
                            </a>
                            <a href="?action=login" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 md:py-4 md:text-lg md:px-10 transition">
                                Masuk Akun
                            </a>
                        </div>
                    </div>
                <?php break; ?>

                <?php case 'login': ?>
                    <div class="min-h-[50vh] flex flex-col justify-center py-6 sm:px-6 lg:px-8">
                        <div class="sm:mx-auto sm:w-full sm:max-w-md">
                            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Masuk ke Akun Anda</h2>
                        </div>
                        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                            <div class="bg-white py-8 px-4 shadow-xl rounded-lg sm:px-10 border border-gray-100">
                                <form class="space-y-6" method="POST">
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                        <div class="mt-1">
                                            <input id="email" name="email" type="email" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                        <div class="mt-1">
                                            <input id="password" name="password" type="password" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        </div>
                                    </div>
                                    <div>
                                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Masuk
                                        </button>
                                    </div>
                                </form>
                                <div class="mt-6">
                                    <div class="relative">
                                        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-300"></div></div>
                                        <div class="relative flex justify-center text-sm">
                                            <span class="px-2 bg-white text-gray-500">Belum punya akun? <a href="?action=register" class="text-indigo-600 hover:text-indigo-500">Daftar</a></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php break; ?>

                <?php case 'register': ?>
                    <div class="min-h-[50vh] flex flex-col justify-center py-6 sm:px-6 lg:px-8">
                        <div class="sm:mx-auto sm:w-full sm:max-w-md">
                            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Daftar Akun Baru</h2>
                        </div>
                        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                            <div class="bg-white py-8 px-4 shadow-xl rounded-lg sm:px-10 border border-gray-100">
                                <form class="space-y-6" method="POST">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                                        <div class="mt-1"><input name="nama" type="text" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Email</label>
                                        <div class="mt-1"><input name="email" type="email" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Password</label>
                                        <div class="mt-1"><input name="password" type="password" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></div>
                                    </div>
                                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Daftar Sekarang</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php break; ?>

                <?php case 'dashboard': ?>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">Dashboard Tiket</h2>
                            <a href="?action=create_ticket" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                + Tiket Baru
                            </a>
                        </div>

                        <?php if ($stmt->rowCount() > 0): ?>
                            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                                <ul role="list" class="divide-y divide-gray-200">
                                    <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
                                        $statusColor = match($row['status']) {
                                            'Open' => 'bg-green-100 text-green-800',
                                            'In Progress' => 'bg-yellow-100 text-yellow-800',
                                            'Closed' => 'bg-gray-100 text-gray-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                        $priorityColor = match($row['prioritas']) {
                                            'High' => 'text-red-600',
                                            'Medium' => 'text-yellow-600',
                                            'Low' => 'text-green-600',
                                            default => 'text-gray-500'
                                        };
                                    ?>
                                    <li>
                                        <a href="?action=view_ticket&id=<?php echo $row['id']; ?>" class="block hover:bg-gray-50 transition duration-150 ease-in-out">
                                            <div class="px-4 py-4 sm:px-6">
                                                <div class="flex items-center justify-between">
                                                    <p class="text-sm font-medium text-indigo-600 truncate"><?php echo htmlspecialchars($row['judul']); ?></p>
                                                    <div class="ml-2 flex-shrink-0 flex">
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColor; ?>">
                                                            <?php echo htmlspecialchars($row['status']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="mt-2 sm:flex sm:justify-between">
                                                    <div class="sm:flex">
                                                        <p class="flex items-center text-sm text-gray-500 mr-6">
                                                            <span class="font-medium mr-1">Kat:</span> <?php echo htmlspecialchars($row['kategori']); ?>
                                                        </p>
                                                        <p class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                                            <span class="font-medium mr-1">Prioritas:</span> <span class="<?php echo $priorityColor; ?> font-bold"><?php echo htmlspecialchars($row['prioritas']); ?></span>
                                                        </p>
                                                    </div>
                                                    <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                                        <p>
                                                            <?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?>
                                                            <?php if($_SESSION['user_role'] == 'admin'): ?>
                                                                <span class="ml-2 text-gray-400">by <?php echo htmlspecialchars($row['user_nama']); ?></span>
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12 bg-white rounded-lg shadow">
                                <p class="text-gray-500">Belum ada tiket yang dibuat.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php break; ?>

                <?php case 'create_ticket': ?>
                    <div class="max-w-2xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden border border-gray-100">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Form Tiket Baru</h3>
                            <p class="mt-1 text-sm text-gray-500">Jelaskan permasalahan Anda secara detail.</p>
                        </div>
                        <div class="p-6">
                            <form method="POST" class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Judul Permasalahan</label>
                                    <input type="text" name="judul" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Kategori</label>
                                        <select name="kategori" required class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="">Pilih Kategori</option>
                                            <option value="Technical Support">Technical Support</option>
                                            <option value="Bug Report">Bug Report</option>
                                            <option value="Feature Request">Feature Request</option>
                                            <option value="Account Issue">Account Issue</option>
                                            <option value="General Inquiry">General Inquiry</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Prioritas</label>
                                        <select name="prioritas" required class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="Low">Low (Rendah)</option>
                                            <option value="Medium">Medium (Sedang)</option>
                                            <option value="High">High (Mendesak)</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Deskripsi Lengkap</label>
                                    <textarea name="deskripsi" rows="6" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Ceritakan detail kendala..."></textarea>
                                </div>
                                <div class="flex justify-end">
                                    <a href="?action=dashboard" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none mr-3">Batal</a>
                                    <button type="submit" class="bg-indigo-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Kirim Tiket</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php break; ?>

                <?php case 'view_ticket': 
                    if (isset($ticket_not_found)) { echo "<div class='text-center text-red-600'>Tiket tidak ditemukan</div>"; break; }
                    if (isset($access_denied)) { echo "<div class='text-center text-red-600'>Akses ditolak</div>"; break; }
                    $statusColor = match($ticket_data['status']) {
                        'Open' => 'bg-green-100 text-green-800',
                        'In Progress' => 'bg-yellow-100 text-yellow-800',
                        'Closed' => 'bg-gray-100 text-gray-800',
                        default => 'bg-gray-100 text-gray-800'
                    };
                ?>
                    <div class="bg-white shadow-xl rounded-lg overflow-hidden border border-gray-100">
                        <div class="bg-gray-50 px-6 py-5 border-b border-gray-200 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-xl leading-6 font-bold text-gray-900 flex items-center gap-3">
                                    #<?php echo $ticket_data['id']; ?> - <?php echo htmlspecialchars($ticket_data['judul']); ?>
                                </h3>
                                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                    Dibuat oleh <span class="font-medium text-gray-900"><?php echo htmlspecialchars($ticket_data['user_nama'] ?? 'User'); ?></span> pada <?php echo date('d M Y, H:i', strtotime($ticket_data['created_at'])); ?>
                                </p>
                            </div>
                            <div class="mt-4 sm:mt-0 flex items-center gap-2">
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full <?php echo $statusColor; ?>">
                                    <?php echo htmlspecialchars($ticket_data['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="px-6 py-6 bg-white border-b border-gray-100">
                            <div class="prose max-w-none text-gray-800">
                                <p class="whitespace-pre-wrap leading-relaxed"><?php echo htmlspecialchars($ticket_data['deskripsi']); ?></p>
                            </div>
                            <div class="mt-6 flex gap-4 text-sm text-gray-500 bg-gray-50 p-3 rounded-md inline-block">
                                <span>📂 Kategori: <strong><?php echo htmlspecialchars($ticket_data['kategori']); ?></strong></span>
                                <span>⚡ Prioritas: <strong><?php echo htmlspecialchars($ticket_data['prioritas']); ?></strong></span>
                            </div>
                        </div>

                        <?php if($_SESSION['user_role'] == 'admin'): ?>
                            <div class="bg-indigo-50 px-6 py-4 border-b border-gray-200">
                                <form method="POST" action="?action=update_status" class="flex items-center gap-3">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
                                    <label class="text-sm font-medium text-indigo-900">Update Status:</label>
                                    <select name="status" class="block w-40 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        <option value="Open" <?php echo $ticket_data['status'] == 'Open' ? 'selected' : ''; ?>>Open</option>
                                        <option value="In Progress" <?php echo $ticket_data['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="Closed" <?php echo $ticket_data['status'] == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                    <button type="submit" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm">Simpan</button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <div class="px-6 py-6 bg-gray-50">
                            <h4 class="text-lg font-medium text-gray-900 mb-6">Diskusi & Respon</h4>
                            
                            <div class="space-y-6">
                                <?php if ($responses->rowCount() > 0): 
                                    while($resp = $responses->fetch(PDO::FETCH_ASSOC)): 
                                        $is_me = ($resp['user_id'] == $_SESSION['user_id']); // Asumsi ada user_id di query response
                                        // Jika query response tidak ada user_id, kita pakai nama saja untuk styling
                                        $isAdmin = ($resp['user_role'] ?? '') == 'admin'; // Perlu join table user untuk tau role
                                ?>
                                    <div class="flex <?php echo $isAdmin ? 'justify-start' : 'justify-end'; ?>">
                                        <div class="max-w-3xl <?php echo $isAdmin ? 'bg-white border-gray-200' : 'bg-indigo-50 border-indigo-100'; ?> border rounded-xl p-4 shadow-sm">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="font-bold text-sm text-gray-900"><?php echo htmlspecialchars($resp['user_nama']); ?></span>
                                                <span class="text-xs text-gray-400"><?php echo date('d/m/Y H:i', strtotime($resp['created_at'])); ?></span>
                                            </div>
                                            <p class="text-gray-800 text-sm whitespace-pre-wrap leading-relaxed"><?php echo htmlspecialchars($resp['response']); ?></p>
                                        </div>
                                    </div>
                                <?php endwhile; else: ?>
                                    <p class="text-center text-gray-400 italic">Belum ada balasan.</p>
                                <?php endif; ?>
                            </div>

                            <?php if($ticket_data['status'] != 'Closed'): ?>
                                <div class="mt-8">
                                    <form method="POST" action="?action=add_response" class="relative">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
                                        <div class="border border-gray-300 rounded-lg shadow-sm overflow-hidden focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 bg-white">
                                            <label for="response" class="sr-only">Tulis balasan...</label>
                                            <textarea rows="3" name="response" id="response" class="block w-full py-3 px-4 border-0 resize-none focus:ring-0 sm:text-sm" placeholder="Tulis balasan Anda di sini..."></textarea>
                                            <div class="py-2 px-3 bg-gray-50 flex justify-between items-center">
                                                <div class="flex-shrink-0">
                                                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                        Kirim Balasan
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="mt-6 p-4 bg-gray-200 rounded text-center text-gray-600 font-medium">
                                    Tiket ini sudah ditutup. Tidak dapat mengirim balasan baru.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php break; ?>
            <?php } ?>
        </div>
    </main>

    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto py-6 px-4 overflow-hidden sm:px-6 lg:px-8">
            <p class="mt-1 text-center text-base text-gray-400">
                &copy; <?php echo date('Y'); ?> PT Cipta Hospital INA. All rights reserved.
            </p>
        </div>
    </footer>
</body>
</html>
