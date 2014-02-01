<?php

/**
* Schedule Functions lib for Pressbackup.
*
* This Class provide the functionality to mannage the scheduled functions
*
* Licensed under The GPL v2 License
* Redistributions of files must retain the above copyright notice.
*
* @link			http://pressbackup.com
* @package		libs
* @subpackage	libs.schedule
* @since		0.1
* @license		GPL v2 License
*/

class SchedulerPBLib
{
	/**
	 * Local Copy to Misc Lib
	 * 
	 * @var object
	 * @access public
	 */
	public $Misc = null;

	/**
	 * Constructor.
	 *
	 * @param object FramePress Core
	 * @access public
	 */
	public function __construct ()
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$this->Misc = new MiscPBLib();
	}

	//----------------------------------------------------------------------------------------

	/**
	 * shedule a new cron job based on arguments or on stored settings
	 */
	public function add ($time=null, $task = 'pressbackup.cron.doBackupAndSaveIt')
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		date_default_timezone_set($misc->timezoneString());
		
		//when??
		if($time){
			$start_time = strtotime($time);
		} else {
			//$start_time = strtotime('+5 minutes');
			$start_time = $this->nextScheduledTime();
		}

		//remove the task in case it was actually setted
		$this->remove($task);

		//add the new schedule
		@wp_schedule_single_event($start_time, $task);
		return true;
	}

	/**
	 * Remove scheduled jobs
	 */
	public function remove($task = 'pressbackup.cron.doBackupAndSaveIt')
	{
		if ($task == 'all'){
			@wp_clear_scheduled_hook('pressbackup.cron.doBackupAndSaveIt');
			@wp_clear_scheduled_hook('pressbackup.main.doBackupAndSaveIt');
			@wp_clear_scheduled_hook('pressbackup.ajax.doBackupAndSaveIt');
			@wp_clear_scheduled_hook('pressbackup.main.doBackupAndDownloadIt');
			@wp_clear_scheduled_hook('pressbackup.ajax.doBackupAndDownloadIt');
		}else{
			@wp_clear_scheduled_hook($task);
		}
		return true;
	}

	//----------------------------------------------------------------------------------------

	function updateTaskLastDate($taskDone)
	{
		$settings= get_option('pressbackup.preferences');
		$advancedTasks = $settings['backup'];

		$taskDone = explode(',',$taskDone);

		if(in_array('7',$taskDone)){
			$last_date = $this->calculateTaskLastDate($advancedTasks['db']);
			$settings['backup']['db']['last_date'] = $last_date;
		}
		if(in_array('5',$taskDone)){
			$last_date = $this->calculateTaskLastDate($advancedTasks['themes']);
			$settings['backup']['themes']['last_date'] = $last_date;
		}
		if(in_array('3',$taskDone)){
			$last_date = $this->calculateTaskLastDate($advancedTasks['plugins']);
			$settings['backup']['plugins']['last_date'] = $last_date;
		}
		if(in_array('1',$taskDone)){
			$last_date = $this->calculateTaskLastDate($advancedTasks['uploads']);
			$settings['backup']['uploads']['last_date'] = $last_date;
		}

		update_option('pressbackup.preferences', $settings); 
	}

	//----------------------------------------------------------------------------------------

	/**
	* Return activated tasks
	*/
	public function activatedTasks($settings = null)
	{
		if(!$settings){
			$settings= get_option('pressbackup.preferences');
		}

		$advancedTasks = $settings['backup'];
		//backup type is set up on "create and save" and "create and download" functions
		unset($advancedTasks['type']);

		$activatedTasks = array();
		foreach($advancedTasks as $task => $tSettings){
			if(isset($tSettings['time']) && $tSettings['time'] != 0){
				$activatedTasks[] = $task;
			}
		}

		return  str_replace( array('uploads', 'plugins', 'themes', 'db'), array('1', '3', '5', '7') , join(',', $activatedTasks) );
	}

	/**
	 * Return tasks to permform now based on settings
	 */
	public function tasksToRunNow($settings = null)
	{
		if(!$settings){
			$settings= get_option('pressbackup.preferences');
		}

		$advancedTasks = $settings['backup'];

		$jobs_to_do = array();

		if($this->isTimeToRun($advancedTasks['db'])){
			$jobs_to_do[] = '7';
		}

		if($this->isTimeToRun($advancedTasks['themes'])){
			$jobs_to_do[] = '5';
		}

		if($this->isTimeToRun($advancedTasks['plugins'])){
			$jobs_to_do[] = '3';
		}
		
		if($this->isTimeToRun($advancedTasks['uploads'])){
			$jobs_to_do[] = '1';
		}

		$jobs_to_do = join (',', $jobs_to_do);

		return $jobs_to_do;//'7,5,3,1';//
	}

	/**
	 * Return schedule task for the next job based on settings
	 */
	public function nextScheduledTasks($settings = null)
	{
		if(!$settings){
			$settings= get_option('pressbackup.preferences');
		}

		$advancedTasks = $settings['backup'];

		$nextJobs = array();

		if($this->isTaskForNextRun($advancedTasks['db'])){
			$nextJobs[] = '7';
		}

		if($this->isTaskForNextRun($advancedTasks['themes'])){
			$nextJobs[] = '5';
		}

		if($this->isTaskForNextRun($advancedTasks['plugins'])){
			$nextJobs[] = '3';
		}

		if($this->isTaskForNextRun($advancedTasks['uploads'])){
			$nextJobs[] = '1';
		}

		$nextJobs = join (',', $nextJobs);

		return $nextJobs;
	}

	/**
	 * Return schedule time for the next job based on settings
	 */
	public function nextScheduledTime($settings = null)
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		if(!$settings){
			$settings= get_option('pressbackup.preferences');
		}

		$minTimeTask = $this->minScheduledTaskTime($settings);

		$last_date = $minTimeTask['last_date'];
		$time_step = $minTimeTask['time'] * 3600;
		$start_time = $last_date + $time_step;

		//if the last time executed was days ago (nobody visit the blog)
		//we have to find the correct time
		$curdate = strtotime($misc->midnightUTC() . " +1 day");

		//repeat above step, until start_time be greater than curdate
		while($start_time < $curdate){
			$start_time += $time_step;
		}

		return $start_time;
	}

	//----------------------------------------------------------------------------------------

	/**
	* return minimum task time step setting
	* and the last time performed 
	*/
	public function minScheduledTaskTime($settings)
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		if(!$settings){
			$settings= get_option('pressbackup.preferences');
		}

		$advancedTasks = $settings['backup'];

		$minTask['time'] = 99999999;
		foreach($advancedTasks as $task => $tSettings){
			if(isset($tSettings['time']) && $tSettings['time'] != 0 && $tSettings['time'] < $minTask['time']){
				$minTask = $tSettings;
			}
		}

		if(!isset($minTask['last_date'])) {
			$minTask['last_date'] = strtotime($misc->midnightUTC());
		}

		return $minTask;
	}

	/**
	 * for a given task's advanced settings decide
	 * if it is time to run it
	 */
	private function isTimeToRun($taskSettings)
	{
		global $pressbackup;

		$pressbackup->import('Misc.php');
		$misc = new MiscPBLib();

		//task is disabled
		if($taskSettings['time'] == '0'){ return false;}

		date_default_timezone_set($misc->timezoneString());

		$curdate = strtotime('now');
		$last_date = $taskSettings['last_date'];

		//calculates the elapsed time since last execution 
		$elapsed_time = ($curdate - $last_date) / 3600;

		//must be executed
		if($elapsed_time >= $taskSettings['time']){
			return true;
		}

		//must not be executed
		return false;
	}

	/**
	 * for a given task's advanced settings decide 
	 * if it will run in next schedule
	 */
	private function isTaskForNextRun($taskSettings)
	{
		//task is disabled
		if($taskSettings['time'] == '0'){ return false;}

		$nextSchedule = $this->nextScheduledTime();
		$taskNextRun = $taskSettings['last_date'] + ($taskSettings['time'] * 3600);

		//must be executed
		if($taskNextRun <= $nextSchedule){
			return true;
		}

		return false;
	}

	/**
	 * for a given task's advanced settings
	 * claculate the last time done
	 */
	private function calculateTaskLastDate($taskSettings)
	{
		$minTimeTask = $this->minScheduledTaskTime();

		//calculate the expected run datetime
		$expected_task_datetime = $taskSettings['last_date'] + ($taskSettings['time'] * 3600);

		$expected_scheduled_datetime = strtotime($this->nextScheduledTime() . " -{$minTimeTask['time']} hours");

		while($expected_task_datetime < $expected_scheduled_datetime ){
			$expected_task_datetime += $taskSettings['time'] * 3600;
		}

		return $expected_task_datetime;
	}

}
?>
