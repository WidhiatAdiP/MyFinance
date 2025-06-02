<?php
include 'config.php';
include 'auth.php';

date_default_timezone_set('Asia/Jakarta');
$timeNow = time();

$current_page = basename($_SERVER['PHP_SELF']);
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

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
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Financial Dashboard</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" />
  <link rel="stylesheet" href="assets/style.css" />
  <!-- SweetAlert2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

      <main class="main-content">
        <div class="dashboard-wrapper">
          <h2>Financial Dashboard</h2>
          <h3>Greetings, <?= htmlspecialchars($username) ?>!</h3>
          <p class="datetime" id="datetimeDisplay"></p>

          <br>
          <div class="summary-box">
            <div class="card income">
              <h4>Total Income</h4>
              <p>+ Rp <?= number_format($in, 2, ',', '.') ?></p>
            </div>
            <div class="card expense">
              <h4>Total Expense</h4>
              <p>- Rp <?= number_format($out, 2, ',', '.') ?></p>
            </div>
            <div class="card balance">
              <h4>Closing Balance</h4>
              <p>Rp <?= number_format($saldo, 2, ',', '.') ?></p>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script>
    // Ambil elemen toggle button
    const toggleBtn = document.getElementById('toggleThemeBtn');

    // Fungsi untuk toggle mode
    function toggleTheme() {
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
    }

    // Pasang event listener
    if (toggleBtn) {
      toggleBtn.addEventListener('click', toggleTheme);
    }

    // Saat halaman dimuat, cek preferensi tema dari localStorage
    document.addEventListener('DOMContentLoaded', () => {
      const savedTheme = localStorage.getItem('theme') || 'light';
      document.body.classList.remove('light-mode', 'dark-mode');
      document.body.classList.add(savedTheme + '-mode');
    });

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
            confirmButtonColor: isDark ? '#a689ff' : '#7b5cff',
            cancelButtonColor: isDark ? '#555' : '#ccc',
            customClass: {
              popup: 'rounded-xl',
              title: 'swal2-title-custom',
              htmlContainer: 'swal2-text-custom',
              confirmButton: 'swal2-confirm-custom',
              cancelButton: 'swal2-cancel-custom',
            }
          }).then((result) => {
            if (result.isConfirmed) {
              window.location.href = "logout.php";
            }
          });
        });
      }
    });

    // Waktu awal dari server, dalam detik Unix timestamp
    const serverTimestamp = <?= $timeNow ?> * 1000; // convert ke ms

    // Fungsi untuk format tanggal seperti di PHP date('l, d F Y - H:i')
    function formatDate(date) {
      const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
      const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

      const dayName = days[date.getDay()];
      const day = date.getDate().toString().padStart(2, '0');
      const monthName = months[date.getMonth()];
      const year = date.getFullYear();

      const hours = date.getHours().toString().padStart(2, '0');
      const minutes = date.getMinutes().toString().padStart(2, '0');

      return `${dayName}, ${day} ${monthName} ${year} - ${hours}:${minutes}`;
    }

    // Hitung offset antara waktu client dan waktu server (dalam ms)
    const clientNow = Date.now();
    const offset = serverTimestamp - clientNow;

    function updateTime() {
      // waktu server sekarang berdasarkan client + offset
      const now = new Date(Date.now() + offset);
      document.getElementById('datetimeDisplay').textContent = formatDate(now) + ' WIB';
    }

    // Update langsung dan set interval update setiap 1 menit (atau bisa 1 detik)
    updateTime();
    setInterval(updateTime, 1000); // update setiap 60 detik agar efisien

    document.querySelectorAll('.sidebar ul li a').forEach(link => {
      link.addEventListener('click', function(e) {
        // Hilangkan class clicked di semua nav link
        document.querySelectorAll('.sidebar ul li a').forEach(el => el.classList.remove('clicked'));

        // Tambahkan class clicked pada elemen ini
        this.classList.add('clicked');

        // Hilangkan class clicked setelah animasi selesai (3 detik)
        setTimeout(() => {
          this.classList.remove('clicked');
        }, 3000);
      });
    });
  </script>
</body>

</html>