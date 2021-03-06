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

class JFBConnectControllerProfiles extends JFBConnectController
{
    function apply()
    {
        $app = JFactory::getApplication();
        $configs = JRequest::get('POST');
        $model = JFBCFactory::config();

        JPluginHelper::importPlugin('socialprofiles');
        $profilePlugins = $app->triggerEvent('socialProfilesGetPlugins');

        foreach ($profilePlugins as $plugin)
        {
            $pluginName = $plugin->getName();
            $settings = new JRegistry();
            $search = "profiles_" . $pluginName . "_";
            $stdFields = JRequest::getVar('profiles_' . $pluginName);
            $settings->loadArray($stdFields);
            foreach ($configs as $key => $value)
            {
                $pos = strpos($key, $search);
                if ($pos === 0)
                {
                    $key = str_replace($search, "", $key);
                    if (strpos($key, "field_map") != false)
                    {
                        $key = str_replace("_field_map", ".", $key);
                        $settings->set('field_map.' . $key, $value);
                    }
                }
            }
            $model->update("profile_" . $pluginName, $settings->toString());
        }

        JFBCFactory::log(JText::_('COM_JFBCONNECT_MSG_SETTINGS_UPDATED'));
        $this->display();
    }

}