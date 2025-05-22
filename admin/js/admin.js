jQuery(document).ready(function($) {
    const currentDate = '2025-05-21 08:35:08'; // UTC zaman bilgisi
    const currentUserLogin = 'gezerronurr';
    let formChanged = false; // Form değişikliği takibi için

    // Form değişiklik izleme
    $('#bkm-aksiyon-form').on('change', 'input, select, textarea', function() {
        formChanged = true;
    });

    // Sayfadan çıkma uyarısı
    $(window).on('beforeunload', function(e) {
        if (formChanged) {
            // Modern tarayıcılar için standart mesaj gösterilir
            // Bu metin tarayıcılar tarafından genellikle göz ardı edilir
            return 'Kaydedilmemiş değişiklikleriniz var. Sayfadan çıkmak istediğinize emin misiniz?';
        }
    });

    // Select2 başlatma - güncellendi
    initializeSelect2();
    
    function initializeSelect2() {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Seçiniz...',
            allowClear: true,
            language: {
                noResults: function() {
                    return 'Sonuç bulunamadı';
                }
            }
        }).on('select2:select', function(e) {
            // Select2 seçim sonrası tetikleyici
            $(this).trigger('change');
        });
    }

    // DatePicker başlatma
    $('.datepicker').flatpickr({
        dateFormat: "Y-m-d",
        locale: "tr",
        allowInput: true,
        minDate: "today",
        defaultDate: "today"
    });

    // Önem derecesi seçimi değiştiğinde görsel güncelleme
    $('#onem_derecesi').on('change', function() {
        const value = $(this).val();
        let badge = $(this).siblings('.onem-badge');
        
        if (badge.length === 0) {
            $(this).after('<span class="onem-badge"></span>');
            badge = $(this).siblings('.onem-badge');
        }
        
        badge.removeClass('high medium low').empty();
        
        if (value) {
            switch(value) {
                case '1':
                    badge.addClass('high').html('<i class="fas fa-exclamation-circle"></i> Yüksek');
                    break;
                case '2':
                    badge.addClass('medium').html('<i class="fas fa-exclamation"></i> Orta');
                    break;
                case '3':
                    badge.addClass('low').html('<i class="fas fa-info-circle"></i> Düşük');
                    break;
            }
        }
    }).trigger('change'); // Sayfa yüklendiğinde mevcut seçimi göster

    // Form gönderimi
    $('#bkm-aksiyon-form').on('submit', function(e) {
        e.preventDefault();

        // Form validasyonu
        if (!validateForm()) {
            return false;
        }

        // Sayfadan çıkma uyarısını kaldır
        formChanged = false;
        $(window).off('beforeunload');

        // Form verilerini topla
        const formData = new FormData(this);
        formData.append('action', 'save_aksiyon');
        formData.append('nonce', bkm_admin.nonce);

        // AJAX isteği
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
                    showNotification('success', 'Aksiyon başarıyla kaydedildi');
                    logAction('create', response.data.aksiyon_id);
                    
                    // Yönlendirme URL'sini kontrol et ve yönlendir
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        // Eğer URL yoksa varsayılan olarak tüm aksiyonlar sayfasına git
                        window.location.href = 'admin.php?page=bkm-aksiyon-takip';
                    }
                } else {
                    showNotification('error', response.data.message);
                    logError('save_aksiyon', response.data.message);
                    // Hata durumunda formu tekrar etkinleştir
                    enableForm();
                    // Hata durumunda uyarıyı tekrar etkinleştir
                    formChanged = true;
                }
            },
            error: function(xhr, status, error) {
                showNotification('error', 'Bir hata oluştu: ' + error);
                logError('save_aksiyon', error);
                // Hata durumunda formu tekrar etkinleştir
                enableForm();
                // Hata durumunda uyarıyı tekrar etkinleştir
                formChanged = true;
            },
            complete: function() {
                hideLoader();
            }
        });
    });

    // Form validasyonu
    function validateForm() {
        let isValid = true;
        const requiredFields = $('#bkm-aksiyon-form').find('[required]');
        
        requiredFields.each(function() {
            const field = $(this);
            const value = field.val();
            
            if (!value || (Array.isArray(value) && !value.length)) {
                isValid = false;
                field.addClass('error');
                showFieldError(field, 'Bu alan zorunludur');
            } else {
                field.removeClass('error');
                removeFieldError(field);
            }
        });

        // Tarih kontrolleri
        const acilmaTarihi = new Date($('#acilma_tarihi').val());
        const hedefTarih = new Date($('#hedef_tarih').val());
        const kapanmaTarihi = $('#kapanma_tarihi').val() ? new Date($('#kapanma_tarihi').val()) : null;

        if (hedefTarih < acilmaTarihi) {
            isValid = false;
            showFieldError($('#hedef_tarih'), 'Hedef tarih, açılma tarihinden önce olamaz');
        }

        if (kapanmaTarihi && kapanmaTarihi < acilmaTarihi) {
            isValid = false;
            showFieldError($('#kapanma_tarihi'), 'Kapanma tarihi, açılma tarihinden önce olamaz');
        }

        // İlerleme durumu kontrolü
        const ilerlemeDurumu = parseInt($('#ilerleme_durumu').val());
        if (ilerlemeDurumu === 100 && !kapanmaTarihi) {
            isValid = false;
            showFieldError($('#kapanma_tarihi'), 'İlerleme %100 ise kapanma tarihi zorunludur');
        }

        return isValid;
    }

    // Alan hatası göster
    function showFieldError(field, message) {
        if (!field.next('.field-error').length) {
            field.after('<div class="field-error">' + message + '</div>');
        }
        field.closest('.form-group').addClass('has-error');
    }

    // Alan hatasını kaldır
    function removeFieldError(field) {
        field.next('.field-error').remove();
        field.closest('.form-group').removeClass('has-error');
    }

    // Formu devre dışı bırak
    function disableForm() {
        $('#bkm-aksiyon-form').find('input, select, textarea, button').prop('disabled', true);
    }

    // Formu etkinleştir
    function enableForm() {
        $('#bkm-aksiyon-form').find('input, select, textarea, button').prop('disabled', false);
    }

    // Yükleme göstergesi
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

    // İşlem logu
    function logAction(action, aksiyonId) {
        $.ajax({
            url: bkm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'log_aksiyon_action',
                nonce: bkm_admin.nonce,
                log_action: action,
                aksiyon_id: aksiyonId,
                user: currentUserLogin,
                timestamp: currentDate
            }
        });
    }

    // Hata logu
    function logError(action, error) {
        $.ajax({
            url: bkm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'log_aksiyon_error',
                nonce: bkm_admin.nonce,
                error_action: action,
                error_message: error,
                user: currentUserLogin,
                timestamp: currentDate
            }
        });
    }

    // İlerleme çubuğu kontrolü - güncellendi
    function initializeProgressBar() {
        const progressSlider = $('#ilerleme_durumu');
        const progressBar = progressSlider.closest('.progress-input-container').find('.progress-bar');
        const progressValue = progressSlider.closest('.progress-input-container').find('.progress-value');

        progressSlider.on('input change', function() {
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
        const initialValue = progressSlider.val();
        progressBar.css('width', initialValue + '%');
        progressValue.text(initialValue + '%');
    }

    // İlerleme çubuğunu başlat
    initializeProgressBar();

    // Hafta numarası otomatik hesaplama
    $('#acilma_tarihi').on('change', function() {
        const date = new Date($(this).val());
        const weekNumber = getWeekNumber(date);
        $('#hafta').val(weekNumber);
    });

    function getWeekNumber(date) {
        const firstDayOfYear = new Date(date.getFullYear(), 0, 1);
        const pastDaysOfYear = (date - firstDayOfYear) / 86400000;
        return Math.ceil((pastDaysOfYear + firstDayOfYear.getDay() + 1) / 7);
    }

    // Form temizleme
    $('.bkm-btn[type="reset"]').on('click', function() {
        $('.select2').val(null).trigger('change');
        $('.onem-badge').remove();
        $('.field-error').remove();
        $('.form-group').removeClass('has-error');
        $('#ilerleme_durumu').val(0).trigger('change');
        showNotification('info', 'Form temizlendi');
    });

    // Otomatik kaydetme
    let autoSaveTimeout;
    const AUTO_SAVE_DELAY = 30000; // 30 saniye

    function setupAutoSave() {
        const formFields = $('#bkm-aksiyon-form').find('input, select, textarea');
        
        formFields.on('change', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(autoSave, AUTO_SAVE_DELAY);
        });
    }

    function autoSave() {
        const formData = new FormData($('#bkm-aksiyon-form')[0]);
        formData.append('action', 'auto_save_aksiyon');
        formData.append('nonce', bkm_admin.nonce);

        $.ajax({
            url: bkm_admin.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification('info', 'Taslak otomatik kaydedildi');
                }
            }
        });
    }

    // Sayfa yüklendiğinde otomatik kaydetmeyi başlat
    setupAutoSave();

    // Form başlangıç durumunu kaydet
    $('#bkm-aksiyon-form').data('original-state', $('#bkm-aksiyon-form').serialize());

    // Sayfa kapatılmadan önce uyarı
    $(window).on('beforeunload', function() {
        const form = $('#bkm-aksiyon-form');
        if (form.length && form.serialize() !== form.data('original-state')) {
            return 'Kaydedilmemiş değişiklikler var. Sayfadan ayrılmak istediğinizden emin misiniz?';
        }
    });

    // Sayfa yüklendiğinde mevcut seçimleri göster
    $('#onem_derecesi, #ilerleme_durumu').trigger('change');
});