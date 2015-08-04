<?php

class ConfigPanel {
	
	/**
	* Array where info from servers is saved
	*/
	
	static public $servers=array();
	
	static public $base_path=__DIR__;
	
	static public $logs_path=__DIR__.'/logs';
	
	static public $scripts=array();
	
	static public $server_tmp='tmp';
	
	static public $user_ssh=array('root');
	
	static public $home_ssh=array('/root');
	
	static public $public_key='';
	
	static public $private_key='';
	
	static public $password_key='';
	
	static public $port_shh=22;
	
	static public $logger;
	
	static public $ssh_instances=10;
	
	//Variables for internal tasks
	
	static public $exit_if_error=false;
	
	static public $num_errors=0;
	
	static public $num_success=0;
	
	static public $progress;

}

?>