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

class JFBConnectProviderLinkedinWidgetRecommend extends JFBConnectWidget
{
    var $name = "Recommend";
    var $systemName = "recommend";
    var $className = "jlinkedRecommend";
    var $tagName = array("jlinkedrecommend","sclinkedinrecommend");
    var $examples = array(
        '{SCLinkedInRecommend}',
        '{SCLinkedInRecommend companyid=365848 productid=201714 counter=top}'
    );

    protected function getTagHtml()
    {
        $tag = '<script type="IN/RecommendProduct"';
        $tag .= $this->getField('companyid', null, null, '', 'data-company');
        $tag .= $this->getField('productid', null, null, '', 'data-product');
        $tag .= $this->getField('counter', null, null, '', 'data-counter');
        $tag .= '></script>';
        return $tag;
    }
}
