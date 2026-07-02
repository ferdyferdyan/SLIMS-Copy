<?php
/**
 * @Plugin Name: SLiMS Copy (Multi-Server Search)
 * Description: Plugin untuk menyalin data katalog dari sesama SLiMS 
 * Version: 1.0.0
 * Author: Ruang Perpustakaan
 * Author URI: https://ruangperpustakaan.com
 */

// Made translatable with English strings , by gurujim.02/07/2026

defined('INDEX_AUTH') OR die('Direct access not allowed!');

// --- 1. LOAD LIBRARY ---
use SLiMS\Filesystems\Storage;

require SIMBIO . 'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO . 'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO . 'simbio_DB/simbio_dbop.inc.php';
require MDLBS . 'system/biblio_indexer.inc.php';

// Load Library XML
if (!function_exists('modsXMLsenayan')) {
    if (file_exists(LIB . 'modsxmlsenayan.inc.php')) {
        require LIB . 'modsxmlsenayan.inc.php';
    } else {
        $fallback = __DIR__ . '/../../lib/modsxmlsenayan.inc.php';
        if (file_exists($fallback)) require $fallback;
    }
}

// --- 2. FUNGSI BANTUAN ---
function cleanUrl($url) {
    return rtrim($url, '/') . '/';
}

function downloadFile($url, $storage, $path) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // Timeout agak cepat agar tidak membebani jika download banyak
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); 
    $data = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($data && !$error) {
        return $storage->put($path, $data);
    }
    return false;
}

// --- 3. LOAD SERVER LIST ---
$sysconf['p2pserver'] = [];
$server_list_file = __DIR__ . '/server_list.inc.php';
if (file_exists($server_list_file)) {
    include $server_list_file;
    if (isset($sysconf['marc_XML_source'])) {
        foreach ($sysconf['marc_XML_source'] as $idx => $srv) {
            $sysconf['p2pserver'][$idx] = ['id' => $idx, 'uri' => $srv['uri'], 'name' => $srv['desc']];
        }
    }
}

// --- VARIABLES ---
$current_action_url = $_SERVER['REQUEST_URI']; 
$results_html = '';
$msg_box = '';

// =============================================================================
// LOGIKA 1: PROSES SIMPAN (SAVE)
// =============================================================================
if (isset($_POST['act']) && $_POST['act'] == 'save' && isset($_POST['p2precord'])) {
    
    // Mencegah Timeout saat download banyak gambar
    @set_time_limit(0);
    @ini_set('memory_limit', '-1');

    require MDLBS . 'bibliography/biblio_utils.inc.php';
    $count = 0;
    
    // Cache array untuk mempercepat lookup
    $gmd_cache = []; $publ_cache = []; $place_cache = []; $lang_cache = []; $author_cache = []; $subject_cache = [];
    
    foreach ($_POST['p2precord'] as $composite_value) {
        // Pecah Value: "INDEX_SERVER|ID_BUKU"
        $parts = explode('|', $composite_value);
        if (count($parts) < 2) continue;

        $server_idx = $parts[0];
        $book_id = $parts[1];

        // Ambil URL Server berdasarkan Index
        if (!isset($sysconf['p2pserver'][$server_idx])) continue;
        $target_server = cleanUrl($sysconf['p2pserver'][$server_idx]['uri']);

        $xml_uri = $target_server . "index.php?p=show_detail&inXML=true&id=" . $book_id;
        
        if (function_exists('modsXMLsenayan')) {
            // PROTEKSI XML SAAT SIMPAN
            $data = false;
            libxml_use_internal_errors(true);
            try {
                $data = @modsXMLsenayan($xml_uri, 'uri');
            } catch (Throwable $t) { $data = false; }
            libxml_clear_errors();

            if ($data && isset($data['records'][0])) {
                $rec = $data['records'][0];
                $sql_op = new simbio_dbop($dbs);
                $biblio = [];

                foreach ($rec as $k => $v) {
                    if (is_string($v)) $biblio[$k] = $dbs->escape_string(trim($v));
                }
                
                // Lookup ID
                $biblio['gmd_id'] = utility::getID($dbs, 'mst_gmd', 'gmd_id', 'gmd_name', $rec['gmd'] ?? 'Text', $gmd_cache);
                $biblio['publisher_id'] = utility::getID($dbs, 'mst_publisher', 'publisher_id', 'publisher_name', $rec['publisher'] ?? '-', $publ_cache);
                $biblio['publish_place_id'] = utility::getID($dbs, 'mst_place', 'place_id', 'place_name', $rec['publish_place'] ?? '-', $place_cache);
                $biblio['language_id'] = utility::getID($dbs, 'mst_language', 'language_id', 'language_name', $rec['language']['name'] ?? 'Indonesia', $lang_cache);
                
                // Cleanup
                unset($biblio['gmd'], $biblio['publisher'], $biblio['publish_place'], $biblio['language'], $biblio['authors'], $biblio['subjects']);
                unset($biblio['manuscript'], $biblio['collection'], $biblio['resource_type'], $biblio['genre_authority'], $biblio['genre'], $biblio['issuance'], $biblio['location'], $biblio['id'], $biblio['create_date'], $biblio['modified_date'], $biblio['origin']);
                
                $biblio['input_date'] = date('Y-m-d H:i:s');
                $biblio['last_update'] = date('Y-m-d H:i:s');
                $biblio['uid'] = $_SESSION['uid'] ?? 1;

                // Download Image
                if (!empty($rec['image'])) {
                    $remote_img = $target_server . 'lib/minigalnano/createthumb.php?filename=images/docs/' . $rec['image'];
                    $local_path = 'docs' . DS . $rec['image'];
                    downloadFile($remote_img, Storage::images(), $local_path);
                }

                if ($sql_op->insert('biblio', $biblio)) {
                    $new_id = $sql_op->insert_id;
                    $count++;
                    
                    if (isset($rec['authors'])) {
                        foreach ($rec['authors'] as $au) {
                            $aid = getAuthorID($au['name'], 'p', $author_cache);
                            $dbs->query("INSERT IGNORE INTO biblio_author VALUES ($new_id, $aid, 1)");
                        }
                    }
                    if (isset($rec['subjects'])) {
                        foreach ($rec['subjects'] as $subj) {
                            $sid = getSubjectID($subj['term'], 't', $subject_cache);
                            $dbs->query("INSERT IGNORE INTO biblio_topic VALUES ($new_id, $sid, 1)");
                        }
                    }
                    $indexer = new biblio_indexer($dbs);
                    $indexer->makeIndex($new_id);
                }
            }
        }
    }
    // LINE 303 - Made translatable
    $msg_box = '<div class="alert alert-success">' . __('Copying successfully') . ' <b>'.$count.'</b> ' . __('books to local database!') . '</div>';
}

