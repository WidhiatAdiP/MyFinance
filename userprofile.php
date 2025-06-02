<?php
include 'config.php';
include 'auth.php';

date_default_timezone_set('Asia/Jakarta');

$current_page = basename($_SERVER['PHP_SELF']);
$user_id = $_SESSION['user_id'];
// Pastikan nama user diambil dari session langsung, tanpa default 'User'
$nama = $_SESSION['nama'] ?? '';

if (empty($nama)) {
  $nama = 'User'; // fallback jika session kosong, optional
}

// Ambil data transaksi dan saldo
$filter_jenis = $_GET['jenis'] ?? '';

if ($filter_jenis === 'pemasukan' || $filter_jenis === 'pengeluaran') {
  $stmt = $conn->prepare("SELECT * FROM transaksi WHERE user_id = ? AND jenis = ? ORDER BY tanggal DESC, id DESC");
  $stmt->bind_param("is", $user_id, $filter_jenis);
  $stmt->execute();
  $result = $stmt->get_result();
} else {
  $result = $conn->query("SELECT * FROM transaksi WHERE user_id = $user_id ORDER BY tanggal DESC, id DESC");
}

$in_result = $conn->query("SELECT SUM(jumlah) as total FROM transaksi WHERE user_id=$user_id AND jenis='income'");
$in = $in_result->fetch_assoc()['total'] ?? 0;

$out_result = $conn->query("SELECT SUM(jumlah) as total FROM transaksi WHERE user_id=$user_id AND jenis='expense'");
$out = $out_result->fetch_assoc()['total'] ?? 0;

$saldo = $in - $out;

// Ambil waktu last login dari DB
$stmt = $conn->prepare("SELECT last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user_data = $res->fetch_assoc();
$last_login = $user_data['last_login'] ?? null;

$last_login_display = "No last login data";
if ($last_login) {
  // Format tanggal dan waktu bahasa Inggris default PHP, tanpa ubah bahasa
  $last_login_display = date('l, d F Y - H:i', strtotime($last_login)) . ' WIB';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>User Profile - MyFinance</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" />
  <link rel="stylesheet" href="assets/style.css" />
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();
  </script>

</head>

<body class="light-mode">
  <div class="app-container">
    <div class="layout">
      <nav class="sidebar">
        <h3>MyFinance</h3>
        <ul>
          <li>
            <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active wave' : 'wave' ?>">
              <span class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                  <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8v-10h-8v10zm0-18v6h8V3h-8z" />
                </svg>
              </span>
              Dashboard
            </a>
          </li>
          <li>
            <a href="transaksi.php" class="<?= $current_page == 'transaksi.php' ? 'active' : '' ?>">
              <span class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                  <path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h12v2H3v-2z" />
                </svg>
              </span>
              Transaction History
            </a>
          </li>
          <li>
            <a href="userprofile.php" class="<?= $current_page == 'userprofile.php' ? 'active' : '' ?>">
              <span class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z" />
                </svg>
              </span>
              Profile
            </a>
          </li>
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
      </nav>

      <main class="main-content profile-page">
        <div class="dashboard-wrapper">
          <div class="profile-header">
            <h2>Welcome, <?= htmlspecialchars($nama) ?>!</h2>
            <p id="datetimeDisplay"></p>
          </div>

          <?php
          // Ambil data lengkap user dari database
          $stmt = $conn->prepare("SELECT nama, email, last_login FROM users WHERE id = ?");
          $stmt->bind_param("i", $user_id);
          $stmt->execute();
          $user_data = $stmt->get_result()->fetch_assoc();

          $user_nama = $user_data['nama'] ?? 'Unknown';
          $user_email = $user_data['email'] ?? 'Not available';
          $last_login_raw = $user_data['last_login'] ?? null;
          $last_login_formatted = $last_login_raw ? date('l, d F Y - H:i', strtotime($last_login_raw)) . ' WIB' : 'No last login data';
          ?>

          <div class="profile-content">

            <div class="profile-card">
              <h4><i data-lucide="user" class="icon"></i> Account Info</h4>
              <p><strong>Username:</strong> <?= htmlspecialchars($user_nama) ?></p>
              <p><strong>Email:</strong> <?= htmlspecialchars($user_email) ?></p>
              <button class="btn-action">Edit Profile</button>
            </div>

            <div class="profile-card">
              <h4><i data-lucide="shield" class="icon"></i> Security</h4>
              <p><strong>Last Login:</strong> <?= htmlspecialchars($last_login_display) ?></p>
              <p><strong>Status:</strong> Online</p>
              <button class="btn-action">Change Password</button>
            </div>

            <div class="profile-card">
              <h4><i data-lucide="bar-chart-3" class="icon"></i> Financial Summary</h4>
              <p><strong>Total Income:</strong> Rp <?= number_format($in, 2, ',', '.') ?></p>
              <p><strong>Total Expense:</strong> Rp <?= number_format($out, 2, ',', '.') ?></p>
              <p><strong>Balance:</strong> Rp <?= number_format($saldo, 2, ',', '.') ?></p>
              <a href="transaksi.php" class="btn-action">View Transactions</a>
            </div>

          </div>

        </div>
      </main>
    </div>
  </div>

  <script>
    // Toggle theme function
    const toggleBtn = document.getElementById('toggleThemeBtn');

    function toggleTheme() {
      const body = document.body;
      if (body.classList.contains('dark-mode')) {
        body.classList.remove('dark-mode');
        body.classList.add('light-mode');
        localStorage.setItem('theme', 'light');
        document.getElementById('themeStatus').textContent = 'Light';
      } else {
        body.classList.remove('light-mode');
        body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
        document.getElementById('themeStatus').textContent = 'Dark';
      }
    }
    if (toggleBtn) toggleBtn.addEventListener('click', toggleTheme);

    // Load theme from localStorage
    document.addEventListener('DOMContentLoaded', () => {
      const savedTheme = localStorage.getItem('theme') || 'light';
      document.body.classList.remove('light-mode', 'dark-mode');
      document.body.classList.add(savedTheme + '-mode');
      document.getElementById('themeStatus').textContent = savedTheme.charAt(0).toUpperCase() + savedTheme.slice(1);
    });

    // Logout confirmation
    document.addEventListener('DOMContentLoaded', function() {
      const logoutBtn = document.getElementById('logout-btn');
      if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
          e.preventDefault();
          const isDark = document.body.classList.contains('dark-mode');
          Swal.fire({
            title: 'Logout?',
            text: "Are you sure you want to exit?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, logout!',
            cancelButtonText: 'Cancel',
            background: isDark ? '#2a2a3c' : '#fff',
            color: isDark ? '#f0f0f0' : '#333',
            confirmButtonColor: isDark ? '#d33' : '#3085d6',
            cancelButtonColor: isDark ? '#555' : '#aaa',
          }).then((result) => {
            if (result.isConfirmed) {
              window.location.href = logoutBtn.href;
            }
          });
        });
      }
    });

    // Update realtime datetime in English, no seconds
    function updateDateTime() {
      const dtDisplay = document.getElementById('datetimeDisplay');
      if (!dtDisplay) return;
      const now = new Date();

      const options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
      };

      // Format bahasa Inggris default browser
      const enDateTime = new Intl.DateTimeFormat('en-US', options).format(now);
      dtDisplay.textContent = enDateTime + ' WIB';
    }

    updateDateTime();
    setInterval(updateDateTime, 60000); // update tiap 1 menit
  </script>
</body>

</html>