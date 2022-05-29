<?php

namespace Http;

class Router 
{

    private $_uri = array();
    private $_action = array();

    public function add($uri, $action = null) 
    {
        $this->_uri[] = '/' . trim($uri, '/');

        if ($action != null) 
        {
            $this->_action[] = $action;
        }
    }

    public function run() 
    {
        $uriGet = isset($_GET['uri']) ? '/' . $_GET['uri'] : '/';
        
        foreach ($this->_uri as $key => $value) 
        {
            if (preg_match("#^$value$#", $uriGet)) 
            {
                $action = $this->_action[$key];
                $this->runAction($action);
            }
        }
    }

    private function runAction($action) 
    {
        if($action instanceof \Closure)
        {
            $action();
        }  
        else 
        {
            $params = explode('@', $action);
            $obj = new $params[0];
            $obj->{$params[1]}();
        }
    }

}
?>