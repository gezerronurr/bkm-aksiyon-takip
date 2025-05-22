<?php
// Bu template, JS tarafında aksiyonlar yüklendiğinde her bir satırı oluşturmak için kullanılır
?>
<script type="text/template" id="aksiyon-row-template">
    <tr data-id="{{id}}">
        <td>
            <button class="bkm-btn small primary show-gorevler-btn" data-aksiyon-id="{{id}}" title="<?php _e('Görevleri Göster', 'bkm-aksiyon-takip'); ?>">
                <i class="fas fa-tasks"></i>
            </button>
        </td>
        <td>{{id}}</td>
        <td>{{kategori_adi}}</td>
        <td>
            <span class="onem-badge {{onem_derecesi_class}}">
                <i class="fas fa-{{onem_derecesi_icon}}"></i> {{onem_derecesi_text}}
            </span>
        </td>
        <td>{{acilma_tarihi_formatted}}</td>
        <td>{{hafta}}</td>
        <td>{{sorumlular}}</td>
        <td class="{{hedef_gecikme_class}}">{{hedef_tarih_formatted}}</td>
        <td>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: {{ilerleme_durumu}}%"></div>
                <span class="progress-text">{{ilerleme_durumu}}%</span>
            </div>
        </td>
        <td>{{performans_adi}}</td>
        <td class="actions">
            <button class="bkm-btn small primary aksiyon-detay-btn" data-id="{{id}}" title="<?php _e('Detay', 'bkm-aksiyon-takip'); ?>">
                <i class="fas fa-search"></i>
            </button>
            {{#if can_edit}}
            <button class="bkm-btn small aksiyon-duzenle-btn" data-id="{{id}}" title="<?php _e('Düzenle', 'bkm-aksiyon-takip'); ?>">
                <i class="fas fa-edit"></i>
            </button>
            {{/if}}
        </td>
    </tr>
</script> 