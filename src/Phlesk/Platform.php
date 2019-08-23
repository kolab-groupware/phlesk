<?php

namespace Phlesk;

class Platform
{
    const UNKNOWN     = 'UNKNOWN';
    const CENTOS7     = 'CENTOS7';
    const RHEL7       = 'RHEL7';
    const DEBIAN8     = 'DEBIAN8';
    const DEBIAN9     = 'DEBIAN9';
    const UBUNTU1604  = 'UBUNTU1604';
    const UBUNTU1804  = 'UBUNTU1804';

    private static function matchPlatform($os, $minimum_version, $maximum_version = null)
    {
        \pm_Log::debug("Checking: {$os} {$minimum_version}");

        $os_name = \pm_ProductInfo::getOsName();

        \pm_Log::debug("OS Name: {$os_name}");

        if ($os_name != $os) {
            return false;
        }

        $os_version = \pm_ProductInfo::getOsVersion();
        $os_version = str_replace('el', '', $os_version);
        \pm_Log::debug("OS Version: {$os_version}");

        if (!$maximum_version) {
            $maximum_version = $minimum_version + 1;
        }

        if (version_compare($os_version, "{$minimum_version}", '>=')) {
            if (version_compare($os_version, "{$maximum_version}", '<')) {
                \pm_Log::debug("Range version match.");
                return true;
            }
        }

        \pm_Log::debug("No version match.");
        return false;
    }

    public static function getPlatform()
    {
        if (self::matchPlatform(\pm_ProductInfo::OS_CENTOS, 7)) {
            return self::CENTOS7;
        }
        if (self::matchPlatform(\pm_ProductInfo::OS_REDHAT, 7)) {
            return self::RHEL7;
        }
        if (self::matchPlatform(\pm_ProductInfo::OS_DEBIAN, 8)) {
            return self::DEBIAN8;
        }
        if (self::matchPlatform(\pm_ProductInfo::OS_DEBIAN, 9)) {
            return self::DEBIAN9;
        }
        if (self::matchPlatform(\pm_ProductInfo::OS_UBUNTU, '16.04')) {
            return self::UBUNTU1604;
        }
        if (self::matchPlatform(\pm_ProductInfo::OS_UBUNTU, '18.04')) {
            return self::UBUNTU1804;
        }
        \pm_Log::err("Unknown platform.");
        return self::UNKNOWN;
    }

    public static function isPlatform($platform)
    {
        return (self::getPlatform() == $platform);
    }
}
