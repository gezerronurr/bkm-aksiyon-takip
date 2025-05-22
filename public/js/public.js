jQuery(document).ready(function($) {
    const currentDate = '2025-05-21 08:41:43'; // UTC zaman bilgisi
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
    function initializeProgressBar() {
        const progressSlider = $('.progress-slider');
        
        progressSlider.each(function() {
            const slider = $(this);
            const container = slider.closest('.progress-input-container');
            const progressBar = container.find('.progress-bar');
            const progressValue = container.find('.progress-value');

            slider.on('input change', function() {
                const value = $(this).val();
                progressBar.css('width', value + '%');
                progressValue.text(value + '%');

                // İlerleme 100% olduğunda kapanma tarihini otomatik ayarla
                if (parseInt(value) === 100) {
                    $('#kapanma_tarihi').val(currentDate.split(' ')[0]).trigger('change');
                    showNotification('success', 'Aksiyon tamamlandı, kapanma tarihi otomatik ayarlandı');
                }
            });

            // Başlangıç değerini ayarla
            const initialValue = slider.val();
            progressBar.css('width', initialValue + '%');
            progressValue.text(initialValue + '%');
        });
    }

    // İlerleme çubuğunu başlat
    initializeProgressBar();

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

        // Modal içindeki ilerleme çubuğu
        initializeProgressBar();
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

    // Aksiyon durumunu güncelle
    function updateAksiyonStatus(aksiyonId, status) {
        $.ajax({
            url: bkm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'update_aksiyon_status',
                nonce: bkm_ajax.nonce,
                aksiyon_id: aksiyonId,
                status: status
            },
            beforeSend: function() {
                showLoader();
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', 'Aksiyon durumu güncellendi');
                    updateAksiyonRow(aksiyonId);
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Durum güncellenirken bir hata oluştu');
            },
            complete: function() {
                hideLoader();
            }
        });
    }

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
                    initializeRowComponents($(`tr[data-id="${aksiyonId}"]`));
                }
            }
        });
    }

    // Satır bileşenlerini başlat
    function initializeRowComponents(row) {
        row.find('.select2').select2();
        row.find('.datepicker').flatpickr({
            dateFormat: "Y-m-d",
            locale: "tr",
            allowInput: true
        });
        row.find('.progress-slider').each(function() {
            initializeProgressBar($(this));
        });
    }

    // Tüm bileşenleri yeniden başlat
    function reinitializeComponents() {
        // Select2 yeniden başlat
        $('.select2').select2({
            width: '100%',
            placeholder: 'Seçiniz...',
            allowClear: true
        });

        // DatePicker yeniden başlat
        $('.datepicker').flatpickr({
            dateFormat: "Y-m-d",
            locale: "tr",
            allowInput: true
        });

        // İlerleme çubuğu kontrolü başlat
        initializeProgressBar();
    }

    // GÖREV İŞLEMLERİ
    
    // Görev ekle butonuna tıklama
    $(document).on('click', '.gorev-ekle-btn', function(e) {
        e.preventDefault();
        const aksiyonId = $(this).data('aksiyon-id');
        
        $('#gorev-ekle-form').trigger('reset');
        $('#gorev_id').val('');
        $('#aksiyon_id').val(aksiyonId);
        $('#gorev-ekle-modal').fadeIn(200);
        
        // Form elemanlarını başlat
        setTimeout(function() {
            $('.select2').trigger('change');
            reinitializeComponents();
        }, 200);
    });
    
    // Görev ekleme formunu gönderme
    $('#gorev-ekle-form').on('submit', function(e) {
        e.preventDefault();
        
        // Form validasyonu
        if (!validateGorevForm()) {
            return false;
        }
        
        const formData = new FormData(this);
        formData.append('action', 'save_gorev');
        
        $.ajax({
            url: bkm_ajax_takipx.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                showLoader();
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message);
                    $('#gorev-ekle-modal').fadeOut(200);
                    
                    // Eğer görevler modali açıksa, görevleri yeniden yükle
                    if ($('#gorevler-modal').is(':visible')) {
                        loadGorevler($('#aksiyon_id').val());
                    }
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Görev kaydedilirken bir hata oluştu');
            },
            complete: function() {
                hideLoader();
            }
        });
    });
    
    // Görev formunu doğrula
    function validateGorevForm() {
        let isValid = true;
        const requiredFields = $('#gorev-ekle-form').find('[required]');
        
        requiredFields.each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('error');
                
                // Select2 için özel işlem
                if ($(this).hasClass('select2')) {
                    $(this).next('.select2-container').addClass('error');
                }
            } else {
                $(this).removeClass('error');
                
                // Select2 için özel işlem
                if ($(this).hasClass('select2')) {
                    $(this).next('.select2-container').removeClass('error');
                }
            }
        });
        
        // Tarih validasyonları
        const baslangicTarihi = new Date($('#baslangic_tarihi').val());
        const hedefBitisTarihi = new Date($('#hedef_bitis_tarihi').val());
        
        if (hedefBitisTarihi < baslangicTarihi) {
            isValid = false;
            $('#hedef_bitis_tarihi').addClass('error');
            showNotification('error', 'Hedef bitiş tarihi, başlangıç tarihinden önce olamaz');
        }
        
        return isValid;
    }
    
    // Görevleri yükle
    function loadGorevler(aksiyonId) {
        $.ajax({
            url: bkm_ajax_takipx.ajax_url,
            type: 'POST',
            data: {
                action: 'load_gorevler',
                nonce: bkm_ajax_takipx.nonce,
                aksiyon_id: aksiyonId
            },
            beforeSend: function() {
                $('#gorevler-modal .bkm-modal-body').html(
                    '<div class="bkm-loader"><i class="fas fa-spinner fa-spin"></i> Yükleniyor...</div>'
                );
                $('#gorevler-modal').fadeIn(200);
                
                // Görev ekle butonuna aksiyon ID'si ekle
                $('#gorevler-modal .gorev-ekle-btn').attr('data-aksiyon-id', aksiyonId);
            },
            success: function(response) {
                if (response.success) {
                    $('#gorevler-modal .bkm-modal-body').html(response.data.html);
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Görevler yüklenirken bir hata oluştu');
            }
        });
    }
    
    // Görev göster butonuna tıklama
    $(document).on('click', '.show-gorevler-btn', function(e) {
        e.preventDefault();
        const aksiyonId = $(this).data('aksiyon-id');
        loadGorevler(aksiyonId);
    });
    
    // Görev düzenle butonuna tıklama
    $(document).on('click', '.edit-gorev-btn', function(e) {
        e.preventDefault();
        const gorevId = $(this).data('id');
        
        $.ajax({
            url: bkm_ajax_takipx.ajax_url,
            type: 'POST',
            data: {
                action: 'load_gorev_detay',
                nonce: bkm_ajax_takipx.nonce,
                gorev_id: gorevId
            },
            beforeSend: function() {
                showLoader();
            },
            success: function(response) {
                if (response.success) {
                    const gorev = response.data;
                    
                    // Form verilerini doldur
                    $('#gorev_id').val(gorev.id);
                    $('#aksiyon_id').val(gorev.aksiyon_id);
                    $('#gorev_icerik').val(gorev.icerik);
                    $('#baslangic_tarihi').val(gorev.baslangic_tarihi.split(' ')[0]);
                    $('#sorumlu_id').val(gorev.sorumlu_id).trigger('change');
                    $('#hedef_bitis_tarihi').val(gorev.hedef_bitis_tarihi.split(' ')[0]);
                    $('#gorev_ilerleme_durumu').val(gorev.ilerleme_durumu).trigger('change');
                    
                    // Modalı göster
                    $('#gorev-ekle-modal').fadeIn(200);
                    
                    // Form elemanlarını başlat
                    setTimeout(function() {
                        reinitializeComponents();
                    }, 200);
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Görev detayı yüklenirken bir hata oluştu');
            },
            complete: function() {
                hideLoader();
            }
        });
    });
    
    // Görev tamamla butonuna tıklama
    $(document).on('click', '.complete-gorev-btn', function(e) {
        e.preventDefault();
        const gorevId = $(this).data('id');
        
        if (confirm(bkm_ajax_takipx.strings.confirm_gorev_complete)) {
            completeGorev(gorevId);
        }
    });
    
    // Görevi tamamla
    function completeGorev(gorevId) {
        $.ajax({
            url: bkm_ajax_takipx.ajax_url,
            type: 'POST',
            data: {
                action: 'complete_gorev',
                nonce: bkm_ajax_takipx.nonce,
                gorev_id: gorevId
            },
            beforeSend: function() {
                showLoader();
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message);
                    
                    // Görevler listesini yenile
                    const aksiyonId = $('#gorevler-modal .gorev-ekle-btn').data('aksiyon-id');
                    loadGorevler(aksiyonId);
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Görev tamamlanırken bir hata oluştu');
            },
            complete: function() {
                hideLoader();
            }
        });
    }
    
    // Görev sil butonuna tıklama
    $(document).on('click', '.delete-gorev-btn', function(e) {
        e.preventDefault();
        const gorevId = $(this).data('id');
        
        if (confirm(bkm_ajax_takipx.strings.confirm_gorev_delete)) {
            deleteGorev(gorevId);
        }
    });
    
    // Görevi sil
    function deleteGorev(gorevId) {
        $.ajax({
            url: bkm_ajax_takipx.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_gorev',
                nonce: bkm_ajax_takipx.nonce,
                gorev_id: gorevId
            },
            beforeSend: function() {
                showLoader();
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message);
                    
                    // Görevler listesini yenile
                    const aksiyonId = $('#gorevler-modal .gorev-ekle-btn').data('aksiyon-id');
                    loadGorevler(aksiyonId);
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'Görev silinirken bir hata oluştu');
            },
            complete: function() {
                hideLoader();
            }
        });
    }
});