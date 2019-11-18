<?php

namespace Phlesk;

/**
 * A link between packages and Plesk components.
 *
 * PHP Version 5
 *
 * @category  PHP
 * @package   Phlesk
 * @author    Jeroen van Meeuwen (Kolab Systems) <vanmeeuwen@kolabsys.com>
 * @author    Christian Mollekopf (Kolab Systems) <mollekopf@kolabsys.com>
 * @copyright 2019 Kolab Systems AG <contact@kolabsystems.com>
 * @license   GPLv3 (https://www.gnu.org/licenses/gpl.txt)
 * @link      https://pxts.ch
 */
class Component
{
    /**
     * Install components.
     *
     * @param array $components The list of components to install.
     *
     * @return boolean
     */
    public static function install($components)
    {
        if (empty($components)) {
            return false;
        }

        if (!is_array($components)) {
            $components = (array) $components;
        }

        $result = \Phlesk::exec(
            [
                'plesk', 'installer', 'add', '--components',
                implode(',', $components)
            ]
        );

        return ($result['code'] == 0);
    }

    /**
     * Confirm or deny a Plesk component is installed using `plesk sbin packagemng --list`.
     *
     * @return boolean
     */
    public static function isInstalled($package)
    {
        $result = \pm_ApiCli::callSbin('packagemng', ['--list'], \pm_ApiCli::RESULT_STDOUT);

        $packages = [];

        foreach (explode("\n", $result) as $line) {
            $line = explode(':', $line);

            $pkgName = trim(array_shift($line));
            $pkgVersion = trim(implode(':', $line));

            if (!empty($pkgVersion)) {
                $packages[$pkgName] = $pkgVersion;
            }
        }

        return array_key_exists($package, $packages);
    }
}
