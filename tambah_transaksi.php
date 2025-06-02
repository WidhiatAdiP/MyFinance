<?php
include 'config.php';
include 'auth.php';

$current_page = basename($_SERVER['PHP_SELF']);
date_default_timezone_set('Asia/Jakarta');
$username = $_SESSION['username'] ?? 'User';
$theme = (date('H') >= 18 || date('H') < 6) ? 'dark-mode' : 'light-mode';

$showSuccess = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal_input = $_POST['tanggal'];
    $date = new DateTime($tanggal_input);
    $tanggal = $date->format('Y-m-d');
    $keterangan = $_POST['keterangan'];
    $jumlah = $_POST['jumlah'];
    $jenis = $_POST['jenis'];

    $query_saldo = $conn->prepare("SELECT 
        SUM(CASE WHEN jenis = 'income' THEN jumlah ELSE 0 END) -
        SUM(CASE WHEN jenis = 'expense' THEN jumlah ELSE 0 END) AS saldo 
        FROM transaksi 
        WHERE user_id = ?");
    $query_saldo->bind_param("i", $_SESSION['user_id']);
    $query_saldo->execute();
    $query_saldo->bind_result($saldo);
    $query_saldo->fetch();
    $query_saldo->close();

    if ($jenis === 'expense' && $jumlah > $saldo) {
        $error = "Saldo tidak mencukupi. Sisa saldo Anda adalah Rp " . number_format($saldo, 2, ',', '.');
    } else {
        $stmt = $conn->prepare("INSERT INTO transaksi (user_id, tanggal, keterangan, jumlah, jenis) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issds", $_SESSION['user_id'], $tanggal, $keterangan, $jumlah, $jenis);
        if ($stmt->execute()) {
            $showSuccess = true;
        } else {
            $error = "Terjadi kesalahan saat menyimpan transaksi.";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Add Transaction</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="<?= $theme ?> tambah-transaksi-page">
    <div class="app-container">
        <div class="sidebar">
            <h3>MyFinance</h3>
            <ul>
                <?php
                $pages = [
                    "dashboard.php" => ["Dashboard", '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8v-10h-8v10zm0-18v6h8V3h-8z"/></svg>'],
                    "transaksi.php" => ["Transaction History", '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h12v2H3v-2z"/></svg>'],
                    "tambah_transaksi.php" => ["Add Transaction", '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"> <path d="M19 11h-6V5h-2v6H5v2h6v6h2v-6h6z"/></svg>'],
                ];

                // Disable semua link selain halaman aktif saat di halaman yang sedang aktif.
                // Jadi hanya $current_page yang link-nya aktif, sisanya disabled.
                foreach ($pages as $page => [$label, $icon]) {
                    $isActive = ($current_page == $page);
                    // Jika current_page adalah salah satu page di list, selain itu disable
                    $isDisabled = (!$isActive);
                ?>
                    <li>
                        <?php if ($isDisabled): ?>
                            <a href="#" class="disabled" aria-disabled="true">
                                <span class="icon"><?= $icon ?></span>
                                <?= $label ?>
                            </a>
                        <?php else: ?>
                            <a href="<?= $page ?>" class="active">
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

        <div class="main-content">
            <h2>Tambah Transaksi</h2>

            <?php if (!empty($error)): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Pastikan tema diterapkan dulu dari localStorage
                        const savedTheme = localStorage.getItem('theme') || '<?= $theme ?>';
                        document.body.classList.remove('light-mode', 'dark-mode');
                        document.body.classList.add(savedTheme + '-mode');

                        const isDark = document.body.classList.contains('dark-mode');

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: <?= json_encode($error) ?>,
                            background: isDark ? '#2a2a3c' : '#fff',
                            color: isDark ? '#f0f0f0' : '#333',
                            confirmButtonColor: isDark ? '#ff6b81' : '#e74c3c'
                        });
                    });
                </script>
            <?php endif; ?>

            <form method="post" class="form-tambah" autocomplete="off">
                <label>Date</label>
                <input type="date" name="tanggal" required />

                <label>Information</label>
                <input type="text" name="keterangan" placeholder="Contoh: Gaji, Belanja" required autocomplete="off" />

                <label>Amount (Rp)</label>
                <input type="number" step="0.01" name="jumlah" required />

                <label>Transaction Type</label>
                <select name="jenis">
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
                <button type="submit">Simpan</button>
                <button type="button" id="btnKembali" class="btn-kembali">Back to Transaction Page</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Atur tema dari localStorage
            const savedTheme = localStorage.getItem('theme') || '<?= $theme ?>';
            document.body.classList.remove('light-mode', 'dark-mode');
            document.body.classList.add(savedTheme + '-mode');

            const isDark = document.body.classList.contains('dark-mode');

            // Tombol tema
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

            // Logout confirm
            const logoutBtn = document.getElementById('logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
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
                            popup: 'rounded-xl',
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "logout.php";
                        }
                    });
                });
            }

            // ✅ SweetAlert untuk validasi error
            <?php if (!empty($error)): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Save Failed!',
                    text: <?= json_encode($error) ?>,
                    showCancelButton: true,
                    confirmButtonText: 'Stay Here',
                    cancelButtonText: 'To Transaction History',
                    background: isDark ? '#2a2a3c' : '#fff',
                    color: isDark ? '#f0f0f0' : '#333',
                    confirmButtonColor: '#7b5cff',
                    cancelButtonColor: '#ccc',
                    customClass: {
                        popup: 'rounded-xl',
                    }
                }).then((result) => {
                    if (result.dismiss === Swal.DismissReason.cancel) {
                        window.location.href = 'transaksi.php';
                    }
                });
            <?php endif; ?>

            // ✅ SweetAlert untuk sukses
            <?php if ($showSuccess): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Added Success!',
                    text: 'Transaction saved successfully!',
                    confirmButtonColor: '#7b5cff',
                    background: isDark ? '#2a2a3c' : '#fff',
                    color: isDark ? '#f0f0f0' : '#333',
                }).then(() => {
                    window.location.href = 'transaksi.php';
                });
            <?php endif; ?>

            // Tombol Kembali ke Riwayat Transaksi
            const kembaliBtn = document.getElementById('btnKembali');
            if (kembaliBtn) {
                kembaliBtn.addEventListener('click', function() {
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