<?php

Class DashHelperPBLib
{

	public $types_names = null;
	public $types_numbers = null;
	public $fp = null;

	public function __construct()
	{ 
		global $pressbackup;

		$this->fp = &$pressbackup;
		$this->types_names = array(__('Database','pressbackup'), ' '.__('Themes','pressbackup'), ' '.__('Plugins','pressbackup'), ' '.__('Uploads','pressbackup'));
		$this->types_numbers = array(7, 5, 3, 1);
	}

	public function currentSettings ()
	{
		//tools
		$settings= get_option('pressbackup.preferences');

		$this->fp->import('Scheduler.php');
		$sch = new SchedulerPBLib();

		$activated_type = str_replace( $this->types_numbers, $this->types_names, $sch->activatedTasks($settings) );
		$next_type  = str_replace( $this->types_numbers, $this->types_names, $sch->nextScheduledTasks() );

		$schedule = __('Next on','pressbackup').': '.date_i18n('M d, H:i', wp_next_scheduled('pressbackup.cron.doBackupAndSaveIt'));

		if($activated_type != $next_type ) {
			$schedule .= '  ( ' . __('Will backup','pressbackup') . ': ' . $next_type . ' )';
		}

		return
		'
		<div class="left">
			<h3 style="margin-bottom: 0px;">'.__('Backup type', 'pressbackup').'</h3>
			<p style="margin-top: 5px;">'.$activated_type.'</p>
		</div>
		<div class="left" style="margin-left: 100px">
			<h3 style="margin-bottom: 0px;">'.__('Scheduled backups', 'pressbackup').'</h3>
			<p style="margin: 5px 0 5px;">'.$schedule.'</p>
		</div>
		<div class="clear"></div>
		';
	}

	public function backupListTabs ($type = null, $selected = null )
	{
		//tools
		$settings= get_option('pressbackup.preferences');
		
		//init output
		$output = '';

		if ($type == 'Sites'){

			$link_class= null;
			if($settings['membership']=='developer' && !$this->fp->sessionCheck('dev.auth')){
				$link_class = array('class'=>'requestpass');
			}

			$this_site = $all_sites=''; $$selected='t_tabactive';

			$output .= '<div class="tab  '.$this_site.' " >';
			$output .= $this->fp->link(__('This site','pressbackup'),  array('function'=>'dashOptions', 'this_site'));
			$output .= '</div>';
			$output .= '<div class="tab  '.$all_sites.' " >';
			$output .= $this->fp->link(__('All sites','pressbackup'),  array('function'=>'dashOptions', 'all_sites'), $link_class);
			$output .= '</div>';
		}

		else if ($type == 'Services'){
			//enabled services
			$this->fp->import('Misc.php');
			$misc = new MiscPBLib();
			$enabled_services = $misc->currentService();

			if(count($enabled_services) == 1) {
				return '';
			}

			$servicesData = array(
				'pressbackup' => array('icon' => 'services/pressbackup16.png', 'icong' => 'services/pressbackup16g.png', 'href' =>array('function'=>'dashOptions', false, 'pressbackup')),
				'dropbox' => array('icon' => 'services/dropbox16.png', 'icong' => 'services/dropbox16g.png', 'href' =>array('function'=>'dashOptions', false, 'dropbox')),
				's3' => array('icon' => 'services/amazon16.jpg', 'icong' => 'services/amazon16g.jpg', 'href' =>array('function'=>'dashOptions', false, 's3')),
				'local' => array('icon' => 'services/folderServer16.png', 'icong' => 'services/folderServer16g.png', 'href' =>array('function'=>'dashOptions', false, 'local')),
			);

			foreach($enabled_services as $service) {
				$class_active = ( $service['id'] == $selected)? 't_tabactive' : '' ;
				$icon = ( $service['id'] == $selected)? 'icon' : 'icong' ;

				$output .= '<div class="tab icon '.$class_active.' " >';
				$output .= $this->fp->link($this->fp->img($servicesData[$service['id']][$icon]), $servicesData[$service['id']]['href']);
				$output .= '</div>';
			}
		}

		return $output;
	}

	public function paginator ($pagination = array())
	{
		if(!$pagination || $pagination['total'] < 5){
			return '&nbsp;';
		}

		$output = null;

		if (($pagination['page'] < $pagination['pages']) &&($pagination['page']==1))
		{
			$pageNext=$pagination['page'] + 1;
			$next = $pagination['func_path']; $next[] .= $pageNext;
			$last = $pagination['func_path']; $last[] .= $pagination['pages'];

			$output=	"<span class=\"displaying-num\">".sprintf(__('Displaying %1$s - %2$s of %3$s.','pressbackup'), '1', $pagination['pagination'], $pagination['total'])."</span> ".
						$this->fp->link(__('Next','pressbackup'), $next, array('class'=>'button abpdding') )." ".
						$this->fp->link(__('Last','pressbackup'), $last, array('class'=>'button abpdding') );
			$output .= '<script>jQuery(".tabclear.paginator").addClass("notEmpty");</script>';
		}
		elseif (($pagination['page'] < $pagination['pages']) &&($pagination['page'] >1))
		{
			$pageNext=$pagination['page'] + 1;
			$pagePrev=$pagination['page'] - 1;
			$first = $pagination['func_path']; $first[] .= '1';
			$next = $pagination['func_path']; $next[] .= $pageNext;
			$prev = $pagination['func_path']; $prev[] .= $pagePrev;
			$last = $pagination['func_path']; $last[] .= $pagination['pages'];

			$output =	"<span class=\"displaying-num\">".sprintf(__('Displaying %1$s - %2$s of %3$s.','pressbackup'), $pagination['ini'], $pagination['fin'], $pagination['total'])."</span> ".
							$this->fp->link(__('First','pressbackup'), $first, array('class'=>'button abpdding') )." ".
							$this->fp->link(__('Prev','pressbackup'), $prev, array('class'=>'button abpdding') )." ".
							$this->fp->link(__('Next','pressbackup'), $next, array('class'=>'button abpdding') )." ".
							$this->fp->link(__('Last','pressbackup'), $last, array('class'=>'button abpdding') );
			$output .= '<script>jQuery(".tabclear.paginator").addClass("notEmpty");</script>';
		}
		elseif (($pagination['page'] == $pagination['pages']) &&($pagination['pages'] !=1))
		{
			$pagePrev=$pagination['page'] - 1;
			$first = $pagination['func_path']; $first[] .= '1';
			$prev = $pagination['func_path']; $prev[] .= $pagePrev;

			if ($pagination['fin'] > $pagination['total']) {$pagination['fin'] = $pagination['total'];}
			$output =	"<span class=\"displaying-num\">".sprintf(__('Displaying %1$s - %2$s of %3$s.','pressbackup'), $pagination['ini'], $pagination['fin'], $pagination['total'])."</span> ".
							$this->fp->link(__('First','pressbackup'), $first, array('class'=>'button abpdding') )." ".
							$this->fp->link(__('Prev','pressbackup'), $prev, array('class'=>'button abpdding') );
			$output .= '<script>jQuery(".tabclear.paginator").addClass("notEmpty");</script>';
		}
		elseif(($pagination['pages'] == 1)&&($pagination['total']!=0))
		{
			$output = "<span class=\"displaying-num\">".sprintf(__('Displaying %1$s - %2$s of %3$s.','pressbackup'), '1', $pagination['total'], $pagination['total'])."</span>";
		}

		
		return $output;
	}
}

?>
