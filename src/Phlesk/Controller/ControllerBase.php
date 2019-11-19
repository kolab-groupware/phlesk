<?php

/**
 * Controller Base
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

namespace Phlesk\Controller;

class ControllerBase extends \pm_Controller_Action
{
    protected function disableRendering()
    {
        // Disable decorating the returned html
        $this->getHelper('Layout')->disableLayout();

        // Don't render at all, we already use echo for that below
        $this->getHelper('ViewRenderer')->setNoRender();
    }

    protected function getPartialRenderer()
    {
        return new \Zend_View(['scriptPath' => \pm_Context::getPlibDir() . 'views']);
    }
}
