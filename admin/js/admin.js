jQuery(document).ready(function($) {
    // DataTables inicializasyonu
    var table = $('#aksiyonlar-table').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Turkish.json'
        },
        order: [[0, "desc"]],
        responsive: true,
        pageLength: 10,
        dom: '<"top"f>rt<"bottom"ilp><"clear">',
        columnDefs: [
            {
                targets: [2, 8], // Önem ve Durum kolonları
                className: 'text-center'
            },
            {
                targets: [7], // İlerleme kolonu
                className: 'text-center',
                width: '150px'
            },
            {
                targets: [9], // İşlemler kolonu
                orderable: false,
                className: 'text-right'
            }
        ],
        initComplete: function() {
            // Custom filtre alanını oluştur
            $('.filter-group select').on('change', function() {
                table.draw();
            });
        }
    });

    // Custom filtre fonksiyonu
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        var kategoriFilter = $('#kategori-filter').val();
        var onemFilter = $('#onem-filter').val();
        var durumFilter = $('#durum-filter').val();
        
        var kategori = data[4]; // Kategori kolonu
        var onem = data[2];     // Önem kolonu
        var durum = data[8];    // Durum kolonu
        
        if (
            (kategoriFilter === '' || kategori === kategoriFilter) &&
            (onemFilter === '' || onem.includes(onemFilter)) &&
            (durumFilter === '' || durum.includes(durumFilter))
        ) {
            return true;
        }
        return false;
    });

    // Animasyonlu sayaç
    $('.count').each(function() {
        $(this).prop('Counter', 0).animate({
            Counter: $(this).text()
        }, {
            duration: 1000,
            easing: 'swing',
            step: function(now) {
                $(this).text(Math.ceil(now));
            }
        });
    });
});