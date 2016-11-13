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

class JFBConnectProviderGoogleWidgetFollow extends JFBConnectWidget
{
    var $name = "Follow";
    var $systemName = "follow";
    var $className = "sc_gfollow";
    var $tagName = "scgooglefollow";
    var $examples = array (
        '{SCGoogleFollow href=https://plus.google.com/110967630299632321627}',
        '{SCGoogleFollow href=https://plus.google.com/110967630299632321627 annotation=bubble height=20 rel=author}'
    );

    protected function getTagHtml()
    {
      $tag = '<div class="g-follow"';
      $tag .= $this->getField('href', 'url', null, '', 'data-href');     
      $tag .= $this->getField('annotation', null, null, 'bubble', 'data-annotation');
      $tag .= $this->getField('height', null, null, '20', 'data-height');
      $tag .= $this->getField('rel', null, null, '—', 'data-rel');      
      $tag .= '></div>';   
    
      return $tag;
    }
}
