<?php

/**
 * Supplement functions of \pm_Domain.
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
 * Supplemental facilities for \pm_Domain.
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
class Domain
{
    /**
     * Emit an event notification letting other extensions know the current context is being
     * disabled for the domain.
     *
     * Uses a `\pm_ActionLog` submission, for which the providing extension will require definition
     * of, in `plib/hooks/ActionLog.php`, at minimum:
     *
     * ```php
     * <?php
     * class Modules_Foo_ActionLog extends \Phlesk\Hook\ActionLog {}
     * ```
     *
     * For other extensions to listen to and receive the event notification, they will need to
     * include in their `Modules_Bar_EventListener->filterActions()` result, for example;
     *
     * ```php
     * <?php
     * class Modules_Bar_EventListener implements EventListener
     * {
     *     public function filterActions()
     *     {
     *         return [
     *             (...)
     *             'ext_foo_disable_domain',
     *             (...)
     *         ];
     *     }
     * }
     * ```
     *
     * @param \pm_Domain $domain The domain to disable integration for.
     *
     * @return null
     *
     * @see \Phlesk\Hook\ActionLog
     */
    public static function disableIntegration(\pm_Domain $domain)
    {
        $source = \Phlesk\Context::getModuleId();

        \pm_Log::debug("Triggering event 'disable_domain'");
        \pm_ActionLog::submit('disable_domain', $domain->getId(), [$source], []);
    }

    /**
     * Emit an event notification letting other extensions know the current context is being
     * disabled for the domain.
     *
     * Uses a `\pm_ActionLog` submission, for which the providing extension will require definition
     * of, in `plib/hooks/ActionLog.php`, at minimum:
     *
     * ```php
     * <?php
     * class Modules_Foo_ActionLog extends \Phlesk\Hook\ActionLog {}
     * ```
     *
     * For other extensions to listen to and receive the event notification, they will need to
     * include in their `Modules_Bar_EventListener->filterActions()` result, for example;
     *
     * ```php
     * <?php
     * class Modules_Bar_EventListener implements EventListener
     * {
     *     public function filterActions()
     *     {
     *         return [
     *             (...)
     *             'ext_foo_enable_domain',
     *             (...)
     *         ];
     *     }
     * }
     * ```
     *
     * @param \pm_Domain $domain The domain to enable integration for.
     *
     * @return null
     *
     * @see \Phlesk\Hook\ActionLog
     */
    public static function enableIntegration(\pm_Domain $domain)
    {
        $source = \Phlesk\Context::getModuleId();

        \pm_Log::debug("Triggering event 'enable_domain'");
        \pm_ActionLog::submit('enable_domain', $domain->getId(), [], [$source]);
    }

    /**
     * Determine if a domain actually has hosting.
     *
     * Needed because `\pm_Domain::hasHosting()` does not accurately reflect the then-current
     * status.
     *
     * @param \pm_Domain $domain Determine whether the domain currently has hosting.
     *
     * @return boolean
     */
    public static function hasHosting(\pm_Domain $domain)
    {
        $hasHosting = $domain->hasHosting();

        // If hosting still exists, no need to dig any further.
        if ($hasHosting) {
            return $hasHosting;
        }

        // \pm_Domain::getByGuid would log an error if the domain no longer exists.
        $domain = \Phlesk::getDomainByGuid($domain->getGuid());

        if (!$domain) {
            // The domain has already disappeared
            return $hasHosting;
        } else {
            // Avoid recursiveness
            $domain = \pm_Domain::getByGuid($domain->getGuid());
        }

        if ($hasHosting != $domain->hasHosting()) {
            \pm_Log::debug("\Phlesk\Domain::hasHosting(): Good thing you're here.");
        }

        return $domain->hasHosting();
    }

    /**
     * Determine if a domain actually has mail service enabled.
     *
     * Needed because the function doesn't exist for \pm_Domain.
     *
     * @param \pm_Domain $domain The domain for which to determine mail service availability
     *
     * @return Boolean
     */
    public static function hasMailService(\pm_Domain $domain)
    {
        $rpc = new \Phlesk\RPC();
        $result = $rpc->requestMailServiceForDomain($domain->getId());
        return true;
    }

    /**
     * Determine if the domain is the primary domain for a subscription.
     *
     * @param \pm_Domain $domain The domain to test.
     *
     * @return boolean
     */
    public static function isPrimary(\pm_Domain $domain)
    {
        $guid = $domain->getGuid();
        $primary = \Phlesk::getPrimaryDomain($guid);

        if ($primary) {
            return ($guid = $primary->getGuid());
        }

        \pm_Log::debug("Domain {$domain->getName()} isn't a primary?");

        return false;
    }

    /**
     * Determine if the domain is a wildcard domain.
     *
     * @param \pm_Domain $domain The domain to test.
     *
     * @return boolean
     */
    public static function isWildcard(\pm_Domain $domain)
    {
        return substr($domain->getName(), 0, 1) == '_';
    }

    /**
     * List the user accounts for this domain.
     *
     * @param \pm_Domain $domain  The domain to list users for.
     * @param Bool       $decrypt Decrypt the password.
     *
     * @return array
     */
    public static function listUsers(\pm_Domain $domain, $decrypt = false)
    {
        $users = [];

        $db = \pm_Bootstrap::getDbAdapter();

        $query = "
            SELECT
                CONCAT(m.mail_name, '@', d.name) AS email,
                a.password AS password
            FROM mail m
                INNER JOIN accounts a ON m.account_id = a.id
                INNER JOIN domains d ON m.dom_id = d.id
            WHERE d.id = {$domain->getId()}
        ";

        $result = $db->query($query);

        while ($row = $result->fetch()) {
            $users[] = array(
                'email' => $row['email'],
                'password' => ($decrypt ? \pm_Crypt::decrypt($row['password']) : $row['password'])
            );
        }

        return $users;
    }
}
