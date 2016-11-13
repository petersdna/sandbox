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

class JFBConnectProviderGoogleWidgetEmbeddedPosts extends JFBConnectWidget
{
    var $name = "Embedded Posts";
    var $systemName = "embeddedposts";
    var $className = "sc_gembeddedposts";
    var $tagName = "scgoogleembeddedposts";
    var $examples = array (
        '{SCGoogleEmbeddedPosts href=https://plus.google.com/110105427116687355332/posts/GBeXiZgZhFH}'
    );

    protected function getTagHtml()
    {
      $tag = '<div class="g-post"';
      $tag .= $this->getField('href', 'url', null, '', 'data-href'); 
      $tag .= '></div>';
      
      return $tag;     
    }
}