// =============================================================================
// LOGIKA 2: PROSES CARI (MULTI-SERVER DENGAN SORTING HASIL)
// =============================================================================
if (isset($_POST['act']) && $_POST['act'] == 'search') {
    // ANTI TIMEOUT
    @set_time_limit(0);
    @ini_set('memory_limit', '-1');

    $keyword = urlencode($_POST['keywords']);
    $total_found_global = 0;
    
    // Penampung HTML untuk Sorting
    $html_found = ''; // Untuk server yang ADA bukunya
    $html_empty = ''; // Untuk server yang KOSONG/ERROR

    // --- LOOPING SEMUA SERVER ---
    foreach ($sysconf['p2pserver'] as $idx => $srv) {
        $server_url = cleanUrl($srv['uri']);
        $server_name = $srv['name'];
        $api_url = $server_url . "index.php?resultXML=true&search=Search&keywords=" . $keyword;
        
        $server_card_html = '';
        $found_in_this_server = false;

        // Try Catch untuk request XML
        $data = false;
        libxml_use_internal_errors(true);
        try {
            if (function_exists('modsXMLsenayan')) {
                $data = @modsXMLsenayan($api_url, 'uri');
            }
        } catch (Throwable $t) { $data = false; }
        libxml_clear_errors();
        
        // Header Card
        $header_class = 'bg-secondary'; // Default abu-abu (kosong/loading)
        $border_class = 'border-secondary';
        $badge = '<span class="badge badge-dark">0</span>';
        
        if ($data && isset($data['records']) && count($data['records']) > 0) {
            $count_rec = count($data['records']);
            $total_found_global += $count_rec;
            $found_in_this_server = true;
            
            // Ubah warna jadi biru jika ada data
            $header_class = 'bg-info'; 
            $border_class = 'border-info';
            // LINE 318 - Made translatable
            $badge = '<span class="badge badge-light text-dark">'.$count_rec.' ' . __('Book') . '</span>';
        }

        $server_card_html .= '<div class="card mt-3 mb-3 '.$border_class.'">';
        $server_card_html .= '<div class="card-header '.$header_class.' text-white d-flex justify-content-between align-items-center">
                                <div><strong><i class="fa fa-university"></i> '.$server_name.'</strong> <small class="text-white-50 ml-2">'.$server_url.'</small></div>
                                '.$badge.'
                              </div>';
        $server_card_html .= '<div class="card-body p-0">';

        if ($found_in_this_server) {
            // Render Tabel Hasil
            $table = new simbio_table();
            $table->table_attr = 'class="s-table table table-striped table-hover mb-0"';
            // LINE 329 - Made translatable
            $table->setHeader([__('Select'), __('Cover'), __('Title'), __('Detail')]);
            
            foreach ($data['records'] as $rec) {
                $composite_val = $idx . '|' . $rec['id'];
                $cb = '<input type="checkbox" name="p2precord[]" value="'.$composite_val.'" style="transform: scale(1.5);">';
                
                $rec_image = isset($rec['image']) ? $rec['image'] : '';
                $img_src = $server_url . 'lib/minigalnano/createthumb.php?filename=images/docs/' . $rec_image . '&width=60';
                $img = '<img src="'.$img_src.'" style="width:50px; border:1px solid #ddd; padding:2px;">';
                
                $rec_title = isset($rec['title']) ? $rec['title'] : '-';
                $info = '<strong style="font-size:1.1em;">'.$rec_title.'</strong><br>';
                
                if(isset($rec['authors'][0]['name'])) {
                    $info .= '<i class="text-muted">'.$rec['authors'][0]['name'].'</i>';
                }
                
                $rec_pub = isset($rec['publisher']) ? $rec['publisher'] : '-';
                $rec_year = isset($rec['publish_year']) ? $rec['publish_year'] : '-';
                $rec_place = isset($rec['publish_place']) ? $rec['publish_place'] : '-';

                $det = $rec_pub . ' (' . $rec_year . ')<br>';
                $det .= '<small>'.$rec_place.'</small>';
                
                $table->appendTableRow([$cb, $img, $info, $det]);
            }
            $server_card_html .= $table->printTable();
        } else {
            // LINE 349 - Made translatable
            $server_card_html .= '<div class="p-2 text-muted font-italic text-center small">' . __('No books found.') . '</div>';
        }
        
        $server_card_html .= '</div></div>'; // Tutup Card

        // SORTING: Masukkan ke wadah yang sesuai
        if ($found_in_this_server) {
            $html_found .= $server_card_html;
        } else {
            $html_empty .= $server_card_html;
        }
    }
    
    // GABUNGKAN HASIL (Found di atas, Empty di bawah)
    $results_html = '<form method="post" action="'.$current_action_url.'">';
    $results_html .= '<input type="hidden" name="act" value="save">';
    
    if ($total_found_global > 0) {
        // LINE 363 - Made translatable
        $msg_box = '<div class="alert alert-info">' . __('Total found') . ' <b>'.$total_found_global.'</b> ' . __('books from all servers.') . '</div>';
        $results_html .= '<div class="p-3 bg-light sticky-top shadow-sm mb-3" style="z-index:99; border-bottom:1px solid #ccc; top:0;">
                            <button type="submit" class="s-btn btn btn-success btn-lg shadow">
                                <i class="fa fa-save"></i> ' . __('Save selected data') . '
                            </button>
                          </div>';
    } else {
        // LINE 370 - Made translatable
        $msg_box = '<div class="alert alert-warning mt-3">' . __('No books found on any servers.') . '</div>';
    }

    $results_html .= $html_found; // Tampilkan yang ada isinya DULUAN
    
    // Tampilkan pemisah jika ada hasil kosong
    if (!empty($html_found) && !empty($html_empty)) {
        // LINE 377 - Made translatable
        $results_html .= '<div class="text-center mt-4 mb-2"><span class="badge badge-secondary p-2">' . __('Server without results') . '</span></div>';
    }
    
    $results_html .= $html_empty; // Tampilkan yang kosong BELAKANGAN
    $results_html .= '</form>';
}
?>

