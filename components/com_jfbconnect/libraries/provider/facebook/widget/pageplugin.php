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

class JFBConnectProviderFacebookWidgetPageplugin extends JFBConnectProviderFacebookWidget
{
    var $name = "Page Plugin";
    var $systemName = "pageplugin";
    var $className = "sc_facebookpageplugin";
    var $tagName = "scfacebookpageplugin";
    var $examples = array (
        '{SCFacebookPagePlugin}',
        '{SCFacebookPagePlugin height=200 width=200 href=http://www.facebook.com/SourceCoast show_faces=true hide_cover=false tabs=timeline,events}'
    );

    protected function getTagHtml()
    {
        $tag = '<div class="fb-page"';
        $tag .= $this->getField('show_faces', null, 'boolean', 'true', 'data-show-facepile');
        $tag .= $this->getField('tabs', null, null, '', 'data-tabs');
        $tag .= $this->getField('hide_cover', null, 'boolean', 'false', 'data-hide-cover');
        $tag .= $this->getField('width', null, null, '', 'data-width');
        $tag .= $this->getField('height', null, null, '', 'data-height');
        $tag .= $this->getField('href', 'url', null, '', 'data-href');
        $tag .= $this->getField('small_header', null, 'boolean', 'false', 'data-small-header');
        $tag .= $this->getField('adapt_width', null, 'boolean', 'true', 'data-adapt-container-width');
        $tag .= $this->getField('hide_cta', null, 'boolean', 'false', 'data-hide-cta');
        $tag .= '></div>';
        return $tag;
    }
}
