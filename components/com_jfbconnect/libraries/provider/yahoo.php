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

class JFBConnectProviderYahoo extends JFBConnectProvider
{
    function __construct()
    {
        $this->name = "Yahoo";
        $this->usernamePrefix = "yh_";
        parent::__construct();
    }

    function setupAuthentication()
    {
        $options = new JRegistry();
        $options->def('accessTokenURL', 'https://api.login.yahoo.com/oauth/v2/get_token');
        $options->def('authenticateURL', 'https://api.login.yahoo.com/oauth/v2/request_auth');
        $options->def('authoriseURL', 'https://api.login.yahoo.com/oauth/v2/request_auth');
        $options->def('requestTokenURL', 'https://api.login.yahoo.com/oauth/v2/get_request_token');
        $options->set('consumer_key', $this->appId);
        $options->set('consumer_secret', $this->secretKey);
        $options->set('callback', JURI::base() . 'index.php?option=com_jfbconnect&task=authenticate.callback&provider=yahoo&state=' . JSession::getFormToken());
        $options->set('sendheaders', true); // Enabled for now. Should probably switch to force the redirect so we can just detect if user is logged in

        $headers = array();
        $headers['Content-Type'] = 'application/xml';
        $options->set('headers', $headers);

        $this->client = new JFBConnectProviderYahooOauth1($options);

        $token = JFactory::getApplication()->getUserState('com_jfbconnect.' . strtolower($this->name) . '.token', null);
        if ($token)
        {
            $token = (array)json_decode($token);
            $this->client->setToken($token);
        }
    }

    /* getProviderUserId
    * Gets the provider User IdFacebook. This is regardless of whether they are mapped to an
    *  existing Joomla account.
    */
    function getProviderUserId()
    {
        $app = JFactory::getApplication();
        if ($app->getUserState('com_jfbconnect.yahoo.provider_id', null) == null)
        {
            $token = $this->client->getToken();
            if (empty($token))
                return false;

            $creds = $this->client->verifyCredentials();
            if ($creds->code == 200)
            {
                $id = $creds->body->guid;
            }

            if (!empty($id))
                $app->setUserState('com_jfbconnect.yahoo.provider_id', $id);

            else
                $app->setUserState('com_jfbconnect.yahoo.provider_id', null);
        }
        return $app->getUserState('com_jfbconnect.yahoo.provider_id');
    }
}