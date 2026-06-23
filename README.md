# SLiMS Copy Cataloging Plugin
Plugin ini digunakan untuk melakukan salin katalog (copy cataloging) antar instansi yang menggunakan platform SLiMS. Ketika pengguna mengklik sebuah judul, SLiMS akan secara otomatis mencari daftar OPAC/katalog yang memiliki koleksi buku tersebut.

Dikembangkan oleh: **Ruang Perpustakaan** ([ruangperpustakaan.com](https://ruangperpustakaan.com))

---

## 🛠️ Langkah Pemasangan (Installation)

1. **Unduh & Ekstrak:**
   Unduh file plugin ini, kemudian ekstrak file ZIP ke dalam direktori `plugins/` yang berada di dalam folder utama SLiMS Anda.
   
2. **Pastikan Struktur Direktori:**
   Pastikan seluruh kode dan berkas plugin berada di dalam direktori dengan nama yang tepat:
   ```text
   slims_root/plugins/SLIMS_COPY/

⚙️ Konfigurasi Daftar Katalog (Server List)
Sebelum atau sesudah aktivasi, Anda dapat menyesuaikan daftar alamat server katalog target di dalam file konfigurasi plugin:

Lokasi File: inlislite_copy_cataloging/server_list.inc.php

Petunjuk: Buka file tersebut menggunakan text editor, lalu tambahkan baris kode daftar perpustakaan berbasis SLiMS yang ingin dijadikan target pencarian pada variabel array yang tersedia.

🔌 Langkah Aktivasi (Activation)
1. Masuk ke panel Admin SLiMS Anda.

2. Buka modul System (Sistem) pada menu navigasi utama.

3. Pilih sub-menu Plugins.

4. Cari nama plugin SLiMS Copy

5. Ubah status plugin dari Nonaktif menjadi Aktif.

📖 Cara Penggunaan (Usage)
1. Setelah plugin berhasil diaktifkan, buka modul Bibliography (Bibliografi).

2. Di dalam sub-menu modul tersebut, Anda akan menemukan menu baru bernama SLIMS Copy.

3. Masukkan kata kunci buku di kolom pencarian

4. Plugin akan mulai mencari buku berdasarkan daftar katalog slims yang sudah dimasukkan di server list.php

5. Plih buku yang sesuai kemudian klik simpan data terpilih. Buku tersebut otomatis akan tersimpan di Bibliografi kamu.

6. Silakan gunakan menu tersebut untuk mencari dan menyalin data katalog berdasarkan judul buku secara otomatis.

📄 Lisensi & Kontribusi
Plugin ini dikembangkan untuk mempermudah pustakawan dalam melakukan pengolahan data bibliografi secara efisien. Didukung penuh oleh ekosistem Ruang Perpustakaan.

