<?php

/**
 * Provide a standardized extension of \pm_ActionLog
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
namespace Phlesk\Hook;

/**
 * Provide a standardized emission of custom events for extensions.
 *
 * Rather than extending `\pm_Hook_ActionLog`, extensions may extend `\Phlesk\Hook\ActionLog` to
 * provide a standardized set of custom events;
 *
 * ```php
 * class Modules_Kolab_ActionLog extends \Phlesk\Hook\ActionLog
 * {
 * }
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
 * @see       \pm_Hook_ActionLog
 */
class ActionLog extends \pm_Hook_ActionLog
{
    /**
     * Disclose the events we're planning on submitting.
     *
     * Currently supports enabling (the function of an extension for) a domain, and disabling.
     *
     * Use `ext_myextension_enable_domain` and `ext_myextension_disable_domain` as the actions
     * for objects in your EventListener.
     *
     * @return Array
     *
     * @see \Phlesk\Domain::disableIntegration()
     * @see \Phlesk\Domain::enableIntegration()
     */
    public function getEvents()
    {
        return [
            'enable_domain' => 'Enable Integration for Domain',
            'disable_domain' => 'Disable Integration for Domain'
        ];
    }
}
