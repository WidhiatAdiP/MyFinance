<?php
include 'config.php';
include 'auth.php';

$current_page = basename($_SERVER['PHP_SELF']);

date_default_timezone_set('Asia/Jakarta');
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$theme = (date('H') >= 18 || date('H') < 6) ? 'dark-mode' : 'light-mode';

$filter = $_GET['filter'] ?? 'semua';

// Prepare SQL query dengan prepared statement
if ($filter === 'income' || $filter === 'expense') {
    $stmt = $conn->prepare("SELECT * FROM transaksi WHERE user_id = ? AND jenis = ? ORDER BY tanggal DESC, id DESC");
    $stmt->bind_param("is", $user_id, $filter);
} else {
    $stmt = $conn->prepare("SELECT * FROM transaksi WHERE user_id = ? ORDER BY tanggal DESC, id DESC");
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History</title>
    <link rel="stylesheet" href="assets/style.css">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="<?= $theme ?>">
    <div class="app-container">
        <div class="layout">

            <nav class="sidebar">
                <h3>MyFinance</h3>
                <ul>
                    <li>
                        <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                            <span class="icon">
                                <!-- Dashboard Icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8v-10h-8v10zm0-18v6h8V3h-8z" />
                                </svg>
                            </span>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="transaksi.php" class="<?= $current_page == 'transaksi.php' ? 'active wave' : 'wave' ?>">
                            <span class="icon">
                                <!-- Transaksi Icon -->
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
                                <!-- Logout SVG icon -->
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
                <div class="header-flex">
                    <h2>Transaction History</h2>
                    <a href="tambah_transaksi.php" class="btn btn-add">+ Add Transaction</a>
                </div>

                <form method="get" class="filter-form">
                    <label for="filter">Filter: </label>
                    <select id="filter" name="filter" onchange="this.form.submit()">
                        <option value="semua" <?= $filter == 'semua' ? 'selected' : '' ?>>All</option>
                        <option value="income" <?= $filter == 'income' ? 'selected' : '' ?>>Income</option>
                        <option value="expense" <?= $filter == 'expense' ? 'selected' : '' ?>>Expense</option>
                    </select>
                </form>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Date</th>
                                <th>Information</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th colspan="2">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            while ($row = $result->fetch_assoc()) : ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                    <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                    <td><?= ucfirst($row['jenis']) ?></td>
                                    <td class="<?= $row['jenis'] == 'income' ? 'text-income' : 'text-expense' ?>">
                                        <?= $row['jenis'] == 'income' ? '+' : '-' ?> Rp <?= number_format($row['jumlah'], 2, ',', '.') ?>
                                    </td>
                                    <td><a href="edit_transaksi.php?id=<?= $row['id'] ?>" class="btn btn-small btn-edit edit-btn">Edit</a></td>
                                    <td><a href="hapus_transaksi.php?id=<?= $row['id'] ?>" class="btn btn-small btn-delete delete-btn">Delete</a></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </main>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Setup tema dari localStorage
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.body.classList.remove('light-mode', 'dark-mode');
            document.body.classList.add(savedTheme + '-mode');

            // Toggle theme button
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

            // Logout dengan SweetAlert2
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

            // Delete dengan SweetAlert2
            document.querySelectorAll('.delete-btn').forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    const url = this.getAttribute('href');
                    const isDark = document.body.classList.contains('dark-mode');

                    Swal.fire({
                        title: 'Delete Transaction?',
                        text: "This transaction will be permanently deleted.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete',
                        cancelButtonText: 'Cancel',
                        background: isDark ? '#2a2a3c' : '#fff',
                        color: isDark ? '#f0f0f0' : '#333',
                        confirmButtonColor: isDark ? '#ff4f4f' : '#e74c3c',
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
                            window.location.href = url;
                        }
                    });
                });
            });

            // Edit dengan SweetAlert2
            document.querySelectorAll('.edit-btn').forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    const url = this.getAttribute('href');
                    const isDark = document.body.classList.contains('dark-mode');

                    Swal.fire({
                        title: 'Edit Transaction?',
                        text: "You will be directed to edit this transaction.",
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Confirm Edit',
                        cancelButtonText: 'Cancel',
                        background: isDark ? '#2a2a3c' : '#fff',
                        color: isDark ? '#f0f0f0' : '#333',
                        confirmButtonColor: isDark ? '#6c63ff' : '#4a47a3',
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
                            window.location.href = url;
                        }
                    });
                });
            });

            document.querySelectorAll('.sidebar ul li a').forEach(link => {
                link.addEventListener('click', function(e) {
                    // Hilangkan class clicked di semua nav link
                    document.querySelectorAll('.sidebar ul li a').forEach(el => el.classList.remove('clicked'));

                    // Tambahkan class clicked pada elemen ini
                    this.classList.add('clicked');

                    // Hilangkan class clicked setelah animasi selesai (600ms)
                    setTimeout(() => this.classList.remove('clicked'), 600);
                });
            });
        });
    </script>

</body>

</html> <?php $stmt->close();
        $conn->close(); ?>