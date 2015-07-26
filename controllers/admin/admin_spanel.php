<?php

function SpanelAdmin()
{

	settype($_GET['op'], 'string');
	
	$_GET['op']=Utils::slugify($_GET['op']);

	Webmodel::load_model('spanel');
	Utils::load_libraries(array('admin/generate_admin_class', 'utilities/menu_selected', 'utilities/menu_barr_hierarchy_class', 'forms/selectmodelform'));
	
	$arr_op[0]['link']=set_admin_link('spanel', array('op' => 0));
	$arr_op[0]['text']=I18n::lang('spanel', 'servers_type', 'Servers types');
	
	$arr_op[1]['link']=set_admin_link('spanel', array('op' => 'os_server'));
	$arr_op[1]['text']=I18n::lang('spanel', 'os_types', 'OS types');
	
	/*$arr_op[2]['link']=set_admin_link('spanel', array('op' => 2));
	$arr_op[2]['text']=I18n::lang('spanel', 'servers', 'Servers');*/
	
	$arr_hierarchy[0][$arr_op[0]['link']]=$arr_op[0]['text'];
	
	menu_selected($_GET['op'], $arr_op, $type=1);
  
  switch($_GET['op'])
  {
  
  
	default:
	
		$admin=new GenerateAdminClass('server_type');
		
		$admin->arr_fields=array('name');
		
		$admin->set_url_post(set_admin_link('spanel', array('op' => 0)));
		
		$admin->options_func='ServerOptionsListModel';
		
		$admin->show();
	
	break;
	
	case 'os_server':
	
		$admin=new GenerateAdminClass('os_server');
		
		$admin->arr_fields=array('name', 'version');
		
		$admin->set_url_post(set_admin_link('spanel', array('op' => 'os_server')));
		
		$admin->show();
	
	break;
	
	case 'servers':
	
		settype($_GET['server_id'], 'integer');
		
		$arr_server=Webmodel::$model['server_type']->select_a_row($_GET['server_id']);
		
		settype($arr_server['IdServer_type'], 'integer');
		
		if($arr_server['IdServer_type']>0)
		{
		
			$link_servers=set_admin_link('spanel', array('op' => 'servers', 'server_id' => $arr_server['IdServer_type']));
			
			$arr_hierarchy[$arr_op[0]['link']][$link_servers]=I18n::lang('spanel', 'servers_type', 'Servers').': '.$arr_server['name'];
			
			$menu_h=new HierarchyLinks($arr_hierarchy);
			
			echo $menu_h->show($link_servers);
	
			Webmodel::$model['server']->create_form();
			
			Webmodel::$model['server']->forms['type']->form='HiddenForm';
			
			Webmodel::$model['server']->forms['type']->set_parameter_value($arr_server['IdServer_type']);
			
			Webmodel::$model['server']->forms['os_server']->form='SelectModelForm';
			
			Webmodel::$model['server']->forms['os_server']->set_parameters_form(array('', '', 'os_server', 'codename'));
	
			/*$arr_menu[0][0]=I18n::lang('spanel', 'servers_type', 'Servers types');
			$arr_menu[0][1]=set_admin_link('spanel', array('op' => 0));
			
			$arr_menu['servers'][0]=$arr_server['name'];
			$arr_menu['servers'][1]=set_admin_link('spanel', array('op' => 'servers'));
		
			echo menu_barr_hierarchy($arr_menu, 'op');*/
		
			$admin=new GenerateAdminClass('server');
			
			$admin->options_func='AddServiceOptionsListModel';
			
			$admin->arr_fields=array('name');
			
			$admin->where_sql='where type='.$arr_server['IdServer_type'];
			
			//$admin->arr_fields_edit=array('name', 'os_server');
			
			$admin->set_url_post(set_admin_link('spanel', array('op' => 'servers', 'server_id' => $arr_server['IdServer_type'])));
			
			$admin->show();
			
		}
	
	break;
	
	case 'services':
	
		echo '<h3>'.I18n::lang('spanel', 'services_list', 'Services list').'</h3>';
		
		settype($_GET['server_id'], 'integer');
		
		$arr_server=Webmodel::$model['server']->select_a_row($_GET['server_id']);
		
		settype($arr_server['IdServer'], 'integer');
		
		if($arr_server['IdServer']>0)
		{
		
			$link_servers=set_admin_link('spanel', array('op' => 'servers', 'server_id' => $arr_server['type']));
			
			$url_add_service=set_admin_link('spanel', array('op' => 'add_service', 'server_id' => $arr_server['IdServer']));
			
			$url_service=set_admin_link('spanel', array('op' => 'services', 'server_id' => $arr_server['IdServer']));
			
			$arr_hierarchy[$arr_op[0]['link']][$link_servers]=I18n::lang('spanel', 'servers_type', 'Servers').': '.$arr_server['name'];
			
			$arr_hierarchy[$link_servers][$url_service]=I18n::lang('spanel', 'services', 'Services');
			
			$menu_h=new HierarchyLinks($arr_hierarchy);
			
			echo $menu_h->show($url_service);
		
			echo View::show_flash();
			
			echo '<p><a href="'.$url_add_service.'">'.I18n::lang('spanel', 'add_service', 'Add service').'</a></p>';
		
			$list=new SimpleList('server_service');
			
			$list->show();
		
		}
	
	break;
	
	case 'add_service':
	
		//Load the services of server type and os
		
		//Services are, tar elements and a php function with a admin element for this service. Only can a same service onenly.
	
		//If service is adding, cannot add new server_service
		
		//If no service, show services lists for webserver.
	
		//Can choose options 
		
		ob_start();
		
		?>
		<script language="javascript">
		
			$(document).ready( function () {
			
			});
		
		</script>
		<?php
		
		View::$header[]=ob_get_contents();
		
		ob_end_clean();
		
		
		
		Webmodel::$model['server']->components['os_server']->name_field_to_field='codename';
		Webmodel::$model['server']->components['type']->name_field_to_field='codename';
		
		Webmodel::$model['server']->components['type']->fields_related_model=array('IdServer_type');
		
		Webmodel::$model['server']->components['os_server']->fields_related_model=array('IdOs_server');
		
		echo '<h3>'.I18n::lang('spanel', 'add_service', 'Add service').'</h3>';
		
		settype($_GET['server_id'], 'integer');
		
		$arr_server=Webmodel::$model['server']->select_a_row($_GET['server_id']);
		
		settype($arr_server['IdServer'], 'integer');
		
		if($arr_server['IdServer']>0)
		{
		
			//print_r($arr_server);
			
			$description=array();
			
			$path_scripts=PhangoVar::$base_path.'/modules/spanel/scripts/'.$arr_server['os_server'].'/'.$arr_server['type'];
			
			$dirs=scandir($path_scripts);
			
			foreach($dirs as $dir)
			{
			
				if(!preg_match('/^\..*/', $dir))
				{
					
					if(file_exists($path_scripts.'/'.$dir.'/config.php'))
					{
					
						include($path_scripts.'/'.$dir.'/config.php');
					
					}
				
				}
			
			}
			
			echo '<h3>'.I18n::lang('spanel', 'avaliable_services', 'Avaliable services').'</h3>';
			
			echo up_table_config(array(I18n::lang('common', 'name', 'Name'), I18n::lang('common', 'description', 'Description'), I18n::lang('common', 'options', 'Options')));
			
			foreach($description as $arr_desc)
			{
			
				$options='<input type="button" name=" class="install_service" value="'.I18n::lang('spanel', 'add_service', 'Add Service').'" />';
			
				echo middle_table_config( array( $arr_desc['name'], $arr_desc['description'], $options) );
			
			}
			
			echo down_table_config();
			
			echo '<h3>'.I18n::lang('spanel', 'installed_services', 'Installed services').'</h3>';
		
		}
		
		
	
	break;
	
	case 'ajax_install_service':
	
		
	
	break;
	
	case 'execute_script':
	
	break;
	
	
  }
  
  /*$ram=  memory_get_usage ();
  
   //echo round($ram/1048576,2)." megabytes";
   
   echo $ram." bytes"; */

}

function ServerOptionsListModel($url_options, $model_name, $id)
{

	 $arr_urls=BasicOptionsListModel($url_options, $model_name, $id);
	 
	 $arr_urls[]='<a href="'.set_admin_link('spanel', array('op' => 'servers', 'server_id' => $id)).'">'.I18n::lang('spanel', 'servers', 'Servers').'</a>';
	 
	 return $arr_urls;

}

function AddServiceOptionsListModel($url_options, $model_name, $id)
{

	 $arr_urls=BasicOptionsListModel($url_options, $model_name, $id);
	 
	 $arr_urls[]='<a href="'.set_admin_link('spanel', array('op' => 'services', 'server_id' => $id)).'">'.I18n::lang('spanel', 'services', 'Services').'</a>';
	 
	 return $arr_urls;

}


?>