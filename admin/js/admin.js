jQuery(document).ready(function($) {
    const currentDate = '2025-05-21 08:10:37'; // UTC zaman bilgisi
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

    // Önem derecesi seçimi değiştiğinde görsel güncelleme
    $('#onem_derecesi').on('change', function() {
        const value = $(this).val();
        const badge = $(this).closest('.form-group').find('.onem-badge');
        
        if (badge.length === 0) {
            $(this).after('<span class="onem-badge"></span>');
        }
        
        const newBadge = $(this).closest('.form-group').find('.onem-badge');
        newBadge.removeClass('high medium low').empty();
        
        switch(value) {
            case '1':
                newBadge.addClass('high').html('<i class="fas fa-exclamation-circle"></i> Yüksek');
                break;
            case '2':
                newBadge.addClass('medium').html('<i class="fas fa-exclamation"></i> Orta');
                break;
            case '3':
                newBadge.addClass('low').html('<i class="fas fa-info-circle"></i> Düşük');
                break;
        }
    });

    // Form gönderimi
    $('#bkm-aksiyon-form').on('submit', function(e) {
        e.preventDefault();

        // Form validasyonu
        if (!validateForm()) {
            return false;
        }

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
                    setTimeout(() => {
                        window.location.href = response.data.redirect_url;
                    }, 1500);
                } else {
                    showNotification('error', response.data.message);
                    logError('save_aksiyon', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                showNotification('error', 'Bir hata oluştu: ' + error);
                logError('save_aksiyon', error);
            },
            complete: function() {
                hideLoader();
                enableForm();
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

    // İlerleme durumu değiştiğinde kapanma tarihini otomatik ayarla
    $('#ilerleme_durumu').on('change', function() {
        const value = parseInt($(this).val());
        if (value === 100) {
            $('#kapanma_tarihi').val(currentDate.split(' ')[0]);
            showNotification('success', 'Aksiyon tamamlandı, kapanma tarihi otomatik ayarlandı');
        }
    });

    // Form temizleme
    $('.bkm-btn[type="reset"]').on('click', function() {
        $('.select2').val(null).trigger('change');
        $('.onem-badge').remove();
        $('.field-error').remove();
        $('.form-group').removeClass('has-error');
        $('#ilerleme_durumu').val(0).trigger('input');
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

    // Sayfa kapatılmadan önce uyarı
    $(window).on('beforeunload', function() {
        const form = $('#bkm-aksiyon-form');
        if (form.length && form.serialize() !== form.data('original-state')) {
            return 'Kaydedilmemiş değişiklikler var. Sayfadan ayrılmak istediğinizden emin misiniz?';
        }
    });

    // Form başlangıç durumunu kaydet
    $('#bkm-aksiyon-form').data('original-state', $('#bkm-aksiyon-form').serialize());

    // Modal işlemleri
    $('.bkm-modal-trigger').on('click', function(e) {
        e.preventDefault();
        const modalId = $(this).data('modal');
        $(`#${modalId}`).fadeIn(200);
    });

    $('.bkm-modal-close, .bkm-modal-cancel').on('click', function() {
        $(this).closest('.bkm-modal').fadeOut(200);
    });

    $(window).on('click', function(e) {
        if ($(e.target).hasClass('bkm-modal')) {
            $('.bkm-modal').fadeOut(200);
        }
    });

    // Kullanıcı tercihleri
    function saveUserPreferences() {
        const preferences = {
            defaultCategory: $('#kategori_id').val(),
            defaultImportance: $('#onem_derecesi').val(),
            autoSave: true
        };

        $.ajax({
            url: bkm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'save_user_preferences',
                nonce: bkm_admin.nonce,
                preferences: preferences
            }
        });
    }

    // Kullanıcı tercihlerini yükle
    function loadUserPreferences() {
        $.ajax({
            url: bkm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'load_user_preferences',
                nonce: bkm_admin.nonce
            },
            success: function(response) {
                if (response.success && response.data.preferences) {
                    const prefs = response.data.preferences;
                    if (prefs.defaultCategory) {
                        $('#kategori_id').val(prefs.defaultCategory).trigger('change');
                    }
                    if (prefs.defaultImportance) {
                        $('#onem_derecesi').val(prefs.defaultImportance).trigger('change');
                    }
                }
            }
        });
    }

    // Sayfa yüklendiğinde kullanıcı tercihlerini yükle
    loadUserPreferences();

    // Form alanlarında değişiklik olduğunda tercihleri kaydet
    $('#kategori_id, #onem_derecesi').on('change', function() {
        saveUserPreferences();
    });

    // Performans değişikliği izleme
    $('#performans_id').on('change', function() {
        const selectedPerformans = $(this).val();
        if (selectedPerformans) {
            logAction('performans_change', selectedPerformans);
        }
    });
});