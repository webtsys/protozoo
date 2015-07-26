<?php

Utils::load_libraries('admin/generate_admin_class');

Webmodel::$model['server_type']=new Webmodel('server_type');

Webmodel::$model['server_type']->register('name', 'CharField', array(255), 1);

Webmodel::$model['server_type']->register('codename', 'CharField', array(255), 1);

Webmodel::$model['os_server']=new Webmodel('os_server');

Webmodel::$model['os_server']->register('name', 'CharField', array(255), 1);

Webmodel::$model['os_server']->register('version', 'CharField', array(255), 1);

Webmodel::$model['os_server']->register('codename', 'CharField', array(255), 1);

Webmodel::$model['server']=new Webmodel('server');

Webmodel::$model['server']->register('name', 'CharField', array(255), 1);

Webmodel::$model['server']->register('type', 'ForeignKeyField', array(Webmodel::$model['server_type']), 1);

Webmodel::$model['server']->register('os_server', 'ForeignKeyField', array(Webmodel::$model['os_server']), 1);

Webmodel::$model['server_service']=new Webmodel('server_service');

Webmodel::$model['server_service']->register('name', 'CharField', array(255), 1);

Webmodel::$model['server_service']->register('service', 'CharField', array(255), 1);

Webmodel::$model['server_service']->register('server_id', 'ForeignKeyField', array(Webmodel::$model['server']), 1);

class AdminServiceServerClass extends GenerateAdminClass {

	function basic_insert_model($model_name, $arr_fields, $post)
	{

		//Check $std_error if fail

		$post=Webmodel::filter_fields_array($arr_fields, $post);

		if( Webmodel::$model[$model_name]->insert($post) )
		{

			return 1;

		}

		return 0;

	}

}

?>