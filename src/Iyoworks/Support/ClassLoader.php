<?php namespace Iyoworks\Support;

class ClassLoader extends  \Illuminate\Support\ClassLoader
{

    protected static $composer;

    /**
     * @param  \Composer\Autoload\ClassLoader  $composer
     * @return void
     */
    public static function setComposer(\Composer\Autoload\ClassLoader $composer)
    {
        static::$composer = $composer;
    }

    /**
     * @return \Composer\Autoload\ClassLoader $composer
     */
    public static function getComposer()
    {
        return static::$composer;
    }

    /**
     * Register the given class loader on the auto-loader stack.
     *
     * @param null $composer
     * @return void
     */
    public static function register($composer = null)
    {
        if ( ! static::$registered)
        {
            static::$registered = spl_autoload_register(array('\Iyoworks\Support\ClassLoader', 'load'));
            if ($composer) static::setComposer($composer);
        }
    }
}
