<?php

namespace Phlesk\Hook;

class CustomInfo implements \pm_Hook_Interface
{
    /*
     * Hook in for providing our statistics in JSON format.
     *
     * @return string
     */
    public function getInfo()
    {
        return json_encode($this->getStatistics());
    }

    /*
     * Hook in for the extension to provide statistics that it knows about.
     *
     * * The version of the extension installed.
     * * The licensing status of the extension, if any.
     * * The installation status of the extension, if any.
     * * The number of domains with any of the the extension permissions enabled.
     * * The total number of domains.
     * * The number of domains that are;
     *     * a primary domain,
     *     * have hosting,
     *     * have mail service,
     *     * are wildcard domains (not yet implemented, -1),
     *     * are IDN domains,
     * * The number of users for each of the permissions.
     * * The total number of users.
     * * The automatic update configuration.
     *
     * @return array
     */
    public function getStatistics()
    {
        $stats = [
            'licensed' => $this->isLicensed(),
            'numLicensed' => $this->numLicensed(),
            'version' => $this->version(),
            'installed' => $this->isInstalled(),
            'updateConfig' => $this->updateConfig()
        ];

        $domains = \Phlesk::getAllDomains(false, false, false);

        $stats['domains'] = [
            'numTotal' => sizeof($domains),
            'numPrimary' => 0,
            'numHosting' => 0,
            'numMailservice' => 0,
            'numWildcard' => -1,
            'numIDN' => 0,
            'numEligible' => 0,
            'permissions' => [],
        ];

        $stats['users'] = [
            'numTotal' => 0,
            'numPrimary' => 0,
            'numHosting' => 0,
            'numMailservice' => 0,
            'numWildcard' => -1,
            'numIDN' => 0,
            'numEligible' => 0,
            'permissions' => [],
        ];

        $permissions = \Phlesk\Extension::getPermissions(\Phlesk\Context::getModuleId());

        foreach ($permissions as $permission) {
            $stats['domains']['permissions'][$permission] = 0;
            $stats['users']['permissions'][$permission] = 0;
        }

        foreach ($domains as $domain) {
            $stats['users']['numTotal'] += sizeof(
                \Phlesk\Domain::listUsers($domain, $decrypt = false)
            );

            foreach ($permissions as $permission) {
                if ($domain->hasPermission($permission)) {
                    $stats['domains']['permissions'][$permission] += 1;
                    $stats['users']['permissions'][$permission] += sizeof(
                        \Phlesk\Domain::listUsers($domain, $decrypt = false)
                    );
                }
            }

            if (\Phlesk\Domain::isPrimary($domain)) {
                $stats['domains']['numPrimary'] += 1;
                $stats['users']['numPrimary'] += sizeof(
                    \Phlesk\Domain::listUsers($domain, $decrypt = false)
                );
            }

            if (\Phlesk\Domain::hasHosting($domain)) {
                $stats['domains']['numHosting'] += 1;
                $stats['users']['numHosting'] += sizeof(
                    \Phlesk\Domain::listUsers($domain, $decrypt = false)
                );
            }

            if (\Phlesk\Domain::hasMailservice($domain)) {
                $stats['domains']['numMailservice'] += 1;
                $stats['users']['numMailservice'] += sizeof(
                    \Phlesk\Domain::listUsers($domain, $decrypt = false)
                );
            }

            if (\Phlesk\Domain::isWildcard($domain)) {
                $stats['domains']['numWildcard'] += 1;
                $stats['users']['numWildcard'] += sizeof(
                    \Phlesk\Domain::listUsers($domain, $decrypt = false)
                );
            }

            if ($domain->getName() != $domain->getDisplayName()) {
                $stats['domains']['numIDN'] += 1;
                $stats['users']['numIDN'] += sizeof(
                    \Phlesk\Domain::listUsers($domain, $decrypt = false)
                );
            }

            if (
                    \Phlesk\Domain::isPrimary($domain)
                    && \Phlesk\Domain::hasHosting($domain)
                    && \Phlesk\Domain::hasMailservice($domain)
                    && !\Phlesk\Domain::isWildcard($domain)
            ) {
                $stats['domains']['numEligible'] += 1;
                $stats['users']['numEligible'] += sizeof(
                    \Phlesk\Domain::listUsers($domain, $decrypt = false)
                );
            }
        }

        return $stats;
    }

