jQuery(".pb_delete").click(function(){
	if (confirm(delete_confirm)){
		jQuery('#contact_info').hide("fast");
		jQuery('#pressbackup_loading_img_status').html(delete_status);
		jQuery('#pressbackup_loading_img').show("fast");
		return true;
	}
	return false;
});

jQuery(".pb_restore").click(function(){
	if (confirm(restore_confirm)){
		jQuery('#contact_info').hide("fast");
		jQuery('#pressbackup_loading_img_status').html(restore_status);
		jQuery('#pressbackup_loading_img').show("fast");
		return true;
	}
	return false;
});

//--------------------------------

//cancel a running backup
jQuery(".cancelBackup").click(function(){
	if (confirm(cancelBackup_confirm)){
		return true;
	}
	return false;
});

//--------------------------------

jQuery( "#backup_options").dialog({
	resizable: false,
	autoOpen: false,
	height:200,
	width: 350,
	modal: true
});

//open backup now dialog
jQuery("#press_send_backup, #press_download_backup").click(function(){

	if( jQuery(this).hasClass('disabled') ){ return false;}

	//hide both submit buttons and show the correct one
	jQuery('.pb_nowoption').hide();
	if( jQuery(this).attr('id') == 'press_send_backup') {
		jQuery('#press_send_backup_ok').show();
	}else{
		jQuery('#press_download_backup_ok').show();
	}

	//set values
	jQuery.each(backup_types,function(key,val){
		jQuery("#backup_options input[value='"+val+"']").attr('checked',true);
	});

	//hide previous error messages and open dialog
	jQuery("#backup_options .msgbox").hide();
	jQuery( "#backup_options").dialog( "open" );

	return false;
});

//dialog buttons handlers
jQuery("#press_send_backup_ok, #press_download_backup_ok").click(function(){
	var msgHeight,
	i=0, args = [],
	msgDisplay = jQuery("#backup_options .msgbox").css('display');

	jQuery('#backup_options input[name="data[preferences][type][]"]:checked').each(function(){
		args[i]= jQuery(this).val(); i++
	});
	args = args.join(',');

	if(args == "" && msgDisplay == 'none'){
		jQuery("#backup_options .msgbox").show();
		msgHeight = parseInt(jQuery("#backup_options .msgbox").css('height')) + 15;
		jQuery("#backup_options" ).animate({height:'+=' + msgHeight + 'px' });
		return false;
	} else if(args == "") {
		return false;
	}

	jQuery( this ).attr('href',jQuery( this ).attr('href')+'&fargs[]='+args);
});

jQuery(".press_backup_cancel").click(function(){
	jQuery("#backup_options").dialog( "close" );
	return false;
});

//--------------------------------

jQuery("#pass_request").dialog({
	resizable: false,
	autoOpen: false,
	height:210,
	width: 400,
	modal: true
});

//open request pasword dialog
jQuery(".requestpass").click(function(){
	jQuery( "#pass_request").dialog( "open" );
	return false;
});

//dialog buttons handlers
jQuery(".requestpass_cancel").click(function(){
	jQuery("#pass_request").dialog( "close" );
	return false;
});

jQuery(".requestpass_ok").click(function(){
	var url = jQuery(".requestpass").attr("href") + '&token=' + hex_md5(jQuery.trim(jQuery('#requestpass_input').val()));
	document.location.href=url;
	return false;
});

//--------------------------------

jQuery( "#upgrade_message").dialog({
	resizable: false,
	autoOpen: true,
	height:300,
	width: 600,
	modal: true
});

//--------------------------------

jQuery( ".target-popup").click(function(){
	window.open(jQuery(this).attr('href'),"PressBackup","width=640,height=300");
	return false;
});

