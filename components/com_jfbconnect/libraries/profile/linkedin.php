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

// Probably should work toward extending the JFBConnectFacebookLibrary for this class.
// Too much intertwined in the root library right now.
class JFBConnectProfileLinkedin extends JFBConnectProfile
{
    protected function setProviderFields()
    {
        $this->providerFields = array(
                '0' => 'None',
                'first-name' => 'First Name',
                'last-name' => 'Last Name',
                'maiden-name' => 'Maiden Name',
                'formatted-name' => 'Formatted Name',
                'headline' => 'Headline',
                'location.name' => 'Current Location',
                'location.country.code' => 'Current Country Code',
                'industry' => 'Industry',
                'current-share.comment' => 'Current Status/Last Share',
                'num-connections' => 'Number of Connections',
            //        'num-connections-capped' => 'Basic Info - Number of Connections (Capped)',
                'summary' => 'Summary (Long description)',
                'specialties' => 'Specialities (Short description)',
            //'specialties' => 'Basic Info - Specialties',
                'positions' => 'Current Position',
                'picture-url' => 'Profile Picture URL',
                'site-standard-profile-request' => 'Authenticated Profile URL',
                'public-profile-url' => 'Public Profile URL',
            //Email Fields - r_emailaddress permission
                'email-address' => 'Primary Email Address',
        );
    }

    /**
     *  Get all permissions that are required by LinkedIn for email, status, and/or profile, regardless
     *    of whether they're set to required in JFBConnect
     * @return string Comma separated list of FB permissions that are required
     */

    public function getRequiredScope()
    {
        return "r_basicprofile r_emailaddress";
    }

    /* Fetch a user's profile based on the passed in array of fields
    * @return JRegistry with profile field values
    */
    public function fetchProfile($userId, $fields)
    {
        if (!is_array($fields))
            $fields = array($fields);

        if (in_array('first_name', $fields))
        {
            $fields[] = 'first-name';
            unset($fields[array_search('first_name', $fields)]);
        }
        if (in_array('last_name', $fields))
        {
            $fields[] = 'last-name';
            unset($fields[array_search('last_name', $fields)]);
        }
        if (in_array('full_name', $fields))
        {
            $fields[] = 'first-name';
            $fields[] = 'last-name';
            unset($fields[array_search('full_name', $fields)]);
        }
        if (in_array('email', $fields))
        {
            $fields[] = 'email-address';
            unset($fields[array_search('email', $fields)]);
        }
        if (in_array('middle_name', $fields))
            unset($fields[array_search('middle_name', $fields)]);

        $fields = array_unique($fields);

        $profile = new JFBConnectProfileDataLinkedin();
        if (!empty($fields))
        {
            $url = 'https://api.linkedin.com/v1/people/' . $userId . ':(' . implode(',', $fields) . ')';
            try
            {
                $data = $this->provider->client->query($url);
                $data = json_decode($data->body, true);
                $profile->loadObject($data);
            }
            catch (Exception $e)
            {
                if (JFBCFactory::config()->get('facebook_display_errors'))
                    JFBCFactory::log($e->getMessage());
            }
        }
        return $profile;
    }

    /* Fetch a user's profile based on the passed in array of fields
    * @return JRegistry with profile field values
    */
    public function fetchStatus($providerId)
    {
        $status = $this->fetchProfile($providerId, 'current-share');
        return $status->get('current-share.comment');
    }


    // nullForDefault - If the avatar is the default image for the social network, return null instead
    // Prevents the default avatars from being imported
    function getAvatarUrl($providerId, $nullForDefault = true, $params = null)
    {
        $avatarUrl = JFBCFactory::cache()->get('linkedin.avatar.' . $providerId);
        if ($avatarUrl === false)
        {
            $data = $this->fetchProfile($providerId, 'picture-urls::(original)');
            $avatarUrl = $data->get('picture-urls.values.0');
            if ($avatarUrl == "")
                $avatarUrl = null;
            JFBCFactory::cache()->store($avatarUrl, 'linkedin.avatar.' . $providerId);
        }
        return $avatarUrl;
    }

    function getProfileUrl($memberId)
    {
        return 'http://www.linkedin.com/x/profile/' . $this->provider->appId . '/' . $memberId;
    }

}

class JFBConnectProfileDataLinkedin extends JFBConnectProfileData
{
    public function get($path, $default = "")
    {
        $value = $default;

        if ($path == 'full_name')
            return parent::get('firstName') . ' ' . parent::get('lastName');
        else if ($path == 'email')
            return parent::get('emailAddress');
        else if ($path == "middle_name")
            return "";

        if ($path != 'id')
        {
            if ($path == "first_name" || $path == "last_name")
                $path = str_replace('_', '-', $path);
            // Make the path into a JSON key value
            $parts = explode('-', $path);
            $parts = array_map('ucfirst', $parts);
            $path = implode('', $parts);
            $path = lcfirst($path);
        }

        if ($this->exists($path))
            $value = parent::get($path, $default);

        $valueParts = explode('.', $path);
        $element = parent::get($valueParts[0]);
        if (array_key_exists(1, $valueParts))
            $index = intval($valueParts[1]);
        else
            $index = 0;

        switch ($valueParts[0])
        {
            case 'positions':
                return $this->getPosition($element, $index);
        }

        return $value;
    }

    private function getPosition($element, $index)
    {
        if (!property_exists($element->values, $index))
            return null;

        $position = $element->values->$index;
        $dateString = $this->getDateRange($position);

        $newValue = $position->title . ' at ' . $position->company->name;
        $newValue .= ': ' . $dateString;
        if ($position->summary)
            $newValue .= '. ' . $position->summary;

        return $newValue;
    }

    /** Helper functions **/

    private function getDateRange($element)
    {
        $dateRange = "";
        $startDateString = 'startDate';
        $endDateString = 'endDate';
        $start = '';
        $end = '';
        if (property_exists($element, $startDateString))
            $start = $this->getDateString($element->$startDateString, null, null);
        if (property_exists($element, $endDateString))
            $end = $this->getDateString($element->$endDateString, null, null);

        if ($start)
            $dateRange = $start . ' to ';

        if ($end)
            $dateRange .= $end;
        else
            $dateRange .= 'Present';
        return $dateRange;
    }

    private function getDateString($date, $prefix, $suffix)
    {
        $dateValue = '';
        if (is_object($date))
        {
            if (property_exists($date, 'month'))
                $dateValue = $date->month . '-' . $date->year;
            else if (property_exists($date, 'year'))
                $dateValue = $date->year;

            if ($dateValue != '')
            {
                if ($prefix)
                    $dateValue = $prefix . $dateValue;
                if ($suffix)
                    $dateValue = $dateValue . $suffix;
            }
        }
        return $dateValue;
    }

}