<?php
/**
 * Plugin Name: SLiMS Copy Cataloging
 * Description: Plugin untuk menyalin data katalog dari sesama SLiMS 
 * Version: 1.0.0
 * Author: Ruang Perpustakaan
 * Author URI: https://ruangperpustakaan.com
 */

// get plugin instance
$plugin = \SLiMS\Plugins::getInstance();

// registering menus
// Saya ubah label menunya menjadi "SLiMS Copy" agar mudah dicari di menu Bibliografi
$plugin->registerMenu('bibliography', 'SLiMS Copy', __DIR__ . '/index.php');