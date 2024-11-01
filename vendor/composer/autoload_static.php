<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit87126ce30d16f9d1d0a1b30fe52a6c11
{
    public static $prefixLengthsPsr4 = array (
        'G' => 
        array (
            'GPLSCore\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'GPLSCore\\' => 
        array (
            0 => __DIR__ . '/../..' . '/',
        ),
    );

    public static $classMap = array (
        'GPLSCore\\GPLS_WSM\\Core' => __DIR__ . '/../..' . '/core/core.php',
        'GPLSCore\\GPLS_WSM\\Modules\\Services\\Helpers' => __DIR__ . '/../..' . '/core/modules/services/class-helpers.php',
        'GPLSCore\\GPLS_WSM\\Modules\\Services\\Pro_Tab' => __DIR__ . '/../..' . '/core/modules/services/class-pro.php',
        'GPLSCore\\GPLS_WSM_Settings\\GPLS_WSM_Settings' => __DIR__ . '/../..' . '/settings.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit87126ce30d16f9d1d0a1b30fe52a6c11::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit87126ce30d16f9d1d0a1b30fe52a6c11::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit87126ce30d16f9d1d0a1b30fe52a6c11::$classMap;

        }, null, ClassLoader::class);
    }
}
