<?php

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

include('Net/SSH2.php');
include('Net/SFTP.php');
include('Crypt/RSA.php');

#define('NET_SFTP_LOGGING', NET_SFTP_LOG_COMPLEX);

//For execute in paralell need find the scritp when all works are over. If error don't open more process.  


function LaunchConsole()
{

	Routes::$app='protozoo';

	Utils::load_libraries('configpanel');

	$options=get_opts_console('', $arr_opts=array('task:', 'resume:', 'profile:'));
	
	$climate=new League\CLImate\CLImate;
	
	if(!isset($options['task']))
	{
		
		$climate->white()->backgroundBlack()->out("Use: php console.php -m=protozoo -c=launch --task=folder_task [--resume] [--profile=profile_name]");
		
		die;
	
	}
	
	//Set predefinided default values for ConfigPanel class.
	
	ConfigPanel::$base_path=PhangoVar::$base_path.'/modules/protozoo';
	
	ConfigPanel::$logs_path=PhangoVar::$base_path.'/modules/protozoo/logs';
	
	ConfigPanel::$servers=array();
	
	ConfigPanel::$public_key=getenv('HOME').'/.ssh/id_rsa.pub';
	
	ConfigPanel::$private_key=getenv('HOME').'/.ssh/id_rsa';
	
	//Load global config with for example, ssh paswords.
	
	Utils::load_config('protozoo', 'config');
	
	//Load profile with servers
	
	$config_name='config_servers';
	
	if(isset($options['profile']))
	{
	
		$config_name=$options['profile'];
	
	}
	
	Utils::load_config('protozoo', $config_name);
	
	//Paths for task
	
	$task_base_path=ConfigPanel::$base_path.'/tasks';
	
	$task_path=$task_base_path.'/'.$options['task'];
	
	if(!Utils::load_libraries('config_scripts', $task_path.'/'))
	{
	
	
		$climate->white()->backgroundRed()->out("Error: not found config_scripts.php in ${script_path}");
	
		return false;
	
	}
	
	//Little checking
	
	if(!isset(ConfigPanel::$scripts[$options['task']]))
	{
	
		$climate->white()->backgroundRed()->out("Error, check your task because don't have any action");
		
		exit(1);
	
	}
	
	//Preparing SSH2
	
	/*if(ConfigPanel::$password_key!='')
	{
		
		$key->setPassword(ConfigPanel::$password_key);
		
	}*/
	
	/*if(!$key->loadKey(file_get_contents(ConfigPanel::$private_key)))
	{
	
		echo "Password:";
	
		$password = fgets(STDIN);
	
		$climate->white()->backgroundRed()->out("Error, check the password of your ssh key");
		exit(1);
	
	}*/
	
	$key=check_password_key(ConfigPanel::$password_key, $climate, $num_repeat=0);
	
	if(isset($options['servers']))
	{
	
		include($options['servers']);
	
	}
	
	$c_servers=count(ConfigPanel::$servers);
	
	$climate->white()->bold()->out('Welcome to Protozoo');
	$climate->yellow()->out('Executing task <'.$options['task'].'> in '.$c_servers.' machines');
	
	$count_p=0;
	
	$arr_process=array();
	
	ConfigPanel::$progress = $climate->progress()->total($c_servers);
	
	ConfigPanel::$progress->current(0);
	
	foreach(ConfigPanel::$servers as $host => $data_host)
	{
	
		//Fork de process
	
		$pid = pcntl_fork();
		
		if ($pid == -1) {
		
			$climate->white()->backgroundRed()->out("ERROR: CANNOT FORK THE PROCESS. Please, review your php configuration...");
			exit(1);
		
		} 
		else if ($pid) 
		{
		
			$wait=true;
			
			$arr_process[$pid]=$host;
			
			$count_p++;
			
			if($count_p>=ConfigPanel::$ssh_instances)
			{
				
				list($arr_process, $p_count, $climate)=check_process_free($arr_process, $count_p, $climate);
			
			}
		
		} 
		else 
		{
		
			
			if(!exec_tasks($options, $host, $data_host, $key, $climate))
			{
			
				exit(1);
			
			}
			
			
			
			exit(0);
		
		}
		
	
	}
	
	list($arr_process, $p_count, $climate)=check_process_wait($arr_process, $count_p, $climate);
	
	$climate->yellow()->bold()->out('Results: success:'.ConfigPanel::$num_success.', fails:'.ConfigPanel::$num_errors);
	
	$climate->white()->backgroundLightBlue()->out("Tasks on all servers were finished!!");
	
	/*if($z>0)
	{
	
		$climate->white()->backgroundLightBlue()->out("Tasks on all servers were finished!!");
	
	}
	else
	{
	
		$climate->white()->backgroundRed()->out("Profile ".$options['profile'].' not exists');
	
	}*/
	
	/*$mem_usage=memory_get_usage(true);
	
	echo round($mem_usage/1048576,2)." megabytes"; */
	
	/*$pid = getmypid(); 
	echo'MEMORY USAGE (% KB PID ): ' . `ps --pid $pid --no-headers -o%mem,rss,pid`; */

}

