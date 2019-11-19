<?php

namespace Phlesk;

/**
 * Platform utility functions for Phlesk.
 *
 * Example usage:
 *
 * ```php
 * if (\Phlesk\Platform::isMaipo(true)) {
 *     // ... use subscription manager to enable supported repository ...
 * } else {
 *     // ... use some third-party CentOS repository ...
 * }
 * ```
 *
 * ## Example Usage -- test specific platform
 *
 * To execute any one particular action on, say, CentOS 7 or Red Hat Enterprise Linux 7, such as
 * installing PostgreSQL 9.4+ from a third party repository (and corresponding authentication,
 * authorization, backup, restore, etc.), consider the following examples:
 *
 * ```php
 * if (\Phlesk\Platform::getPlatform() == \Phlesk\Platform::MAIPO) {
 *     // ... do something ...
 * }
 * ```
 *
 * Supposedly, this helps with static analysis and prevents typographic errors, however, note that
 * the following is also available, and is actually used as part of
 * {@link \Phlesk\Platform::getPlatform()}:
 *
 * ```php
 * if (\Phlesk\Platform::isMaipo()) {
 *     // ... do something ...
 * }
 * ```
 *
 * This would also allow for analysis, and surely also prevents typographic errors.
 *
 * ## Example Usage -- switch/case platforms.
 *
 * This example shows how to switch/case platforms;
 *
 * ```php
 * switch (\Phlesk\Platform::getPlatform()) {
 *     case \Phlesk\Platform::MAIPO:
 *         // ... do something
 *         break;
 *     case \Phlesk\Platform::JESSIE:
 *         // ... do something
 *         break;
 *     case \Phlesk\Platform::UNKNOWN:
 *         // ... a degree of error control and recovery
 *         break;
 *     default:
 *         // .. we should never end up here
 * }
 * ```
 */
class Platform
{
    /** This is an unknown platform. */
    const UNKNOWN       = 'UNKNOWN';

    /**
     * CentOS and Red Hat Enterprise Linux.
     */

    /** The name for CentOS 5 and Red Hat Enterprise Linux 5. */
    const TIKANGA       = "tikanga";
    /** The name for CentOS 6 and Red Hat Enterprise Linux 6. */
    const SANTIAGO      = "santiago";
    /** The name for CentOS 7 and Red Hat Enterprise Linux 7. */
    const MAIPO         = "maipo";
    /** The name for CentOS 8 and Red Hat Enterprise Linux 8. */
    const OOTPA         = "ootpa";

    /**
     * Debian.
     */

    /** The name for Debian 8. */
    const JESSIE        = "jessie";
    /** The name for Debian 9. */
    const STRETCH       = "stretch";
    /** The name for Debian 10. */
    const BUSTER        = "buster";

    /**
     * Ubuntu.
     */

    /** The name for Ubuntu 16.04 LTS. */
    const XENIAL        = "xenial";
    /** The name for Ubuntu 18.04 LTS. */
    const BIONIC        = "bionic";
    /** The name for Ubuntu 20.04 LTS. */
    const FOCAL         = "focal";

    /** Constant for Community Enterprise OS (CentOS). */
    const CENTOS        = \pm_ProductInfo::OS_CENTOS;
    /** Constant for Debian. */
    const DEBIAN        = \pm_ProductInfo::OS_DEBIAN;
    /** Constant for Red Hat Enterprise Linux. */
    const REDHAT        = \pm_ProductInfo::OS_REDHAT;
    /** Constant for Ubuntu. */
    const UBUNTU        = \pm_ProductInfo::OS_UBUNTU;

    /**
     * Name-version constant for CentOS 7.
     *
     * Use {@link \Phlesk\Platform::isMaipo()} instead.
     *
     * @deprecated 0.1
     */
    const CENTOS7       = self::MAIPO;

    /**
     * Name-version constant for CentOS 8.
     *
     * Can't have been used, but may be required by future versions of extensions that, rather than
     * update their code completely, would wish to retain a level of consistency.
     *
     * Use {@link \Phlesk\Platform::isOotpa()} instead.
     *
     * @deprecated 0.1
     */
    const CENTOS8       = self::OOTPA;

    /**
     * Name-version constant for Red Hat Enterprise Linux 7.
     *
     * Use {@link \Phlesk\Platform::isMaipo()} instead.
     *
     * @deprecated 0.1
     */
    const RHEL7         = self::MAIPO;

