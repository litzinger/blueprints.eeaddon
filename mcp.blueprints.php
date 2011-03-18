<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if (! defined('BLUEPRINTS_VERSION'))
{
    // get the version from config.php
    require PATH_THIRD.'blueprints/config.php';
    define('BLUEPRINTS_VERSION', $config['version']);
    define('BLUEPRINTS_NAME', $config['name']);
    define('BLUEPRINTS_DESC', $config['description']);
}

/**
 * ExpressionEngine Module Class
 *
 * @package     ExpressionEngine
 * @subpackage  Modules
 * @category    Blueprints
 * @author      Brian Litzinger
 * @copyright   Copyright 2011 to infinity and beyond! - Boldminded / Brian Litzinger
 * @link        http://boldminded.com/add-ons/blueprints
 */

class Blueprints_mcp {
    
    function Blueprints_mcp()
    {
        $this->EE =& get_instance();
    }
    
    function index() {}
    
    function get_autosave_entry()
    {
        $entry_id = $this->EE->input->post('entry_id');
        $channel_id = $this->EE->input->post('channel_id');
        $site_id = $this->EE->config->item('site_id');
        
        $autosave_entry_id = $this->EE->db->select('entry_id')
                                          ->where('original_entry_id', $entry_id)
                                          ->where('channel_id', $channel_id)
                                          ->where('site_id', $site_id)
                                          ->get('channel_entries_autosave')
                                          ->row('entry_id');
                                        
        $this->send_ajax_response($autosave_entry_id);
    }
    
    private function send_ajax_response($msg)
    {
        $this->EE->output->enable_profiler(FALSE);
        
        @header('Content-Type: text/html; charset=UTF-8');  
        
        exit($msg);
    }
    
}