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

class JFBConnectProviderLinkedinWidgetCompanyinsider extends JFBConnectWidget
{
    var $name = "Company Insider";
    var $systemName = "companyinsider";
    var $className = "jlinkedCompanyInsider";
    var $tagName = array("jlinkedcompanyinsider","sclinkedincompanyinsider");
    var $examples = array (
        '{SCLinkedInCompanyInsider companyid=1441}',
        '{SCLinkedInCompanyInsider companyid=365848}'
    );

    protected function getTagHtml()
    {
        $tag = '<script type="IN/CompanyInsider"';
        $tag .= $this->getField('companyid', null, null, '', 'data-id');

/*        $modules = array();

        if($this->getParamValueEx('in_network', null, 'boolean', 'false') == 'true')
            $modules[] = 'innetwork';

        if($this->getParamValueEx('new_hires', null, 'boolean', 'false') == 'true')
            $modules[] = 'newhires';

        if($this->getParamValueEx('promotions_changes', null, 'boolean', 'false') == 'true')
            $modules[] = 'jobchanges';

        if (count($modules) > 0)
            $tag .= ' data-modules="' . implode(',', $modules) . '"';
*/
        $tag .= '></script>';

        return $tag;
    }
}
