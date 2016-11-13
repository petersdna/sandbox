<?php
/**
 * @package         SourceCoast Extensions
 * @copyright (c)   2009-2014 by SourceCoast - All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @version         Release v6.2.0
 * @build-date      2014/08/21
 */

// Check to ensure this file is included in Joomla!
if (!(defined('_JEXEC') || defined('ABSPATH'))) { die('Restricted access'); };

class JFBConnectEasytags
{
    private $tagsToReplace = null;

    public function initialize()
    {
        $scTagArray = array('SCPinterest');
        foreach(JFBCFactory::getAllProviders() as $provider)
            $scTagArray[] = 'SC'.$provider->name;
        $this->scTags = implode('|', $scTagArray);

        $this->buildListOfTagsToReplace();
    }

    private function buildListOfTagsToReplace()
    {
        $tagsToReplace = JFBCFactory::cache()->get('system.alleasytags');
        if ($tagsToReplace === false)
        {
            $providers = JFBCFactory::getAllWidgetProviderNames();
            $tagsToReplace = array();
            foreach ($providers as $pName)
            {
                $widgets = JFBCFactory::getAllWidgets($pName);
                foreach ($widgets as $widget)
                {
                    if(is_array($widget->tagName))
                    {
                        foreach($widget->tagName as $tagVariation)
                            $tagsToReplace[strtolower($tagVariation)] = array('provider' => $pName, 'widget' => $widget->systemName);
                    }
                    else
                        $tagsToReplace[strtolower($widget->tagName)] = array('provider' => $pName, 'widget' => $widget->systemName);
                }
            }
            //Manually add SCLinkedinLogin, since JLinkedLogin is the actual tag
            $tagsToReplace['sclinkedinlogin'] = array('provider' => 'linkedin', 'widget' => 'login');

            //Tags like JFBCShare and JFBCRecommendations need to come up after JFBCShareDialog and JFBCRecommendationsBar
            $tagsToReplace = array_reverse($tagsToReplace);
            JFBCFactory::cache()->store($tagsToReplace, 'system.alleasytags');
        }
        $this->tagsToReplace = $tagsToReplace;
    }

    public function replaceWidgetTags($contents)
    {
        //Tag like {JFBCTag} {JLinkedTag} {SCTag} {JFBCTag field=value field2=value2} {JLinkedTag field=value field2=value2} {SCTag field=value field2=value2}
        $regex = '/\{('.$this->scTags.'|JFBC|JLinked)(.*?)}/i';

        if (preg_match_all($regex, $contents, $matches, PREG_SET_ORDER))
        {
            $count = count($matches[0]);
            if ($count == 0)
                return $contents;

            $jfbcRenderKey = JFBCFactory::config()->get('social_tag_admin_key');

            foreach ($matches as $match)
            {
                $tagFields = explode(' ',$match[2]);
                $method = strtolower($match[1]) . strtolower($tagFields[0]);
                unset($tagFields[0]);
                $val = implode(' ', $tagFields);

                $params = $this->splitIntoTagParameters($val);
                $cannotRender = $this->cannotRenderEasyTag($params, $jfbcRenderKey);
                if ($cannotRender)
                    continue;

                if (array_key_exists($method, $this->tagsToReplace))
                {
                    $widgetInfo = $this->tagsToReplace[$method];
                    $fields = $this->getTagParameters($params);
                    $widget = JFBCFactory::widget($widgetInfo['provider'], $widgetInfo['widget'], $fields);
                    $newText = $widget->render();
                }
                else
                {
                    $newText = '';
                }

                $search = '/' . preg_quote($match[0], '/') . '/';
                $contents = preg_replace($search, $newText, $contents, 1);

            }
        }

        return $contents;
    }

    public function replaceJSPlaceholders($contents)
    {
        $javascript = '';

        $providers = JFBCFactory::getAllProviders();
        foreach ($providers as $provider)
        {
            $javascript .= $provider->getHeadData();
        }

        $pinterestWidgets = JFBCFactory::getAllWidgets('pinterest');
        $javascript .= $pinterestWidgets[0]->getHeadData();
        JFBConnectProviderPinterestWidgetShare::$needsJavascript = false;

        $contents = str_replace('<SourceCoastProviderJSPlaceholder />', $javascript, $contents);
        return $contents;
    }

