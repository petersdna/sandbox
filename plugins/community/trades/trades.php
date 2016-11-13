<?php
/**
* @copyright (C) 2013 iJoomla, Inc. - All rights reserved.
* @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
* @author iJoomla.com <webmaster@ijoomla.com>
* @url https://www.jomsocial.com/license-agreement
* The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the IJOOMLA Proprietary Use License v1.0
* More info at https://www.jomsocial.com/license-agreement
*/
// no direct access
defined('_JEXEC') or die('Restricted access');

require_once( JPATH_ROOT .'/components/com_community/libraries/core.php');

class plgCommunityTrades extends CApplications
{
    var $name       = "Trades";
    var $_name      = 'trades';

    function plgCommunityTrades(& $subject, $config)
    {
        parent::__construct($subject, $config);
		$this->db = JFactory::getDbo();
    }

    function onProfileDisplay()
    {
        JPlugin::loadLanguage( 'plg_community_myevents', JPATH_ADMINISTRATOR );

        $config = CFactory::getConfig();

        if( !$config->get('enableevents') )
        {
            return JText::_('PLG_EVENTS_EVENT_DISABLED');
        }

        $document   = JFactory::getDocument();

        $mainframe  = JFactory::getApplication();
        $user       = CFactory::getRequestUser();
		$userid 	= $user->id;
        $caching    = $this->params->get('cache', 1);
        $model      = CFactory::getModel( 'Events' );
        $my         = CFactory::getUser();
        $this->loadUserParams();

        //CFactory::load( 'helpers' , 'event' );
        $event      = JTable::getInstance( 'Event' , 'CTable' );
        $handler    = CEventHelper::getHandler( $event );
		
		// get the trades for the User
		$rows = $this->getTrades($userid);


        $events     = $model->getEvents( null , $user->id , $this->params->get( 'sorting' , 'startdate' ) , null , true , false , null , null ,$handler->getContentTypes() , $handler->getContentId() , $this->userparams->get('count', 5 ), false, false, true );

        if($this->params->get('hide_empty', 0) && !count($events)) return '';

        if($caching)
        {
            $caching = $mainframe->getCfg('caching');
        }

        $creatable  = $my->canCreateEvents();
        $cache      = JFactory::getCache('plgCommunityMyEvents');
        $cache->setCaching($caching);
        $callback   = array( $this , '_getEventsHTML');
        $content    = $cache->call($callback, true , $rows , $user , $config , $model->getEventsCount( $user->id ) , $creatable );
		//from old $content 	= $cache->call($callback, $userid, $limit, $limitstart, $row, $app, $total, $cat, $this->params);
        return $content;
    }
	
	function getTrades($userid) {
		$condition = "";

		$sql = "	SELECT * FROM " . $this->db->quoteName('#__trade_assets') . "
					WHERE
							" . $this->db->quoteName('user_id') . " = " . $this->db->quote($userid);

		$this->db->setQuery($sql);
		$row = $this->db->loadObjectList();
		if ($this->db->getErrorNum()) {
			JError::raiseError(500, $this->db->stderr());
		}
		return $row;
	}

    function _getEventsHTML( $createEvents , $rows , $user , $config , $totalEvents , $creatable )
    {
        $titleLength = $config->get('header_title_length', 30);
        $summaryLength = $config->get('header_summary_length', 80);

        ob_start();
        ?>

        <?php

        if( $rows ) { ?>
        <ul class="joms-list--event">
        <?php
        foreach( $rows as $row ) {

            // Get the formated date & time
            $format         =   ($config->get('eventshowampm')) ?  JText::_('COM_COMMUNITY_DATE_FORMAT_LC2_12H') : JText::_('COM_COMMUNITY_DATE_FORMAT_LC2_24H');
            $startdatehtml   =  CTimeHelper::getFormattedTime('10/10/2016', $format);
            $enddatehtml        =   CTimeHelper::getFormattedTime('10/10/2016', $format);
        ?>

            <li class="joms-media--event" title="<?php echo JText::_('THIS IS A TITLE'); ?>">
                <div class="joms-media__calendar">
                    <span class="month"><?php echo $row->asset; ?></span>	
					<span class="date"><?php echo $row->balance; ?></span>
                </div>
                <div class="joms-media__body">
                    <h4 class="reset-gap"><a href="<?php echo CRoute::_('index.php?option=com_community&view=events&task=viewevent&eventid=' . $row->user_id );?>"><?php echo JText::_('Jeroens portfolio');?></a></h4>
                    <div class="joms-gap--small"></div>
                    <?php echo JText::_('Trading in BTC and ETH'); ?>
                    <div class="joms-gap--small"></div>
                    <a href="<?php echo CRoute::_('index.php?option=com_community&view=events&task=viewguest&eventid=' . $row->user_id . '&type='.COMMUNITY_EVENT_STATUS_ATTEND);?>"><?php echo JText::sprintf((!CStringHelper::isSingular($row->user_id)) ? '2 Investors':'4 Investors', $row->user_id);?></a>
                </div>
            </li>
            <?php } ?>
        </ul>
        <?php
        }
        else
        {
        ?>
            <div><?php echo JText::_('PLG_MYEVENTS_NO_EVENTS_CREATED_BY_THE_USER_YET');?></div>
        <?php
        }
        ?>
        <small>
        <?php if ($creatable) { ?>
		    <button
            onclick="joms.api.photoUpload('<?php echo $row->user_id; ?>', '<?php echo $row->user_id; ?>', '<?php echo $row->user_id; ?>');">
        <?php echo JText::_('Trade'); ?>
    </button>
        <a class="joms-button--link" href="<?php echo CRoute::_('index.php?option=com_community&view=events&task=create' );?>"><?php echo JText::_('COM_COMMUNITY_EVENTS_CREATE');?></a>
        <?php } ?>
        <a class="joms-button--link" href="<?php echo CRoute::_('index.php?option=com_community&view=events');?>"><?php echo JText::_('COM_COMMUNITY_EVENTS_ALL_EVENTS').' ('.$totalEvents.')';?></a>
        </small>

        <?php
        $content    = ob_get_contents();
        ob_end_clean();

        return $content;
    }
}