<style>
    .card { border: 1px solid #ccc; border-radius: 4px; overflow: hidden; }
    .card-header { padding: 8px 15px; font-weight: bold; }
    .bg-info { background-color: #17a2b8 !important; }
    .bg-secondary { background-color: #e2e3e5 !important; color: #383d41 !important; border-color: #d6d8db; }
    .border-info { border-color: #17a2b8 !important; }
    .border-secondary { border-color: #e2e3e5 !important; }
    .text-white { color: #fff !important; }
    .text-white-50 { color: rgba(255,255,255,0.7) !important; }
</style>

<div class="menuBox">
    <div class="menuBoxInner biblioIcon">
        <!-- LINE 393 - Made translatable -->
        <div class="per_title"><h2><?php echo __('SLiMS Copy (Multi-Search)'); ?></h2></div>
        
        <?php echo $msg_box; ?>

        <div class="sub_section">
            <form action="<?php echo $current_action_url; ?>" method="post" class="form-inline">
                <input type="hidden" name="act" value="search">
                <!-- LINE 399 - Made translatable -->
                <label class="mr-2"><?php echo __('Keywords:'); ?></label>
                <input type="text" name="keywords" class="form-control" style="width:300px;" value="<?= isset($_POST['keywords'])?$_POST['keywords']:'' ?>" required placeholder="<?php echo __('Type the title of the book...'); ?>">
                <!-- LINE 401 - Made translatable -->
                <button type="submit" class="btn btn-primary ml-2"><?php echo __('Search all servers'); ?></button>
            </form>
        </div>
        
        <div class="mt-3">
            <?php echo $results_html; ?>
        </div>
    </div>
</div>