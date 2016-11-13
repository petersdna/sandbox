<?php
/**
 * @package         JFBConnect
 * @copyright (c)   2009-2015 by SourceCoast - All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @build-date      2015/12/19
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.utilities.date');
jimport('sourcecoast.plugins.socialprofile');

class plgSocialProfilesJomsocial extends SocialProfilePlugin
{
    var $_profileType = null;

    function __construct(&$subject, $params)
    {
        $this->displayName = "JomSocial";
        // Setup the file paths that detect if this component is actually installed.
        // Needed before calling the parent constructor
        $this->_componentFolder = JPATH_SITE . '/components/com_community';
        $this->_componentFile = 'libraries/core.php';

        parent::__construct($subject, $params);

        // Now do any initialization or defaultSettings setup required
        $this->defaultSettings->set('import_avatar', '1');
        $this->defaultSettings->set('import_always', '0');
        $this->defaultSettings->set('import_cover_photo', '1');
        $this->defaultSettings->set('import_status', '1');
        $this->defaultSettings->set('push_status', '0');
        $this->defaultSettings->set('registration_show_fields', '0'); //0=None, 1=Required, 2=All
        $this->defaultSettings->set('imported_show_fields', '0'); //0=No, 1=Yes
        $this->defaultSettings->set('skip_tos', '1');
        $this->defaultSettings->set('profiletype_visible', '0');
        $this->defaultSettings->set('profiletype_default', '0');

        // Set this for allowing registration through this component
        $this->registration_url = 'index.php?option=com_community&view=register';

        $this->_importEnabled = true; // This plugin has a method to transfer existing facebook connections over to JFBConnect

        // Hook into JomSocial activity calls to do our actions.
        $this->_type = "community";
        $this->_name = 'SocialProfilesJomSocial'; // Set our 'name' to something unique that won't collide with real community plugins
    }

    var $jsVersion;

    private function getJomSocialVersion()
    {
        if (!$this->jsVersion)
        {
            $db = JFactory::getDBO();
            $query = $db->getQuery(true);
            $query->select('manifest_cache')->from('#__extensions')->where('element="com_community"');
            $db->setQuery($query);
            $manifest = $db->loadResult();
            $manifest = json_decode($manifest);
            $this->jsVersion = strtolower($manifest->version);
        }
        return $this->jsVersion;
    }

    public function socialProfilesGetRequiredScope($network)
    {
        $scope = parent::socialProfilesGetRequiredScope($network);
        if ($network == 'facebook' && $this->settings->get('push_status'))
            $scope[] = 'publish_actions';
        else if($network == 'facebook' && $this->settings->get('import_status'))
            $scope[] = 'user_posts';

        return $scope;
    }

    // Query must return at least id, name
    protected function getProfileFields()
    {
        $query = 'SELECT * FROM #__community_fields WHERE type="text" OR type="textarea" OR type="date" OR type="birthdate" OR type="url" OR type="gender" ORDER BY ordering';
        $this->db->setQuery($query);
        $jsFields = $this->db->loadObjectList();
        return $jsFields;
    }

    public function getConfigurationTemplate($network)
    {
        $this->profileTypes = $this->getProfileTypes(true);
        return parent::getConfigurationTemplate($network);
    }

    public function prefillRegistration()
    {
        $input = JFactory::getApplication()->input;
        if ($input->getCmd('option') == 'com_community' && $input->getCmd('view') == 'register')
        {
            switch ($input->getCmd('task', null))
            {
                case null :
                    $profileData = $this->profileLibrary->fetchProfile($this->socialId, array('full_name', 'first_name', 'last_name', 'email'));
                    $this->prefillRegistrationField('jsname', $profileData->get('full_name'));
                    $this->prefillRegistrationField('jsusername', $this->getAutoUsername($profileData));
                    $this->prefillRegistrationField('jsemail', $profileData->get('email'));
                    return true;
                case 'registerProfile' :
                    $profileData = $this->fetchProfileFromFieldMap();
                    foreach ($profileData->fieldMap as $fieldId => $mapping)
                    {
                        if ($mapping != "0")
                            $this->prefillRegistrationField('field' . $fieldId, $profileData->get($mapping));
                    }
                    return true;
                case 'registerAvatar' :
                    $tmpUser = JFactory::getSession()->get('tmpUser');
                    if (get_class($tmpUser) == 'JUser')
                    {
                        $this->joomlaId = $tmpUser->get('id');
                        if ($this->joomlaId)
                        {
                            if ($this->settings->get('import_cover_photo'))
                                $this->importCoverPhoto();
                            if ($this->settings->get('import_avatar'))
                            {
                                $this->importSocialAvatar();
                                // Try to skip the avatar import page and 'reg complete' page at this point
                                // Probably should have an option for this...
                                return $this->finalizeRegistration();
                            }
                        }
                    }
                    return true;
                case 'register_save' :
                case 'registerUpdateProfile' :
                    return true; // These are stages where activation is used, so we need to make sure the socialprofile plugin alters things as necessary.
                case 'registerSucess' : // Incorrect spelling, but what's actually used
                case 'registerSuccess' : // This is the correct spelling, so adding it just in case it's fixed in JS
                    return $this->finalizeRegistration();
            }
        }
        return false;
    }

    protected function getRegistrationForm($profileData)
    {
        $html = '';

        CFactory::load('models', 'profile');
        CFactory::load('libraries', 'profile');
        $language = JFactory::getLanguage();
        $language->load('com_community');

        $defaultType = $this->settings->get('profiletype_default', 0);
        $profileType = $defaultType;
        if ($this->settings->get('profiletype_visible', 0))
        {
            $profileType = JRequest::getInt('profile_id', $profileType);
            $availableTypes = $this->getProfileTypes(!$defaultType); // Only show default if it's selected
            $profileJs = "var jfbcProfilesCommunityType = $('profile_id').value;" .
                    "window.location.href = jfbc.base + 'index.php?option=com_jfbconnect&view=loginregister&provider=" . $this->network . "&profile_id='+jfbcProfilesCommunityType;";
            $html .= "<br/><b>" . JText::_('COM_COMMUNITY_MULTIPROFILE_SELECT_TYPE') . ":</b><br/>";
            $html .= JHTML::_('select.genericlist', $availableTypes, 'profile_id', ' onchange="' . $profileJs . '"', 'id', 'name', $profileType);
            $html .= "<br/>";
        }
        $this->_profileType = $profileType;

        //Get register field forms
        $showRegistrationFields = $this->settings->get('registration_show_fields');
        $showImportedFields = $this->settings->get('imported_show_fields');

        $profileModel = CFactory::getModel('profile');

        // Get the groups and their fields for the selected profile type that are published and registered
        $filter = array('published' => '1', 'registration' => '1'); //, 'required' => '1');
        $fieldGroups = & $profileModel->getAllFields($filter, $this->_profileType);

        CFactory::load('libraries', 'template');
        CTemplate::addStylesheet('style');
        CTemplate::addStylesheet('minitip');

        $doc = JFactory::getDocument();

        foreach ($fieldGroups as $group)
        {
            $groupHtml = "";
            $hasVisibleFields = false;
            foreach ($group->fields as $field)
            {
                $fieldName = $this->settings->get('field_map.' . $field->id, 0);
                // Show All/Required Fields. Hide mapped fields if not showing imported fields
                $showField = ($showRegistrationFields == '2' || ($field->required && $showRegistrationFields == '1')) &&
                        ($showImportedFields == "1" || ($showImportedFields == "0" && $fieldName == '0'));

                if (!$showField)
                {
                    if ($fieldName != '0')
                        $this->set('performsSilentImport', 1);
                    continue;
                }
                $fieldValue = $profileData->getFieldWithUserState('field' . $field->id);
                if (empty($fieldValue)) // Just fetch it from the social network (no real post data is checked here)
                    $fieldValue = $profileData->getFieldWithUserState($field->id);

                if (($field->type == "date" || $field->type == "birthdate"))
                {
                    $doc->addScript(JURI::root() . 'components/com_community/assets/jqueryui/datepicker/js/jquery-ui-1.9.2.custom.js');
                    $doc->addStyleSheet(JURI::root() . 'components/com_community/assets/jqueryui/datepicker/css/ui-lightness/jquery-ui-1.9.2.custom.css');
                    $fieldValue = $this->getJomSocialDate($fieldValue);
                }
                if ($field->type == 'gender')
                    $fieldValue = $this->getJomSocialGender($fieldValue);
                if ($field->type == "url" && is_array($fieldValue))
                    $fieldValue = implode("", $fieldValue);

                $field->value = $fieldValue;

                if ($showField)
                {
                    $hasVisibleFields = true;
                    $groupHtml .= '<dt><span id="lblfield' . $field->id . '">' . JText::_( $field->name );
                    if ( $field->required == 1 ) {
                        $groupHtml .= ' <span class="joms-required">*</span>';
                    }
                    $groupHtml .= "</span></dt><dd>" . CProfileLibrary::getFieldHTML($field) . "</dd>";
                }
            }
            if ($hasVisibleFields)
                $html .= "<fieldset><legend>" . $group->name . "</legend>";

            if ($groupHtml != "")
                $html .= '<dl>' . $groupHtml . '</dl>';

            if ($hasVisibleFields)
                $html .= "</fieldset>";
        }

        if (!$this->settings->get('skip_tos'))
        {
            CFactory::load('configuration', 'model');
            $cConfig = CConfig::getInstance();
            $reg_tos_enabled = $cConfig->get('enableterms');

            //Load javascript for popup

            if(JFile::exists(JPATH_SITE . 'components/com_community/assets/joms.jquery-1.8.1.js'))
                $doc->addScript(JURI::root() . 'components/com_community/assets/joms.jquery-1.8.1.js');
            else if(JFile::exists(JPATH_SITE . 'components/com_community/assets/joms.jquery.js'))
                $doc->addScript(JURI::root() . 'components/com_community/assets/joms.jquery.js');

            $doc->addScript(JURI::root() . 'components/com_community/assets/script-1.2.js');
            $doc->addScript(JURI::root() . 'components/com_community/assets/window-1.0.js');
            $doc->addScript(JURI::root() . 'components/com_community/assets/joms.ajax.js');
            $doc->addStyleSheet(JURI::root() . 'components/com_community/assets/window.css');

            if ($reg_tos_enabled)
            {
                $html .= '<div>' . JText::_('COM_COMMUNITY_REGISTER_TITLE_TNC') . '</div>
    		     <input type="checkbox" name="tnc" id="tnc" value="Y" class="inputbox required"/>' .
                        JText::_('COM_COMMUNITY_I_HAVE_READ') .
                        ' <a href="javascript:void(0);" onclick="joms.registrations.showTermsWindow();">' .
                        JText::_('COM_COMMUNITY_TERMS_AND_CONDITION') .
                        '</a>';
            }
            $html .= "<br/><br/>";
        }

        return $html;
    }

    protected function createUser($profileData)
    {
        CFactory::load('models', 'profile');
        CFactory::load('libraries', 'profile');
        $language = JFactory::getLanguage();
        $language->load('com_community');

        // Init the JomSocial user
        $my = CFactory::getUser($this->joomlaId);

        // Set the profile of the user here, regardless of whether its a Full Joomla or FB Only
        $defaultProfile = $this->settings->get('profiletype_default', 0);
        $selectedProfile = JRequest::getInt('profile_id', $defaultProfile);

        $my->set('_profile_id', $selectedProfile);
        $my->save();

        // Check if we're good so far and the user has been created
        if ($my->id == 0)
            return;

        $model = CFactory::getModel('profile');
        CFactory::load('libraries', 'apps');
        $appsLib = CAppPlugins::getInstance();
        $saveSuccess = $appsLib->triggerEvent('onFormSave', array('jsform-profile-edit'));

        // Start pulling in the profile data the user submitted during registration (if any)
        // This really only has an effect if the Normal Registration Flow is use, not the automatic user creation
        if (empty($saveSuccess) || !in_array(false, $saveSuccess))
        {
            $values = array();
            $profiles = $model->getEditableProfile($my->id, $my->getProfileType());

            foreach ($profiles['fields'] as $group => $fields)
            {
                foreach ($fields as $data)
                {
                    // Get value from posted data and map it to the field.
                    // Here we need to prepend the 'field' before the id because in the form, the 'field' is prepended to the id.
                    // First, check if a fieldXY data value is returned. If so, use it. If not, check without the field, which is what would happen during
                    //    auto registration or normal reg with fields hidden
                    $postData = $profileData->getFieldWithUserState('field' . $data['id']);
                    if (empty($postData)) // Just fetch it from the social network (no real post data is checked here)
                        $postData = $profileData->getFieldWithUserState($data['id']);

                    if (($data['type'] == "date" || $data['type'] == "birthdate") && !is_array($postData) && $postData)
                    {
                        $postData = $this->getJomSocialDate($postData);
                    }
                    else if ($data['type'] == 'gender')
                    {
                        $postData = $this->getJomSocialGender($postData);
                    }
                    else if (($data['type'] == 'url'))
                    {
                        if (!is_array($postData))
                        {
                            $postData = explode("://", $postData);
                            $postData[0] = $postData[0] . "://";
                        }
                    }
                    $value = CProfileLibrary::formatData($data['type'], $postData);

                    if (!empty($value))
                    {
                        // @rule: Validate custom profile if necessary. SC - Set required to 'no' for all fields.
                        if (CProfileLibrary::validateField($data['id'], $data['type'], $value, 0))
                        {
                            // SC: If the value doesn't validate, just discard it.
                            // Not perfect, but prevents 'bad' values while still continuing with registration
                            $values[$data['id']] = strval($value);
                        }
                    }
                }
            }

            // Rebuild new $values with field code
            $valuesCode = array();
            foreach ($values as $key => &$val)
            {
                $fieldCode = $model->getFieldCode($key);
                if ($fieldCode)
                {
                    $valuesCode[$fieldCode] = & $val;
                }
            }

            $saveSuccess = true;
            $appsLib->loadApplications();

            // Trigger before onBeforeUserProfileUpdate
            $args = array();
            $args[] = $my->id;
            $args[] = $valuesCode;

            $result = $appsLib->triggerEvent('onBeforeProfileUpdate', $args);

            // make sure none of the $result is false
            if (!$result || (!in_array(false, $result)))
            {
                $model->saveProfile($my->id, $values);
            }
            else
            {
                $saveSuccess = false;
            }
        }

        // Trigger before onAfterUserProfileUpdate
        $args = array();
        $args[] = $my->id;
        $args[] = $saveSuccess;
        $result = $appsLib->triggerEvent('onAfterProfileUpdate', $args);

        if ($saveSuccess)
        {
            // @rule: increment user points for registrations.
            $my->_points += 2;

            // increase default value set by admin (only apply to new registration)
            $config = CFactory::getConfig();
            $default_points = $config->get('defaultpoint');

            if (isset($default_points) && $default_points > 0)
            {
                $my->_points += $config->get('defaultpoint');

                $my->save();
            }
        }

        // Check if this profile type should be blocked, and if so, do it.
        $profileTypes = $this->getProfileTypes(false);
        $block = 0;
        foreach ($profileTypes as $pt)
        {
            if ($pt->id == $selectedProfile)
                $block = $pt->approvals;
        }
        if ($block == 1)
        {
            $jUser =& JUser::getInstance($this->joomlaId);
            $jUser->set('block', 1);
            $jUser->save();
            $my->set('block', 1);
            $lang = JFactory::getLanguage();
            $lang->load('com_community');
            JFBCFactory::log(JText::_('COM_COMMUNITY_REGISTRATION_COMPLETED_NEED_APPROVAL'));
        }
    }

    protected function saveProfileField($fieldId, $value)
    {
        // Actual ID stored looks like 'field5'. Need to strip the 'field' from it to get the int ID
        $query = $this->db->getQuery(true);
        $query->select($this->db->qn('type'))
                ->from($this->db->qn('#__community_fields'))
                ->where($this->db->qn('id') . " = " . $this->db->q($fieldId));
        $this->db->setQuery($query);
        $type = $this->db->loadResult();

        #format field, if necessary
        if ($type == "date" || $type == "birthdate")
        {
            jimport('joomla.utilities.date');
            $date = new JDate($value);
            $value = $date->toSql();
        }
        else if ($type == 'gender')
        {
            $value = $this->getJomSocialGender($value);
        }

        #check if row already exists (it doesn't if they didn't fill out their profile originally, and
        #  just connected accounts)
        $query = $this->db->getQuery(true);
        $query->select($this->db->qn('id'))
                ->from($this->db->qn('#__community_fields_values'))
                ->where($this->db->qn('field_id') . " = " . $this->db->q($fieldId))
                ->where($this->db->qn('user_id') . " = " . $this->db->q($this->joomlaId));
        $this->db->setQuery($query);
        $rowId = $this->db->loadResult();

        $query = $this->db->getQuery(true);
        if (!$rowId)
        {
            $query->insert($this->db->qn('#__community_fields_values'))
                    ->set($this->db->qn('user_id') . ' = ' . $this->db->q($this->joomlaId))
                    ->set($this->db->qn('field_id') . ' = ' . $this->db->q($fieldId))
                    ->set($this->db->qn('value') . ' = ' . $this->db->q($value));
        }
        else
        {
            $query->update($this->db->qn('#__community_fields_values'))
                    ->set($this->db->qn('value') . ' = ' . $this->db->q($value))
                    ->where($this->db->qn('id') . ' = ' . $this->db->q($rowId));
        }
        $this->db->setQuery($query);
        $this->db->execute();
    }

    protected function setDefaultAvatar()
    {
        $jsUser = CFactory::getUser($this->joomlaId);
        $jsUser->set('_avatar', DEFAULT_USER_AVATAR);
        $jsUser->set('_thumb', DEFAULT_USER_THUMB);
        $jsUser->save();
        return true;
    }

    protected function setCoverPhoto($cover)
    {
        if (version_compare($this->getJomSocialVersion(), '2.99', '<='))
            return false;

        $type = "profile";
        $hash = md5('cover_' . $cover->get('url'));
        $dest = JPATH_ROOT . '/images/cover/' . $type . '/' . $this->joomlaId . '/' . $hash . '.jpg';
        if (JFile::exists($dest))
            return true; // bail, the same cover photo was previously imported

        $now = new JDate();

        $album = JTable::getInstance('Album', 'CTable');

        if (!$albumId = $album->isCoverExist($type, $this->joomlaId))
        {
            $langstring = JText::_('COM_COMMUNITY_COVER_' . strtoupper($type));
            $now = new JDate();
            $album->creator = $this->joomlaId;
            $album->name = JText::sprintf('COM_COMMUNITY_ALBUM_COVER_NAME', $langstring);
            $album->type = $type . '.Cover';
            $album->path = 'images/cover/' . $type . '/' . $this->joomlaId . '/';
            $album->created = $now->toSql();
            $album->store();
            $albumId = $album->id;
        }

        if (!JFolder::exists(JPATH_ROOT . '/images/cover/profile/' . $this->joomlaId . '/'))
            JFolder::create(JPATH_ROOT . '/images/cover/profile/' . $this->joomlaId . '/');

        $thumbPath = JPATH_ROOT . '/images/cover/' . $type . '/' . $this->joomlaId . '/thumb_' . $hash . '.jpg';

        // generate files
        JFile::copy($cover->get('path'), $dest);
        CPhotos::generateThumbnail($cover->get('path'), $thumbPath, $cover->get('type'));

        $cTable = JTable::getInstance(ucfirst($type), 'CTable');
        $cTable->load($this->joomlaId);

        if ($cTable->setCover(str_replace(JPATH_ROOT . '/', '', $dest)))
        {
            $photo = JTable::getInstance('Photo', 'CTable');

            $photo->albumid = $albumId;
            $photo->image = str_replace(JPATH_ROOT . '/', '', $dest);
            $photo->caption = ucwords($this->network) . ' Cover Photo';
            $photo->filesize = filesize($cover->get('path'));
            $photo->creator = $this->joomlaId;
            $photo->created = $now->toSql();
            $photo->published = 1;
            $photo->thumbnail = str_replace(JPATH_ROOT . '/', '', $thumbPath);

            if ($photo->store())
            {
                $album->load($albumId);
                $album->photoid = $photo->id;
                $album->store();
            }
        }
    }

    protected function setAvatar($socialAvatar)
    {
        jimport('joomla.filesystem.file');

        CFactory::load('helpers', 'image');

        $imageMaxWidth = 160;
        $errorDetected = false;
        // Get a hash for the file name.
        $fileName = JApplication::getHash($socialAvatar . time());
        $hashFileName = JString::substr($fileName, 0, 24);
        $socialAvatarFile = $this->getAvatarPath() . '/' . $socialAvatar;

        //@todo: configurable path for avatar storage?
        $socialFileExtension = substr($socialAvatar, strpos($socialAvatar, '.'));
        switch ($socialFileExtension)
        {
            case ".png" :
                $socialFileExtensionType = "image/png";
                break;
            case ".gif" :
                $socialFileExtensionType = "image/gif";
                break;
            case ".jpg" :
                $socialFileExtensionType = "image/jpg";
                break;
            default:
                JFBCFactory::log("File type not supported for user " . $this->joomlaId . ", Social Avatar '" . $socialAvatar . "', type '" . $socialFileExtension . "'", 'error');
                $errorDetected = true;
        }
        if ($errorDetected)
            return false;

        $storage = JPATH_ROOT . '/images/avatar';
        $storageImage = $storage . '/' . $hashFileName . $socialFileExtension;
        $storageThumbnail = $storage . '/thumb_' . $hashFileName . $socialFileExtension;
        $image = 'images/avatar/' . $hashFileName . $socialFileExtension;
        $thumbnail = 'images/avatar/' . 'thumb_' . $hashFileName . $socialFileExtension;

        $app = JFactory::getApplication();
        $tmpPath = $app->getCfg('tmp_path');
        $tmpAvatarImage = 'tmp_copy' . $this->socialId . '.jpg';
        $copiedAvatarFile = $tmpPath . '/' . $tmpAvatarImage;
        JFile::copy($socialAvatarFile, $copiedAvatarFile);

        // Generate full image
        if (JFile::exists($socialAvatarFile))
        {
            if (!CImageHelper::resizeProportional($socialAvatarFile, $storageImage, $socialFileExtensionType, $imageMaxWidth))
            {
                JFBCFactory::log('There was an error when trying to move image (' . $socialAvatarFile . ') to ' . $storageImage, 'error');
                return false;
            }
        }
        else
        {
            JFBCFactory::log('The avatar file (' . $socialAvatarFile . ') for user ' . $this->joomlaId . ' does not exist. Using default image instead.', 'error');
            return false;
        }

        // Generate thumbnail
        if (!CImageHelper::createThumb($copiedAvatarFile, $storageThumbnail, $socialFileExtensionType))
        {
            JFBCFactory::log('There was an error when trying to move image (' . $copiedAvatarFile . ') to ' . $storageImage, 'error');
            return false;
        }

        $origGuest = JFactory::getUser($this->joomlaId)->guest;
        $jsUser = CFactory::getUser($this->joomlaId);
        $currentAvatar = $jsUser->_avatar;
        $currentThumb = $jsUser->_thumb;
        $jsUser->set('_avatar', $image);
        $jsUser->set('_thumb', $thumbnail);
        if ($jsUser->guest === null)
            $jsUser->set('guest', $origGuest); // This prevents a weird issue where the 'guest' is set to null.
        // Save and delete the old avatars, if they exist
        if ($jsUser->save())
        {
            if (JFile::exists(JPATH_SITE . '/' . $currentAvatar))
                JFile::delete(JPATH_SITE . '/' . $currentAvatar);
            if (JFile::exists(JPATH_SITE . '/' . $currentThumb))
                JFile::delete(JPATH_SITE . '/' . $currentThumb);
        }

        return true;
    }

    private function getProfileTypes($showDefault = false)
    {
        $profileModel = CFactory::getModel('profile');

        $profileTypes = $profileModel->getProfileTypes();
        if ($showDefault)
        {
            $defaultType = array(0 => array('id' => 0, 'name' => 'Default'));
            $profileTypes = array_merge($defaultType, $profileTypes);
        }

        return $profileTypes;
    }

    /* Import previous JomSocial profile connections
     *
     */

    public function jfbcImportConnections()
    {
        // Get original JomSocial connections
        $query = 'SELECT * FROM #__community_connect_users WHERE type="facebook"';
        $this->db->setQuery($query);
        $jsConnections = $this->db->loadObjectList();
        $userMapModel = JFBCFactory::usermap();

        foreach ($jsConnections as $jsConnection)
            $userMapModel->map($jsConnection->userid, $jsConnection->connectid, 'facebook');
    }

    // Helper function for escaping profile fields - required.
    // Copied from /com_community/libraries/template.php
    public function escape($text)
    {
        CFactory::load('helpers', 'string');
        return CStringHelper::escape($text);
    }

    private function getJomSocialDate($fieldValue)
    {
        if (empty($fieldValue))
        {
            $dateValue = null;
        }
        else if (is_array($fieldValue))
        {
            $dateValue = $fieldValue;
        }
        else
        {
            $date = new JDate($fieldValue);
            $dateValue[0] = $date->format("d");
            $dateValue[1] = $date->format("m");
            $dateValue[2] = $date->format("Y");
        }
        return $dateValue;
    }

    private function getJomSocialGender($fieldValue)
    {
        if (version_compare($this->getJomSocialVersion(), '3.2.0.6', '>='))
        {
            if ($fieldValue == 'female' || $fieldValue == 'COM_COMMUNITY_FEMALE')
                $fieldValue = 'COM_COMMUNITY_FEMALE';
            else
                $fieldValue = 'COM_COMMUNITY_MALE';
        }

        return $fieldValue;
    }

    protected function loadSettings($network, $joomlaId = null, $socialId = null)
    {
        include_once($this->_componentFolder . '/' . $this->_componentFile);
        parent::loadSettings($network, $joomlaId, $socialId);
    }

    //***************** Point rewards *******************//
    public function awardPoints($userId, $name, $args)
    {
        $key = $args->get('key', '');
        require_once(JPATH_ROOT . '/components/com_community/libraries/userpoints.php');
        $name = 'jfbconnect.' . $name;
        CUserPoints::assignPoint($name, $userId);
    }

    //***************** Import status and rendering ******************************//
    protected function setStatus($socialStatus)
    {
        $user = CFactory::getUser($this->joomlaId);
        CFactory::load('helpers', 'string');

        $jsStatus = $user->getStatus();
        if ($socialStatus != $jsStatus)
        {
            $act = new stdClass();
            $act->cmd = 'profile.write';
            $act->actor = $this->joomlaId;
            $act->target = 0;
            $act->title = $socialStatus;
            $act->content = '';
            $act->app = $this->_name . '.status.' . $this->network;
            $act->comment_type = 'profile.status';
            $act->comment_id = -1;
            $act->like_type = 'profile.status';
            $act->like_id = -1;

            $act->cid = $this->joomlaId;

            CFactory::load('libraries', 'activities');
            CActivityStream::add($act);

            $user->setStatus($socialStatus);
        }
    }

    public function onCommunityStreamRender($act)
    {
        JFBConnectUtilities::loadLanguage('plg_socialprofiles_jomsocial', JPATH_ADMINISTRATOR);
        $actor = CFactory::getUser($act->actor);
        $actorLink = '<a class="cStream-Author" href="' . CUrlHelper::userLink($actor->id) . '">' . $actor->getDisplayName() . '</a>';
        $stream = new stdClass();
        $stream->actor = $actor;
        $stream->message = $act->title;

        $statusAppInfo = explode('.', $act->app);
        $network = array_pop($statusAppInfo);
        if ($network == 'fbstatus')
            $network = 'facebook';
        $network = ucfirst($network);
        $stream->headline = JText::sprintf("PLG_SOCIALPROFILES_JOMSOCIAL_STATUS_UPDATE_HEADLINE", $actorLink, $network);

        return $stream;
    }


    //***************** Status Update Posting ************************************//
    function onProfileStatusUpdate($poster, $prevStatus, $newStatus)
    {
        $this->loadSettings('facebook');

        if (!$this->settings->get('push_status'))
            return;

        if (!$this->checkPrivacy())
            return;

        $jfbcLibrary = JFBCFactory::provider('facebook');
        $user = JFactory::getUser();
        if ($jfbcLibrary->getMappedUserId() && $user->get('id') == $poster)
        {
            $jfbcLibrary->setFacebookMessage($newStatus);
        }
    }

    protected function checkPrivacy($level = PRIVACY_PUBLIC)
    {
        $privacy = null;
        $arg1 = JRequest::getVar('arg1');
        $arg1 = json_decode($arg1);
        $args = rawurldecode($arg1[1]);
        $args = json_decode($args);
        if (isset($args->privacy)) // JomSocial < 3.2(?) way
            $privacy = $args->privacy;
        else // JomSocial 3.2+ way
        {
            $arg3raw = JRequest::getVar('arg3');
            $arg3filtered = substr($arg3raw, strpos($arg3raw, '{'), strpos($arg3raw, '}') - strpos($arg3raw, '{') + 1);
            $arg3 = json_decode($arg3filtered);
            if (isset($arg3->privacy))
                $privacy = $arg3->privacy;
        }

        if (isset($privacy) && ($privacy <= $level))
            return true;
        else
            return false;
    }
}
