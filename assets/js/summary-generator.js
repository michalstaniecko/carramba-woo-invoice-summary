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
            if (Object.keys(data).length === 0 || Object.keys(data).length === 1 && data.hasOwnProperty('all') && data.all.total === 0) {
                resultsContainer.html('<p>No matching orders found.</p>');
                return;
            }

            let table = '<table class="widefat fixed" cellspacing="0"><thead><tr>' +
                '<th>Payment Method</th>' +
                '<th>Number of Orders</th>' +
                '<th style="text-align:right">Total VAT</th>' +
                '<th style="text-align:right">Total Amount</th>' +
                '</tr></thead><tbody>';

            let totalSum = data.all.total;
            let totalTaxSum = data.all.total_tax;
            delete data.all;

            for (const method in data) {
                if (data.hasOwnProperty(method)) {
                    table += '<tr>' +
                        '<td>' + method + '</td>' +
                        '<td>' + data[method].count + '</td>' +
                        '<td style="text-align:right">' + data[method].total_tax.toFixed(2) + '</td>' +
                        '<td style="text-align:right">' + data[method].total.toFixed(2) + '</td>' +
                        '</tr>';
                }
            }
            
            table += '</tbody><tfoot><tr>' +
                '<th colspan="2" style="text-align:right"><strong>Total:</strong></th>' +
                '<th style="text-align:right"><strong>' + totalTaxSum.toFixed(2) + '</strong></th>' +
                '<th style="text-align:right"><strong>' + totalSum.toFixed(2) + '</strong></th>' +
                '</tr></tfoot></table>';

            resultsContainer.html(table);
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
