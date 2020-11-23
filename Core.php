<?php

namespace Core;

$curDir = dirname(__FILE__);

// Prevent to forge Config
if(!file_exists($curDir.'/Config.php')) die('Error - Place "Config.php" in the same level as "Core.php".');

// Prevent to forge SleekDB
if(!file_exists($curDir.'/vendor/autoload.php')) die('Error - Did you /composer install? <br> We cant\'t find "autoload.php" file inside "vendor" directory');

require $curDir.'/Config.php';
require $curDir.'/vendor/autoload.php';

// If public path is not defined or not exists, error
if(!isset($config['public_path'])) die('Define the "public_path" in your Config file.');

if(!file_exists($config['public_path'])) die('The "public_path" does not exists, define it your Config file.');


// Create storage path for data storage.
if(!is_dir($curDir.'/storage')){
	mkdir($curDir.'/storage',0777);
	if(!is_dir($curDir.'/storage/public')) mkdir($curDir.'/storage/public',0777);
}

// Create data path for database storage.
if(!is_dir($curDir.'/storage/stores')) mkdir($curDir.'/storage/stores',0777);

// Under windows, no symlink so we need to create Storage folder instead.
if(!is_dir($config['public_path'].'/storage')){
	if(!@symlink($curDir.'/storage/public',$config['public_path'].'/storage')){
		mkdir($config['public_path'].'/storage',0777);
	}
}


/*
	DEFAULT STORES TO MAKE THE CMS WORK PROPERLY
*/


/*
	MAIN CMS CLASS
*/

class CMS {
	var $database;
	var $options;
	var $language;
	var $pendingLanguage = [];
	var $setup = false;
	var $allowed_extensions = array('image/jpeg' => 'jpg');
	var $default_stores = [
		'users' => [
			'username' => 'text',
			'password' => 'password',
			'email' => 'email',
			'created'=> 'datetime',
		],
		'translation' => [
			'key' => 'text',
			'value' => 'text',
			'language' => 'text',
		]
	];


	function __construct($config){
		$this->config = $config;

		// Initializating the language
		$this->language();
		
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
		return $_SESSION['language'];
	}	

	// Initcialize the Database Playground
	function database(){
		$database = array();

		// If no stores, exit.
		if(!isset($this->config['stores']) || empty($this->config['stores'])) return false;
		foreach($this->config['stores'] as $store_key=>$store_columns) $database[$store_key] = \SleekDB\SleekDB::store($store_key, dirname(__FILE__).'/storage/stores', $this->options);
		$this->database = (object) $database; 

		// If we have users table, create a default user.
		if(isset($this->database->users) && empty($this->database->users->fetch())){
			$this->database->users->insert([
				'username' => 'admin',
				'email' => 'admin@admin.com',
				'password' => md5('password'),
			]);
		}
	}

	// Check if user is Logged
	function isLogged(){
		if(isset($_SESSION['logged']) && !empty($_SESSION['logged'])) return true; else return false;
	}

