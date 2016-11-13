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

class JFBConnectProviderLinkedinWidgetMember extends JFBConnectWidget
{
    var $name = "Member Profile";
    var $systemName = "member";
    var $className = "jlinkedMember";
    var $tagName = array("jlinkedmember","sclinkedinmember");
    var $examples = array(
        '{SCLinkedInMember}',
        '{SCLinkedInMember href=http://www.linkedin.com/in/alexandreae display_mode=inline related=false width=300}',
        '{SCLinkedInMember href=http://www.linkedin.com/in/alexandreae display_mode=icon_name display_behavior=click display_text=Alex Andreae related=true}',
        '{SCLinkedInMember href=http://www.linkedin.com/in/alexandreae display_mode=icon display_behavior=hover related=1}',
    );

    protected function getTagHtml()
    {
        $tag = '<span class="IN-canvas-member"><script type="IN/MemberProfile"';
        $tag .= $this->getField('href', 'url', null, '', 'data-id');
        $tag .= $this->getField('related', null, 'boolean', 'true', 'data-related');

        $displayMode = $this->getParamValue('display_mode');
        if ($displayMode == 'inline')
        {
            $tag .= ' data-format="inline"';
            $tag .= $this->getField('width', null, null, '', 'data-width');
        }
        else if ($displayMode == 'icon_name')
        {
            $tag .= $this->getField('display_behavior', null, null, '', 'data-format');
            $tag .= $this->getField('display_text', null, null, '', 'data-text');
        } else if ($displayMode == 'icon')
        {
            $tag .= $this->getField('display_behavior', null, null, '', 'data-format');
        }
        $tag .= '></script></span>';

        return $tag;
    }
}
