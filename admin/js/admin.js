jQuery(document).ready(function($) {
    // DataTables initialization
    var table = $('#aksiyonlar-table').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Turkish.json'
        },
        dom: '<"table-top"f>rt<"table-bottom"ilp><"clear">',
        pageLength: 10,
        ordering: true,
        responsive: true,
        columnDefs: [
            {
                targets: [2], // Önem kolonu
                render: function(data, type, row) {
                    let className = '';
                    let text = '';
                    switch(data) {
                        case '1':
                            className = 'status-badge status-urgent';
                            text = 'Yüksek';
                            break;
                        case '2':
                            className = 'status-badge status-medium';
                            text = 'Orta';
                            break;
                        case '3':
                            className = 'status-badge status-low';
                            text = 'Düşük';
                            break;
                    }
                    return `<span class="${className}">${text}</span>`;
                }
            },
            {
                targets: [6], // İlerleme kolonu
                render: function(data, type, row) {
                    return `
                        <div class="progress-bar-wrapper">
                            <div class="progress-bar" style="width: ${data}%"></div>
                        </div>
                        <span class="progress-text">${data}%</span>
                    `;
                }
            },
            {
                targets: [7], // Durum kolonu
                render: function(data, type, row) {
                    let className = data === 'Tamamlandı' ? 'status-badge status-completed' : 'status-badge status-active';
                    return `<span class="${className}">${data}</span>`;
                }
            },
            {
                targets: [8], // İşlemler kolonu
                orderable: false,
                render: function(data, type, row) {
                    return `
                        <div class="action-buttons">
                            <button class="bkm-btn icon-btn edit-btn" data-id="${row[0]}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="bkm-btn icon-btn delete-btn" data-id="${row[0]}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });

    // Animasyonlu sayaçlar
    $('.stat-number').each(function() {
        const $this = $(this);
        const countTo = parseInt($this.data('count'));
        
        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 1000,
            easing: 'swing',
            step: function() {
                $this.text(Math.floor(this.countNum));
            },
            complete: function() {
                $this.text(this.countNum);
            }
        });
    });

    // Filtre işlemleri
    $('.filter-group select').on('change', function() {
        table.draw();
    });

    // Filtre temizleme
    $('.filter-clear').on('click', function() {
        $('.filter-group select').val('').trigger('change');
        table.search('').columns().search('').draw();
    });

    // Custom search function
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const kategoriFilter = $('#kategori-filter').val();
        const onemFilter = $('#onem-filter').val();
        const durumFilter = $('#durum-filter').val();
        
        const kategori = data[3]; // Kategori kolonu
        const onem = data[2];     // Önem kolonu
        const durum = data[7];    // Durum kolonu
        
        if (
            (kategoriFilter === '' || kategori === kategoriFilter) &&
            (onemFilter === '' || onem.includes(onemFilter)) &&
            (durumFilter === '' || durum.includes(durumFilter))
        ) {
            return true;
        }
        return false;
    });

    // Yeni aksiyon ekleme butonu
    $('#yeni-aksiyon-btn').on('click', function() {
        window.location.href = ajaxurl + '?page=bkm-aksiyon-ekle';
    });

    // Edit button click handler
    $(document).on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        window.location.href = ajaxurl + '?page=bkm-aksiyon-ekle&action=edit&id=' + id;
    });

    // Delete button click handler
    $(document).on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        if (confirm('Bu aksiyonu silmek istediğinizden emin misiniz?')) {
            // AJAX ile silme işlemi yapılacak
            $.post(ajaxurl, {
                action: 'delete_aksiyon',
                id: id,
                nonce: bkm_vars.nonce
            }, function(response) {
                if (response.success) {
                    table.row($(`[data-id="${id}"]`).parents('tr')).remove().draw();
                    // Başarılı bildirim göster
                    showNotification('success', 'Aksiyon başarıyla silindi');
                } else {
                    // Hata bildirimi göster
                    showNotification('error', 'Silme işlemi başarısız oldu');
                }
            });
        }
    });

    // Notification function
    function showNotification(type, message) {
        const notification = $(`
            <div class="bkm-notification ${type}">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(function() {
            notification.addClass('show');
        }, 100);
        
        setTimeout(function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // Responsive tasarım için pencere yeniden boyutlandırma olayı
    $(window).on('resize', function() {
        table.columns.adjust().responsive.recalc();
    });
});

/* Form Styles */
.form-container {
    background: var(--surface);
    border-radius: 1.5rem;
    padding: 2rem;
    box-shadow: var(--card-shadow);
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.form-section {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    color: var(--text-secondary);
    font-size: 0.875rem;
    font-weight: 500;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="date"],
.form-group select,
.form-group textarea {
    padding: 0.75rem 1rem;
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    font-size: 0.875rem;
    transition: var(--transition);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

/* Multi-select stillleri */
.form-group select[multiple] {
    height: auto;
    min-height: 120px;
    padding: 0.5rem;
}

.form-group select[multiple] option {
    padding: 0.5rem;
    border-radius: 0.5rem;
    margin-bottom: 0.25rem;
}

.form-group select[multiple] option:checked {
    background: var(--primary);
    color: white;
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border);
}

/* Responsive Form */
@media (max-width: 1024px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .bkm-btn {
        width: 100%;
    }
}

/* Form Validation Styles */
.form-group input:invalid,
.form-group select:invalid,
.form-group textarea:invalid {
    border-color: var(--danger);
}

.form-group .error-message {
    color: var(--danger);
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

/* Custom Select Styling */
.form-group select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}
// Select2 initializasyonu
    $('.form-control:not([type="range"])').select2({
        width: '100%',
        placeholder: 'Seçiniz...',
        allowClear: true
    });

    // Çoklu seçim için Select2
    $('#sorumlular').select2({
        width: '100%',
        placeholder: 'Sorumlu kişileri seçiniz...',
        allowClear: true,
        multiple: true
    });

    // İlerleme çubuğu güncelleme
    $('#ilerleme').on('input change', function() {
        var value = $(this).val();
        $('.progress-value').text(value + '%');
        
        // Progress bar renk gradyanı
        var color;
        if (value < 30) {
            color = '#ef4444'; // Kırmızı
        } else if (value < 70) {
            color = '#f59e0b'; // Turuncu
        } else {
            color = '#22c55e'; // Yeşil
        }
        
        $(this).css('background', `linear-gradient(to right, ${color} ${value}%, #e5e7eb ${value}%)`);
    });

    // Önem derecesi seçimi renklendirme
    $('#onem').on('change', function() {
        var value = $(this).val();
        $(this).removeClass('high-priority medium-priority low-priority');
        
        switch(value) {
            case '1':
                $(this).addClass('high-priority');
                break;
            case '2':
                $(this).addClass('medium-priority');
                break;
            case '3':
                $(this).addClass('low-priority');
                break;
        }
    });

    // Form gönderimi
    $('#aksiyon-form').on('submit', function(e) {
        e.preventDefault();
        
        // Form verilerini topla
        var formData = new FormData(this);
        
        // AJAX isteği
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                // Gönder butonunu devre dışı bırak
                $('button[type="submit"]').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin"></i> İşleniyor...'
                );
            },
            success: function(response) {
                if (response.success) {
                    // Başarılı bildirim göster
                    showNotification('success', response.data.message);
                    
                    // 2 saniye sonra listeye geri dön
                    setTimeout(function() {
                        window.location.href = 'admin.php?page=bkm-aksiyon-takip';
                    }, 2000);
                } else {
                    // Hata bildirimi göster
                    showNotification('error', response.data.message || 'Bir hata oluştu');
                    // Gönder butonunu tekrar aktif et
                    $('button[type="submit"]').prop('disabled', false).html(
                        '<i class="fas fa-save"></i> Kaydet'
                    );
                }
            },
            error: function() {
                showNotification('error', 'Sunucu hatası oluştu');
                // Gönder butonunu tekrar aktif et
                $('button[type="submit"]').prop('disabled', false).html(
                    '<i class="fas fa-save"></i> Kaydet'
                );
            }
        });
    });

    // Bildirim gösterme fonksiyonu
    function showNotification(type, message) {
        var icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
        var notification = $(`
            <div class="bkm-notification ${type}">
                <i class="fas fa-${icon}"></i>
                <span>${message}</span>
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(function() {
            notification.addClass('show');
        }, 100);
        
        setTimeout(function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // Sayfa yüklendiğinde ilerleme çubuğunu güncelle
    $('#ilerleme').trigger('input');

    // Tarih alanları için datepicker
    $('.form-control[type="date"]').flatpickr({
        dateFormat: "Y-m-d",
        locale: "tr",
        allowInput: true
    });
});