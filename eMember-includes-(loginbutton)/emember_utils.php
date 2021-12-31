<?php

/*
 * Checks if the string exists in the array key value of the provided array. If it doesn't exist, it returns the first key element from the valid values.
 */

function emember_sanitize_value_by_array($val_to_check, $valid_values) {
    $keys = array_keys($valid_values);
    $keys = array_map('strtolower', $keys);
    if (in_array($val_to_check, $keys)) {
        return $val_to_check;
    }
    return reset($keys); //Return the first element from the valid values
}

/*
 * Sanitize the text field value with htmlspecialchars, strip_tags, sanitize_text_field, esc_attr
 */
function emember_sanitize_text( $text ) {
    $text = htmlspecialchars( $text );
    $text = strip_tags( $text );
    $text = sanitize_text_field( $text );
    $text = esc_attr( $text );
    return $text;
}

/*
 * Returns just the domain name. Something like example.com
 */
function emember_get_home_url_without_http_and_www(){
        $site_url = get_site_url();
        $parse = parse_url($site_url);
        $site_url = $parse['host'];
        $site_url = str_replace('https://', '', $site_url);
        $site_url = str_replace('http://', '', $site_url);
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $site_url, $regs)) {
            $site_url = $regs['domain'];
        }
        return $site_url;
}

function emember_compare_url( $url1, $url2 ) {
    $url1 = trailingslashit( strtolower( $url1 ) );
    $url2 = trailingslashit( strtolower( $url2 ) );
    if ( $url1 == $url2 ) {
            return true;
    }

    $url1 = parse_url( $url1 );
    $url2 = parse_url( $url2 );

    $components = array( 'scheme', 'host', 'port', 'path' );

    foreach ( $components as $key => $value ) {
            if ( ! isset( $url1[ $value ] ) && ! isset( $url2[ $value ] ) ) {
                    continue;
            }

            if ( ! isset( $url2[ $value ] ) ) {
                    return false;
            }
            if ( ! isset( $url1[ $value ] ) ) {
                    return false;
            }

            if ( $url1[ $value ] != $url2[ $value ] ) {
                    return false;
            }
    }

    if ( ! isset( $url1['query'] ) && ! isset( $url2['query'] ) ) {
            return true;
    }

    if ( ! isset( $url2['query'] ) ) {
            return false;
    }
    if ( ! isset( $url1['query'] ) ) {
            return false;
    }

    return strpos( $url1['query'], $url2['query'] ) || strpos( $url2['query'], $url1['query'] );
}

function emember_is_current_url_a_system_page(){
    $emember_config = Emember_Config::getInstance();

    $current_page_url = wp_emember_get_current_url();

    //Check if the current page is the membership renewal page.
    $renewal_url = $emember_config->getValue('eMember_account_upgrade_url');
    if (empty($renewal_url)) {return false;}
    if (emember_compare_url($renewal_url, $current_page_url)) {return true;}

    //Check if the current page is the membership logn page.
    $login_page_url = $emember_config->getValue('login_page_url');
    if (empty($login_page_url)) {return false;}
    if (emember_compare_url($login_page_url, $current_page_url)) {return true;}

    //Check if the current page is the membership join page.
    $registration_page_url = $emember_config->getValue('eMember_registration_page');
    if (empty($registration_page_url)) {return false;}
    if (emember_compare_url($registration_page_url, $current_page_url)) {return true;}

    return false;
}
