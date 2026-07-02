##### Notes on translating this plugin.

The original *index.php* has interface strings hard-coded in Indonesian and providing this plugin in other languages can be done in two ways:

1. Individual language versions can also be hard-coded, named indicatively, and packaged along with the original e.g  *index_eng.php* , *index_ara.php* , *index_mya.php* etc. The end-user can then rename their preferred file as  *index.php* and use that interface.  [ Alternatively, *slims_copy.plugin.php* could be edited to point to the desired version, although this is less recommended for inexperienced users ] . This general  approach has the advantage of slightly reduced translation load during operations but some maintenance disadvantages.        **OR**
2. The interface strings can be appropriately wrapped for gettext translation in the index.php code. In order to maintain consistency with existing SLiMS practice, the strings should be in English. This approach requires additional coding, and then modification of SLiMS po & mo files for the various translations. 

The strings in <u>the original version</u> that  must be translated are,  (one per line), :

Berhasil menyalin 
Buku 
Pilih
Cover
Judul
Detail 
Tidak ada buku ditemukan.
Total ditemukan
buku dari seluruh server. 
Simpan Data Terpilih
Tidak ada buku ditemukan di server manapun. 
Server Tanpa Hasil 
SLiMS Copy (Multi-Search) 
Kata Kunci: 
Ketik judul buku... 
Cari di Semua Server 

------

A file which implements the gettext approach is included , as  *index_eng_translatable.php* . If used without changes to translations, the interface will be in English.

gurujim. 02/06/2026