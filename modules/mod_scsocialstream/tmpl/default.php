<?php
/**
 * @package         JFBConnect
 * @copyright (c)   2009-2015 by SourceCoast - All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @version         Release v6.5.3
 * @build-date      2015/12/19
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

echo '<div class="sourcecoast socialstream"'.$heightStyle.'>';

// Edit the themes files in the /media/sourcecoast/themes/scsocialstream directory or use template overrides in the
// /templates/<YOUR_TEMPLATE>/html/com_jfbconnect/themes/scsocialstream/default/ directory
$stream->render();

echo '</div>';