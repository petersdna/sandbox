<?php

defined('JPATH_PLATFORM') or die;

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldSocialProfilesJomSocialProfileTypes extends JFormFieldList
{
    public $type = 'SocialProfilesJomSocialProfileTypes';

    protected function getOptions()
    {
        include_once(JPATH_SITE . '/components/com_community/libraries/core.php');

        $profileModel = CFactory::getModel('profile');
        $profileTypes = $profileModel->getProfileTypes();

        $options = array();
        $options[] = JHtml::_('select.option', "0", "Default");
        foreach ($profileTypes as $p)
            $options[] = JHtml::_('select.option', $p->id, $p->name);

        return $options;
    }
}
