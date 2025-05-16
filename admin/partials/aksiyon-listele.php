<?php
// Direkt erişimi engelle
if (!defined('WPINC')) {
    die;
}

// Mevcut kullanıcı bilgisini al
$current_user = wp_get_current_user();
?>

<div class="wrap bkm-aksiyon-wrap">
    <div class="bkm-header">
        <h1 class="wp-heading-inline">BKM Aksiyon Takip</h1>
        <a href="<?php echo admin_url('admin.php?page=bkm-aksiyon-ekle'); ?>" class="page-title-action">Yeni Aksiyon Ekle</a>
        <hr class="wp-header-end">
    </div>

    <!-- Özet Kartları -->
    <div class="bkm-dashboard-summary">
        <div class="summary-card">
            <div class="card-icon pending">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="card-content">
                <h3>Bekleyen Aksiyonlar</h3>
                <?php
                global $wpdb;
                $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkm_aksiyonlar WHERE kapanma_tarihi IS NULL");
                ?>
                <span class="count"><?php echo $pending_count ?? '0'; ?></span>
            </div>
        </div>

        <div class="summary-card">
            <div class="card-icon completed">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="card-content">
                <h3>Tamamlanan Aksiyonlar</h3>
                <?php
                $completed_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkm_aksiyonlar WHERE kapanma_tarihi IS NOT NULL");
                ?>
                <span class="count"><?php echo $completed_count ?? '0'; ?></span>
            </div>
        </div>

        <div class="summary-card">
            <div class="card-icon urgent">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="card-content">
                <h3>Acil Aksiyonlar</h3>
                <?php
                $urgent_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkm_aksiyonlar WHERE onem_derecesi = 1 AND kapanma_tarihi IS NULL");
                ?>
                <span class="count"><?php echo $urgent_count ?? '0'; ?></span>
            </div>
        </div>

        <div class="summary-card">
            <div class="card-icon my-tasks">
                <span class="dashicons dashicons-businessman"></span>
            </div>
            <div class="card-content">
                <h3>Benim Aksiyonlarım</h3>
                <?php
                $my_tasks = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}bkm_aksiyonlar WHERE FIND_IN_SET(%d, sorumlular) AND kapanma_tarihi IS NULL",
                    $current_user->ID
                ));
                ?>
                <span class="count"><?php echo $my_tasks ?? '0'; ?></span>
            </div>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="bkm-filters">
        <div class="filter-group">
            <select id="kategori-filter">
                <option value="">Tüm Kategoriler</option>
                <?php
                $kategoriler = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkm_kategoriler ORDER BY kategori_adi ASC");
                foreach ($kategoriler as $kategori) {
                    echo '<option value="' . esc_attr($kategori->id) . '">' . esc_html($kategori->kategori_adi) . '</option>';
                }
                ?>
            </select>

            <select id="onem-filter">
                <option value="">Tüm Önem Dereceleri</option>
                <option value="1">Yüksek</option>
                <option value="2">Orta</option>
                <option value="3">Düşük</option>
            </select>

            <select id="durum-filter">
                <option value="">Tüm Durumlar</option>
                <option value="aktif">Aktif</option>
                <option value="tamamlandi">Tamamlandı</option>
            </select>
        </div>
    </div>

    <!-- Ana Tablo -->
    <div class="card mt-4">
        <div class="card-body">
            <table id="aksiyonlar-table" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="10%">Tanımlayan</th>
                        <th width="8%">Önem</th>
                        <th width="10%">Açılma Tarihi</th>
                        <th width="12%">Kategori</th>
                        <th width="15%">Sorumlular</th>
                        <th width="10%">Hedef Tarih</th>
                        <th width="10%">İlerleme</th>
                        <th width="10%">Durum</th>
                        <th width="10%">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $aksiyonlar = $wpdb->get_results("
                        SELECT a.*, k.kategori_adi 
                        FROM {$wpdb->prefix}bkm_aksiyonlar a
                        LEFT JOIN {$wpdb->prefix}bkm_kategoriler k ON a.kategori_id = k.id
                        ORDER BY a.id DESC
                    ");

                    foreach ($aksiyonlar as $aksiyon) {
                        // Önem derecesi sınıfı
                        $onem_class = '';
                        $onem_text = '';
                        switch ($aksiyon->onem_derecesi) {
                            case 1:
                                $onem_class = 'badge-danger';
                                $onem_text = 'Yüksek';
                                break;
                            case 2:
                                $onem_class = 'badge-warning';
                                $onem_text = 'Orta';
                                break;
                            case 3:
                                $onem_class = 'badge-info';
                                $onem_text = 'Düşük';
                                break;
                        }

                        // Sorumlular listesi
                        $sorumlular_array = explode(',', $aksiyon->sorumlular);
                        $sorumlular_html = '';
                        foreach ($sorumlular_array as $sorumlu_id) {
                            $user_data = get_userdata($sorumlu_id);
                            if ($user_data) {
                                $sorumlular_html .= '<span class="user-badge">' . esc_html($user_data->display_name) . '</span>';
                            }
                        }

                        echo '<tr>';
                        echo '<td>' . esc_html($aksiyon->id) . '</td>';
                        echo '<td>' . esc_html(get_userdata($aksiyon->tanimlayan_id)->display_name) . '</td>';
                        echo '<td><span class="badge ' . $onem_class . '">' . $onem_text . '</span></td>';
                        echo '<td>' . esc_html(date('d.m.Y', strtotime($aksiyon->acilma_tarihi))) . '</td>';
                        echo '<td>' . esc_html($aksiyon->kategori_adi) . '</td>';
                        echo '<td>' . $sorumlular_html . '</td>';
                        echo '<td>' . esc_html(date('d.m.Y', strtotime($aksiyon->hedef_tarih))) . '</td>';
                        echo '<td>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: ' . esc_attr($aksiyon->ilerleme_durumu) . '%" 
                                         aria-valuenow="' . esc_attr($aksiyon->ilerleme_durumu) . '" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">' . 
                                    esc_html($aksiyon->ilerleme_durumu) . '%</div>
                                </div>
                              </td>';
                        
                        $durum_class = $aksiyon->kapanma_tarihi ? 'badge-success' : 'badge-primary';
                        $durum_text = $aksiyon->kapanma_tarihi ? 'Tamamlandı' : 'Devam Ediyor';
                        echo '<td><span class="badge ' . $durum_class . '">' . $durum_text . '</span></td>';
                        
                        echo '<td>
                                <div class="btn-group">
                                    <a href="' . admin_url('admin.php?page=bkm-aksiyon-ekle&action=edit&id=' . $aksiyon->id) . '" 
                                       class="button button-small">
                                       <span class="dashicons dashicons-edit"></span>
                                    </a>
                                    <button type="button" 
                                            class="button button-small delete-aksiyon" 
                                            data-id="' . $aksiyon->id . '">
                                            <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                              </td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>