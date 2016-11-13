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

class JFBConnectProviderInstagramWidgetOembed extends JFBConnectProviderWidgetOembed
{
    public $examples = array (
        '{SCInstagramOEmbed url=http://instagr.am/p/BUG/ maxwidth=612}'
    );

    function __construct($provider, $fields)
    {
        parent::__construct($provider, $fields, 'scInstagramOembedTag');

        $this->name = "Embedded Media";
        $this->className = 'sc_instagramoembed';
        $this->tagName = 'scinstagramoembed';

        $options = new JRegistry();
        $options->set('oembed_url', 'http://api.instagram.com/oembed');
        $options->set('url', $this->getParamValueEx('url', null, null, ''));
        $options->set('maxwidth', $this->getParamValueEx('maxwidth', null, null, ''));

        $headers = array();
        $headers['Content-Type'] = 'application/json';
        $options->set('headers', $headers);

        $this->options = $options;
    }
}
