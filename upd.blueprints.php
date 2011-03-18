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

class Blueprints_upd {

    var $version = BLUEPRINTS_VERSION;

    function Blueprints_upd($switch = TRUE)
    {
        $this->EE =& get_instance();
    }

    function install()
    {
        // Module data
        $data = array(
            'module_name' => BLUEPRINTS_NAME,
            'module_version' => BLUEPRINTS_VERSION,
            'has_cp_backend' => 'n',
            'has_publish_fields' => 'n'
        );

        $this->EE->db->insert('modules', $data);
        
        // Insert our Action
        $query = $this->EE->db->get_where('actions', array('class' => 'Blueprints_mcp'));

        if($query->num_rows() == 0)
        {
            $data = array(
                'class' => 'Blueprints_mcp',
                'method' => 'get_autosave_entry'
            );

            $this->EE->db->insert('actions', $data);
        }
        
        return TRUE;
    }
    
    function uninstall()
    {
        $this->EE->db->where('module_name', BLUEPRINTS_NAME);
        $this->EE->db->delete('modules');
        
        $this->EE->db->where('class', 'Blueprints_mcp')->delete('actions');
        
        return TRUE;
    }
    
    function update($current = '')
    {
        return TRUE;
    }
}