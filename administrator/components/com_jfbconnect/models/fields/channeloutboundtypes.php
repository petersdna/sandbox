<?php
/**
 * @package         JFBConnect
 * @copyright (c)   2009-2015 by SourceCoast - All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @version         Release v6.5.3
 * @build-date      2015/12/19
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldChannelOutboundTypes extends JFormFieldList
{
    public $type = 'ChannelOutbound';

    protected function getOptions()
    {
        $options = array();
        $options[] = JHtml::_('select.option', "--", "-- Select a Channel --");

        $p = $this->form->getValue('provider');
        if ($p && $p != '--')
        {
            $provider = JFBCFactory::provider($p);
            $channels = $provider->getChannelsOutbound();
            foreach ($channels as $c)
            {
                $options[] = JHtml::_('select.option', strtolower($c->name), $c->name);
            }
        }
        return $options;
    }
}