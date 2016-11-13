<?php
/**
 * @package         JFBConnect
 * @copyright (c)   2009-2015 by SourceCoast - All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @version         Release v6.5.3
 * @build-date      2015/12/19
 */

class JFBConnectProviderFacebookChannelPage extends JFBConnectChannel
{
    public function setup()
    {
        $this->name = "Page";
        $this->outbound = true;
        $this->inbound = true;
        $this->requiredScope[] = 'manage_pages';
    }

    // manipulate the input data in some way (retrieve an access token, etc)
    public function onBeforeSave($data)
    {
        //get page access token
        // set $newData['token'] to page access token
        $jid = $data['attribs']['user_id'];
        $uid = JFBCFactory::usermap()->getProviderUserId($jid, 'facebook');
        $pageId = $data['attribs']['page_id'];
        $pageAccessToken = '';
        $params = array();
        $params['access_token'] = JFBCFactory::usermap()->getUserAccessToken($jid, 'facebook');
        $accounts = $this->provider->api($uid . '/accounts', $params, true, 'GET');
        foreach($accounts['data'] as $account)
        {
            if($account['id'] == $pageId)
            {
                $pageAccessToken = $account['access_token'];
                break;
            }
        }
        $data['attribs']['access_token'] = $pageAccessToken;

        return $data;
    }

    public function onAfterSave($newData, $oldData)
    {
        if($newData['attribs']['allow_posts'])
        {
            $this->requiredScope[] = 'publish_actions';
            $this->requiredScope[] = 'publish_pages';

        }

        parent::onAfterSave($newData, $oldData);
    }

    public function canPublish($data)
    {
        $canPublish = false;

        $jid = $data['attribs']['user_id'];
        if ($jid)
        {
            $uid = JFBCFactory::usermap()->getProviderUserId($jid, 'facebook');
            if ($uid && isset($data['attribs']['page_id']) && ($data['attribs']['page_id'] != '--') &&
                JFBCFactory::provider('facebook')->hasScope($uid, 'manage_pages') &&
                (JFBCFactory::provider('facebook')->hasScope($uid, 'publish_actions')
                    || JFBCFactory::provider('facebook')->hasScope($uid, 'publish_pages')
                    || !$data['attribs']['allow_posts'])
            )
            {
                $canPublish = true;
            }
        }

        return $canPublish;
    }

    public function getStream($stream)
    {
        $pageId = $this->options->get('page_id');
        if (!$pageId || $pageId == '--')
            return;

        $feed = JFBCFactory::cache()->get('facebook.page.stream.' . $pageId);
        if ($feed === false)
        {
            $params = array();
            $params['access_token'] = $this->options->get('access_token');
            //NOTE: Uncomment to use user access token instead of page access token
            //$params['access_token'] = JFBCFactory::usermap()->getUserAccessToken($this->options->get('user_id'), 'facebook');
            $feed = $this->provider->api($pageId . '/feed?fields=message,from,updated_time,name,link,picture,caption,description,comments', $params, true, 'GET');
            JFBCFactory::cache()->store($feed, 'facebook.page.stream.' . $pageId);
        }

        if($feed['data'])
        {
            foreach($feed['data'] as $data)
            {
                if(array_key_exists('from', $data) && array_key_exists('message', $data) && ($this->options->get('show_admin_only') == 0 || $data['from']['id'] == $pageId))
                {
                    $post = new JFBConnectPost();

                    if(isset($data['actions'][0]))
                    {
                        $post->link = $data['actions'][0]['link'];
                    }
                    else
                    {
                        $ids = explode("_", $data['id']);
                        $idIndex = count($ids) - 1;
                        $post->link = 'https://www.facebook.com/'.$pageId.'/posts/'.$ids[$idIndex];
                    }

                    $post->message = (array_key_exists('message', $data)?$data['message']:"");
                    $post->authorID = $data['from']['id'];
                    $post->authorScreenName = $data['from']['name'];
                    $post->updatedTime = (array_key_exists('updated_time', $data)?$data['updated_time']:"");
                    $post->thumbTitle = (array_key_exists('name', $data)?$data['name']:"");
                    $post->thumbLink = (array_key_exists('link', $data)?$data['link']:"");
                    $post->thumbPicture = (array_key_exists('picture', $data)?$data['picture']:"");
                    $post->thumbCaption = (array_key_exists('caption', $data)?$data['caption']:"");
                    $post->thumbDescription = (array_key_exists('description', $data)?$data['description']:"");
                    $post->comments = (array_key_exists('comments', $data)?$data['comments']:"");

                    $stream->addPost($post);
                }
            }
        }
    }

    public function post(JRegistry $data)
    {
        $pageId = $this->options->get('page_id');
        $message = $data->get('message', '');
        $link = $data->get('link', '');

        $params = array();
        if($this->options->get('post_as_page'))
            $params['access_token'] = $this->options->get('access_token');
        else
            $params['access_token'] = JFBCFactory::usermap()->getUserAccessToken($this->options->get('user_id'), 'facebook');
        $params['message'] = $message;
        $params['link'] = $link;

        $return = $this->provider->api($pageId . '/feed', $params);
        if ($return && $return !== false)
            return JText::_('COM_JFBCONNECT_CHANNELS_FACEBOOK_PAGE_POST_SUCCESS');
        else
            return false;
    }
}