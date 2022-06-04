<?php

namespace Core;


use \SleekDB\SleekDB;
use \SleekDB\Store;
use \SleekDB\Query;
use \ZipArchive;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use \App\Router;

$curDir = dirname(__FILE__);

// Prevent to forge Config
if(!file_exists($curDir.'/Config.php')) die('Error - Place "Config.php" in the same level as "Core.php".');

// Prevent to forge SleekDB
if(!file_exists($curDir.'/vendor/autoload.php')) die('Error - Did you /composer install? <br> We cant\'t find "autoload.php" file inside "vendor" directory');

require $curDir.'/Config.php';
require $curDir.'/Router.php';
require $curDir.'/vendor/autoload.php';

// If public path is not defined or not exists, error
if(!isset($config['public_path'])) die('Define the "public_path" in your Config file.');

if(!file_exists($config['public_path'])) die('The "public_path" does not exists, define it your Config file.');


// Create storage path for data storage.
if(!is_dir($curDir.'/storage')){
	mkdir($curDir.'/storage',0777);
	if(!is_dir($curDir.'/storage/public')) mkdir($curDir.'/storage/public',0777);
}

// Create storage path for data backups.
if(!is_dir($curDir.'/backups')){
	mkdir($curDir.'/backups',0777);
}

// Create data path for database storage.
if(!is_dir($curDir.'/storage/stores')) mkdir($curDir.'/storage/stores',0777);

// Under windows, no symlink so we need to create Storage folder instead.
if(!is_dir($config['public_path'].'/storage')){
	if(!@symlink($curDir.'/storage/public',$config['public_path'].'/storage')){
		mkdir($config['public_path'].'/storage',0777);
	}
}

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
/*
	MAIN CMS CLASS
*/

class CMS {
	var $database;
	var $options = [];
	var $language;
	var $pendingLanguage = [];
	var $setup = false;
	var $allowed_extensions = array('image/jpeg' => 'jpg');
	var $store_path;
	var $storage_path;
	var $root_path;

	/*
		DEFAULT STORES TO MAKE THE CMS WORK PROPERLY
	*/

	var $default_stores = [
		'users' => [
			'username' => 'text',
			'password' => 'password',
			'email' => 'email',
			'created'=> 'datetime',
		]
	];


	function __construct($config){
		$this->config = $config;

		// Initializating the language
		$this->language();

		// Set the store path
		$this->root_path =  __DIR__;
		$this->storage_path =  __DIR__.'/storage';
		$this->store_path =  dirname(__FILE__).'/storage/stores';
		
		// Setting up allowed extensions to upload files.
		$this->allowed_extensions = $config['upload_files_extensions_allowed'];

		// Merge the default CMS stores and the user stores
		$this->config['stores'] = $this->config['stores'] + $this->default_stores;

		// Initializating the database stuff
		$this->database();
	}

	// Initcialize languages
	function language(){
		$this->language = isset($config['language']) ? $config['language'] : 'en';
		if(isset($_SESSION['language']) && !empty($_SESSION['language'])){
			$this->language = $_SESSION['language'];
		}
	}

	// Set language
	function setLanguage($language){
		$_SESSION['language'] = $language;
		$this->language();
	}

	//  Get language
	function getLanguage(){
		return $this->language;
	}	

	// Initcialize the Database Playground
	function database(){
		$database = array();

		// If no stores, exit.
		if(!isset($this->config['stores']) || empty($this->config['stores'])) return false;

		$this->database = (object) $database; 
		$users = SleekDB::Store('users', $this->store_path, $this->options);

		// If we have users table, create a default user.
		if(isset($users) && empty($users->fetch())){
			$users->insert([
				'username' => 'admin',
				'email' => 'admin@admin.com',
				'password' => password_hash('password', PASSWORD_DEFAULT),
			]);
		}
	}

	// Check if user is Logged
	function isLogged(){
		if(isset($_SESSION['logged']) && !empty($_SESSION['logged'])) return true; else return false;
	}

	// Login
	function login($username,$password){

		$user = SleekDB::Store('users', $this->store_path, $this->options)
		->findOneBy( ['username', '=', $username]);

		if(!$user){
			return 'Invalid user/password';
		}

		$passwordChecked = password_verify($password, $user['password']);

		// If the user exists, redirect to admin.php
		if($passwordChecked){
			$_SESSION['logged'] = $user;
			$this->redirect("admin.php");
		} else {
			return 'Invalid user/password';
		}
		
	}

	// Logout
	function logout(){
		unset($_SESSION['logged']);
		$this->redirect("admin.php");
	}

	/*
		CMS
	*/

