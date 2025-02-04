<?php

/**
 * Basic extension-level functions, to assist multiple extensions working in unison.
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
namespace Phlesk;

/**
 * The `\Phlesk\Extension` class is intended to provide extensions with information about and
 * interface in to other extensions.
 *
 * This allows an extension such as `kolab` to see if an extension `seafile` is active, has its
 * software installed, and is enabled for a feasible domain (has hosting and mail service).
 *
 * Example usage:
 *
 * ```php
 * $seafileEnabled = \Phlesk\Extension::isEnabled('seafile', $domain);
 * ```
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
class Extension
{
    /**
     * Obtain the permissions for an extension.
     *
     * @return array
     */
    public static function getPermissions($target)
    {
        $permissions = [];

        if (!self::isActive($target)) {
            \pm_Log::debug("Extension {$target} is not active or not available.");
            return $permissions;
        }

        $module = \Phlesk\Context::in($target);

        $extension = ucfirst(strtolower($target));

        $hasPermission = false;

        // Attempt to include the file that defines the permission class.
        @include_once "/usr/local/psa/admin/plib/modules/{$target}/hooks/Permissions.php";

        if (class_exists("Modules_{$extension}_Permissions")) {
            if (is_callable(["Modules_{$extension}_Permissions", "getPermissions"], false, $c)) {
                $permissions = $c();
            } else {
                \pm_Log::debug("No callable Modules_{$extension}_Permissions::getPermissions()");
            }
        } else {
            \pm_Log::debug("Could not find class Modules_{$extension}_Permissions");
        }

        return \Phlesk\Context::out($module, array_keys($permissions));
    }

    /**
     * Verify an extension is active.
     *
     * NOTE: Currently attempts to use `\pm_Extension`, which was introduced in later Plesk
     * versions, and otherwise falls back on to detecting whether or not a class
     * `Modules_Myextension_Utils` exists.
     *
     * @param String $target The name of the extension to check.
     *
     * @return Bool
     */
    public static function isActive($target)
    {
        if (class_exists('pm_Extension')) {
            try {
                $extension = \pm_Extension::getById(strtolower($target));
                return $extension->isActive();
            } catch (\pm_Exception $e) {
                \pm_Log::debug("\pm_Extension::getById('{$target}') threw an exception");
                return false;
            }
        }

        // All extensions have their local utilities class.
        $extension = ucfirst(strtolower($target));

        if (!class_exists("Modules_{$extension}_Utils")) {
            \pm_Log::debug("No class Modules_{$extension}_Utils found");
            return false;
        }

        return true;
    }

    /**
     * Verify the extension `$target` is enabled for `\pm_Domain $domain`.
     *
     * This includes verifying the extension `$target` is active, that the source extension is
     * installed, verifying the target extension has its software installed, and that any the
     * target extension's permissions are enabled.
     *
     * @param String     $target The name of the extension.
     * @param \pm_Domain $domain The domain
     *
     * @return Bool
     */
    public static function isEnabled($target, \pm_Domain $domain)
    {
        if (!self::isActive($target)) {
            \pm_Log::debug("Extension {$target} is not active or not available.");
            return false;
        }

        if (!self::isInstalled(\Phlesk\Context::getModuleId())) {
            \pm_Log::debug(
                sprintf(
                    "Extension %s does not have its software installed.",
                    \Phlesk\Context::getModuleId()
                )
            );

            return false;
        }

        $module = \Phlesk\Context::in($target);

        if (!self::isInstalled($target)) {
            \pm_Log::debug("Extension {$target} does not have its software installed.");
            return false;
        }

        $extension = ucfirst(strtolower($target));

        $hasPermission = false;

        // Attempt to include the file that defines the permission class.
        @include_once "/usr/local/psa/admin/plib/modules/{$target}/hooks/Permissions.php";

        if (class_exists("Modules_{$extension}_Permissions")) {
            if (is_callable(["Modules_{$extension}_Permissions", "getPermissions"], false, $c)) {
                $permissions = $c();
                foreach ($permissions as $permission => $attrs) {
                    \pm_Log::debug("Testing permission {$permission}");

                    if (!$hasPermission) {
                        $hasPermission = (bool)$domain->hasPermission($permission);
                    }
                }
            } else {
                \pm_Log::debug("No callable Modules_{$extension}_Permissions::getPermissions()");
            }
        } else {
            \pm_Log::debug("Could not find class Modules_{$extension}_Permissions");

            $hasPermission = (bool)$domain->hasPermission("manage_{$target}");
        }

        return \Phlesk\Context::out($module, $hasPermission);
    }

    /**
     * Verify the extension has installed its software.
     *
     * @param String $target The name of the extension to check.
     *
     * @return Bool
     */
    public static function isInstalled($target)
    {
        $extension = ucfirst(strtolower($target));

        if (!self::isActive($target)) {
            \pm_Log::debug("Extension {$target} is not active.");
            return false;
        }

        // requires software installation
        $extInstallClass = "Modules_{$extension}_Install";

        if (class_exists($extInstallClass)) {
            $instance = $extInstallClass::getInstance();

            if (method_exists($instance, "isInstalled")) {
                return $instance::isInstalled();
            }
        }

        return true;
    }
}
