<?php
/**
 * @package         JFBConnect
 * @copyright (c)   2009-2015 by SourceCoast - All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @version         Release v6.5.3
 * @build-date      2015/12/19
 */

// Check to ensure this file is included in Joomla!
if (!(defined('_JEXEC') || defined('ABSPATH'))) {     die('Restricted access'); };

//Powered By
$showPoweredBy = $params->get('showPoweredByLink');
$showJfbcPoweredBy = (($showPoweredBy == '2' && JFBCFactory::config()->get('show_powered_by_link')) || ($showPoweredBy == '1'));

if($showJfbcPoweredBy)
{
    //Affiliate ID
    $link = JFBConnectUtilities::getAffiliateLink(JFBCFactory::config()->get('affiliate_id'), EXT_JFBCONNECT);

    JFBConnectUtilities::loadLanguage('com_jfbconnect');

    echo '<div class="powered-by">'.JText::_('COM_JFBCONNECT_POWERED_BY').' <a target="_blank" href="'.$link.'" title="Facebook for Joomla">JFBConnect</a></div>';
}