    /**
     * Name-version constant for Red Hat Enterprise Linux 8.
     *
     * Can't have been used, but may be required by future versions of extensions that, rather than
     * update their code completely, would wish to retain a level of consistency.
     *
     * Use {@link \Phlesk\Platform::isOotpa()} instead.
     *
     * @deprecated 0.1
     */
    const RHEL8         = self::OOTPA;

    /**
     * Name-version constant for Debian 8.
     *
     * Use {@link \Phlesk\Platform::isJessie()} instead.
     *
     * @deprecated 0.1
     */
    const DEBIAN8       = self::JESSIE;

    /**
     * Name-version constant for Debian 9.
     *
     * Use {@link \Phlesk\Platform::isStretch()} instead.
     *
     * @deprecated 0.1
     */
    const DEBIAN9       = self::STRETCH;

    /**
     * Name-version constant for Debian 10.
     *
     * Can't have been used, but may be required by future versions of extensions that, rather than
     * update their code completely, would wish to retain a level of consistency.
     *
     * Use {@link \Phlesk\Platform::isBuster()} instead.
     *
     * @deprecated 0.1
     */
    const DEBIAN10      = self::BUSTER;

    /**
     * Name-version constant for Ubuntu 16.04 LTS.
     *
     * Use {@link \Phlesk\Platform::isXenial()} instead.
     *
     * @deprecated 0.1
     */
    const UBUNTU1604    = self::XENIAL;

    /**
     * Name-version constant for Ubuntu 18.04 LTS.
     *
     * Use {@link \Phlesk\Platform::isBionic()} instead.
     *
     * @deprecated 0.1
     */
    const UBUNTU1804    = self::BIONIC;

    /**
     * Name-version constant for Ubuntu 20.04 LTS.
     *
     * Can't have been used, but may be required by future versions of extensions that, rather than
     * update their code completely, would wish to retain a level of consistency.
     *
     * Use {@link \Phlesk\Platform::isFocal()} instead.
     *
     * @deprecated 0.1
     */
    const UBUNTU2004    = self::FOCAL;

    /**
     * Get the package installation command prefix for this platform.
     *
     * Assists consuming extensions in determining the package installation routine for the current
     * distribution / version.
     *
     * Example usage:
     *
     * ```php
     * $installPkgs = Phlesk\Platform::getInstallCmd();
     * $installPkgs[] = 'foo';
     * $installPkgs[] = 'bar';
     *
     * $result = \Phlesk::exec($installPkgs);
     * ```
     *
     * However, know that the following is also available:
     *
     * ```php
     * $result = \Phlesk\Package::install(['foo', 'bar']);
     * ```
     *
     * @return array The aptitude (if available), apt-get, yum or dnf-based installation command.
     */
    public static function getInstallCmd()
    {
        switch (self::getPlatform()) {
            case self::JESSIE:
            case self::STRETCH:
            case self::BUSTER:
            case self::XENIAL:
            case self::BIONIC:
            case self::FOCAL:
                if (\Phlesk\Package::isInstalled('aptitude')) {
                    $cmd = 'aptitude';
                } else {
                    $cmd = 'apt-get';
                }

                return [
                    $cmd,
                    '--assume-yes',
                    '-o', 'Dpkg::Options::=--force-confdef',
                    '-o', 'Dpkg::Options::=--force-confold',
                    '-o', 'APT::Install-Recommends=no',
                    'install'
                ];

            case self::MAIPO:
                return ['yum', '-y', 'install'];

            case self::OOTPA:
                return ['dnf', '-y', 'install'];

            default:
                self::logUnknownPlatform();
                return [];
        }
    }

    /**
     * Confirm or deny this platform is Ubuntu 18.04 LTS (Bionic)
     *
     * @return boolean
     */
    public static function isBionic()
    {
        if (\pm_ProductInfo::getOsName() != self::UBUNTU) {
            return false;
        }

        if (version_compare(self::getOsVersion(), '18.04', '<')) {
            return false;
        }

        if (version_compare(self::getOsVersion(), '18.10', '>=')) {
            return false;
        }

        return true;
    }

    /**
     * Confirm or deny this platform is Debian 10 (Buster)
     *
     * @return boolean
     */
    public static function isBuster()
    {
        if (\pm_ProductInfo::getOsName() != self::DEBIAN) {
            return false;
        }

        if (version_compare(self::getOsVersion(), '10', '<')) {
            return false;
        }

        if (version_compare(self::getOsVersion(), '11', '>=')) {
            return false;
        }

        return true;
    }

