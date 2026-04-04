<?php if (isset ($_REQUEST[ 'get_settings' ])) {
    $response[ 'error' ]                                  = false;
    $response[ 'settings' ][ 'app_share' ]                = "https://srihariagencies.com";
    $response[ 'settings' ][ 'current_version' ]          = '1.0';
    $response[ 'settings' ][ 'minimum_version_required' ] = '1.0';
    $response[ 'settings' ][ 'is-version-system-on' ]     = '1';
    print_r ( json_encode ( $response ) );
    }

