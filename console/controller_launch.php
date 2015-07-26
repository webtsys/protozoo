<?php

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

include('Net/SSH2.php');
include('Net/SFTP.php');
include('Crypt/RSA.php');

//For execute in paralell need find the scritp when all works are over. If error don't open more process.  


function LaunchConsole()
{

	Routes::$app='spanel';

	Utils::load_libraries('configpanel');

	$options=get_opts_console('', $arr_opts=array('script:', 'resume:', 'profile:'));
	
	$climate=new League\CLImate\CLImate;
	
	if(!isset($options['script']))
	{
	
		//echo "Use: php console.php -m=padmin -c=padmin --model=module/model\n";
		
		$climate->white()->backgroundBlack()->out("Use: php console.php -m=spanel -c=launch --script=folder_script [--resume] [--profile=profile_name]");
		
		die;
	
	}
	
	//Variable used for set the number of ssh process created. For future versiones.
	
	/*$ssh_limit=10;*/
	
	//Set predefinided default values for ConfigPanel class.
	
	ConfigPanel::$base_path=PhangoVar::$base_path.'/modules/spanel/scripts';
	
	ConfigPanel::$logs_path=PhangoVar::$base_path.'/modules/spanel/logs';
	
	ConfigPanel::$servers=array();
	
	ConfigPanel::$public_key=getenv('HOME').'/.ssh/id_rsa.pub';
	
	ConfigPanel::$private_key=getenv('HOME').'/.ssh/id_rsa';
	
	$config_name='config_servers';
	
	if(isset($options['profile']))
	{
	
		$config_name=$options['profile'];
	
	}
	
	Utils::load_config('spanel', $config_name);
	
	//Preparing SSH2

	$key = new Crypt_RSA();
	
	if(ConfigPanel::$password_key!='')
	{
		
		$key->setPassword(ConfigPanel::$password_key);
		
	}
	
	$key->loadKey(file_get_contents(ConfigPanel::$private_key));
	
	if(isset($options['servers']))
	{
	
		include($options['servers']);
	
	}
	
	$z=0;
	
	foreach(ConfigPanel::$servers as $host => $data_host)
	{
		
		//Execute the script. 
		
		$script_base_path=ConfigPanel::$base_path.'/'.$data_host['os_codename'];
		
		$script_path=$script_base_path.'/'.$options['script'];
		
		$climate->white()->backgroundBlack()->out("Executing tasks with codename ${options['script']} in host ${host}...");
		
		if(!Utils::load_libraries('config_scripts', $script_path.'/'))
		{
		
			$climate->white()->backgroundRed()->out("Error: not found config_scripts.php in ${script_path}");
		
			die;
		
		}
		
		//Here you can load global parameters for this tasks.
		
		//need that reinclude config_parameters_ if overwrite
		
		Utils::reload_config('spanel', 'config_parameters_'.$options['script']);
		
		//Here you can load particular parameters for this tasks in this server.
		
		Utils::load_config('spanel', 'config_parameters_'.$options['script'].'_'.$host);
		
		//print_r(ConfigPanel::$scripts);
		
		//Example, i can create a script that install all libraries for my scripts first and after the scripts that use this libraries. After i can execute a script that clean all rubbish.
		
		//Array with scripts to copy.
		
		//Copy scripts with scp command. Save on log using monolog.
		
		//Execute ssh commands specified. Scp for copy. 
		
		//If error, stop. 
		
		foreach(ConfigPanel::$scripts[$options['script']] as $script_codename => $script_config)
		{
			
			//Prepare new log
			
			if(!isset($script_config['name']))
			{
			
				$climate->white()->backgroundRed()->out("Error: check your config files, because don't exist name for this task ".$host);
			
				die;
			
			}
			
			$log=ConfigPanel::$logs_path.'/'.$host.'_'.$options['script'].'_'.$script_codename.'.log';
			
			$output = "[%datetime%] | %message%\n";
			
			$date_format='Y-m-d H:i:s';
			
			$formatter = new LineFormatter($output, $date_format);
		
			ConfigPanel::$logger = new Logger($script_config['name']);
			
			$stream_handler=new StreamHandler($log, Logger::DEBUG);
			
			$stream_handler->setFormatter($formatter);
			
			ConfigPanel::$logger->pushHandler($stream_handler);
		
			//Show task to execute
		
			$climate->white()->backgroundBlue()->out("Run task ${script_config['name']}");
			
			//Copy scripts via scp 
			
			//Prepare sftp session
			
			$sftp = new Net_SFTP($host);
			
			if (!$sftp->login(ConfigPanel::$user_ssh, $key)) {
			
				$climate->white()->backgroundRed()->out("Error: cannot login on the server ".$host);
			
				die;
			}
			
			//Create tmp directory
			
			$climate->white()->backgroundBlue()->out("Creating tmp folders on remote server...");
			
			//Deleting old directory
			
			$sftp->delete(ConfigPanel::$server_tmp, true);
			
			if(!$sftp->mkdir(ConfigPanel::$server_tmp))
			{
			
				$climate->white()->backgroundRed()->out("Error: cannot create a new tmp folder ${ConfigPanel::$server_tmp}");
			
				die;
			
			}
			
			//Copy files 
			
			//Count total files.
			
			$climate->white()->backgroundBlue()->out("Uploading files to remote server...");
			
			//Create progress bar for files.
			
			$progress = $climate->progress()->total(100);
			
			$progress->current(0);
			
			$c_files=1;
			
			if(isset($script_config['extra_files']))
			{
			
				$c_files+=count($script_config['extra_files']);
				
			}
			
			$total_count=0;
			
			$sum_count=round(100/$c_files);
			
			$script_basename=basename($script_config['script_path']);
			
			$script_to_execute=ConfigPanel::$server_tmp.'/'.$script_basename;
			
			$script_src=$script_base_path.'/'.$script_config['script_path'];
			
			$total_count+=$sum_count;

			upload_sftp_file($sftp, $script_to_execute, $script_src, $climate);
			
			$progress->current($total_count);
			
			if($c_files>1)
			{
				foreach($script_config['extra_files'] as $extra_file)
				{
				
					//Upload the rest of files
				
					$extra_file=$script_base_path.'/'.$extra_file;
					
					$script_copy=ConfigPanel::$server_tmp.'/'.basename($extra_file);
					
					upload_sftp_file($sftp, $script_copy, $extra_file, $climate);
				
					//Upload file
					//if(!$sftp->put($script_to_execute, $extra_file, NET_SFTP_LOCAL_FILE))
					
					$total_count+=$sum_count;
					
					$progress->current($total_count);
				
				}
				
			}
			
			$total_count=100;
			
			$progress->current($total_count);
			
			//Prepare ssh session
			
			$ssh = new Net_SSH2($host);
	
			if (!$ssh->login(ConfigPanel::$user_ssh, $key)) {
			
				$climate->white()->backgroundRed()->out("Error: cannot login on the server ".$host);
			
				die;

			}
			
			$climate->white()->backgroundBlue()->out("You can see the progress on ".$log);
			
			//Create command to execute
			
			$parameters='';
			
			if(isset($script_config['parameters']))
			{
			
				$parameters=' '.implode(' ', $script_config['parameters']);
			
			}
			
			$command=$script_config['script_interpreter'].' '.$script_to_execute.$parameters;

			//Execute command
			
			$ssh->exec($command, 'packet_handler');
			
			if($ssh->getExitStatus()>0)
			{
				$climate->white()->backgroundRed()->out("Error: script show error. Please check ".$log." for more information");
			
				die;
			
			}
			
			$climate->white()->backgroundLightBlue()->out("Task ".$script_config['name']." was finished succesfully!!!");

			//Upload the script and the specified files to the tmp home in server, normally /tmp.
			
			//Run the script
			
			//If all fine, delete all files.
			
			//If not, show error in log and in screen. Stop script.
			
			//Resume option
			
		
		}
		
		$climate->white()->backgroundLightBlue()->out("Tasks on server ${host} were finished succesfully!!");
	
		$z++;
	
	}
	
	if($z>0)
	{
	
		$climate->white()->backgroundLightBlue()->out("Tasks on all servers were finished succesfully!!");
	
	}
	else
	{
	
		$climate->white()->backgroundRed()->out("Profile ".$options['profile'].' not exists');
	
	}
	
	

}

function packet_handler($str)
{
	//echo $str;
	ConfigPanel::$logger->addInfo($str);
	
	
}

function upload_sftp_file($sftp, $script_to_execute, $script_src, $climate)
{

	if(!$sftp->put($script_to_execute, $script_src, NET_SFTP_LOCAL_FILE))
	{
	
		$climate->white()->backgroundRed()->out("Error: cannot upload the file to the server, :(.\nScript: ".$script_src.' to '.$script_to_execute);
	
		die;
	
	}

}

?>