    /**
     * Confirm or deny this platform is Ubuntu 20.04 LTS (Focal)
     *
     * @return boolean
     */
    public static function isFocal()
    {
        if (\pm_ProductInfo::getOsName() != self::UBUNTU) {
            return false;
        }

        if (version_compare(self::getOsVersion(), '20.04', '<')) {
            return false;
        }

        if (version_compare(self::getOsVersion(), '20.10', '>=')) {
            return false;
        }

        return true;
    }

    /**
     * Confirm or deny this platform is Debian 8 (Jessie)
     *
     * @return boolean
     */
    public static function isJessie()
    {
        if (\pm_ProductInfo::getOsName() != self::DEBIAN) {
            return false;
        }

        if (version_compare(self::getOsVersion(), '8', '<')) {
            return false;
        }

        if (version_compare(self::getOsVersion(), '9', '>=')) {
            return false;
        }

        return true;
    }

    /**
     * Confirm or deny this platform is Red Hat Enterprise Linux 7 or CentOS 7 (Maipo)
     *
     * @param boolean $strict Only treat Red Hat Enterprise Linux 7 as genuinely maipo.
     *
     * @return boolean
     */
    public static function isMaipo($strict = false)
    {
        $osName = \pm_ProductInfo::getOsName();

        if ($strict && $osName != self::REDHAT) {
            return false;
        }

        if ($osName != self::REDHAT && $osName != self::CENTOS) {
            return false;
        }

        if (version_compare(self::getOsVersion(), '7', '<')) {
            return false;
        }

        if (version_compare(self::getOsVersion(), '8', '>=')) {
            return false;
        }

        return true;
    }

    /**
     * Confirm or deny this platform is Red Hat Enterprise Linux 8 or CentOS 8 (Ootpa)
     *
     * @param boolean $strict Only treat Red Hat Enterprise Linux 8 as genuinely ootpa.
     *
     * @return boolean
     */
    public static function isOotpa($strict = false)
    {
        $osName = \pm_ProductInfo::getOsName();

        if ($strict && $osName != self::REDHAT) {
            return false;
        }

        if ($osName != self::REDHAT && $osName != self::CENTOS) {
            return false;
        }

        if (version_compare(self::getOsVersion(), '8', '<')) {
            return false;
        }

        if (version_compare(self::getOsVersion(), '9', '>=')) {
            return false;
        }

        return true;
    }

    /**
     * Confirm or deny this platform is Debian 9 (Stretch)
     *
     * @return boolean
     */
    public static function isStretch()
    {
        if (\pm_ProductInfo::getOsName() != self::DEBIAN) {
            return false;
        }

        if (version_compare(self::getOsVersion(), '9', '<')) {
            return false;
        }

        if (version_compare(self::getOsVersion(), '10', '>=')) {
            return false;
        }

        return true;
    }

    /**
     * Confirm or deny this platform is Ubuntu 16.04 LTS (Xenial)
     *
     * @return boolean
     */
    public static function isXenial()
    {
        if (\pm_ProductInfo::getOsName() != self::UBUNTU) {
            return false;
        }

        if (version_compare(self::getOsVersion(), '16.04', '<')) {
            return false;
        }

        if (version_compare(self::getOsVersion(), '16.10', '>=')) {
            return false;
        }

        return true;
    }

    /**
     * Get the distribution. Provided for consistency in using Phlesk.
     *
     * Really only needs to return `\pm_ProductInfo::getOsName()` without modification.
     *
     * Does not change the case, so extensions should use {@link \Phlesk\Platform::isPlatform()}
     * instead, which does a case-insensitive comparison.
     *
     * @return string
     */
    public static function getDistribution()
    {
        return \pm_ProductInfo::getOsName();
    }

