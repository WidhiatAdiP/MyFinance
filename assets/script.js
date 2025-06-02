document.addEventListener('DOMContentLoaded', function () {
    const menuLinks = document.querySelectorAll('.sidebar a');

    menuLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            // Tambah animasi klik
            link.classList.add('clicked');

            // Hapus class setelah animasi selesai (0.2 detik)
            setTimeout(() => {
                link.classList.remove('clicked');
            }, 200);
        });
    });
});