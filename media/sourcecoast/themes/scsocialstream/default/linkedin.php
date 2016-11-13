<?php
/**
 * @package         JFBConnect
 * @copyright (c)   2009-2015 by SourceCoast - All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @version         Release v6.5.3
 * @build-date      2015/12/19
 */
$message = $this->makeClickableLinks($this->message);
?>
<div class="linkedin <?php echo $this->channelType;?> post row-fluid">
    <div class="span12 author">
        <div class="provider">
            <?php if($stream->options->get('show_post')):?>
            <a target="_blank" href="<?php echo $this->link;?>">
            <?php endif;?>
                <img src="<?php echo JUri::root(true);?>/media/sourcecoast/images/provider/linkedin/icon.png">
            <?php if($stream->options->get('show_post')):?>
            </a>
            <?php endif;?>
        </div>
        <div class="author-details">
            <?php if($this->type == 'job-posting'):?>
                <span class="company-hiring"><?php echo JText::sprintf('COM_JFBCONNECT_CHANNELS_COMPANY_IS_HIRING_POSITION', $this->authorScreenName, $this->link, $this->jobPosition);?></span>
            <?php else:?>
                <span class="screen-name"><?php echo $this->authorScreenName;?></span>
            <?php endif?>
            <?php if ($stream->options->get('show_date')):?>
                <div class="date"><?php echo $date;?></div>
            <?php endif;?>
        </div>
    </div>
    <div class="row-fluid">
        <div class="message span12"><?php echo $this->message;?><?php if($this->type == 'job-posting') echo "...";?></div>
        <?php if($stream->options->get('show_post')):?>
            <div class="link span12">
                <?php $text = $this->type == 'status-update' ? 'COM_JFBCONNECT_CHANNELS_LINK_TO_POST' : 'COM_JFBCONNECT_CHANNELS_LINK_TO_JOB';?>
                <a target="_blank" href="<?php echo $this->link;?>"><?php echo JText::_($text);?></a>
            </div>
        <?php endif;?>
    </div>
<?php
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
}echo '</div>';
