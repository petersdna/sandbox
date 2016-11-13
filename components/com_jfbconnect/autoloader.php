<?php

/**
 * @package         JFBConnect
 * @copyright (c)   2009-2015 by SourceCoast - All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @version         Release v6.5.3
 * @build-date      2015/12/19
 */

class JFBConnectAutoloader
{
    public static function register($cms = 'Joomla')
    {
            spl_autoload_register(array(__CLASS__, 'autoload' . $cms));
    }

    /**
     * Handles autoloading of classes.
     *
     * @param string $class A class name.
     */
    public static function autoloadJoomla($class)
    {
        if (0 !== strpos($class, 'JFBConnect')) {
            return;
        }

        $class = str_replace("JFBConnect" , "", $class);
        $parts = preg_split('/(?=[A-Z])/',$class);
        unset($parts[0]);

        // Let Joomla handle loading these
        if ($parts[1] == "Model")
            return;

        if (isset($parts[2]) && $parts[2] == "Widget")
            unset($parts[1]); // Special case where widget isn't in the /provider/ folder

        $file = JPATH_SITE . '/components/com_jfbconnect/libraries';
        foreach ($parts as $p)
        {
            $path = str_replace(array('/', '\\', '.', "\0"), array('', '', '', ''), $p); // basic sanitization
            $file .= '/' . strtolower($path);
        }
        $file .= '.php';
        if (is_file($file)) {
            require $file;
        }
    }

    public static function autoloadWordpress($class)
    {
        if (0 !== strpos($class, 'JFBConnect')) {
            return;
        }

        $class = str_replace("JFBConnect" , "", $class);
        $parts = preg_split('/(?=[A-Z])/',$class);
        unset($parts[0]);

        // Let Joomla handle loading these
        if ($parts[1] == "Model")
            return;

        if (isset($parts[2]) && $parts[2] == "Widget")
            unset($parts[1]); // Special case where widget isn't in the /provider/ folder

        $file = ABSPATH . '/wp-content/plugins/jfbconnect/jfbconnect/component/frontend/libraries';
        foreach ($parts as $p)
        {
            $path = str_replace(array('/', '\\', '.', "\0"), array('', '', '', ''), $p); // basic sanitization
            $file .= '/' . strtolower($path);
        }
        $file .= '.php';
        if (is_file($file)) {
            require $file;
        }
    }
}