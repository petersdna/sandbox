<?php
/**
 * @package         JFBConnect
 * @copyright (c)   2009-2015 by SourceCoast - All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @version         Release v6.5.3
 * @build-date      2015/12/19
 */

// no direct access
if (!(defined('_JEXEC') || defined('ABSPATH'))) {     die('Restricted access'); };

$input = JFactory::getApplication()->input;
$task = $input->getCmd('task', '');
require_once (dirname(__FILE__) . '/controller.php');

if (!$task)
{
    $view = $input->getCmd('view', '');
    // Build up the controller / task dynamically
    if ($view == "loginregister" || $view == 'opengraph' || $view == 'account')
        $input->set('task', $view . '.' . 'display');
}


$controller = JControllerLegacy::getInstance('jfbconnect');

$controller->execute($input->get('task'));
$controller->redirect();