    public function replaceGraphTags($contents)
    {
        $placeholder = '<SCOpenGraphPlaceholder />';
        $regex1 = '/\{SCOpenGraph\s+(.*?)\}/i';
        $newGraphTags = array();

        $this->getGraphContents($regex1, $contents, $newGraphTags);

        //Replace Placeholder with new Head tags
        $defaultGraphFields = JFBCFactory::config()->getSetting('social_graph_fields');
        $skipGraphFields = JFBCFactory::config()->getSetting('social_graph_skip_fields');
        $locale = JFBCFactory::provider('facebook')->getLocale();

        $openGraphLibrary = OpenGraphLibrary::getInstance();
        $openGraphLibrary->addBlockedTags($skipGraphFields);
        $openGraphLibrary->addOpenGraphEasyTags($newGraphTags);
        $openGraphLibrary->addDefaultSettingsTags($defaultGraphFields);
        $openGraphLibrary->addAutoGeneratedTags($locale);
        $graphTags = $openGraphLibrary->buildCompleteOpenGraphList();

        $contents = $openGraphLibrary->removeOverlappingTags($contents);
        $search = '/' . preg_quote($placeholder, '/') . '/';
        $graphTags = str_replace('$', '\$', $graphTags);
        $contents = preg_replace($search, $graphTags, $contents, 1);
        $contents = str_replace($placeholder, '', $contents); //If JLinked attempts to insert, ignore

        return $contents;
    }

    private function getGraphContents($regex, &$contents, &$newGraphTags)
    {
        if (preg_match_all($regex, $contents, $matches, PREG_SET_ORDER))
        {
            $count = count($matches[0]);
            if ($count == 0)
                return true;

            $jfbcRenderKey = JFBCFactory::config()->get('social_tag_admin_key');

            foreach ($matches as $match)
            {
                if (isset($match[1]))
                    $val = $match[1];
                else
                    $val = '';

                $params = $this->splitIntoTagParameters($val);
                $cannotRenderJFBC = $this->cannotRenderEasyTag($params, $jfbcRenderKey);

                if ($cannotRenderJFBC)
                    continue;

                $val = $this->removeRenderKey($val, $jfbcRenderKey);
                $newGraphTags[] = $val;
                $contents = str_replace($match[0], '', $contents);
            }
        }
    }

    private function removeRenderKey($easyTag, $renderKey)
    {
        if ($renderKey != '')
        {
            $key = 'key=' . $renderKey;
            $easyTag = str_ireplace($key . ' ', '', $easyTag); //Key with blank space
            $easyTag = str_ireplace($key, '', $easyTag);
            $easyTag = JFBConnectUtilities::trimNBSP($easyTag);
        }
        return $easyTag;
    }

    /*
     * Determines if the Easy-Tag can be rendered. If it can, then remove the render key
     */
    private function cannotRenderEasyTag(&$params, $renderKey)
    {
        $hasAKey = false;
        $hasRenderKey = false;
        $foundIndex = -1;

        //Currently there are some indices that are skipped, so count method doesn't
        //quite return what we're expecting. TODO: Fix this to not have skipped index
        end($params);
        $count = key($params) + 1;
        reset($params);

        for($i=0;$i<$count;$i++)
        {
            if(isset($params[$i]))
            {
                $p = $params[$i];
                if (stripos($p, "key=") === 0) //Key starts at position 0
                {
                    $hasAKey = true; //If render key is blank, but any key is set, we should not display it
                    $check = substr($p, 4);
                    if($check == false && $renderKey == '')
                    {
                        //Render key is blank, but a key is not really set, even though key= is present in the tag
                        $hasAKey = false;
                        $foundIndex = $i;
                    }
                    else if ($check == $renderKey)
                    {
                        $hasRenderKey = true;
                        $foundIndex = $i;
                    }
                }
            }
        }
        $canRender = ($renderKey == '' && !$hasAKey) || ($renderKey != '' && $hasRenderKey);

        //Remove the key from the parameter array before it gets rendered
        if($canRender && $foundIndex > -1)
            unset($params[$foundIndex]);

        return (!$canRender);
    }

    private function getTagParameters($params)
    {
        $newFields = array();
        foreach($params as $param)
        {
            if($param != null)
            {
                $paramValues = explode('=', $param, 2);
                if (count($paramValues) == 2) //[0] name [1] value
                {
                    $fieldName = strtolower(trim($paramValues[0]));
                    $fieldValue = trim($paramValues[1]);

                    $newFields[$fieldName] = $fieldValue;
                }
            }
        }
        return $newFields;
        //$this->fields->loadArray($newFields);
    }

    private function splitIntoTagParameters($paramList)
    {
        $paramList = JFBConnectUtilities::replaceNBSPWithSpace($paramList);
        $params = explode(' ', $paramList);

        $count = count($params);
        for ($i = 0; $i < $count; $i++)
        {
            $params[$i] = str_replace('"', '', $params[$i]);
            if (strpos($params[$i], '=') === false && $i > 0)
            {
                $previousIndex = $this->findPreviousParameter($params, $i - 1);
                //Combine this with previous entry and space
                $combinedParamValue = $params[$previousIndex] . ' ' . $params[$i];
                $params[$previousIndex] = $combinedParamValue;
                unset($params[$i]);
            }
        }
        return $params;
    }

