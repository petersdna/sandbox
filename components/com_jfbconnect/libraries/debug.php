<?php
/**
 * @package         JFBConnect
 * @copyright (c)   2009-2015 by SourceCoast - All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @version         Release v6.5.3
 * @build-date      2015/12/19
 */

class JFBConnectDebug
{
    protected $text_file = 'jfbconnect.log';
    protected $debug = 0; // 0 - Off, 1 - Logging only, 2 - Logging and display in frontend

    public function __construct($options = array())
    {
        $this->debug = intval(JFBCFactory::config()->get('facebook_display_errors'));
        $this->text_file = isset($options['text_file']) ? $options['text_file'] : $this->text_file;

        if($this->debug)
            JLog::addLogger(array('text_file' => $this->text_file), JLog::ALL, 'jfbconnect');
    }

    public function add($msg = '', $type = 'message', $debug = false)
    {
        if(empty($msg)) return; // do nothing if empty

        // show message if either "Debug Mode" is 2; or show message if $debug is false
        if($this->debug === 2 || !$debug)
            JFactory::getApplication()->enqueueMessage($msg, $type);

        // logged only if "Debug Mode" is either 1 or 2
        if($this->debug)
        {
            $const = 'JLog::' . strtoupper($type);
            $priority = !defined($const) ? JLog::INFO : constant($const);
            JLog::add($msg, $priority, 'jfbconnect');
        }
    }
}