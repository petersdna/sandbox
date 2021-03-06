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

class JFBConnectControllerNotification extends JFBConnectController
{
    public function __construct()
    {
        parent::__construct();
        $document = JFactory::getDocument();
        $viewType = $document->getType();
        $viewName = 'notification';
        $this->view = $this->getView($viewName, $viewType);
    }

    function display($cachable = false, $urlparams = false)
    {
        $notificationModel = $this->getModel('notification');
        $this->view->setModel($notificationModel, true);

        $viewLayout = JRequest::getCmd('layout', 'default');
        $this->view->setLayout($viewLayout);

        if ($viewLayout == "default")
        {
            JToolBarHelper::back(JText::_('COM_JFBCONNECT_BUTTON_BACK'));
        }

        $task = JRequest::getCmd('task', "display");
        if ($task == "")
            $task = 'display'; // Needed for ordering tasks
        $this->view->$task();
    }
}