    private function findPreviousParameter($params, $i)
    {
        for ($index = $i; $index >= 0; $index--)
        {
            if (isset($params[$index]))
                return $index;
        }
        return 0;
    }

    public function canExtendJoomlaForm($selView = 'login', $isConnected = false, $userFormPosition = '1')
    {
        $option = JRequest::getCmd('option');
        $view = JRequest::getCmd('view');
        $user = JFactory::getUser();

        if ($option == 'com_users' && $view == $selView)
        {
            if (($view == 'login' && $user->guest) ||
                ($view == 'registration' && $user->guest) ||
                ($view == 'profile' && $isConnected)
            )
            {
                if($userFormPosition == SC_VIEW_TOP || $userFormPosition == SC_VIEW_BOTH)
                {
                    return true;
                }
                if($userFormPosition == SC_VIEW_BOTTOM || $userFormPosition == SC_VIEW_BOTH)
                {
                    return true;
                }
            }
        }

        return false;
    }

    /* Important: Call this method in conjunction with canExtendJoomlaForm to see if the HTML should
    actually be added to the page. Split up like this for performance improvements*/
    public function extendJoomlaUserForms($htmlTag, $userFormPosition = '1')
    {
        $document = JFactory::getDocument();
        $output = $document->getBuffer('component');

        if($userFormPosition == SC_VIEW_TOP || $userFormPosition == SC_VIEW_BOTH)
        {
            $output = $htmlTag . $output;
        }
        if($userFormPosition == SC_VIEW_BOTTOM || $userFormPosition == SC_VIEW_BOTH)
        {
            $output = $output . $htmlTag;
        }

        $document->setBuffer($output, 'component');
    }

    /*
     * Given JFBCLike layout of box_count, button_count or standard, returns
     * the parameters to set the same layout for the other share buttons
     */
    public function getShareButtonLayout($provider, $layout, $addQuotes='')
    {
        $layoutParams = '';
        if($layout == 'box_count')
        {
            if($provider == 'facebook')
                $layoutParams = $this->quoteParam($addQuotes, 'layout', 'box_count');
            else if($provider == 'google')
            {
                $layoutParams = $this->quoteParam($addQuotes, 'data-annotation','bubble');
                $layoutParams .= $this->quoteParam($addQuotes, 'data-size','tall');
            }
            else if($provider == 'twitter')
                $layoutParams = $this->quoteParam($addQuotes, 'data-count', 'vertical');
            else if($provider == 'linkedin')
                $layoutParams = $this->quoteParam($addQuotes, 'data-counter', 'top');
            else if($provider == 'pinterest')
                $layoutParams = $this->quoteParam($addQuotes, 'data-pin-config','above');

        }
        else if($layout == 'button_count')
        {
            if($provider == 'facebook')
                $layoutParams = $this->quoteParam($addQuotes, 'layout', 'button_count');
            else if($provider == 'google')
            {
                $layoutParams = $this->quoteParam($addQuotes, 'data-annotation','bubble');
                $layoutParams .= $this->quoteParam($addQuotes, 'data-size','medium');
            }
            else if($provider == 'twitter')
                $layoutParams = $this->quoteParam($addQuotes, 'data-count', 'horizontal');
            else if($provider == 'linkedin')
                $layoutParams = $this->quoteParam($addQuotes, 'data-counter', 'right');
            else if($provider == 'pinterest')
                $layoutParams = $this->quoteParam($addQuotes, 'data-pin-config','beside');
        }
        else //if($layout == 'standard' || $layout == 'button')
        {
            if($provider == 'facebook')
            {
                if($layout == 'standard')
                {
                    $layoutParams = $this->quoteParam($addQuotes, 'layout', 'standard');
                    $layoutParams .= $this->quoteParam($addQuotes, 'width', '50');
                    $layoutParams .= $this->quoteParam($addQuotes, 'show_faces', 'false');
                }
                else if($layout == 'button')
                    $layoutParams = $this->quoteParam($addQuotes, 'layout', 'button');
            }
            else if($provider == 'google')
            {
                $layoutParams = $this->quoteParam($addQuotes, 'data-annotation','none');
                $layoutParams .= $this->quoteParam($addQuotes, 'data-size','standard');
            }
            else if($provider == 'twitter')
                $layoutParams = $this->quoteParam($addQuotes, 'data-count', 'none');
            else if($provider == 'linkedin')
                $layoutParams = $this->quoteParam($addQuotes, 'data-counter', 'no_count');
            else if($provider == 'pinterest')
                $layoutParams = $this->quoteParam($addQuotes, 'data-pin-config','none');
        }

        return $layoutParams;
    }

    private function quoteParam($quote, $option, $value)
    {
        return ' '.$option.'='.$quote.$value.$quote;
    }
}