<?php
/**
 * @package         JFBConnect
 * @copyright (c)   2009-2015 by SourceCoast - All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @build-date      2015/12/19
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
jimport('sourcecoast.articleContent');

class plgContentJFBCContent extends JPlugin
{
    protected $renderKeyString = '';
    protected $app = null;
    public $isJoomla = true;
    public $option = 'com_content';
    public $view = 'article';

    public function __construct(& $subject, $config)
    {
        // Don't even register this plugin if JFBCFactory isn't loaded and available (the jfbcsystem plugin likely isn't enabled)
        if (class_exists('JFBCFactory'))
            parent::__construct($subject, $config);
    }

    public function onContentBeforeDisplay($context, &$row, &$params, $page=0)
    {
        //check if we have to render
        if(!$this->isRender($row, $context, $params))
            return '';

        return $this->getHTML($row, array(SC_VIEW_TOP, SC_VIEW_BOTH));
    }

    public function onContentAfterDisplay($context, &$row, &$params, $page=0)
    {
        //check if we have to render
        if(!$this->isRender($row, $context, $params))
            return '';

        return $this->getHTML($row, array(SC_VIEW_BOTTOM, SC_VIEW_BOTH));
    }

    //only available for joomla 3.x
    public function onContentAfterTitle($context, &$row, &$params, $page=0)
    {
        //check if we have to render
        if(!$this->isRender($row, $context, $params))
            return '';

        return $this->getHTML($row, array(SC_VIEW_TITLE));
    }

    private function isRender($article, $context, $params)
    {
        if (!class_exists('JFBCFactory'))
            return false;

        $this->app = JFactory::getApplication();

        //only render in the frontend/site
        if ($this->app->isAdmin())
            return false;

        //Get Social RenderKey
        $jfbcLibrary = JFBCFactory::provider('facebook');
        $renderKey = $jfbcLibrary->getSocialTagRenderKey();
        if ($renderKey)
            $this->renderKeyString = " key=" . $renderKey;

        $this->option = $this->app->input->getCmd('option');
        $this->view = $this->app->input->getCmd('view');

        //Don't show when printing
        $template = $this->app->input->getCmd('tmpl');
        $printing = $this->app->input->getInt('print', 0);
        if($printing && $template=='component')
            return false;

        switch ($this->option)
        {
            case "com_content":
                $result = $this->isRenderContent($article, $context, $params);
                break;
            case "com_k2":
                $this->isJoomla = false;
                $result = $this->isRenderK2($article, $context, $params);
                break;
            default:
                $result = true;
                break;
        }

        return $result;
    }

    private function isRenderContent($article, $context, $params)
    {
        // Check to only see if we're inside com_content, not tags (or anywhere else)
        if ((false === strpos($context, "com_content")) || empty($article->id))
            return false;

        // Make sure we're showing the article from the component, not from a module
        if (!$params || ((get_class($params) == 'JRegistry' || get_class($params) == 'Joomla\\Registry\\Registry') && !$params->exists('article_layout')))
            return false;

        return true;
    }

    private function isRenderK2($article, $context)
    {
        // Check for correct context
        if (strpos($context, "com_k2") === false) {
            return false;
        }

        //only show on this following view item, itemlist, latest
        if(!in_array($this->view, array('item', 'itemlist', 'latest')))
            return false;

        return true;
    }

    private function getHTML($article, $positionA)
    {
        $html = '';
        if($this->option == 'com_content')
            $infix = $this->view == 'article' ? 'article' : 'blog';
        elseif($this->option == 'com_k2')
            $infix = $this->view == 'item' ? 'k2_item' : 'k2_blog';

        //show like in content
        if(in_array($this->getSocialItemViewPosition($article, 'like'), $positionA) && $this->showSocialItemInArticle($article, 'like') && $this->option == 'com_content')
            $html .= $this->getLikeBtn($article, $infix);

        //show comments in content
        if(in_array($this->getSocialItemViewPosition($article, 'comment'), $positionA) && $this->showSocialItemInArticle($article, 'comment') && $this->option == 'com_content')
            $html .= $this->getComments($article, $infix);

        //show like in content
        if(in_array($this->getSocialK2ItemViewPosition($article, 'like'), $positionA) && $this->showSocialItemInK2Item($article, 'like') && $this->option == 'com_k2')
            $html .= $this->getLikeBtn($article, $infix);

        //show comments in content
        if(in_array($this->getSocialK2ItemViewPosition($article, 'comment'), $positionA) && $this->showSocialItemInK2Item($article, 'comment') && $this->option == 'com_k2')
            $html .= $this->getComments($article, $infix);

        return $html;
    }

    private function getLikeBtn($article, $infix = 'article')
    {
        $buttonStyle = JFBCFactory::config()->get('social_'.$infix.'_like_layout_style');
        $showFaces = JFBCFactory::config()->get('social_'.$infix.'_like_show_faces');
        $showShareButton = JFBCFactory::config()->get('social_'.$infix.'_like_show_send_button');
        $width = JFBCFactory::config()->get('social_'.$infix.'_like_width');
        $verbToDisplay = JFBCFactory::config()->get('social_'.$infix.'_like_verb_to_display');
        $font = JFBCFactory::config()->get('social_'.$infix.'_like_font');
        $colorScheme = JFBCFactory::config()->get('social_a'.$infix.'_like_color_scheme');
        $showFacebook = JFBCFactory::config()->get('social_'.$infix.'_like_show_facebook');
        $showLinkedIn = JFBCFactory::config()->get('social_'.$infix.'_like_show_linkedin');
        $showTwitter = JFBCFactory::config()->get('social_'.$infix.'_like_show_twitter');
        $showGooglePlus = JFBCFactory::config()->get('social_'.$infix.'_like_show_googleplus');
        $showPinterest = JFBCFactory::config()->get('social_'.$infix.'_like_show_pinterest');

        //only call opengraph plugin if we show pinterest to remove unnecessary overhead
        $pinImage = $pinText = '';
        if($showPinterest)
        {
            $pinImage = $this->getPinterestImage($article);
            $pinText = $this->getPinterestText($article);
        }

        $url = SCArticleContent::getCurrentURL($article, $this->isJoomla);
        $btnHTML = $likeHTML = $likeExtraHTML = $widthField = '';

        //Only set width for standard layout, not box_count or button_count
        if ($buttonStyle == 'standard')
            $widthField .= ' width=' . $width;

        //show like or with share button
        if($showFacebook)
        {
            $likeHTML .= '{JFBCLike layout=' . $buttonStyle . ' show_faces=' . $showFaces . ' share=' . $showShareButton
                . $widthField . ' action=' . $verbToDisplay . ' font=' . $font
                . ' colorscheme=' . $colorScheme . ' href=' . $url . $this->renderKeyString . '}';
        }

        //show share button only
        else if($showShareButton && !$showFacebook)
        {
            if($buttonStyle == 'standard' || $buttonStyle == 'button')
                $style = 'button';
            else if($buttonStyle == 'box_count')
                $style = 'box_count';
            else
                $style = 'button_count';

            $likeHTML .= '{JFBCShare layout=' . $style . $widthField . ' href=' . $url . $this->renderKeyString . '}';
        }

        if ($showLinkedIn || $showTwitter || $showGooglePlus || $showPinterest)
            $likeExtraHTML = JFBConnectUtilities::getExtraShareButtons($url, $buttonStyle, false, false, $showTwitter, $showGooglePlus, $this->renderKeyString, $showLinkedIn, '50', $showPinterest, $pinImage, $pinText);

        if(!empty( $likeHTML ) || !empty( $likeExtraHTML ))
        {
            $btnHTML .= '<div style="position: relative; top:0px; left:0px; z-index: 99;" class="scsocialbuttons '.$buttonStyle.'">';
            $btnHTML .= $likeExtraHTML;
            $btnHTML .= $likeHTML;
            $btnHTML .= '</div><div style="clear:both"></div>';
        }

        $html = '';
        $intro_text = JFBCFactory::config()->get('social_'.$infix.'_like_intro_text');
        if(!empty($intro_text))
            $html .= '<div class="scsocialbuttons-intro-text">' . $intro_text . '</div>';

        $html .= $btnHTML;
        return $html;
    }

    private function getComments($article, $infix = 'article')
    {
        $width = JFBCFactory::config()->get('social_'.$infix.'_comment_width');
        $numposts = JFBCFactory::config()->get('social_'.$infix.'_comment_max_num');
        $colorscheme = JFBCFactory::config()->get('social_'.$infix.'_comment_color_scheme');
        $orderBy = JFBCFactory::config()->get('social_'.$infix.'_comment_order_by');

        $href = SCArticleContent::getCurrentURL($article, $this->isJoomla);

        if (!$numposts || $numposts == '0')
        {
            $commentText = '{JFBCCommentsCount href=' . $href . $this->renderKeyString . '}';
        } else
        {
            $commentText = '{JFBCComments href=' . $href . ' width=' . $width . ' num_posts=' . $numposts
                . ' colorscheme=' . $colorscheme . ' order_by=' . $orderBy . $this->renderKeyString . '}';
        }

        $html = '';
        $intro_text = JFBCFactory::config()->get('social_'.$infix.'_comment_intro_text');
        if(!empty($intro_text))
            $html .= '<div class="scsocialcomments-intro-text">' . $intro_text . '</div>';

        $html .= '<div style="z-index: 98;" class="scsocialcomments">' . $commentText . '</div>';
        return $html;
    }

    private function getSocialItemViewPosition($article, $infix = 'like')
    {
        $returnValue = "0";
        if ($this->view == 'article' && $article->id != null)
            $returnValue = JFBCFactory::config()->get('social_'.$infix.'_article_view');
        else if ($this->view == 'frontpage' || $this->view == 'featured')
            $returnValue = JFBCFactory::config()->get('social_'.$infix.'_frontpage_view');
        else if ($this->view == 'category' && $article->catid != null)
            $returnValue = JFBCFactory::config()->get('social_'.$infix.'_category_view');

        return $returnValue;
    }

    private function getSocialK2ItemViewPosition($article, $infix = 'like')
    {
        $layout = $this->app->input->getCmd('layout');
        $task = $this->app->input->getCmd('task');

        $returnValue = "0";
        if ($this->view == 'item' && $article->id != null)
            $returnValue = JFBCFactory::config()->get('social_k2_'.$infix.'_item_view');
        else if ($this->view == 'itemlist')
        {
            if ($this->isK2Layout($layout, $task, 'category')
                || $this->isK2Layout($layout, $task, 'search')
                || $this->isK2Layout($layout, $task, 'date')
            )
                $returnValue = JFBCFactory::config()->get('social_k2_'.$infix.'_category_view');
            else if ($this->isK2Layout($layout, $task, 'generic') || $this->isK2Layout($layout, $task, 'tag'))
                $returnValue = JFBCFactory::config()->get('social_k2_'.$infix.'_tag_view');
            else if ($this->isK2Layout($layout, $task, 'user') && $this->app->input->getInt('id', 0))
                $returnValue = JFBCFactory::config()->get('social_k2_'.$infix.'_userpage_view');
        } else if ($this->view == 'latest')
            $returnValue = JFBCFactory::config()->get('social_k2_'.$infix.'_latest_view');

        return $returnValue;
    }

    private function isK2Layout($layout, $task, $targetLayout)
    {
        return ($layout == $targetLayout || $task == $targetLayout);
    }

    private function showSocialItemInArticle($article, $infix = 'like')
    {
        $articleIncludeIds =  JFBCFactory::config()->get('social_'.$infix.'_article_include_ids');
        $articleExcludeIds =  JFBCFactory::config()->get('social_'.$infix.'_article_exclude_ids');
        $catIncludeType =  JFBCFactory::config()->get('social_'.$infix.'_cat_include_type');
        $catIds =  JFBCFactory::config()->get('social_'.$infix.'_cat_ids');

        //Show in Article
        $includeArticles = explode(",", $articleIncludeIds);
        $excludeArticles = explode(",", $articleExcludeIds);

        //Specific Article is included or excluded, then show or don't show it.
        if ($includeArticles != null && in_array($article->id, $includeArticles))
            return true;
        else if ($excludeArticles != null && in_array($article->id, $excludeArticles))
            return false;

        //Show in Category
        $categories = unserialize($catIds);
        $inCategoryArray = $categories != null && in_array($article->catid, $categories);

        if ($catIncludeType == SC_TYPE_INCLUDE)
            return $inCategoryArray ? true : false;
        else if ($catIncludeType == SC_TYPE_EXCLUDE)
            return $inCategoryArray ? false : true;

        return true;
    }

    private function showSocialItemInK2Item($article, $infix = 'like')
    {
        $articleIncludeIds = JFBCFactory::config()->get('social_k2_'.$infix.'_item_include_ids');
        $articleExcludeIds = JFBCFactory::config()->get('social_k2_'.$infix.'_item_exclude_ids');
        $catIncludeType = JFBCFactory::config()->get('social_k2_'.$infix.'_cat_include_type');
        $catIds = JFBCFactory::config()->get('social_k2_'.$infix.'_cat_ids');

        //Show in Article
        $includeArticles = explode(",", $articleIncludeIds);
        $excludeArticles = explode(",", $articleExcludeIds);

        //Specific Article is included or excluded, then show or don't show it.
        if ($includeArticles != null && in_array($article->id, $includeArticles))
            return true;
        else if ($excludeArticles != null && in_array($article->id, $excludeArticles))
            return false;

        //Show in Category
        $categories = unserialize($catIds);
        $inCategoryArray = $categories != null && in_array($article->catid, $categories);

        if ($catIncludeType == SC_TYPE_INCLUDE)
            return $inCategoryArray ? true : false;
        else if ($catIncludeType == SC_TYPE_EXCLUDE)
            return $inCategoryArray ? false : true;

        return true;
    }

    /* Methods to get / set data in article text */
    private function getPinterestImage($article)
    {
        JPluginHelper::importPlugin('opengraph');
        $images = $this->app->triggerEvent('onOpenGraphGetBestImage', array($article));
        return current(array_filter($images)); // return first non-null image in the array
    }

    private function getPinterestText($article)
    {
        JPluginHelper::importPlugin('opengraph');
        $text = $this->app->triggerEvent('onOpenGraphGetBestText', array($article));
        return current(array_filter($text)); // return first non-null text in the array
    }

}