function exec_tasks($options, $host, $data_host, $key, $climate)
{

	//Prepare logging for this server
	
	$base_log=ConfigPanel::$logs_path.'/'.$host;
	
	if(!is_dir($base_log))
	{
	
		mkdir($base_log, 0755, true);
	
	}
	
	$log=$base_log.'/'.$options['task'].'.log';
		
	$output = "[%datetime%] %message%\n";
	
	$date_format='Y-m-d H:i:s';
	
	$formatter = new LineFormatter($output, $date_format);

	ConfigPanel::$logger = new Logger($host);
	
	$stream_handler=new StreamHandler($log, Logger::DEBUG);
	
	$stream_handler->setFormatter($formatter);
	
	ConfigPanel::$logger->pushHandler($stream_handler);

	//Execute the script. 
	
	//Paths for scripts
	
	$script_base_path=ConfigPanel::$base_path.'/scripts/'.$data_host['os_codename'];
	
	$script_path=$script_base_path.'/';
	
	ConfigPanel::$logger->addInfo("Executing tasks with codename ${options['task']} in host ${host}...");
	
	//Here you can load global parameters for this tasks.
	
	//need that reinclude config_parameters_ if overwrite
	
	Utils::reload_config('protozoo', 'config_parameters_'.$options['task']);
	
	//Here you can load particular parameters for this tasks in this server.
	
	Utils::load_config('protozoo', 'config_parameters_'.$options['task'].'_'.$host);
	
	//print_r(ConfigPanel::$scripts);
	
	//Example, i can create a script that install all libraries for my scripts first and after the scripts that use this libraries. After i can execute a script that clean all rubbish.
	
	//Array with scripts to copy.
	
	//Copy scripts with scp command. Save on log using monolog.
	
	//Execute ssh commands specified. Scp for copy. 
	
	//If error, stop. 
	
	//Prepare ssh session
		
	$sftp = new Net_SFTP($host);
	
	$ssh=&$sftp;

	if (!$ssh->login(ConfigPanel::$user_ssh, $key)) {
	
		ConfigPanel::$logger->addWarning("Error: cannot login on the server ".$host);
	
		return false;

	}
	
	//Prepare sftp session
		
	#$sftp = new Net_SFTP($host);
	
	#if (!$sftp->login(ConfigPanel::$user_ssh, $key)) {
	
	#	ConfigPanel::$logger->addWarning("Error: cannot login on the server ".$host);
	
	#	die;
	#}
	
	foreach(ConfigPanel::$scripts[$options['task']] as $script_codename => $script_config)
	{

		//Prepare new log
		
		if(!isset($script_config['name']))
		{
		
			ConfigPanel::$logger->addWarning("Error: check your config files, because don't exist name for this task ".$script_codename);
		
			return false;
		
		}
	
		//Show task to execute
	
		ConfigPanel::$logger->addInfo("Run task ${script_config['name']}");
		
		//Copy scripts via scp 
		
		//Create tmp directory
		
		ConfigPanel::$logger->addInfo("Creating tmp folders on remote server...");
		
		//Deleting old directory
		
		$sftp->delete(ConfigPanel::$server_tmp, true);
		
		if(!$sftp->mkdir(ConfigPanel::$server_tmp))
		{
		
			ConfigPanel::$logger->addWarning("Error: cannot create a new tmp folder ${ConfigPanel::$server_tmp}");
		
			return false;
		
		}
		
		//Copy files 
		
		//Count total files.
		
		ConfigPanel::$logger->addInfo("Uploading files to remote server...");
		
		//Create progress bar for files.
		
		/*$progress = $climate->progress()->total(100);
		
		$progress->current(0);*/
		
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
		
		if(!upload_sftp_file($sftp, $script_to_execute, $script_src))
		{
			
			ConfigPanel::$logger->addWarning("Error: cannot upload file ".$script_src." to the remote server in ".$script_to_execute);
				
			return false;
		
		}
		
		//$progress->current($total_count);
		
		if($c_files>1)
		{
			foreach($script_config['extra_files'] as $extra_file)
			{
			
				//Upload the rest of files
			
				$extra_script_src=$script_base_path.'/'.$extra_file;
				
				$extra_script_to_execute=ConfigPanel::$server_tmp.'/'.basename($extra_file);
				
				if(!upload_sftp_file($sftp, $extra_script_to_execute, $extra_script_src))
				{
				
					ConfigPanel::$logger->addWarning("Error: cannot upload file ".$extra_file." to the remote server in ".$extra_script_to_execute);
				
					return false;
				
				}
			
				//Upload file
				//if(!$sftp->put($script_to_execute, $extra_file, NET_SFTP_LOCAL_FILE))
				
				$total_count+=$sum_count;
				
				//$progress->current($total_count);
			
			}
			
		}
		
		ConfigPanel::$logger->addInfo("Files were uploaded succesfully");
		
		$total_count=100;
		
		//$progress->current($total_count);
		
		#ConfigPanel::$logger->addInfo("You can see the progress on ".$log);
		
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
			ConfigPanel::$logger->addWarning("Error: script show error. Please check ".$log." for more information");
		
			return false;
		
		}
		
		ConfigPanel::$logger->addInfo("Task ".$script_config['name']." was finished succesfully!!!");

		//Upload the script and the specified files to the tmp home in server, normally /tmp.
		
		//Run the script
		
		//If all fine, delete all files.
		
		//If not, show error in log and in screen. Stop script.
		
		//Resume option
		
	
	}
	
	ConfigPanel::$logger->addInfo("Tasks on server ${host} were finished succesfully!!");
	
	return true;

}

