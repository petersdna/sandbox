<?php
/**
 * @package         JFBConnect
 * @copyright (c)   2009-2015 by SourceCoast - All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @version         Release v6.5.3
 * @build-date      2015/12/19
 */

if (!(defined('_JEXEC') || defined('ABSPATH'))) {     die('Restricted access'); };

?>

<h1><?php echo JText::_('COM_JFBCONNECT_ACCOUNT_LINKED_ACCOUNTS_TITLE'); ?></h1>
<p><?php echo JText::_('COM_JFBCONNECT_ACCOUNT_LINKED_ACCOUNTS_DESC');?></p>
<div class="sourcecoast accounts">
    <table class="table">
        <?php

        foreach($this->providerData as $providerName=>$providerObj)
        {?>
            <tr>
                <td>
                    <img src="<?php echo JURI::root() . '/media/sourcecoast/images/provider/' . $providerObj->systemName . '/icon.png';?>" alt="<?php echo $providerName;?>"/>
                </td>
                <?php
                if($providerObj->isMapped)
                {?>
                    <td><img class="scicon-y" src="<?php echo JURI::base(true);?>/media/sourcecoast/images/icon-y.png" alt="<?php echo JText::_('COM_JFBCONNECT_ACCOUNT_LINKED_ACCOUNTS_LINKED_LABEL');?>" /></td>
                    <td>
                    <?php
                    if($providerObj->profileUrl)
                    { ?>
                        <a href="<?php echo $providerObj->profileUrl;?>" target="_blank">
                            <?php echo JText::sprintf('COM_JFBCONNECT_ACCOUNT_LINKED_ACCOUNTS_VIEW_PROFILE_LABEL', $providerName);?>
                        </a>
                    <?php }
                    else
                    {
                        echo JText::sprintf('COM_JFBCONNECT_ACCOUNT_LINKED_ACCOUNTS_LOGIN_VIEW_PROFILE_LABEL', $providerName);
                    }?>
                    </td>
                    <td><a href="<?php echo JURI::base(true).'/index.php?option=com_jfbconnect&task=account.unlink&provider='.$providerObj->provider->systemName.'&'. JSession::getFormToken() .'=1';?>">Unlink</a></td>
                <?php }
                else
                {?>
                    <td><img class="scicon-x" src="<?php echo JURI::base(true);?>/media/sourcecoast/images/icon-x.png" alt="<?php echo JText::_('COM_JFBCONNECT_ACCOUNT_LINKED_ACCOUNTS_NOT_LINKED_LABEL');?>"/></td>
                    <td>
                        <a href="javascript:void(0)" onclick="jfbc.login.provider('<?php echo $providerObj->systemName;?>')">
                            <?php echo JText::sprintf('COM_JFBCONNECT_ACCOUNT_LINKED_ACCOUNTS_CONNECT_PROFILE_LABEL', $providerName);?>
                        </a>
                    </td>
                    <td></td>
                <?php }
                ?>
            </tr>
        <?php
        }
        ?>
    </table>
</div>