jQuery(document).ready(function($) {
    const currentDate = '2025-05-21 08:51:06'; // UTC zaman bilgisi
    const currentUserLogin = 'gezerronurr';

    // Kategori ekleme/düzenleme formu submit
    $('#bkm-kategori-form').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', 'save_kategori');
        formData.append('nonce', bkm_admin.nonce);

        $.ajax({
            url: bkm_admin.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                showLoader();
                disableForm();
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', 'Kategori başarıyla kaydedildi');
                    resetForm();
                    loadKategoriler();
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Bir hata oluştu');
            },
            complete: function() {
                hideLoader();
                enableForm();
            }
        });
    });

    // Kategori silme
    $(document).on('click', '.kategori-sil-btn', function() {
        const kategoriId = $(this).data('id');
        const kategoriAdi = $(this).data('name');

        if (confirm(`"${kategoriAdi}" kategorisini silmek istediğinize emin misiniz?`)) {
            deleteKategori(kategoriId);
        }
    });

    // Kategori düzenleme
    $(document).on('click', '.kategori-duzenle-btn', function() {
        const kategoriId = $(this).data('id');
        const kategoriAdi = $(this).closest('tr').find('td:first').text();
        
        $('#kategori_id').val(kategoriId);
        $('#kategori_adi').val(kategoriAdi);
        $('#kategori_submit').text('Güncelle');
        $('#kategori_form_title').text('Kategori Düzenle');
    });

    // Kategori silme işlemi
    function deleteKategori(kategoriId) {
        $.ajax({
            url: bkm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_kategori',
                nonce: bkm_admin.nonce,
                kategori_id: kategoriId
            },
            beforeSend: function() {
                showLoader();
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', 'Kategori başarıyla silindi');
                    loadKategoriler();
                } else {
                    showNotification('error', response.data.message || 'Bu kategori kullanımda olduğu için silinemiyor');
                }
            },
            error: function() {
                showNotification('error', 'Silme işlemi sırasında bir hata oluştu');
            },
            complete: function() {
                hideLoader();
            }
        });
    }

    // Kategorileri yükle
    function loadKategoriler() {
        $.ajax({
            url: bkm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'load_kategoriler',
                nonce: bkm_admin.nonce
            },
            beforeSend: function() {
                showLoader();
            },
            success: function(response) {
                if (response.success) {
                    $('#kategoriler-table tbody').html(response.data.html);
                    updateKategoriCount();
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Kategoriler yüklenirken bir hata oluştu');
            },
            complete: function() {
                hideLoader();
            }
        });
    }

    // Form sıfırlama
    function resetForm() {
        $('#bkm-kategori-form')[0].reset();
        $('#kategori_id').val('');
        $('#kategori_submit').text('Ekle');
        $('#kategori_form_title').text('Yeni Kategori Ekle');
    }

    // Form devre dışı bırakma
    function disableForm() {
        $('#bkm-kategori-form').find('input, button').prop('disabled', true);
    }

    // Form etkinleştirme
    function enableForm() {
        $('#bkm-kategori-form').find('input, button').prop('disabled', false);
    }

    // Kategori sayısını güncelle
    function updateKategoriCount() {
        const count = $('#kategoriler-table tbody tr').length;
        $('.kategori-count').text(count);
    }

    // Yükleniyor göstergesi
    function showLoader() {
        $('.bkm-loader').fadeIn(200);
    }

    function hideLoader() {
        $('.bkm-loader').fadeOut(200);
    }

    // Bildirim gösterici
    function showNotification(type, message) {
        const notification = $('<div>')
            .addClass(`bkm-notification ${type}`)
            .html(`<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>${message}`);

        $('body').append(notification);
        setTimeout(() => {
            notification.addClass('show');
        }, 100);

        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // Form iptal butonu
    $('#kategori_cancel').on('click', function() {
        resetForm();
    });
});