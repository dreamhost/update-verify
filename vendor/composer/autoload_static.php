<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9bba1d65a93db35491336fe10e942b4c
{
    public static $files = array (
        '1cd39a82babff4afd40d84ddd5f563da' => __DIR__ . '/../..' . '/wp-cli-load.php',
    );

    public static $prefixLengthsPsr4 = array (
        'U' => 
        array (
            'UpdateVerify\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'UpdateVerify\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9bba1d65a93db35491336fe10e942b4c::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9bba1d65a93db35491336fe10e942b4c::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
