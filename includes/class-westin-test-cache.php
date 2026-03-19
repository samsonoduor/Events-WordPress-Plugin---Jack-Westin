<?php

namespace WestinTest\Events;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cache {
    private const VERSION_OPTION = 'westin_test_events_cache_version';

    public static function get_version(): string {
        $version = get_option( self::VERSION_OPTION );

        if ( empty( $version ) ) {
            $version = (string) time();
            update_option( self::VERSION_OPTION, $version, false );
        }

        return (string) $version;
    }

    public static function set_version( $version ): void {
        update_option( self::VERSION_OPTION, (string) $version, false );
    }

    public static function bump(): void {
        self::set_version( time() );
    }

    public static function key( string $suffix ): string {
        return 'westin_test_events_' . md5( self::get_version() . '|' . $suffix );
    }
}
