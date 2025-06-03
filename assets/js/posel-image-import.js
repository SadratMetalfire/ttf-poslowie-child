jQuery(document).ready(function ($) {
    $('#start-image-import').on('click', function () {
        let offset = 0;
        const limit = 5;
        const $status = $('#image-import-status');

        function importBatch() {
            $.post(poselImportData.ajax_url, {
                action: 'posel_image_import',
                nonce: poselImportData.nonce,
                offset: offset,
                limit: limit
            }).done(function (response) {
                console.log(response);
                if (response.success) {
                    const data = response.data;
                    if (data.done) {
                        $status.text('Import zakończony.');
                        $('#start-image-import').prop('disabled', false);
                    } else {
                        offset = data.next_offset;
                        $status.text(`Zaimportowano ${data.processed} zdjęć...`);
                        importBatch();
                    }
                } else {
                    $status.text('Błąd serwera.');
                }
            }).fail(() => {
                $status.text('Błąd AJAX.');
            });

            $('#start-image-import').prop('disabled', true);
        }

        importBatch();
    });
});
