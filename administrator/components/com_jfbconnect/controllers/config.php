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

class JFBConnectControllerConfig extends JFBConnectController
{
    function apply()
    {
        $configs = JRequest::get('POST', 4);
        JFBCFactory::config()->saveSettings($configs);

        JFBCFactory::log(JText::_('COM_JFBCONNECT_MSG_SETTINGS_UPDATED'));
        $this->display();
    }

    public function migrate()
    {
        $app = JFactory::getApplication();
        $migration = $app->input->getCmd('migration');
        $parts = explode('.', $migration);
        if ($parts[0])
        {
            include_once(JPATH_ADMINISTRATOR . '/components/com_jfbconnect/helpers/migrator/' . $parts[0] . '.php');
            $class = 'JFBConnectMigrator' . $parts[0];
            $migrator = new $class();
            $subtask = isset($parts[1]) ? $parts[1] : 'migrate';
            $result = $migrator->$subtask();
            if ($result)
                JFBCFactory::log(JText::_('COM_JFBCONNECT_MSG_MIGRATION_COMPLETE'));
            else
                JFBCFactory::log(JText::_('COM_JFBCONNECT_MSG_MIGRATION_ERROR'), 'error');
        }
        $this->setRedirect('index.php?option=com_jfbconnect&view=config');
    }

}