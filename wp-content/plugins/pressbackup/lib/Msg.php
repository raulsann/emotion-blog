<?php

/**
 * Msg class for FramePress.
 *
 * Help Class to display info messages on views
 *
 * Licensed under The GPL v2 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @link			none yet
 * @package		core
 * @subpackage	core.pages
 * @since			0.1
 * @license		GPL v2 License

 */
class MsgPBLib
{


	/**
	 * Constructor.
	 *
	 * @param string $FramePress Core Class
	 * @access public
	 */
	public function __construct()
	{
		global $pressbackup;

		if(!$pressbackup->sessionCheck('MsgPBLib.messages')){
			$messages = array(
				'error' => array(),
				'success' => array(),
				'warning' => array(),
				'info' => array(),
			);
			$pressbackup->sessionWrite('MsgPBLib.messages', $messages);
		}
	}

	/**
	 * Store the error message
	 *
	 * @param string $msg Message to store
	 * @return void
	 * @access public
	 */
	public function set ($type='info', $msg = null)
	{
		if(!$msg){ return false; }

		global $pressbackup;

		$messages = $pressbackup->sessionRead('MsgPBLib.messages');

		switch ($type)
		{
			case 'error': $messages['error'][] = $msg; break;
			case 'success': $messages['success'][] = $msg; break;
			case 'warning': $messages['warning'][] = $msg; break;
			case 'info': $messages['info'][] = $msg; break;
		}

		$pressbackup->sessionWrite('MsgPBLib.messages', $messages);
		return true;
	}


	/**
	 * Clear the messages previously stored messages
	 *
	 * @param string $type what type of messages to clear
	 * @return void
	 * @access public
	 */
	public function clear ($type = 'All')
	{
		global $pressbackup;

		$messages = $pressbackup->sessionRead('MsgPBLib.messages');

		switch ($type)
		{
			case 'error': $messages['error'] = array(); break;
			case 'success': $messages['success'] = array(); break;
			case 'warning': $messages['warning'] = array(); break;
			case 'info': $messages['info'] = array(); break;
			default:
				$messages = array(
					'error' => array(),
					'success' => array(),
					'warning' => array(),
					'info' => array(),
				);
			break;
		}

		$pressbackup->sessionWrite('MsgPBLib.messages', $messages);
		return true;
	}

	/**
	 * Display the messages as HTML
	 *
	 * @param string $type what type of messages to Display
	 * @param array $options what type of messages to Display
	 * @return void
	 * @access public
	 */
	public function show ($type = 'error', $options=array())
	{
		global $pressbackup;

		$messages = $pressbackup->sessionRead('MsgPBLib.messages');

		if ( empty ($messages[$type]) ) {
			return false;
		}
		
		$class = ($type == 'error')?'errori':$type;

		$html_msgs = '<p>' . join('</p><p>', $messages[$type]) . '</p>'; 

		$html = 
		'<div class="msgbox ' . $class . ' mbox_wfixed">' .
			'<p style="float: right"><a class="msg-close" href="#">x</a></p>' .
			$html_msgs .
		'</div>' .
		'<script language="javascript">'.
			'setTimeout(function () { if(jQuery(".msgbox") != null) { jQuery(".msgbox").hide("slow"); } }, 30000);' .
			'jQuery(".msg-close").click(function(){ jQuery(this).parent().parent().hide(); return false; });' .
		'</script>';

		$this->clear($type);

		return $html;
	}
}

?>