	// Data TABLE to HTML TABLE
	function table2table($table){
		$text = '<form method="get" class="text-right">
		<input type="hidden" name="p" value="'.$table.'">
		<button name="insert" class="btn btn-primary">'.$this->__('New').'</button>
		</form><table class="table">';
		$text .= '<tr><td>#</td>';

		$relations = [];
		$searchable = [];

		foreach($this->config['stores'][$table] as $name=>$value){
			if(is_array($value)){
				if(isset($value['join'])){
					$searchable[] = '_'.$value['join']['key'];
					array_push($relations,$value['join']);
					$text .= '<td><b>'.$name.'</b><br><small><i class="text-dark"><b>join</b> '.$value['join']['foreing_table'].'</i></small></td>'; 
				}
			} else {

				$searchable[] = $name;
				$text .= '<td><b>'.$name.'</b><br><small><i class="text-dark">'.$value.'</i></small></td>';
			}
		}

		$text .= '<td></td></tr>';

		$storeQuery = $this->store($table);

		if(isset($relations)){
			$storeQuery = $this->join($storeQuery,$relations);
			
			if(isset($_POST['search'])){
				$storeQuery = $storeQuery->search($searchable, $_POST['search']);
			}

			$storeQuery = $storeQuery->disableCache()->getQuery()->fetch();

		} else {
			$storeQuery = $storeQuery->orderBy( 'desc', '_id' )->fetch();
		}

		foreach($storeQuery as $datak=>$datav){
			$text .= '<tr><td>'.$datav['_id'].'</td>';
			foreach($this->config['stores'][$table] as $name=>$value){

				// If is an array data
				if(is_array($value)){
			
					if(isset($value['join'])){

						$text .= "<td>";

						foreach($value['join']['foreing_display'] as $displayv){

							if(isset($datav['_'.$name][$displayv])){
								$text .= $datav['_'.$name][$displayv];	
							}
						 
						}
						$text .= "</td>";

					}
				} else {
					$text .= '<td>'.(isset($datav[$name]) ? $datav[$name] : '').'</td>';
				}	

			}

			$text .= '<td class="text-right">

			<form method="post">
			<input type="hidden" name="p" value="'.$table.'"><input type="hidden" name="id" value="'.$datav['_id'].'">
			<button name="delete" class="btn btn-danger btn-sm mb-1" onclick="return confirm(\'Are you sure?\');">'.$this->__('Delete').'</button>
			</form>

			<form method="get">
			<input type="hidden" name="p" value="'.$table.'"><input type="hidden" name="id" value="'.$datav['_id'].'">
			<button name="update" class="btn btn-success btn-sm">'.$this->__('Edit').'</button>
	
			</form>

			</td></tr>';
		}
		$text .= '</table>';
		return $text;
	}

	function store($table){
		return (new Store($table, $this->store_path, $this->options));
	}

	function join($parentStore,$relations){
			// Create query builder
			$parentStore = $parentStore->createQueryBuilder();
			foreach($relations as $relation){

				$relatedStore = $this->store($relation['foreing_table']);				
				$parentStore = $parentStore->join(function($row) use ($relatedStore, $relation) {

					if(isset($row[$relation['key']]) && !empty($row[$relation['key']])){
  						return $relatedStore->findOneBy([ $relation['foreing_key'], '=', (int) $row[$relation['key']] ]);

					} else {
						// Si no existe la relacion, hacemos algo a parte... para que no derror.
						return  $relatedStore->createQueryBuilder()->where(['_id','=',0]);
					}
				    
				}, '_'.$relation['key']);

			}
 			return $parentStore;
	}

	function row($arr){
		if(is_array($arr) && isset($arr[0])) return $arr[0];
		return false;
	}

	function tableOptions($needle,$array){
		foreach($array as $key=>$ar){
			if(is_array($ar)){
				if(isset($ar[$needle])) return $ar[$needle];
			}
		}
	}

	// Generate the form
	function form($table,$action,$id=null){
		$text = '<form method="post" enctype="multipart/form-data">';
		if($id != null){ 
			$text .= '<input type="hidden" name="id" value="'.$id.'">';
			$text .= '<input type="hidden" name="update" value="1">';
	
			$data = SleekDB::Store($table, $this->store_path, $this->options)->findOneBy(['_id','=', (int) $id]);
		}

		foreach($this->config['stores'][$table] as $name=>$value){
			if(is_array($value)){
				$text .= '<div class="mt-2"><b>'.$name.'</b>';

				$options = SleekDB::Store($value['join']['foreing_table'], $this->store_path, $this->options)->fetch();

 				// Si se puede hacer JOIN
				if(isset($value['join'])){
					$text .= '<br><small><i class="text-dark"><b>join</b> '.$value['join']['foreing_table'].'</i></small>';
					$text .= $this->editable($action,['table'=>$table,'name'=>$name,'type'=>'select','options' => $options],(isset($data[$name]) ? $data[$name] : null));
				}

				$text .= '</div>';	
			} else {
				$text .= '<div class="mt-2"><b>'.$name.'</b><br><small><i class="text-dark">'.$value.'</i></small>';
				$text .= $this->editable($action,['table'=>$table,'name'=>$name,'type'=>$value],(isset($data[$name]) ? $data[$name] : null));
				$text .= '</div>';				
			}

		}

		// IF action is not view_row
		if($action != 'view_row') $text .= '<button name="'.$action.'" class="mt-3 mr-2 btn btn-primary">'.$this->__($action).'</button>';
		$text .= '<a href="admin.php?p='.$_GET['p'].'" class="mt-3 btn btn-danger">'.$this->__('cancel').'</a>';

		$text .= '</form>';
		return $text;
	}

