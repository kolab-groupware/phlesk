<?php

namespace Phlesk;

/**
* A module for licensing.
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
class License
{
    private static $license;
    private static $licenseCount;
    private static $licenseLimit;
    private static $expiryDate;
    private static $renewalDate;

    /**
     * Determines if the current license is actually current.
     *
     * @return boolean: true for a current license.
     */
    public static function isCurrent()
    {
        if (!self::$expiryDate) {
            self::expireDate();
        }

        if (!self::$renewalDate) {
            self::renewDate();
        }

        $now = new DateTime("now");

        if ($now > self::$expiryDate) {
            \pm_Log::debug("License expired.");
            return false;
        }

        if ($now > self::$renewalDate) {
            \pm_Log::debug("License pending renewal.");
            return true;
        }

        return true;
    }

    /**
     * Determines if the system is fully licensed.
     *
     * @return boolean
     */
    public static function isLicensed()
    {
        if (!self::$license) {
            $result = self::getLicense();
            if (!$result) {
                \pm_Log::debug("Can not validate license.");
                return false;
            }
        }

        if (!self::isCurrent()) {
            return false;
        }

        return true;
    }

    /**
     * Determines if the current license is valid.
     *
     * @return boolean
     */
    public static function isValid()
    {
        if (!self::$license) {
            if (!self::getLicense()) {
                return false;
            }
        }

        if (!self::isCurrent()) {
            return false;
        }


        // The limitations encapsulated in the license body are never 0.
        if (self::licenseLimit() == 0) {
            return false;
        }

        return true;
    }

    /**
     * Returns the number of mailboxes for the given domains.
     *
     * The given list of domains will be filtered for active domains.
     *
     * Reimplement for additional domain filtering.
     *
     * @param Array domains List of domains to count mailboxes for.
     *
     * @return integer
     */
    protected static function licenseCountForDomains($domains)
    {
        $api = \pm_ApiRpc::getService();
        $count = 0;
        foreach ($domains as $domain) {
            if (!$domain->isActive()) {
                continue;
            }

            $request = "<webspace><get>" .
                "<filter><id>" . $domain->getId() . "</id>" .
                "</filter><dataset><stat/></dataset>" .
                "</get></webspace>";

            $result = $api->call($request, 'admin');
            $count += $result->webspace->get->result->data->stat->box;
        }
        return $count;
    }

    /**
     * Returns the number of mailboxes currently entitled on this system as an integer.
     *
     * @return integer
     */
    public static function licenseCount()
    {
        if (self::$licenseCount) {
            return self::$licenseCount;
        }

        self::$licenseCount = self::licenseCountForDomains(\Phlesk::getAllDomains(true));
        return self::$licenseCount;
    }

    /**
     * Returns whether or not the current count is within a magic threshold of the maximum licensed.
     *
     * @return boolean
     */
    public static function licenseWarningThreshold()
    {
        if (self::licenseLimit() < 0) {
            return false;
        }

        if (self::licenseLimit() <= 20) {
            $count_threshold = floor(self::licenseLimit() * 0.8);
        } elseif (self::licenseLimit() <= 200) {
            $count_threshold = floor(self::licenseLimit() * 0.9);
        } else {
            $count_threshold = floor(self::licenseLimit() * 0.95);
        }

        if (self::licenseCount() >= $count_threshold) {
            return true;
        }

        return false;
    }

    /**
     * Returns the limitation for the license on this system.
     *
     * Returns -1 if the license is unlimited, or the limit otherwise.
     * @return integer
     */
    public static function licenseLimit()
    {
        if (self::$licenseLimit) {
            return self::$licenseLimit;
        }

        if (!self::$license) {
            if (!self::getLicense()) {
                return 0;
            }
        }

        if (!self::isCurrent()) {
            return 0;
        }

        $body = self::$license['key-body'];

        $body_parsed = openssl_x509_parse($body);

        if (!array_key_exists('extensions', $body_parsed)) {
            self::$licenseLimit = (int)(explode(' ', self::$license['app'])[1]);
            return self::$licenseLimit;
        }

        if (!array_key_exists('nsComment', $body_parsed['extensions'])) {
            self::$licenseLimit = (int)(explode(' ', self::$license['app'])[1]);
            return self::$licenseLimit;
        }

        $comment = json_decode(
            base64_decode($body_parsed['extensions']['nsComment']),
            true
        );

        self::$licenseLimit = intval($comment['users']);
        return self::$licenseLimit;
    }

    /**
     * Returns the date (and time?) renewal is due.
     */
    public static function renewDate()
    {
        if (self::$renewalDate) {
            return self::$renewalDate->format('F j, Y');
        }

        if (!self::$license) {
            if (!self::getLicense()) {
                \pm_Log::warn(
                    "Could not obtain license renewal date. No license available."
                );

                return false;
            }
        }

        $body = openssl_x509_parse(self::$license['key-body']);

        $dt = new DateTime("@{$body['validFrom_time_t']}");
        $dt->add(new DateInterval('P1M'));

        // Ensure we display the correct expiry/renewal for longer lasting licenses (such as faker)
        $dte = new DateTime("@{$body['validTo_time_t']}");
        $dte->sub(new DateInterval('P14D'));

        self::$renewalDate = $dte > $dt ? $dte : $dt;

        return self::$renewalDate->format('F j, Y');
    }

    /**
     * Returns the expiration date for the current license.
     *
     * @return string|null
     */
    public static function expireDate()
    {
        if (self::$expiryDate) {
            return self::$expiryDate->format('F j, Y');
        }

        if (!self::$license) {
            if (!self::getLicense()) {
                \pm_Log::warn(
                    "Could not obtain license expiry date. No license available."
                );

                return null;
            }
        }

        $body = openssl_x509_parse(self::$license['key-body']);

        self::$expiryDate = new DateTime("@{$body['validTo_time_t']}");

        return self::$expiryDate->format('F j, Y');
    }

    /**
     * Activate the license, such that yum and apt and friends work.
     *
     * Returns true on success
     *
     * @return boolean
     */
    public static function activate()
    {
        if (!self::$license) {
            if (!self::getLicense()) {
                \pm_Log::warn(
                    "License could not be activated; no license available"
                );

                return false;
            }
        }

        return self::activateSystem();
    }

    /**
     * Execute steps necessary to active the sytems
     *
     * Implement in subclass
     *
     * @return boolean
     */
    protected static function activateSystem()
    {
        return true;
    }

    /**
     * Obtain actual license.
     *
     * Returns true on success
     *
     * @return boolean
     */
    public static function getLicense()
    {
        $license = pm_License::getAdditionalKey(pm_Context::getModuleId());

        $module = \Phlesk\Context::getModuleId();
        $extension = ucfirst(strtolower($module));
        $filterClass = "Modules_{$extension}_LicenseFaker";

        if (class_exists($filterClass)) {
            $cert = call_user_func_array("{$filterClass}::until_we_maker", []);

            self::$license = [
                'key-body' => $cert
            ];

            self::activate();
            return true;
        }

        if (!$license) {
            self::$license = null;
            return false;
        } else {
            self::$license = $license->getProperties();
            self::activate();
            return true;
        }
    }
}
