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
class JFBConnectProfileYahoo extends JFBConnectProfile
{
    protected function setProviderFields()
    {
        $this->providerFields = array(
            '0' => 'None',
            'familyName' => 'Family Name',
            'givenName' => 'Given Name',
            'nickname' => 'Nickname',
            'message' => 'Message',
            'aboutMe' => 'About Me',
            'gender' => 'Gender', // Can be one of the following values: "M", "F" or "Unspecified".
            'location' => 'Location',
            'profileUrl' => 'Profile URL',
        );
    }

    public function fetchProfile($userId, $fields)
    {
        $profile = new JFBConnectProfileDataYahoo();

        if ($this->provider->client->isAuthenticated())
        {
            $response = $this->provider->client->getCreds();
            $profile->loadObject($response->body);
        }

        return $profile;
    }

    public function fetchStatus($userId)
    {
        $profile = $this->fetchProfile($userId, 'status');
        return $profile->get('status.message', '');
    }


    // nullForDefault - If the avatar is the default image for the social network, return null instead
    // Prevents the default avatars from being imported
    function getAvatarUrl($userId, $nullForDefault = true, $params = null)
    {
        $nullString = $nullForDefault ? 'null' : 'notnull';
        $avatarUrl = JFBCFactory::cache()->get('yahoo.avatar.' . $nullString . '.' . $userId);
        if ($avatarUrl === false)
        {
            $response = $this->fetchProfile($userId, "image");
            $image = $response->get('image', null);
            $avatarUrl = $image->imageUrl;
            if ($nullForDefault && (!$avatarUrl || strpos($avatarUrl, 'https://s.yimg.com/dg/users')))
                $avatarUrl = null;
            JFBCFactory::cache()->store($avatarUrl, 'yahoo.avatar.' . $nullString . '.' . $userId);
        }
        return $avatarUrl;
    }

    function getProfileUrl($userId)
    {
        return 'https://profile.yahoo.com/'.$userId;
    }

}

class JFBConnectProfileDataYahoo extends JFBConnectProfileData
{
    public function get($path, $default = "")
    {
        $data = null;
        if ($this->exists($path))
            $data = parent::get($path, $default);
        else
        {
            if ($path == 'full_name')
                return parent::get('givenName') . ' ' . parent::get('familyName');
            else if ($path == 'first_name')
                $data = parent::get('givenName');
            else if ($path == 'last_name')
                $data = parent::get('familyName');
            else if ($path == "middle_name")
                $data =  "";
            else if ($path == 'email')
                return null; // This isn't available from YAHOO though you can get the email using openid
            else if ($path == 'message')
                $data = parent::get('status.message');
        }

        if (!is_null($data))
        {
            // format or manipulate the data as necessary here
            // format or manipulate the data as necessary here
            return $data;
        }
        else
            return $default;
    }

}