	// Get the extension of the Mimetype given
	function getExtension ($mime_type){
		if(isset($this->allowed_extensions[$mime_type])) return '.'.$this->allowed_extensions[$mime_type];
	   	return false;
	}

	// Move the uploaded file to the correct folder
	function moveUploadedFile($files,$name){
		$dir = dirname(__FILE__);
		$dir_storage = '/storage/public/'.date('FY');
		if(!is_dir($dir.$dir_storage)) mkdir($dir.$dir_storage);
		$path = $dir_storage.'/'.md5($files[$name]['name']).$this->getExtension($files[$name]['type']);
		 
		if(!$this->getExtension($files[$name]['type'])) return false;

		$public_path = '/storage/'.date('FY').'/'.md5($files[$name]['name']).$this->getExtension($files[$name]['type']);


		if (move_uploaded_file($files[$name]['tmp_name'],  $dir.$path)) {

			// Comprobamos que storage es un symlink, sino copiamos el archivo al public
			if(!is_link($this->config['public_path'].'/storage')){
				if(!is_dir($this->config['public_path'].'/storage/'.date('FY'))){
					mkdir($this->config['public_path'].'/storage/'.date('FY'));
				}
				rename($dir.$path,
					$this->config['public_path'].$public_path);
			}
			
		    return $public_path;
		} else {
		    return false;
		}
	}

	function delete($table,$id){
	 	$delete = (new Store($table, $this->store_path, $this->options))->deleteById($id);
	 	return false;
	}

	// Update or Insert data
	function updateInsert($table,$data,$files){
		$store = (new Store($table, $this->store_path, $this->options));

		foreach($this->config['stores'][$table] as $name=>$value){
			// Data
			if(array_key_exists($name,$data)){			
				$update[$name] = $data[$name];
			}

			// If we found (password input) and is not EMPTY.
			if($name === 'password' && !empty($data[$name])){
				$update[$name] = password_hash($data[$name], PASSWORD_DEFAULT);
			}

 
			// Files
			if(array_key_exists($name,$files)){
				if($value == 'image') $update[$name] = $this->moveUploadedFile($files,$name);
			}

		}

		if(isset($data['id']) && !empty($data['id'])){
		 	$update['_id'] = $data['id'];
			$store->update($update);	
		} else {
			$store->insert($update);	
		}
		
	}


