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
 * @copyright   Copyright (c) 2010, 2011 - Brian Litzinger
 * @link        http://boldminded.com/add-ons/blueprints
 * @license 
 *
 * Copyright (c) 2011, 2012. BoldMinded, LLC
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
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
    
    function load_pages()
    {
        $pages = $this->EE->blueprints_helper->get_pages();
        
        $this->send_ajax_response($pages);
    }
    
    private function send_ajax_response($msg)
    {
        $this->EE->output->enable_profiler(FALSE);
        
        @header('Content-Type: text/html; charset=UTF-8');  
        
        exit($msg);
    }
    
}