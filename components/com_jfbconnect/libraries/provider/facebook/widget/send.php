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

class JFBConnectProviderFacebookWidgetSend extends JFBConnectProviderFacebookWidget
{
    var $name = "Send";
    var $systemName = "send";
    var $className = "jfbcsend";
    var $tagName = array("jfbcsend","scfacebooksend");
    var $examples = array (
        '{SCFacebookSend}',
        '{SCFacebookSend href=http://www.sourcecoast.com width=75 height=50 colorscheme=light ref=homepage kid_directed_site=true}'
    );

    protected function getTagHtml()
    {
        $tag = '<div class="fb-send"';
        $tag .= $this->getField('href', 'url', null, JFBConnectUtilities::getStrippedUrl(), 'data-href');
        $tag .= $this->getField('colorscheme', null, null, '', 'data-colorscheme');
        $tag .= $this->getField('ref', null, null, '', 'data-ref');
        $tag .= $this->getField('width', null, null, '', 'data-width');
        $tag .= $this->getField('height', null, null, '', 'data-height');
        $tag .= $this->getField('kid_directed_site', null, 'boolean', 'false', 'data-kid-directed-site');
        $tag .= '></div>';
        return $tag;
    }
}
