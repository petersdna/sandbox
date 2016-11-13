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

class JFBConnectProviderLinkedinWidgetShare extends JFBConnectWidget
{
    var $name = "Share";
    var $systemName = "share";
    var $className = "jlinkedShare";
    var $tagName = array("jlinkedshare","sclinkedinshare");
    var $examples = array(
        '{SCLinkedInShare}',
        '{SCLinkedInShare counter=top}',
        '{SCLinkedInShare href=http://www.sourcecoast.com/jlinked/ counter=right showzero=0}'
    );

    protected function getTagHtml()
    {
        JFBCFactory::addStylesheet('jfbconnect.css');
        $tag = '<script type="IN/Share"';
        $tag .= $this->getField('href', 'url', null, JFBConnectUtilities::getStrippedUrl(), 'data-url');
        $tag .= $this->getField('showzero', 'show_zero', 'boolean', 'false', 'data-showzero');
        if($this->fields->exists('layout'))
            $tag .= JFBCFactory::easytags()->getShareButtonLayout('linkedin', $this->fields->get('layout'), '"');
        else
            $tag .= $this->getField('counter', null, null, '', 'data-counter');

        $tag .= ' data-onsuccess="jfbc.social.linkedin.share"';
        $tag .= '></script>';
        return $tag;
    }
}
