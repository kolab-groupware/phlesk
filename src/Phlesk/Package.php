<?php

namespace Phlesk;

/**
 * Software package utilities for Plesk extensions.
 *
 * Provides selected utility functions so extensions can efficiently check if packages are already
 * installed (for example, `wget`), are available (for example, `plesk-php56`) and also execute
 * installation in a platform-agnostic manner:
 *
 * ## Example Usage
 *
 * Verify a package is installed:
 *
 * ```php
 * if (!\Phlesk\Package::isInstalled('wget')) {
 *     // use php-curl?
 * }
 * ```
 *
 * ## Example Usage
 *
 * Verify a package is available (a package that is installed is also deemed available):
 *
 * ```php
 * if (!\Phlesk\Package::isAvailable('plesk-php56')) {
 *     // something about php 5.6
 * }
 * ```
 *
 * ## Example Usage
 *
 * Install a number of packages, but only those that are not already installed, and only those that
 * are available:
 *
 * ```php
 * $result = \Phlesk\Package::install(['foo', 'bar']);
 * ```
 *
 * This example logs an error if none of the packages are available.
 */
class Package
{
    /**
     * Install the software packages $packages.
     *
     * Checks if the packages are not already installed.
     * Checks if the packages are available, to prevent a lockup in, say, aptitude.
     *
     * @param array $packages The list of packages to install.
     *
     * @return bool Whether or not the packages are successfully installed.
     *
     * @see \Phlesk\Platform::getInstallCmd()
     *
     * @throws \pm_Exception The package manager for this platform is not supported.
     */
    public static function install($packages)
    {
        $installCmd = \Phlesk\Platform::getInstallCmd();

        $installPkgs = [];

        if (empty($installCmd)) {
            throw new \pm_Exception(
                sprintf(
                    "Phlesk does not support the package manager for %s",
                    \pm_ProductInfo::getOsName()
                )
            );
        }

        $pkgsUnavailable = [];

        foreach ($packages as $pkg) {
            if (self::isInstalled($pkg)) {
                \pm_Log::debug("Package {$pkg} already installed.");
                continue;
            }

            if (!self::isAvailable($pkg)) {
                \pm_Log::debug("Package {$pkg} not available.");
                $pkgsUnavailable[] = $pkg;
                continue;
            }

            $installPkgs[] = $pkg;
        }

        if (sizeof($installPkgs) > 0) {
            $result = \Phlesk::exec(array_merge($installCmd, $installPkgs));

            return $result['code'] == 0;
        }

        if (sizeof($pkgsUnavailable) == sizeof($packages)) {
            \pm_Log::err(
                sprintf(
                    "None of the following packages are available for installation; %s",
                    implode(', ', $packages)
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Test if a package is installed.
     *
     * @param string $package The package name.
     *
     * @return bool
     */
    public static function isInstalled($package)
    {
        switch (\pm_ProductInfo::getOsName()) {
            case \pm_ProductInfo::OS_CENTOS:
            case \pm_ProductInfo::OS_REDHAT:
                $result = \Phlesk::exec(['rpm', '-qv', $package], true);
                break;

            case \pm_ProductInfo::OS_DEBIAN:
            case \pm_ProductInfo::OS_UBUNTU:
                $result = \Phlesk::exec(['dpkg', '-l', $package], true);
                break;

            default:
                \pm_Log::err(
                    sprintf(
                        "Phlesk does not support the package manager for %s",
                        \pm_ProductInfo::getOsName()
                    )
                );

                return false;
        }

        return $result['code'] == 0;
    }

    /**
     * Test if a package is available (or installed).
     *
     * @param string $package The package name.
     *
     * @return bool
     */
    public static function isAvailable($package)
    {
        switch (\pm_ProductInfo::getOsName()) {
            case \pm_ProductInfo::OS_CENTOS:
            case \pm_ProductInfo::OS_REDHAT:
                $result = \Phlesk::exec(['yum', 'list', $package], true);
                break;

            case \pm_ProductInfo::OS_DEBIAN:
            case \pm_ProductInfo::OS_UBUNTU:
                $result = \Phlesk::exec(['apt-cache', 'show', $package], true);
                break;

            default:
                \pm_Log::err(
                    sprintf(
                        "Phlesk does not support the package manager for %s",
                        \pm_ProductInfo::getOsName()
                    )
                );

                return false;
        }

        return $result['code'] == 0;
    }
}
