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

    // CSV Export handler
    $(document).on('click', '#wis-export-csv', function(e) {
        e.preventDefault();

        const year = $('#wis-year').val();
        const month = $('#wis-month').val();
        const docTypes = [];
        $('input[name="wis-doc-type"]:checked').each(function() {
            docTypes.push($(this).val());
        });

        // Show loading state
        const button = $(this);
        button.prop('disabled', true).addClass('updating-message');

        wp.apiRequest({
            path: 'wis/v1/summary/export-csv',
            data: {
                year: year,
                month: month,
                document_types: docTypes
            },
            nonce: wis_ajax.nonce
        }).done(function(data) {
            button.prop('disabled', false).removeClass('updating-message');

            if ( data.csv && data.filename ) {
                // Create a Blob from the CSV content
                const blob = new Blob([data.csv], { type: 'text/csv;charset=utf-8;' });

                // Create a temporary link element and trigger download
                const link = document.createElement('a');
                if (link.download !== undefined) {
                    const url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    link.setAttribute('download', data.filename);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                }
            }
        }).fail(function(jqXHR) {
            button.prop('disabled', false).removeClass('updating-message');

            let errorMsg = 'An error occurred while exporting CSV.';
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                errorMsg = jqXHR.responseJSON.message;
            }
            alert(errorMsg);
        });
    });
});
