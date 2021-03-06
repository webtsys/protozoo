<?php

ConfigPanel::$scripts['agent']['python3']=array('name' => 'Python Language', 'description' => 'Script language very powerful, very used for spanel for internal tasks', 'script_path' => 'libraries/install_python.sh', 'script_interpreter' => 'sh');

ConfigPanel::$scripts['agent']['apache']=array('name' => 'Apache Webserver', 'description' => 'Script for install the most famous webserver in the world for debian jessie', 'script_path' => 'libraries/install_apache.py', 'script_interpreter' => 'python3', 'service' => 'webserver');

//For parameters, load a array javascript from config_servers.

ConfigPanel::$scripts['agent']['mariadb']=array('name' => 'MySQL', 'description' => 'The most famous database SQL server', 'script_path' => 'libraries/install_mariadb.py', 'script_interpreter' => 'python3', 'parameters' => array(), 'extra_files' => array(), 'service' => 'mysql');

ConfigPanel::$scripts['agent']['php']=array('name' => 'PHP', 'description' => 'Language used in web applications', 'script_path' => 'libraries/install_php.py', 'script_interpreter' => 'python3', 'parameters' => array(), 'extra_files' => array('files/spanel.conf'));

ConfigPanel::$scripts['agent']['git']=array('name' => 'Git', 'description' => 'A modern CVS system created by Linus Torlvards', 'script_path' => 'libraries/install_git.py', 'script_interpreter' => 'python3');

ConfigPanel::$scripts['agent']['composer']=array('name' => 'Composer', 'description' => 'A package manager for php', 'script_path' => 'libraries/install_composer.sh', 'script_interpreter' => 'sh');

//Install agents that are basic scripts. The basic scripts are mysql and php websites servers.

//More scripts can be add_servers for add the servers created to a mysql database.

ConfigPanel::$scripts['agent']['agent']=array('name' => 'Pastafari agent', 'description' => 'API used by the hosting control panel made with Phango Framework and python scripts', 'script_path' => 'agent/install_agent.py', 'script_command' => 'python3 install_agent.py', 'parameters' => '--hostname_father=', 'extra_files' => 'webserver/agent.conf');

?>