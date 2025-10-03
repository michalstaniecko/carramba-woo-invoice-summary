jQuery(document).ready(function($) {
    const form = $('#wis-summary-form');
    const resultsContainer = $('#wis-summary-results');
    const spinner = form.find('.spinner');

    form.on('submit', function(e) {
        e.preventDefault();
        spinner.addClass('is-active');
        resultsContainer.html('');

        const year = $('#wis-year').val();
        const month = $('#wis-month').val();
        const docTypes = [];
        $('input[name="wis-doc-type"]:checked').each(function() {
            docTypes.push($(this).val());
        });

        wp.apiRequest({
            path: 'wis/v1/summary',
            data: {
                year: year,
                month: month,
                document_types: docTypes
            },
            nonce: wis_ajax.nonce
        }).done(function(data) {
            spinner.removeClass('is-active');
            if ( data.html ) {
                resultsContainer.html(data.html);
            }
        }).fail(function(jqXHR) {
            spinner.removeClass('is-active');
            let errorMsg = 'An error occurred.';
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                errorMsg = jqXHR.responseJSON.message;
            }
            resultsContainer.html('<p>' + errorMsg + '</p>');
        });
    });
});
