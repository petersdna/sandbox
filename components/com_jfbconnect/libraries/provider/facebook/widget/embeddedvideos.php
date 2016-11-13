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

class JFBConnectProviderFacebookWidgetEmbeddedvideos extends JFBConnectProviderFacebookWidget
{
    var $name = "Embedded Videos";
    var $systemName = "embeddedvideos";
    var $className = "sc_facebookembeddedvideos";
    var $tagName = "scfacebookembeddedvideos";
    var $examples = array (
        '{SCFacebookEmbeddedVideos href=https://www.facebook.com/video.php?v=10152454700553553 width=500 allow_fullscreen=true}'
    );

    protected function getTagHtml()
    {
        $tag = '';
        $href = $this->getField('href', 'url', null, '', 'data-href');

        if($href)
        {
            $tag = '<div class="fb-video"' . $href;
            $tag .= $this->getField('width', null, null, '', 'data-width');
            $tag .= $this->getField('allow_fullscreen', null, 'boolean', 'false', 'data-allowfullscreen');
            $tag .= $this->getField('autoplay', null, 'boolean', 'false', 'data-autoplay');
            $tag .='></div>';
        }
        return $tag;
    }
}
