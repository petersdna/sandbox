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

class JFBConnectProviderFacebookWidget extends JFBConnectWidget
{
    public function render()
    {
        $class[] = "sourcecoast facebook";
        $class[] = $this->systemName;

        //Facebook javascript is added automatically
        JFBCFactory::addStylesheet('jfbconnect.css');
        $tag = $this->getTagHtml();

        if($tag)
        {
            $this->provider->widgetRendered = true;
            if($this->className)
                $class[] = $this->className;

            $classString = implode(' ', $class);
            $tag = '<div class="'.$classString.'">' . $tag . '</div>';
        }

        return $tag;
    }
}
