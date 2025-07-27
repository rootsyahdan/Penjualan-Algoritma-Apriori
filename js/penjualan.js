document.addEventListener("DOMContentLoaded", function () {
  // Initialize Select2
  $(".select2").select2({
    placeholder: "Pilih Barang",
    width: "100%",
    minimumInputLength: 2,
    language: {
      inputTooShort: function () {
        return "Ketik minimal 2 karakter";
      },
    },
  });

  // Update harga saat barang dipilih
  $("#barangSelect").on("change", function () {
    const selected = $(this).find("option:selected");
    const harga = selected.data("harga");
    const ukuran = selected.data("ukuran");

    $("#hargaDisplay").val("Rp " + Number(harga).toLocaleString("id-ID"));
    $("#ukuranDisplay").val(ukuran || "-");
  });

  // Auto-close alert
  setTimeout(() => {
    document.querySelectorAll(".alert").forEach((alert) => {
      alert.style.display = "none";
    });
  }, 5000);
});
