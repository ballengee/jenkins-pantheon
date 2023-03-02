<?php

/**
 * Given a key and raw values, adds markup for display on Settings page
 *
 */
class SalesforceSettingsMarkup {

    /**
     * Given a key and raw values, adds markup for display on Settings page
     * @param string $key
     * @param mixed $raw
     * @return string
     */
    public static function markup( $key, $raw ) {

        switch ( $key ) {

            case 'comm_data_debug':
                $markup = self::commDataDebug( $raw );
                break;

            case 'field_map_array':
                $markup = self::fieldMapArray( $raw );
                break;

            case 'ordered_request':
                $markup = self::orderedRequest( $raw );
                break;
            
            default:
                $markup = '<h3>Markup Placeholder</h3>';
        }

        return $markup;
    }

    /**
     * Indexed array, each entry must have object, field_name, value keys
     * 
     * If data doesn't come in as expected, skip over it;
     * @param array $raw
     */
    public static function orderedRequest( $raw ) {

        $markup = '<table><tbody>';

        $markup .= '<tr><td><strong>Object</strong></td><td><strong>Field Name</strong></td><td><strong>Value</strong></td></tr>';

        if ( is_array( $raw ) ) {

            $line_item_markup = '';

            foreach ( $raw as $entry ) {

                if ( isset( $entry[ 'object' ] ) && isset( $entry[ 'field_name' ] ) && isset( $entry[ 'value' ] ) ) {

                    $line_item_markup .= '<tr><td>' . $entry[ 'object' ];
                    $line_item_markup .= '</td><td>' . $entry[ 'field_name' ];
                    $line_item_markup .= '</td><td>' . $entry[ 'value' ] . '</td></tr>';
                }
            }

            $markup .= $line_item_markup;
        }

        $markup .= '</tbody></table>';

        return $markup;
    }

    /**
     * Indexed array, each entry is three key-value pairs
     * @param array $raw
     */
    public static function fieldMapArray( $raw ) {

        $markup = '<table><tbody>';
        $markup .= '<tr><td><strong>Submission value</strong></td><td><strong>Field Map</strong></td><td><strong>Special Instructions</strong></td></tr>';

        if ( is_array( $raw ) ) {


            $line_item_markup = '';
            foreach ( $raw as $line_item ) {

                $line_item_markup .= '<tr>';
                foreach ( $line_item as $value ) {
                    $line_item_markup .= '<td>' . $value . '</td>';
                }
                $line_item_markup .= '</tr>';
            }

            $markup .= $line_item_markup;
        }

        $markup .= '</tbody></table>';

        return $markup;
    }

    /**
     * Creates table, serializes, and formats to break words and wrap
     * 
     * @param array $raw
     * @return string
     */
    public static function commDataDebug($raw)
    {
        $debug_markup = '';

        if (is_array($raw)) {
            foreach ($raw as $indexedCollection) {
                if (is_array($indexedCollection)) {
                    $debug_markup .= self::markupRequestForDisplay($indexedCollection);
                }
            }
        }

        $markup = '<table><tbody><tr><td style="word-break: break-all; word-wrap: break-word;">';

        if (0 < strlen($debug_markup)) {
            $markup .= $debug_markup;
        } else {
            $markup .= serialize($raw);
        }
        
        $markup .= '</td></tr></tbody></table>';

        return $markup;
    }

    /**
     * Iterates debug heading-value pairs to mark up the HTML for display
     *
     * @param array $indexedCollection
     * @return string
     */
    public static function markupRequestForDisplay($indexedCollection): string
    {
        $markup = '';

        // exit gracefully on unexpected data
        if (!is_array($indexedCollection)) {
            return $markup;
        }

        foreach ($indexedCollection as $debugData) {
            $row = '<tr>';

            if (isset($debugData['heading'])) {
                $row .= '<td>' . $debugData['heading'] . '</td>';
            } else {
                $row .= '<td></td>';
            }

            if (isset($debugData['value'])) {

                $output = is_string($debugData['value']) ? $debugData['value'] : serialize($debugData['value']);

                $row .= '<td style="word-break: break-all; word-wrap: break-word;">'
                . $output
                    . '</td>';
            } else {
                $row .= '<td></td>';
            }

            $row .= '</tr>';

            $markup .= $row;
        }
        
        return $markup;
    }

}
