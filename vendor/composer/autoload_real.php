<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit683aa178b1e6ebf68a3d01f3ff0c4d0b
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInit683aa178b1e6ebf68a3d01f3ff0c4d0b', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit683aa178b1e6ebf68a3d01f3ff0c4d0b', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit683aa178b1e6ebf68a3d01f3ff0c4d0b::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
