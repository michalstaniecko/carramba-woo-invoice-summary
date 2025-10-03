<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <form id="wis-summary-form">
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="wis-year"><?php _e( 'Year', 'woocommerce-invoice-summary' ); ?></label></th>
                    <td>
                        <select id="wis-year" name="wis-year">
                            <?php
                            $current_year = date('Y');
                            for ( $i = $current_year; $i >= $current_year - 5; $i-- ) {
                                echo '<option value="' . esc_attr( $i ) . '">' . esc_html( $i ) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wis-month"><?php _e( 'Month', 'woocommerce-invoice-summary' ); ?></label></th>
                    <td>
                        <select id="wis-month" name="wis-month">
                            <?php
                            for ( $i = 1; $i <= 12; $i++ ) {
                                $month_name = date_i18n( 'F', mktime( 0, 0, 0, $i, 1 ) );
                                echo '<option value="' . esc_attr( $i ) . '" ' . selected( date('n'), $i, false ) . '>' . esc_html( $month_name ) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Document Types', 'woocommerce-invoice-summary' ); ?></th>
                    <td>
                        <fieldset>
                            <label><input type="checkbox" name="wis-doc-type" value="invoice" checked> <?php _e( 'Invoices', 'woocommerce-invoice-summary' ); ?></label><br>
                            <label><input type="checkbox" name="wis-doc-type" value="receipt"> <?php _e( 'Receipts', 'woocommerce-invoice-summary' ); ?></label>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <button type="submit" class="button button-primary"><?php _e( 'Generate Summary', 'woocommerce-invoice-summary' ); ?></button>
            <span class="spinner"></span>
        </p>
    </form>

    <div id="wis-summary-results"></div>

</div>
