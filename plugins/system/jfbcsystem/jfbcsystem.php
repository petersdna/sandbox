<?php
/**
 * @package         JFBConnect
 * @copyright (c)   2009-2015 by SourceCoast - All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @build-date      2015/12/19
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.event.plugin');
jimport('sourcecoast.openGraph');
jimport('sourcecoast.easyTags');
jimport('sourcecoast.articleContent');
jimport('joomla.filesystem.file');

class plgSystemJFBCSystem extends JPlugin
{
    var $tagsToReplace;
    var $metadataTagsToStrip = array('JFBC', 'JLinked', 'SC');
    var $scTags;

    static $cssIncluded = false;

    function __construct(& $subject, $config)
    {
        $factoryFile = JPATH_ROOT . '/components/com_jfbconnect/libraries/factory.php';
        if (!JFile::exists($factoryFile))
        {
            JFactory::getApplication()->enqueueMessage("File missing: " . $factoryFile . "<br/>Please re-install JFBConnect or disable the JFBCSystem Plugin", 'error');
            return; // Don't finish loading this plugin to prevent other errors
        }
        require_once($factoryFile);
        JFBCFactory::initializeJoomla();

        parent::__construct($subject, $config);

        $scTagArray = array('SCPinterest');
        foreach(JFBCFactory::getAllProviders() as $provider)
            $scTagArray[] = 'SC'.$provider->name;
        $this->scTags = implode('|', $scTagArray);
    }

    public function onAfterInitialise()
    {
        $app = JFactory::getApplication();
        if (!$app->isAdmin())
        {
            // Need to disable Page caching so that values fetched from Facebook are not saved for the next user!
            // Do this by setting the request type to POST. In the Cache plugin, it's checked for "GET". can't be that.
            $option = JRequest::getCmd("option");
            $view = JRequest::getCmd("view");
            if ($option == 'com_jfbconnect' && $view == 'loginregister')
                $_SERVER['REQUEST_METHOD'] = 'POST';

            // Need to load our plugin group early to be able to hook into to every step after
            JPluginHelper::importPlugin('opengraph');
            JPluginHelper::importPlugin('socialprofiles');

            $providers = JFBCFactory::getAllProviders();
            foreach ($providers as $provider)
                $provider->onAfterInitialise();
        }
    }

    public function onAfterRoute()
    {
        $app = JFactory::getApplication();
        if (!$app->isAdmin())
        {
            $app = JFactory::getApplication();
            $app->triggerEvent('onOpenGraphAfterRoute');
            if ($app->getUserState('com_jfbconnect.registration.alternateflow'))
                $app->triggerEvent('socialProfilesPrefillRegistration');
        }
    }

    // Called after the component has executed and it's output is available in the buffer
    // Modules have *not* executed yet
    public function onAfterDispatch()
    {
        $app = JFactory::getApplication();
        if (!$app->isAdmin())
        {
            $providers = JFBCFactory::getAllProviders();
            foreach ($providers as $provider)
                $provider->onAfterDispatch();

            foreach ($this->metadataTagsToStrip as $metadataTag)
            {
                $this->replaceTagInMetadata($metadataTag);
            }

            $doc = JFactory::getDocument();
            if (JFBCFactory::config()->get('bootstrap_css'))
                $doc->addStyleSheet(JURI::base(true) . '/media/sourcecoast/css/sc_bootstrap.css');
            $doc->addStyleSheet(JURI::base(true) . '/media/sourcecoast/css/common.css');

            if ($doc->getType() == 'html')
            {
                $doc->addCustomTag('<SourceCoastProviderJSPlaceholder />');
                $doc->addCustomTag('<SourceCoastCSSPlaceholder />');

                if (JFBCFactory::config()->getSetting('jquery_load'))
                    $doc->addScript(JURI::base(true) . '/media/sourcecoast/js/jq-bootstrap-1.8.3.js');
            }

            //Add Login with FB button to com_users login view and mod_login
            $showLoginWithJoomla = JFBCFactory::config()->getSetting('show_login_with_joomla_reg');
            if ($showLoginWithJoomla != SC_VIEW_NONE)
            {
                JFBConnectUtilities::loadLanguage('com_jfbconnect');

                if (JFBCFactory::easytags()->canExtendJoomlaForm("login", false, $showLoginWithJoomla))
                {
                    $login = JFBCFactory::cache()->get('system.joomlaform.login');
                    if ($login === false)
                    {
                        $login = JFBCFactory::provider('facebook')->getLoginButton(JText::_('COM_JFBCONNECT_LOGIN_WITH'));
                        JFBCFactory::cache()->store($login, 'system.joomlaform.login');
                    }
                    JFBCFactory::easytags()->extendJoomlaUserForms($login, $showLoginWithJoomla);
                }

                if (JFBCFactory::easytags()->canExtendJoomlaForm('registration', false, $showLoginWithJoomla))
                {
                    $registration = JFBCFactory::cache()->get('system.joomlaform.registration');
                    if ($registration === false)
                    {
                        $registration = JFBCFactory::provider('facebook')->getLoginButton(JText::_('COM_JFBCONNECT_REGISTER_WITH'));
                        JFBCFactory::cache()->store($registration, 'system.joomlaform.registration');
                    }
                    JFBCFactory::easytags()->extendJoomlaUserForms($registration, $showLoginWithJoomla);
                }
            }

            // Add the Open Graph links to the user edit form.
            if ($this->showOpenGraphProfileLinks() && JFBCFactory::provider('facebook')->userIsConnected() &&
                JFBCFactory::easytags()->canExtendJoomlaForm('profile', true, SC_VIEW_BOTTOM)
            )
            {
                JFBConnectUtilities::loadLanguage('com_jfbconnect');

                $htmlTag = '<a href="' . JRoute::_('index.php?option=com_jfbconnect&view=opengraph&layout=activity') . '">' . JText::_('COM_JFBCONNECT_TIMELINE_ACTIVITY_LINK') . '</a>';
                $htmlTag .= '<br/><a href="' . JRoute::_('index.php?option=com_jfbconnect&view=opengraph&layout=settings') . '">' . JText::_('COM_JFBCONNECT_TIMELINE_CHANGESETTINGS') . '</a>';

                JFBCFactory::easytags()->extendJoomlaUserForms($htmlTag, SC_VIEW_BOTTOM);
            }

            JPluginHelper::importPlugin('opengraph');
            $app->triggerEvent('onOpenGraphAfterDispatch');

            // Finally, load the Toolbar classes
            JFBCFactory::library('toolbar')->onAfterDispatch();
        }
    }

    // Called right before the page is rendered
    public function onBeforeRender()
    {
        if (!JFactory::getApplication()->isAdmin() && JFactory::getDocument()->getType() == 'html')
        {
            if (JFactory::getUser()->authorise('jfbconnect.opengraph.debug', 'com_jfbconnect') && JFBCFactory::config()->get('facebook_display_errors'))
                JFBCFactory::addStylesheet('jfbconnect.css');
        }
    }

    public function onAfterRender()
    {
        if (!JFactory::getApplication()->isAdmin())
        {
            $this->doTagReplacements();

            $providers = JFBCFactory::getAllProviders();
            foreach ($providers as $provider)
                $provider->onAfterRender();

            JFBCFactory::library('toolbar')->onAfterRender();
            $this->replaceCSSPlaceholder();
        }
        return true;
    }

    private function replaceCSSPlaceholder()
    {
        $cssFiles = JFBCFactory::getStylesheets();
        $contents = JResponse::getBody();
        $tag = '';
        foreach ($cssFiles as $f)
        {
            $tag .= '<link rel="stylesheet" href="' . JUri::base(true) . '/media/sourcecoast/css/' . $f . '" type="text/css" />';
        }
        $contents = str_replace('<SourceCoastCSSPlaceholder />', $tag, $contents);
        JResponse::setBody($contents);
    }

    private function showOpenGraphProfileLinks()
    {
        if (JFactory::getUser()->guest)
            return false;

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('COUNT(*)')
            ->from($db->qn('#__opengraph_action'))
            ->where($db->qn('published') . '=' . $db->q(1));
        $db->setQuery($query);
        $numOGActionsEnabled = $db->loadResult();

        $user = JFactory::getUser();
        $query = $db->getQuery(true);
        $query->select('COUNT(*)')
            ->from($db->qn('#__opengraph_activity'))
            ->where($db->qn('user_id') . '=' . $db->q($user->id))
            ->where($db->qn('status') . '=' . $db->q(1));
        $db->setQuery($query);
        $numActivities = $db->loadResult();

        return ($numOGActionsEnabled > 0) || ($numActivities > 0);
    }

    private function replaceTagInMetadata($metadataTag)
    {
        $doc = JFactory::getDocument();
        $description = $doc->getDescription();
        $replace = JFBConnectUtilities::stripSystemTags($description, $metadataTag);

        if ($replace)
        {
            $description = JFBConnectUtilities::trimNBSP($description);
            $doc->setDescription($description);
        }
    }

    private function doTagReplacements()
    {
        /*
         * Code to strip any {JFBCxyz} tags from head.
         */
        //Get the head
        $contents = JResponse::getBody();
        $regex = '|<head(.*)?</head>|sui';
        if (preg_match($regex, $contents, $matches))
        {
            if (count($matches) == 2) // more than one head is a problem, don't do anything
            {
                //NOTE: Searching for just SC can incorrectly remove other tags that are valid, e.g. {scrollTop:...}

                //Remove the tag if it's in the head
                $newHead = preg_replace('/\{('.$this->scTags.'|JFBC|JLinked)(.*?)}/ui', '', $matches[0], -1, $count);

                if ($count > 0)
                {
                    //Replace the head
                    $contents = preg_replace('|<head(.*)?</head>|sui', $newHead, $contents, -1, $count);
                    if ($count == 1) // Only update the body if exactly one head was found and replaced
                        JResponse::setBody($contents);
                }
            }
        }

        $contents = JFBCFactory::easytags()->replaceWidgetTags($contents);
        $contents = JFBCFactory::easytags()->replaceGraphTags($contents);
        $contents = JFBCFactory::easytags()->replaceJSPlaceholders($contents);

        JResponse::setBody($contents);
    }

}
