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

class JFBConnectProviderFacebookWidgetLikebox extends JFBConnectProviderFacebookWidget
{
    var $name = "Like Box (Deprecated)";
    var $systemName = "likebox";
    var $className = "jfbcfan";
    var $tagName = array("jfbcfan","scfacebookfan");
    var $examples = array (
        '{SCFacebookFan}',
        '{SCFacebookFan height=200 width=200 href=http://www.facebook.com/SourceCoast show_faces=true stream=false hide_cover=false}'
    );

    protected function getTagHtml()
    {
        JFBCFactory::log('Deprecated Like Box called. Use Page Plugin instead.', 'warning', true);

        $tag = '<div class="fb-page"';
        $tag .= $this->getField('show_faces', null, 'boolean', 'true', 'data-show-facepile');
        $tag .= $this->getField('stream', null, 'boolean', 'false', 'data-show-posts');
        $tag .= $this->getField('hide_cover', null, 'boolean', 'false', 'data-hide-cover');
        $tag .= $this->getField('width', null, null, '', 'data-width');
        $tag .= $this->getField('height', null, null, '', 'data-height');
        $tag .= $this->getField('href', 'url', null, '', 'data-href');
        $tag .= '></div>';
        return $tag;
    }
}
