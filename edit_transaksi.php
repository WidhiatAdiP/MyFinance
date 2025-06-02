<?php
include 'config.php';
include 'auth.php';

$current_page = basename($_SERVER['PHP_SELF']);
date_default_timezone_set('Asia/Jakarta');
$username = $_SESSION['username'] ?? 'User';
$theme = (date('H') >= 18 || date('H') < 6) ? 'dark-mode' : 'light-mode';

// Ambil ID transaksi dari URL
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: transaksi.php");
    exit;
}

// Ambil data transaksi berdasarkan ID
$stmt = $conn->prepare("SELECT * FROM transaksi WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$transaksi = $result->fetch_assoc();

if (!$transaksi) {
    echo "Transaksi tidak ditemukan.";
    exit;
}

// Jika form disubmit, update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'];
    $keterangan = $_POST['keterangan'];
    $jumlah = $_POST['jumlah'];
    $jenis = $_POST['jenis'];

    // Selalu redirect dengan success=1
    if (
        $tanggal != $transaksi['tanggal'] ||
        $keterangan != $transaksi['keterangan'] ||
        $jumlah != $transaksi['jumlah'] ||
        $jenis != $transaksi['jenis']
    ) {
        $sql = "UPDATE transaksi SET tanggal=?, keterangan=?, jumlah=?, jenis=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdsi", $tanggal, $keterangan, $jumlah, $jenis, $id);
        $stmt->execute();
    }

    header("Location: edit_transaksi.php?id=$id&success=1");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Transaksi</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="<?= $theme ?> tambah-transaksi-page">
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h3>MyFinance</h3>
            <ul>
                <?php
                $pages = [
                    "dashboard.php" => ["Dashboard", '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8v-10h-8v10zm0-18v6h8V3h-8z"/></svg>'],
                    "transaksi.php" => ["Transaction History", '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h12v2H3v-2z"/></svg>'],
                    "edit_transaksi.php" => ["Edit Transaction", '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>'],
                ];

                foreach ($pages as $page => [$label, $icon]) {
                    $isActive = ($current_page == $page);
                    $isDisabled = ($current_page == 'edit_transaksi.php' && !$isActive);
                ?>
                    <li>
                        <?php if ($isDisabled): ?>
                            <a href="#" class="disabled" aria-disabled="true">
                                <span class="icon"><?= $icon ?></span>
                                <?= $label ?>
                            </a>
                        <?php else: ?>
                            <a href="<?= $page ?>" class="<?= $isActive ? 'active' : '' ?>">
                                <span class="icon"><?= $icon ?></span>
                                <?= $label ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php } ?>
                <li>
                    <a href="logout.php" id="logout-btn" class="btn-logout">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path d="M16 13v-2H7V8l-5 4 5 4v-3h9zm3-10H5c-1.1 0-2 .9-2 2v6h2V5h14v14H5v-6H3v6c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z" />
                            </svg>
                        </span>
                        Logout
                    </a>
                </li>
            </ul>
            <button id="toggleThemeBtn" class="theme-toggle" aria-label="Toggle Dark/Light Mode" title="Toggle Dark/Light Mode">
                <div class="toggle-track">
                    <div class="toggle-thumb">
                        <svg id="iconSun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFD700">
                            <circle cx="12" cy="12" r="5" />
                            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="#FFD700" stroke-width="2" stroke-linecap="round" />
                        </svg>
                        <svg id="iconMoon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#4B79A1">
                            <path d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z" />
                        </svg>
                    </div>
                </div>
            </button>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <h2>Edit Transaksi</h2>
            <form method="post" class="form-tambah" autocomplete="off">
                <label>Date</label>
                <input type="date" name="tanggal" value="<?= htmlspecialchars($transaksi['tanggal']) ?>" required />

                <label>Information</label>
                <input type="text" name="keterangan" value="<?= htmlspecialchars($transaksi['keterangan']) ?>" required />

                <label>Amount (Rp)</label>
                <input type="number" step="0.01" name="jumlah" value="<?= htmlspecialchars($transaksi['jumlah']) ?>" required />

                <label>Transaction Type</label>
                <select name="jenis">
                    <option value="income" <?= $transaksi['jenis'] == 'income' ? 'selected' : '' ?>>Income</option>
                    <option value="expense" <?= $transaksi['jenis'] == 'expense' ? 'selected' : '' ?>>Expense</option>
                </select>

                <button type="submit">Update</button>
                <button type="button" id="btnKembali" class="btn-kembali">Back to Transaction Page</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Setup theme
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.body.classList.remove('light-mode', 'dark-mode');
            document.body.classList.add(savedTheme + '-mode');

            // Toggle theme
            const toggleBtn = document.getElementById('toggleThemeBtn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const body = document.body;
                    if (body.classList.contains('dark-mode')) {
                        body.classList.remove('dark-mode');
                        body.classList.add('light-mode');
                        localStorage.setItem('theme', 'light');
                    } else {
                        body.classList.remove('light-mode');
                        body.classList.add('dark-mode');
                        localStorage.setItem('theme', 'dark');
                    }
                });
            }

            // Logout confirmation
            const logoutBtn = document.getElementById('logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();

                    const isDark = document.body.classList.contains('dark-mode');
                    Swal.fire({
                        title: 'Logout?',
                        text: "Are you sure you want to logout?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, logout',
                        cancelButtonText: 'Cancel',
                        background: isDark ? '#2a2a3c' : '#fff',
                        color: isDark ? '#f0f0f0' : '#333',
                        confirmButtonColor: isDark ? '#a689ff' : '#7b5cff',
                        cancelButtonColor: isDark ? '#555' : '#ccc',
                        customClass: {
                            popup: 'rounded-xl'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "logout.php";
                        }
                    });
                });
            }

            // Show success alert
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === '1') {
                const isDark = document.body.classList.contains('dark-mode');
                Swal.fire({
                    icon: 'success',
                    title: 'Data saved successfully!',
                    background: isDark ? '#2a2a3c' : '#fff',
                    color: isDark ? '#f0f0f0' : '#333',
                    confirmButtonColor: isDark ? '#a689ff' : '#7b5cff',
                }).then(() => {
                    window.location.href = 'transaksi.php';
                });
            }

            const kembaliBtn = document.getElementById('btnKembali');
            if (kembaliBtn) {
                kembaliBtn.addEventListener('click', function() {
                    const isDark = document.body.classList.contains('dark-mode'); // Pindahkan ke sini
                    Swal.fire({
                        title: 'Back to Transaction Page?',
                        text: 'Changes will not be saved. Are you sure you want to go back?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, go back',
                        cancelButtonText: 'Cancel',
                        background: isDark ? '#2a2a3c' : '#fff',
                        color: isDark ? '#f0f0f0' : '#333',
                        confirmButtonColor: isDark ? '#a689ff' : '#7b5cff',
                        cancelButtonColor: isDark ? '#555' : '#ccc',
                        customClass: {
                            popup: 'rounded-xl',
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'transaksi.php';
                        }
                    });
                });
            }
        });
    </script>
</body>

</html>