    /*
     * Returns whether the extension considers itself installed (true) or not (false).
     *
     * If not applicable, returns null.
     *
     * @return bool|null
     */
    private function isInstalled()
    {
        return \Phlesk\Extension::isInstalled(\Phlesk\Context::getModuleId());
    }

    /*
     * Returns whether the extension is licenced (true) or not (false).
     *
     * If not applicable, returns null.
     *
     * @return bool|null
     */
    private function isLicensed()
    {
        $extension = \Phlesk\Context::getModuleId();
        $class_name = sprintf("Modules_%s_License", ucfirst(strtolower($extension)));

        if (!class_exists($class_name)) {
            return null;
        }

        $instance = $class_name::getInstance();

        if (!method_exists($instance, 'isLicensed')) {
            return null;
        }

        return $instance::isLicensed();
    }

    /*
     * Return the number of licenses.
     *
     * @return int|string
     */
    private function numLicensed()
    {
        $extension = \Phlesk\Context::getModuleId();
        $class_name = sprintf("Modules_%s_License", ucfirst(strtolower($extension)));

        if (!class_exists($class_name)) {
            return "N/A";
        }

        $instance = $class_name::getInstance();

        if (!method_exists($instance, 'licenseLimit')) {
            return "N/A";
        }

        return $instance::licenseLimit();
    }

    /*
     * Returns the update configuration for this system.
     *
     * @return int
     */
    private function updateConfig()
    {
        $db = \pm_Bootstrap::getDbAdapter();

        // (bitflip, absolute) update/upgrade configuration
        $uc = 0;

        // is the updater enabled? add 2^0.
        $rows = $db->query(
            "
                SELECT val FROM misc
                WHERE param = 'disable_updater'
                    AND val = 'false'
            "
        );

        $uc += ($rows->rowCount() == 0 ?: (1 << 0));
        ;

        // are system packages going to be updated? add 2^1.
        $rows = $db->query(
            "
                SELECT val FROM misc
                WHERE param = 'automaticSystemPackageUpdates'
                    AND val = 'true'
            "
        );

        $uc += ($rows->rowCount() == 0 ?: (1 << 1));

        // are third party packages updated? add 2^2.
        $rows = $db->query(
            "
                SELECT val FROM misc
                WHERE param = 'autoupgrade_third_party'
                    AND val = 'true'
            "
        );

        $uc += ($rows->rowCount() == 0 ?: (1 << 2));

        // are repository origins locked? add 2^3.
        $rows = $db->query(
            "
                SELECT val FROM misc
                WHERE param = 'systemPackageUpdatesSafeOnly'
                    AND val = 'true'
            "
        );

        $uc += ($rows->rowCount() == 0 ? 0 : (1 << 3));

        // for each branch from early to late, add 2^$x.
        $rows = $db->query(
            "
                SELECT val FROM misc
                WHERE param = 'autoupgrade_branch'
            "
        );

        $val = $rows->fetch();

        switch ($val['val']) {
            // phpcs:ignore
            case 'current':
                $uc += (1 << 4);
                break;

            // phpcs:ignore
            case 'release':
                $uc += (1 << 5);
                break;

            // phpcs:ignore
            case 'stable';
                $uc += (1 << 6);
                break;

            // phpcs:ignore
            default:
                break;
        }

        // (bitflip, absolute) update/upgrade configuration
        return $uc;
    }

    /*
     * Returns the version number for the extension.
     *
     * @return string
     */
    private function version()
    {
        $db = \pm_Bootstrap::getDbAdapter();

        $query = sprintf(
            "SELECT version, `release` FROM Modules WHERE name = '%s'",
            \Phlesk\Context::getModuleId()
        );

        $values = $db->query($query)->fetch();

        return sprintf("%s-%s", $values['version'], $values['release']);
    }
}
