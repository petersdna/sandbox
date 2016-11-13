<?php
/**
 * @package        JFBConnect
 * @copyright (C) 2009-2013 by Source Coast - All rights reserved
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

class plgsocialprofilesjomsocialInstallerScript
{
    public function postFlight()
    {
        JFile::copy(JPATH_SITE . '/plugins/socialprofiles/jomsocial/jomsocial_rule.xml',
                JPATH_SITE . '/components/com_jfbconnect/jomsocial_rule.xml');
    }
}