    /**
     * Get the platform.
     *
     * @return string
     */
    public static function getPlatform()
    {
        switch (\pm_ProductInfo::getOsName()) {
            case self::CENTOS:
            case self::REDHAT:
                if (self::isOotpa()) {
                    return self::OOTPA;
                }

                if (self::isMaipo()) {
                    return self::MAIPO;
                }

                self::logUnknownPlatform();

                return self::UNKNOWN;

            case self::DEBIAN:
                if (self::isBuster()) {
                    return self::BUSTER;
                }

                if (self::isStretch()) {
                    return self::STRETCH;
                }

                if (self::isJessie()) {
                    return self::JESSIE;
                }

                self::logUnknownPlatform();

                return self::UNKNOWN;

            case self::UBUNTU:
                if (self::isFocal()) {
                    return self::FOCAL;
                }

                if (self::isBionic()) {
                    return self::BIONIC;
                }

                if (self::isXenial()) {
                    return self::XENIAL;
                }

                self::logUnknownPlatform();

                return self::UNKNOWN;

            default:
                self::logUnknownPlatform();

                return self::UNKNOWN;
        }
    }

    /**
     * Get the operating system version (with fixes).
     *
     * @return string
     */
    public static function getOsVersion()
    {
        $osVersion = \pm_ProductInfo::getOsVersion();

        // may incidentally return 'el7' for example.
        $osVersion = str_replace('el', '', $osVersion);

        return $osVersion;
    }

    /**
     * Import a GPG public key used for package and/or repository signing.
     *
     * @param string $uri A URI.
     *
     * @return void
     */
    public static function importPackageKey($uri)
    {
        switch (self::getDistribution()) {
            case self::CENTOS:
            case self::REDHAT:
                \Phlesk::exec(['rpm', '--import', $uri]);
                break;

            case self::DEBIAN:
            case self::UBUNTU:
                $varDir = \Phlesk\Context::getVarDir();

                \Phlesk::exec(['wget', "-O{$varDir}/gpgkey", $uri]);
                \Phlesk::exec(['apt-key', 'add', "{$varDir}/gpgkey"]);

                break;
        }
    }

    /**
     * Confirm or deny this platform is a particular distribution.
     *
     * @param string $distribution "centos", "debian", etc. Case-insensitive.
     * @return boolean
     */
    public static function isDistribution($distribution)
    {
        return (strtolower(self::getDistribution()) == strtolower($distribution));
    }

    /**
     * Confirm or deny this platform is a particular platform.
     *
     * @param string $platform "maipo", "jessie", etc. Case-insensitive.
     *
     * @return boolean
     */
    public static function isPlatform($platform)
    {
        return (strtolower(self::getPlatform()) == strtolower($platform));
    }

    /**
     * Confirm or deny the platform uses Apt.
     *
     * Primarily the case for Debian and Ubuntu, but available on a wider range of deployments than
     * Aptitude.
     *
     * Basically all Debian and Ubuntu systems.
     *
     * @return boolean
     */
    public static function usesApt()
    {
        if (self::isDistribution(self::DEBIAN)) {
            return true;
        }

        if (self::isDistribution(self::UBUNTU)) {
            return true;
        }

        return false;
    }

    /**
     * Confirm or deny the platform uses Aptitude. Only returns true if it is also currently
     * available.
     *
     * Primarily the case for Debian and Ubuntu.
     *
     * @return boolean
     */
    public static function usesAptitude()
    {
        if (!\Phlesk\Package::isInstalled('aptitude')) {
            return false;
        }

        if (self::isDistribution(self::DEBIAN)) {
            return true;
        }

        if (self::isDistribution(self::UBUNTU)) {
            return true;
        }

        return false;
    }

    /**
     * Confirm or deny the platform uses Dandified YUM (DNF)
     *
     * Primarily the case for RHEL 8 and CentOS 8.
     *
     * @return boolean
     */
    public static function usesDnf()
    {
        if (self::isOotpa()) {
            return true;
        }

        return false;
    }

    /**
     * Confirm or deny the platform uses Yellowdog Updater Modified (YUM)
     *
     * Primarily the case for RHEL 7 and CentOS 7.
     *
     * @return boolean
     */
    public static function usesYum()
    {
        if (self::isMaipo()) {
            return true;
        }

        if (self::isSantiago()) {
            return true;
        }

        if (self::isTikanga()) {
            return true;
        }

        return false;
    }

    /**
     * Simply log a debug message about the platform not currently being supported.
     *
     * Include the OS name and version as reported by \pm_ProductInfo for clarity.
     *
     * @return null
     */
    private static function logUnknownPlatform()
    {
        \pm_Log::debug(
            sprintf(
                "Platform %s version %s is not supported.",
                \pm_ProductInfo::getOsName(),
                \pm_ProductInfo::getOsVersion()
            )
        );
    }
}
