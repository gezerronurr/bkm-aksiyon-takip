<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$current_user_id = get_current_user_id();
$current_date = '2025-05-21 06:53:46'; // UTC zaman bilgisi
$current_user_login = 'gezerronurr';

// Yetki kontrolü
if (!current_user_can('edit_posts')) {
    wp_die(__('Bu sayfaya erişim yetkiniz bulunmamaktadır.', 'bkm-aksiyon-takip'));
}

// Filtre parametreleri
$baslangic_tarihi = isset($_GET['baslangic_tarihi']) ? sanitize_text_field($_GET['baslangic_tarihi']) : date('Y-m-d', strtotime('-30 days'));
$bitis_tarihi = isset($_GET['bitis_tarihi']) ? sanitize_text_field($_GET['bitis_tarihi']) : date('Y-m-d');
$kategori_id = isset($_GET['kategori_id']) ? intval($_GET['kategori_id']) : 0;
$performans_id = isset($_GET['performans_id']) ? intval($_GET['performans_id']) : 0;
?>

<div class="wrap">
    <!-- Header -->
    <div class="bkm-header">
        <div class="header-left">
            <h1>Raporlar</h1>
            <p>Aksiyon takip sistemi raporları ve istatistikleri</p>
        </div>
        <div class="header-actions">
            <button type="button" class="bkm-btn btn-success" id="export-excel">
                <i class="fas fa-file-excel"></i> Excel'e Aktar
            </button>
            <button type="button" class="bkm-btn btn-danger" id="export-pdf">
                <i class="fas fa-file-pdf"></i> PDF'e Aktar
            </button>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="form-container">
        <form id="rapor-filter-form" method="get">
            <input type="hidden" name="page" value="bkm-raporlar">
            <div class="form-grid">
                <div class="form-group">
                    <label for="tarih_araligi">Tarih Aralığı</label>
                    <div class="input-group">
                        <input type="text" name="baslangic_tarihi" class="datepicker form-control" value="<?php echo $baslangic_tarihi; ?>">
                        <div class="input-group-append input-group-prepend">
                            <span class="input-group-text">-</span>
                        </div>
                        <input type="text" name="bitis_tarihi" class="datepicker form-control" value="<?php echo $bitis_tarihi; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="kategori_id">Kategori</label>
                    <select name="kategori_id" id="kategori_id" class="select2">
                        <option value="">Tümü</option>
                        <?php
                        $kategoriler = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkm_kategoriler ORDER BY kategori_adi ASC");
                        foreach ($kategoriler as $kategori) {
                            $selected = $kategori_id == $kategori->id ? 'selected' : '';
                            echo '<option value="' . esc_attr($kategori->id) . '" ' . $selected . '>' . 
                                 esc_html($kategori->kategori_adi) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="performans_id">Performans</label>
                    <select name="performans_id" id="performans_id" class="select2">
                        <option value="">Tümü</option>
                        <?php
                        $performanslar = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkm_performanslar ORDER BY performans_adi ASC");
                        foreach ($performanslar as $performans) {
                            $selected = $performans_id == $performans->id ? 'selected' : '';
                            echo '<option value="' . esc_attr($performans->id) . '" ' . $selected . '>' . 
                                 esc_html($performans->performans_adi) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="bkm-btn btn-primary">
                <i class="fas fa-filter"></i> Filtrele
            </button>
            <button type="reset" class="bkm-btn btn-secondary">
                <i class="fas fa-times"></i> Temizle
            </button>
        </form>
    </div>

    <!-- İstatistik Kartları -->
    <div class="stats-container">
        <?php
        // SQL koşulları
        $where_conditions = ["1=1"];
        $where_conditions[] = $wpdb->prepare("a.acilma_tarihi BETWEEN %s AND %s", $baslangic_tarihi, $bitis_tarihi);
        
        if ($kategori_id) {
            $where_conditions[] = $wpdb->prepare("a.kategori_id = %d", $kategori_id);
        }
        if ($performans_id) {
            $where_conditions[] = $wpdb->prepare("a.performans_id = %d", $performans_id);
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        // Toplam aksiyon sayısı
        $total_aksiyonlar = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}bkm_aksiyonlar a 
            WHERE $where_clause
        ");
        
        // Tamamlanan aksiyon sayısı
        $tamamlanan_aksiyonlar = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}bkm_aksiyonlar a 
            WHERE $where_clause AND ilerleme_durumu = 100
        ");
        
        // Ortalama tamamlanma süresi (gün olarak)
        $ortalama_sure = $wpdb->get_var("
            SELECT AVG(DATEDIFF(kapanma_tarihi, acilma_tarihi)) 
            FROM {$wpdb->prefix}bkm_aksiyonlar a 
            WHERE $where_clause AND kapanma_tarihi IS NOT NULL
        ");
        
        // Geciken aksiyon sayısı
        $geciken_aksiyonlar = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}bkm_aksiyonlar a 
            WHERE $where_clause 
            AND hedef_tarih < CURDATE() 
            AND ilerleme_durumu < 100
        ");
        ?>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-tasks"></i></div>
            <div class="stat-value"><?php echo $total_aksiyonlar; ?></div>
            <div class="stat-label">Toplam Aksiyon</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value">%<?php 
                echo $total_aksiyonlar > 0 ? round(($tamamlanan_aksiyonlar / $total_aksiyonlar) * 100) : 0; 
            ?></div>
            <div class="stat-label">Tamamlanma Oranı</div>
            <div class="stat-trend">
                <?php echo $tamamlanan_aksiyonlar; ?> / <?php echo $total_aksiyonlar; ?> Aksiyon
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-value"><?php echo round($ortalama_sure); ?></div>
            <div class="stat-label">Ortalama Tamamlanma Süresi (Gün)</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
            <div class="stat-value"><?php echo $geciken_aksiyonlar; ?></div>
            <div class="stat-label">Geciken Aksiyon</div>
        </div>
    </div>

    <!-- Grafik Alanı -->
    <div class="form-container">
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h3>Kategori Dağılımı</h3>
                    <canvas id="kategoriChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h3>Aylık Aksiyon Trendi</h3>
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detaylı Rapor Tablosu -->
    <div class="form-container">
        <table id="rapor-table" class="bkm-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kategori</th>
                    <th>Tanımlayan</th>
                    <th>Açılma Tarihi</th>
                    <th>Hedef Tarih</th>
                    <th>Kapanma Tarihi</th>
                    <th>Süre (Gün)</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $aksiyonlar = $wpdb->get_results("
                    SELECT a.*, 
                           k.kategori_adi,
                           u.display_name as tanimlayan_adi,
                           DATEDIFF(IF(a.kapanma_tarihi IS NOT NULL, a.kapanma_tarihi, CURDATE()), a.acilma_tarihi) as sure
                    FROM {$wpdb->prefix}bkm_aksiyonlar a
                    LEFT JOIN {$wpdb->prefix}bkm_kategoriler k ON a.kategori_id = k.id
                    LEFT JOIN {$wpdb->users} u ON a.tanimlayan_id = u.ID
                    WHERE $where_clause
                    ORDER BY a.id DESC
                ");

                foreach ($aksiyonlar as $aksiyon):
                    // Durum sınıfı belirleme
                    $durum_class = '';
                    if ($aksiyon->ilerleme_durumu >= 100) {
                        $durum_class = 'status-active';
                        $durum_text = 'Tamamlandı';
                    } elseif ($aksiyon->hedef_tarih < date('Y-m-d')) {
                        $durum_class = 'status-inactive';
                        $durum_text = 'Gecikmiş';
                    } else {
                        $durum_class = 'status-pending';
                        $durum_text = 'Devam Ediyor';
                    }
                    ?>
                    <tr>
                        <td>#<?php echo $aksiyon->id; ?></td>
                        <td><?php echo esc_html($aksiyon->kategori_adi); ?></td>
                        <td><?php echo esc_html($aksiyon->tanimlayan_adi); ?></td>
                        <td><?php echo date('d.m.Y', strtotime($aksiyon->acilma_tarihi)); ?></td>
                        <td><?php echo date('d.m.Y', strtotime($aksiyon->hedef_tarih)); ?></td>
                        <td><?php 
                            echo $aksiyon->kapanma_tarihi ? date('d.m.Y', strtotime($aksiyon->kapanma_tarihi)) : '-';
                        ?></td>
                        <td><?php echo $aksiyon->sure; ?></td>
                        <td>
                            <span class="status-badge <?php echo $durum_class; ?>">
                                <?php echo $durum_text; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Grafik verileri hazırlama
$kategori_data = $wpdb->get_results("
    SELECT k.kategori_adi, COUNT(a.id) as aksiyon_sayisi
    FROM {$wpdb->prefix}bkm_kategoriler k
    LEFT JOIN {$wpdb->prefix}bkm_aksiyonlar a ON k.id = a.kategori_id
    WHERE $where_clause
    GROUP BY k.id
    ORDER BY aksiyon_sayisi DESC
");

$trend_data = $wpdb->get_results("
    SELECT DATE_FORMAT(acilma_tarihi, '%Y-%m') as ay, COUNT(*) as aksiyon_sayisi
    FROM {$wpdb->prefix}bkm_aksiyonlar a
    WHERE $where_clause
    GROUP BY ay
    ORDER BY ay ASC
");
?>

<script>
// Grafik verilerini JavaScript'e aktarma
var kategoriData = {
    labels: <?php echo json_encode(array_column($kategori_data, 'kategori_adi')); ?>,
    data: <?php echo json_encode(array_column($kategori_data, 'aksiyon_sayisi')); ?>
};

var trendData = {
    labels: <?php echo json_encode(array_column($trend_data, 'ay')); ?>,
    data: <?php echo json_encode(array_column($trend_data, 'aksiyon_sayisi')); ?>
};
</script>