	// Login
	function login($username,$password){
		$user = $this->row($this->database->users
		->where( 'username', '=', $username)
		->where( 'password', '=', md5($password))
		->fetch());

		// If the user exists, redirect to admin.php
		if($user){
			$_SESSION['logged'] = $user;
			$this->redirect("admin.php");
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
		$text = '<form method="post" class="text-right"><button name="insert" class="btn btn-primary">'.$this->__('New').'</button></form><table class="table">';
		$text .= '<tr><td>#</td>';
		foreach($this->config['stores'][$table] as $name=>$value){
			if(is_array($value)) continue;
			$text .= '<td><b>'.$name.'</b><br><small><i class="text-dark">'.$value.'</i></small></td>';
		}
		$text .= '<td></td></tr>';
		
		$data = $this->database->$table->orderBy( 'desc', '_id' )->fetch();

 

		foreach($data as $datak=>$datav){
			$text .= '<tr><td>'.$datav['_id'].'</td>';
			foreach($this->config['stores'][$table] as $name=>$value){
				if(is_array($value)) continue;
				$text .= '<td>'.(isset($datav[$name]) ? $datav[$name] : '').'</td>';
			}
			$text .= '<td class="text-right"><form method="post"><input type="hidden" name="id" value="'.$datav['_id'].'"><button name="delete" class="btn btn-danger btn-sm">'.$this->__('Delete').'</button> <button name="update" class="btn btn-success btn-sm">'.$this->__('Edit').'</button> <button name="view" class="btn btn-primary btn-sm">'.$this->__('View').'</button></form></td></tr>';
		}
		$text .= '</table>';
		return $text;
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
			$data = $this->row($this->database->$table->where('_id','=',$id)->limit(1)->fetch());
		}

		$translatable = $this->tableOptions('translatable',$this->config['stores'][$table]);


		foreach($this->config['stores'][$table] as $name=>$value){
			if(is_array($value)) continue;

			// If is translatable
			$translatable_label = null;
			if(isset($translatable) && in_array($name, $translatable)){
				$translatable_label = '/ translatable';
				$data[$name] = $this->__translate($value);
				$text .= '<input type="hidden" name="translatable['.$name.']">';
			}

			$text .= '<div class="mt-2"><b>'.$name.'</b><br><small><i class="text-dark">'.$value.' '.$translatable_label.'</i></small>';
			$text .= $this->editable($action,['name'=>$name,'type'=>$value],(isset($data[$name]) ? $data[$name] : null));
			$text .= '</div>';
		}

		// IF action is not view_row
		if($action != 'view_row') $text .= '<button name="'.$action.'" class="mt-3 mr-2 btn btn-primary">'.$this->__($action).'</button>';
		$text .= '<a href="admin.php?p='.$_GET['p'].'" class="mt-3 btn btn-danger">'.$this->__('Cancel').'</a>';

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

	// Update or Insert data
	function updateInsert($table,$data,$files){

		foreach($this->config['stores'][$table] as $name=>$value){
			// Data
			$translatable = null;
			if(array_key_exists($name,$data)){
				// Translatables
				if(is_array($data['translatable']) && array_key_exists($name, $data['translatable'])){
					$translatable[$name] = $data[$name];
				}
				
				$update[$name] = $data[$name];
							
			}

			// Files
			if(array_key_exists($name,$files)){
				if($value == 'image') $update[$name] = $this->moveUploadedFile($files,$name);
			}
		}

		if(isset($data['id']) && !empty($data['id'])){
			$this->database->$table->where('_id','=',$data['id'])->update($update);	
		} else {
			$this->database->$table->insert($update);	
		}

		// translatables
		if(is_array($translatable)){
			foreach($translatable as $key=>$value){
				if(!$this->database->$table->where('key','=',$key)->update(['value'=>$value,'language'=>$this->language])){
					$this->database->$table->insert([
						'value' => $value,
						'language' => $language,
						'key' => $key,
					]);
				}
			}
			
		}
		
	}


	var $_editable = ['text','textarea','password','checkbox','select'];
	function editable($action,$options=null,$value=null){

		if($action == 'view_row'){
			switch ($options['type']) {
				case 'image':
					$image = '<span class="form-control">'.$value.'</span>';
					$image .= '<img class="img-fluid" src=".'.$value.'">';
					return $image;
				default:
			      	return '<span class="form-control">'.$value.'</span>';
			}
		}

		if($action == 'update_row' || $action == 'insert_row'){
			switch ($options['type']) {
				case 'image':
					return '<input type="file" name="'.$options['name'].'" class="form-control '.(isset($options['class']) ?? $options['class']).'" '.(isset($options['any']) ?? $options['any']).' /><br><b>'.$this->__('allowed_extensions').'</b>: '.implode(', ',$this->allowed_extensions);
			    	case 'textarea':
			        	return '<textarea name="'.$options['name'].'" class="form-control '.(isset($options['class']) ?? $options['class']).'" '.(isset($options['any']) ?? $options['any']).'>'.$value.'</textarea>';
			    	case 'datetime':
			      		return '<input type="date"  name="'.$options['name'].'" value="'.$value.'" class="form-control '.(isset($options['class']) ?? $options['class']).'" '.(isset($options['any']) ?? $options['any']).'>';
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
		if(!$this->database->translation) return $key;
		$data = $this->row($this->database->translation->where('key','=',$key)->where('language','=',$this->language)->limit(1)->fetch());	
		if($data) return $data['value'];
		array_push($this->pendingLanguage,$key);
		return $key;
	}

	// Prints the translated string given if exists.
	function _($key){
		print $this->__($key);
	}

	// Prints the translation BOX to translate strings.
	function translationBox(){
		if(!$this->isLogged()) return false;
		if(!isset($this->config['languages'])) $this->config['languages'][0] = $this->language;
		$text = '<h3 class="text-2xl mt-3">'.$this->__('Translations Box').'</h3>';
		$text .= '<form method="post"><table class="table w-full">';
		$this->pendingLanguage = array_unique($this->pendingLanguage);
		foreach($this->pendingLanguage as $key){

			foreach($this->config['languages'] as $language){

			$text .= '<tr>';
			$text .= '<td width="30%" class="p-2"><b>('.$language.') '.$key.'</b></td><td>'.$this->editable('insert_row',['name'=>'insert_lang['.$language.']['.$key.']','class'=>'btn-secondary','type'=>'text','any'=>'placeholder="Translate this text"']).'</td>';
			$text .= '</tr>';

			}
		}
		$text .= '</table><button name="add_translation" class="mr-2 btn btn-primary bg-gray-200 rounded p-2 hover:bg-gray-300 mt-2">Add translations</button></form>';
		print $text;
	}

	// Add translations to the DATABASE
	function translationBoxAdd($insert_lang){
	     foreach($_POST['insert_lang'] as $language=>$data){
	        foreach($data as $key=>$value){
	        	if(empty($value)) continue;
	            $this->database->translation->insert([
	                'key' => $key,
	                'value' => $value,
	                'language' => $language,
	            ]);
	        }
	        
	     }
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

}

session_start();
$cms = new CMS($config);
$database = $cms->database;

if($cms->setup) die('Needs a setup file.');



