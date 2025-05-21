jQuery(document).ready(function($) {
    const currentDate = '2025-05-21 08:19:27'; // UTC zaman bilgisi
    const currentUserLogin = 'gezerronurr';

    // Select2 başlatma
    $('.select2').select2({
        width: '100%',
        placeholder: 'Seçiniz...',
        allowClear: true,
        language: {
            noResults: function() {
                return 'Sonuç bulunamadı';
            }
        }
    });

    // DatePicker başlatma
    $('.datepicker').flatpickr({
        dateFormat: "Y-m-d",
        locale: "tr",
        allowInput: true,
        minDate: "today",
        defaultDate: "today"
    });

    // İlerleme çubuğu kontrolü
    $('#ilerleme_durumu').on('input', function() {
        const value = $(this).val();
        $(this).closest('.progress-input-container')
            .find('.progress-bar')
            .css('width', value + '%');
        $(this).closest('.progress-input-container')
            .find('.progress-value')
            .text(value + '%');
    }).trigger('input');

    // İlerleme çubuğu değeri değiştiğinde sunucuya gönderme
    $('#ilerleme_durumu').on('change', function() {
        const value = $(this).val();
        const aksiyonId = $(this).closest('form').data('id');
        
        if (!aksiyonId) return; // Yeni kayıtlarda gönderme yapma

        $.ajax({
            url: bkm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'update_aksiyon_ilerleme',
                nonce: bkm_ajax.nonce,
                aksiyon_id: aksiyonId,
                ilerleme_durumu: value
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', 'İlerleme durumu güncellendi');
                    updateAksiyonRow(aksiyonId);
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Bir hata oluştu');
            }
        });
    });

    // Aksiyon satırını güncelle
    function updateAksiyonRow(aksiyonId) {
        $.ajax({
            url: bkm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_aksiyon_row',
                nonce: bkm_ajax.nonce,
                aksiyon_id: aksiyonId
            },
            success: function(response) {
                if (response.success) {
                    $(`tr[data-id="${aksiyonId}"]`).replaceWith(response.data.html);
                }
            }
        });
    }

    // Aksiyon detay modalını aç
    $(document).on('click', '.aksiyon-detay-btn', function(e) {
        e.preventDefault();
        const aksiyonId = $(this).data('id');
        loadAksiyonDetay(aksiyonId);
    });

    // Aksiyon silme
    $(document).on('click', '.aksiyon-sil-btn', function(e) {
        e.preventDefault();
        const aksiyonId = $(this).data('id');
        const aksiyonAdi = $(this).data('name');

        if (confirm(`"${aksiyonAdi}" aksiyonunu silmek istediğinize emin misiniz?`)) {
            deleteAksiyon(aksiyonId);
        }
    });

    // Aksiyon silme işlemi
    function deleteAksiyon(aksiyonId) {
        $.ajax({
            url: bkm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_aksiyon',
                nonce: bkm_ajax.nonce,
                aksiyon_id: aksiyonId
            },
            beforeSend: function() {
                showLoader();
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', 'Aksiyon başarıyla silindi');
                    $(`tr[data-id="${aksiyonId}"]`).fadeOut(300, function() {
                        $(this).remove();
                        updateAksiyonCount();
                    });
                } else {
                    showNotification('error', response.data.message);
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

    // Aksiyon sayısını güncelle
    function updateAksiyonCount() {
        const count = $('#aksiyonlar-table tbody tr').length;
        $('.aksiyon-count').text(count);
        
        if (count === 0) {
            $('#aksiyonlar-table tbody').append(
                '<tr><td colspan="10" class="text-center">Kayıt bulunamadı</td></tr>'
            );
        }
    }

    // Modal kapat
    $('.bkm-modal-close, .bkm-modal-cancel').on('click', function() {
        $(this).closest('.bkm-modal').fadeOut(200);
    });

    // Modal dışına tıklanınca kapat
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('bkm-modal')) {
            $('.bkm-modal').fadeOut(200);
        }
    });

    // Filtre formu
    $('#bkm-filter-form').on('submit', function(e) {
        e.preventDefault();
        loadAksiyonlar();
    });

    // Filtre temizle
    $('#bkm-filter-form button[type="reset"]').on('click', function() {
        $('#bkm-filter-form select').val('').trigger('change');
        setTimeout(loadAksiyonlar, 100);
    });

    // Aksiyonları yükle
    function loadAksiyonlar(page = 1) {
        const filters = {
            kategori: $('#filter_kategori').val(),
            durum: $('#filter_durum').val(),
            hafta: $('#filter_hafta').val(),
            page: page
        };

        $.ajax({
            url: bkm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'load_aksiyonlar',
                nonce: bkm_ajax.nonce,
                filters: filters
            },
            beforeSend: function() {
                showLoader();
            },
            success: function(response) {
                if (response.success) {
                    $('#aksiyonlar-table tbody').html(response.data.html);
                    updatePagination(response.data.pagination);
                    updateAksiyonCount();
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Veriler yüklenirken bir hata oluştu');
            },
            complete: function() {
                hideLoader();
            }
        });
    }

    // Sayfalama güncelle
    function updatePagination(data) {
        if (!data) return;

        const pagination = $('.bkm-pagination');
        pagination.empty();

        if (data.total_pages > 1) {
            let html = '<ul>';
            
            // Önceki sayfa
            if (data.current_page > 1) {
                html += `<li><a href="#" data-page="${data.current_page - 1}">&laquo;</a></li>`;
            }

            // Sayfa numaraları
            for (let i = 1; i <= data.total_pages; i++) {
                if (
                    i === 1 || 
                    i === data.total_pages || 
                    (i >= data.current_page - 2 && i <= data.current_page + 2)
                ) {
                    html += `<li class="${i === data.current_page ? 'active' : ''}">
                                <a href="#" data-page="${i}">${i}</a>
                            </li>`;
                } else if (
                    i === data.current_page - 3 || 
                    i === data.current_page + 3
                ) {
                    html += '<li>...</li>';
                }
            }

            // Sonraki sayfa
            if (data.current_page < data.total_pages) {
                html += `<li><a href="#" data-page="${data.current_page + 1}">&raquo;</a></li>`;
            }

            html += '</ul>';
            pagination.html(html);
        }
    }

    // Sayfalama tıklama
    $(document).on('click', '.bkm-pagination a', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        loadAksiyonlar(page);
        $('html, body').animate({ scrollTop: 0 }, 300);
    });

    // Aksiyon detayı yükle
    function loadAksiyonDetay(aksiyonId) {
        $.ajax({
            url: bkm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'load_aksiyon_detay',
                nonce: bkm_ajax.nonce,
                aksiyon_id: aksiyonId
            },
            beforeSend: function() {
                $('#aksiyon-detay-modal .bkm-modal-body').html(
                    '<div class="bkm-loader"><i class="fas fa-spinner fa-spin"></i></div>'
                );
                $('#aksiyon-detay-modal').fadeIn(200);
            },
            success: function(response) {
                if (response.success) {
                    $('#aksiyon-detay-modal .bkm-modal-body').html(response.data.html);
                    initModalComponents();
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Detaylar yüklenirken bir hata oluştu');
            }
        });
    }

    // Modal bileşenlerini başlat
    function initModalComponents() {
        // Modal içindeki Select2
        $('#aksiyon-detay-modal .select2').select2({
            dropdownParent: $('#aksiyon-detay-modal')
        });

        // Modal içindeki DatePicker
        $('#aksiyon-detay-modal .datepicker').flatpickr({
            dateFormat: "Y-m-d",
            locale: "tr",
            allowInput: true
        });
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

    // Yükleme göstergesi
    function showLoader() {
        $('.bkm-loader').fadeIn(200);
    }

    function hideLoader() {
        $('.bkm-loader').fadeOut(200);
    }

    // Dışa aktarma
    $('.bkm-export-btn').on('click', function(e) {
        e.preventDefault();
        const format = $(this).data('format');
        exportAksiyonlar(format);
    });

    // Dışa aktarma işlemi
    function exportAksiyonlar(format) {
        const filters = {
            kategori: $('#filter_kategori').val(),
            durum: $('#filter_durum').val(),
            hafta: $('#filter_hafta').val()
        };

        $.ajax({
            url: bkm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'export_aksiyonlar',
                nonce: bkm_ajax.nonce,
                format: format,
                filters: filters
            },
            beforeSend: function() {
                showLoader();
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.download_url;
                    showNotification('success', 'Dışa aktarma başarılı');
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Dışa aktarma sırasında bir hata oluştu');
            },
            complete: function() {
                hideLoader();
            }
        });
    }

    // Otomatik yenileme
    let autoRefreshInterval;
    const AUTO_REFRESH_DELAY = 300000; // 5 dakika

    function setupAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }

        autoRefreshInterval = setInterval(function() {
            loadAksiyonlar($('.bkm-pagination .active a').data('page') || 1);
        }, AUTO_REFRESH_DELAY);
    }

    // Sayfa yüklendiğinde otomatik yenilemeyi başlat
    setupAutoRefresh();

    // Kullanıcı etkileşiminde süreyi sıfırla
    $(document).on('click keypress', function() {
        setupAutoRefresh();
    });
});