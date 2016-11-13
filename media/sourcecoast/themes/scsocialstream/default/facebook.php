<?php
/**
 * @package         JFBConnect
 * @copyright (c)   2009-2015 by SourceCoast - All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @version         Release v6.5.3
 * @build-date      2015/12/19
 */
$message = $this->makeClickableLinks($this->message);

echo
'<div class="facebook ' . $this->channelType . ' post row-fluid">
   <div class="span12 author">';
        if ($stream->options->get('show_provider'))
        {
            echo '<div class="provider">';
            if($stream->options->get('show_post'))
                echo '<a href="'.$this->link.'" target="_blank">';
            echo '<img src="' . JUri::root(true) . '/media/sourcecoast/images/provider/facebook/icon.png" />';
            if($stream->options->get('show_post'))
                echo '</a>';
            echo '</div>';
        }
        echo '<div class="author-details">
                <div class="author-image"><img src="'.JFBCFactory::provider('facebook')->profile->getAvatarUrl($this->authorID,false,null).'" /></div>
                <span class="screen-name">' . $this->authorScreenName . '</span>';
        if ($stream->options->get('show_date'))
            echo '<div class="date">' . $date . '</div>';
        echo "</div>";
   echo '</div>';

echo '<div class="row-fluid">
  <div class="message span12">' . $message . '</div>';
if($stream->options->get('show_post'))
    echo '<div class="link span12"><a href="'.$this->link.'" target="_blank">'.JText::_('COM_JFBCONNECT_CHANNELS_LINK_TO_POST').'</a></div>';
echo '</div>';

if ($showLink == 'title')
{
    if ($this->thumbTitle != '')
    {
        ?>
        <div class="row-fluid">
            <div class="preview span12">
                <div class="title"><a href="<?php echo $this->thumbLink; ?>" target="_blank" rel="nofollow"><?php echo $this->thumbTitle; ?></a></div>
            </div>
        </div>
    <?php
    }
}
else if ($showLink == 'full' && ($hasPageInfo || $hasPicture))
{
    ?>
    <div class="row-fluid">
        <div class="preview span12">
            <div class="title"><a href="<?php echo $this->thumbLink; ?>" target="_blank" rel="nofollow"><?php echo $this->thumbTitle; ?></a></div>
            <?php if ($this->thumbPicture)
                echo '<div class="image"><img src="' . $this->thumbPicture . '"/></div>';
            if ($this->thumbCaption)
                echo '<div class="caption"><a href="' . $this->thumbLink . '" target="_blank" rel="nofollow">' . $this->thumbCaption . '</a></div>';
            if ($this->thumbDescription)
                echo '<div class="description"><a href="' . $this->thumbLink . '" target="_blank" rel="nofollow">' . $this->makeClickableLinks($this->thumbDescription) . '</a></div>';
            ?>
        </div>
    </div>
<?php
}
if (is_array($this->comments) && $stream->options->get('show_comments'))
{
    echo '<div class="row-fluid">
      <div class="comments preview span12">
      <div class="text-center">'.JText::_('COM_JFBCONNECT_CHANNELS_FACEBOOK_COMMENTS').'</div>';

    foreach ($this->comments['data'] as $comment)
    {
        $date = $this->getTimestamp($comment['created_time'], $dateTimeFormat);

        echo '<div class="row-fluid">
          <div class="span12 comment">
              <div>
                <span class="from text-left"><img src="https://graph.facebook.com/' . $comment['from']['id'] . '/picture" width="25" /> ' . $comment['from']['name'] . '</span>
                <span class="date text-right">' . $date . '</span>
              </div>
              <div class="message">' . $comment['message'] . '</div>
        </div>
        </div>';
    }
    echo '</div>
    </div>';
}
echo '</div>';