// Fungsi umum yang bisa dipakai semua halaman
document.addEventListener("DOMContentLoaded", function () {
  // Contoh inisialisasi
  console.log("Aplikasi sudah siap!");
});

// Fungsi untuk toggle menu (jika diperlukan)
function toggleSideNav() {
  const sideNav = document.querySelector(".sidenav");
  sideNav.classList.toggle("collapsed");
}