function packet_handler($str)
{
	//echo $str;
	ConfigPanel::$logger->addInfo($str);
	
	
}

function upload_sftp_file($sftp, $script_to_execute, $script_src)
{
	
	$return_trans=$sftp->put($script_to_execute, $script_src, NET_SFTP_LOCAL_FILE);
	
	if(!$return_trans)
	{
		
		return false;
	
	}
	
	return true;

}

function check_process_free($arr_process, $p_count, $climate)
{
	
	list($arr_process, $p_count, $climate)=check_process($arr_process, $p_count, $climate);

	return [$arr_process, $p_count, $climate];
	
}

//Wait to all processes to end

function check_process_wait($arr_process, $p_count, $climate)
{
	
	foreach($arr_process as $process)
	{
	
		list($arr_process, $p_count, $climate)=check_process($arr_process, $p_count, $climate);
		
	
	}
	
	return [$arr_process, $p_count, $climate];

}

function check_process($arr_process, $p_count, $climate)
{

	$pid=pcntl_waitpid(0,$status);
	
	//Delete process when end its journey...
	
	ConfigPanel::$progress->advance();
	
	$host=$arr_process[$pid];
	
	unset($arr_process[$pid]);
	
	$p_count--;
	
	//If true, process exist sucessfully
	
	if(pcntl_wifexited($status))
	{
	
		//If return 1, error of the script in the server.
	
		if(pcntl_wexitstatus($status))
		{
		
			$climate->white()->backgroundRed()->out("Error: A error exists in server ".$host.". Please, see in the log for more info\n");
			
			//If defined die if error, use check_process_wait for wait to errors.
			
			if(ConfigPanel::$exit_if_error==true)
			{

				ConfigPanel::$num_errors++;
				
				list($arr_process, $p_count, $climate)=check_process_wait($arr_process, $p_count, $climate);
				$climate->white()->backgroundRed()->out("You check get out of the program if error exists, stopping. All tasks before of error was finished");
				$climate->yellow()->bold()->out('Results: success:'.ConfigPanel::$num_success.', fails:'.ConfigPanel::$num_errors);
				exit(1);
				
			}
			
			ConfigPanel::$num_errors++;
		
		}
		else
		{
		
			ConfigPanel::$num_success++;
		
		}
	}
	else
	{
	
	
		$climate->white()->backgroundRed()->out("Error: A error exists in the process for server ".$host.". Please, see in the log for more info");
	
		if(ConfigPanel::$exit_if_error==true)
		{

			list($arr_process, $p_count, $climate)=check_process_wait($arr_process, $p_count, $climate);
			$climate->white()->backgroundRed()->out("You check get out of the program if error exists, stopping. All tasks before of error was finished");
			exit(1);
			
		}
		
		ConfigPanel::$num_errors++;
	
	}
	
	
	return [$arr_process, $p_count, $climate];

}

function check_password_key($password, $climate, $num_repeat=0)
{
	$key = new Crypt_RSA();
	
	$key->setPassword($password);
	
	if(!$key->loadKey(file_get_contents(ConfigPanel::$private_key)))
	{
	
		if($num_repeat<3)
		{
	
			//Horrible hack for obtain password 
	
			$fh = fopen('php://stdin','r');
			
			echo 'Password: ';
			
			`/bin/stty -echo`;
			
			$password = rtrim(fgets($fh,64));
			
			`/bin/stty echo`;
			
			print "\n";

			// nothing more to read from the keyboard
			fclose($fh);
			
			#$password = trim(fgets(STDIN));
			
			//$password=`read -s -p "Enter Password: " pass`;
			
			$num_repeat+=1;
			
			$key=check_password_key($password, $climate, $num_repeat);
			
		}
		else
		{
		
			$climate->white()->backgroundRed()->out("Error, check the password of your ssh key");
			
			exit(1);
		
		}
	
	}
	
	return $key;

}

?>