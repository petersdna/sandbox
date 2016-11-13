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

class JFBConnectControllerAccount extends JFBConnectController
{
    function display($cachable = false, $urlparams = false)
    {
        if(JFactory::getUser()->guest)
            return;

        parent::display();
    }

    // User is choosing to unlink a user map for a given provider
    public function unlink()
    {
        JSession::checkToken( 'get' ) or die( 'Invalid Token' );

        $provider = JRequest::getString('provider', '');
        $user = JFactory::getUser();
        $userModel = JFBConnectModelUserMap::getUser($user->get('id'), $provider);
        $provider_user_id = $userModel->getProviderUserId($user->get('id'), $provider);

        $userModel->deleteMapping($provider_user_id, $provider);

        JFBCFactory::log(JText::sprintf('COM_JFBCONNECT_ACCOUNT_LINKED_ACCOUNTS_UNLINK_MESSAGE', $provider));

        $this->setRedirect(JRoute::_('index.php?option=com_jfbconnect&view=account'));
    }
}