	var $_editable = ['text','textarea','password','checkbox','select'];
	function editable($action,$options=null,$value=null){

		if($action == 'view_row'){
			switch ($options['type']) {
				case 'image':
					if(file_exists($value)){
						$image = '<span class="form-control">'.$value.'</span>';
						$image .= '<img class="img-fluid" src=".'.$value.'">';
					} else {
						$image = '<p>Image not found</p>';
					}

					return $image;
				default:
			      	return '<span class="form-control">'.$value.'</span>';
			}
		}

		if($action == 'update_row' || $action == 'insert_row'){
			switch ($options['type']) {
				case 'select':

					$foreing_display = null;
					if($this->config['stores'][$options['table']][$options['name']]['join']['foreing_display']){
						$foreing_display = $this->config['stores'][$options['table']][$options['name']]['join']['foreing_display'];				
					}

					$text_display = null;
		 


					$input = '<select name="'.$options['name'].'" class="form-control '.(isset($options['class']) ?? $options['class']).'" '.(isset($options['any']) ?? $options['any']).' />';
						$input .= '<option selected></option>';
					foreach($options['options'] as $option){


					if($foreing_display){
						foreach($foreing_display as $displayv){

							$text_display .= $option[$displayv].' ';	
						}						
					}


							$input .= '<option value="'.$option['_id'].'" '. (($value == $option['_id']) ? 'selected' : '') .'>'.$text_display.'</option>';				
					}

					$input .= '</select>';
					return $input;
				case 'password':
					return '<input name="'.$options['name'].'" class="form-control" type="text" value="" placeholder="'.$value.'"><small>Leave blank if you don\'t want to change the password.</small>';
				case 'color':
					return '<input name="'.$options['name'].'" class="form-control" type="color" value="'.$value.'">';
				case 'url':
					return '<input name="'.$options['name'].'" class="form-control" type="url" placeholder="https://" value="'.$value.'">';
				case 'number':
					return '<input name="'.$options['name'].'" class="form-control" type="number" value="'.$value.'">';
				case 'email':
					return '<input name="'.$options['name'].'" class="form-control" type="email" value="'.$value.'">';
				case 'decimal':
					return ' <input  name="'.$options['name'].'" class="form-control" type="number" placeholder="0.00" required min="0" value="'.$value.'" step="0.01" title="Currency" pattern="^\d+(?:\.\d{1,2})?$" onblur="this.parentNode.parentNode.style.backgroundColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?\'inherit\':\'red\'">';


				case 'image':
					$img = '<input type="hidden" name="'.$options['name'].'" value="'.$value.'">';
					if(!empty($value)){
						$img .= '<br><img src="public'.$value.'" loading="lazy" class="m-2">';
					}
					$img .= '<input type="file" name="'.$options['name'].'" class="form-control '.(isset($options['class']) ?? $options['class']).'" '.(isset($options['any']) ?? $options['any']).' /><br><b>'.$this->__('allowed_extensions').'</b>: '.implode(', ',$this->allowed_extensions);
					return $img;
			   	case 'textarea':
			       	return '<textarea name="'.$options['name'].'" class="form-control '.(isset($options['class']) ?? $options['class']).'" '.(isset($ptions['any']) ?? $options['any']).'>'.$value.'</textarea>';
			    case 'rich_textarea':
			    	return '<textarea name="'.$options['name'].'" class="form-control" id="html_editor" style="min-height:400px"></textarea>';
			   	case 'date':
			     	return '<input type="date"  name="'.$options['name'].'" value="'.$value.'" class="form-control '.(isset($options['class']) ?? $ptions['class']).'" '.(isset($options['any']) ?? $options['any']).'>';
			   	case 'datetime':
			     	return '<input type="datetime-local"  name="'.$options['name'].'" value="'.$value.'" class="form-control '.(isset($options['class']) ?? $ptions['class']).'" '.(isset($options['any']) ?? $options['any']).'>';
			   	default:
			     	return '<input name="'.$options['name'].'" value="'.$value.'" class="form-control '.(isset($options['class']) ?? $options['class']).'" '.(isset($options['any']) ?? $options['any']).'>';
			}
		}

	}

	/*
		TRANSLATIONS
	*/

	// Return the translated string give if exists (used in CMS admin.php)
	function __translate($key){
		$data = $this->row($this->database->translation->where('key','=',$key)->where('language','=',$this->language)->limit(1)->fetch());
		if($data) return $data['value'];
	}

	// Return the translated string given if exists.
	function __($key){
		return $key;
	}

	// Prints the translated string given if exists.
	function _($key){
		print $this->__($key);
	}

 
	/*
		HELPERS
	*/

	// Get current datetime
	function now(){
		return date("Y-m-d H:i:s");
	}

	// Redirect
	function redirect($url,$alert=null){
		header('Location: '.$url);
		$_SESSION['notifications'] = $alert;
	}

	//update config
	function updateConfig($data){
		file_put_contents(__DIR__.'/.default_stores',$data);
	}

	//backup
	function backup()
	{
		$destination = __DIR__.'/backups/'.time().'.zip';
		$source = $this->storage_path;
	    if (!extension_loaded('zip') || !file_exists($source)) {
	        return false;
	    }

	    $zip = new ZipArchive();
	    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
	        return false;
	    }

	    $source = str_replace('\\', '/', realpath($source));

	    if (is_dir($source) === true) {

	        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

	        foreach ($files as $file) {
	            $file = str_replace('\\', '/', $file);

	            // Ignore "." and ".." folders
	            if (in_array(substr($file, strrpos($file, '/')+1), array('.', '..'))) {
	                continue;
	            }          

	            $file = realpath($file);

	            if (is_dir($file) === true) {
	                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
	            } elseif (is_file($file) === true) {
	                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
	            }
	        }
	    } elseif (is_file($source) === true) {
	        $zip->addFromString(basename($source), file_get_contents($source));
	    }

	    return $zip->close();
	}

	// read file
	function readFile($path){
		$myfile = fopen($this->root_path.$path, "r") or die("Unable to open config file!");
		$file = fread($myfile,filesize($this->root_path.$path));
		fclose($myfile);
		return $file;
	}

	// is valid json
	function isValidJson($string) {
	   json_decode($string);
	   return json_last_error() === JSON_ERROR_NONE;
	}

	// flatten
	function flatten(array $array) {
	    $return = array();
	    array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
	    return $return;
	}

	function toJson($array){
		return json_encode($array);
	}

}

session_start();
$cms = new CMS($config);
$database = $cms->database;

if($cms->setup) die('Needs a setup file.');



