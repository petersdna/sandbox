<?php
/**
 * @package         JFBConnect
 * @copyright (c)   2009-2015 by SourceCoast - All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @version         Release v6.5.3
 * @build-date      2015/12/19
 */

defined('_JEXEC') or die('Restricted access');

if ($userIntro != '') {
    echo '<div class="sc_social_widget">'.$userIntro."</div>";
}

if($widget)
    echo $widget->render();

require(JPATH_ROOT.'/components/com_jfbconnect/assets/poweredBy.php');
?>