<?php

// Autoload classes
function plugin_scrumban_autoload($classname) {
    if (strpos($classname, 'PluginScrumban') === 0) {
        $classname = str_replace('PluginScrumban', '', $classname);
        $filename = GLPI_ROOT . "/plugins/scrumban/inc/" . strtolower($classname) . ".class.php";
        if (is_readable($filename) && is_file($filename)) {
            include_once($filename);
            return true;
        }
    }
    return false;
}

// Register autoloader
spl_autoload_register('plugin_scrumban_autoload');

// Load main menu class
include_once(GLPI_ROOT . "/plugins/scrumban/inc/menu.class.php");