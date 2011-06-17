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

    public $version = BLUEPRINTS_VERSION;

    function __construct()
    {
        $this->EE =& get_instance();
    }

    public function install()
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
        
        $this->_add_tables();
        
        return TRUE;
    }
    
    public function uninstall()
    {
        $this->EE->db->where('module_name', BLUEPRINTS_NAME);
        $this->EE->db->delete('modules');
        
        $this->EE->db->where('class', 'Blueprints_mcp')->delete('actions');
        
        return TRUE;
    }
    
    public function update($current = '')
    {
        if ($current == $this->version)
        {
            return FALSE;
        }

        if($current < '2.0')
        {
            $this->_add_tables();
            $this->_migrate_settings();
        }
        
        return TRUE;
    }
    
    private function _migrate_settings()
    {
        $qry = $this->EE->db->select('settings')
                            ->from('extensions')
                            ->where('enabled', 'y')
                            ->where('class', 'Blueprints_ext')
                            ->limit(1)
                            ->get();
                            
        $settings = unserialize($qry->row('settings'));

        foreach($settings as $site_id => $setting)
        {
            foreach($setting['layout_group_names'] as $k => $v)
            {
                $data = array(
                    'site_id'       => $site_id,
                    'group_id'      => $setting['layout_group_ids'][$k],
                    'template'      => $setting['template'][$k],
                    'thumbnail'     => $setting['thumbnails'][$k],
                    'name'          => $setting['layout_group_names'][$k],
                );
            
                $this->EE->db->insert('blueprints_layouts', $data);
            }
        
            foreach($setting['template_layout'] as $entry_id => $v)
            {
                $data = array(
                    'site_id'       => $site_id,
                    'entry_id'      => $entry_id,
                    'template_id'   => $v['template_id'],
                    'group_id'      => $v['layout_group_id']
                );
            
                $this->EE->db->insert('blueprints_entries', $data);
            }
        }
    }
    
    // Added in 2.0
    private function _add_tables()
    {
        // Create our external tables
        $this->EE->load->dbforge();
        
        if (! $this->EE->db->table_exists('blueprints_layouts'))
        {
            $this->EE->dbforge->add_field(array(
                'id'            => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE, 'auto_increment' => TRUE),
                'site_id'       => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
                'group_id'      => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
                'template'      => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
                'thumbnail'     => array('type' => 'text'),
                'name'          => array('type' => 'text')
            ));

            $this->EE->dbforge->add_key('id', TRUE);
            $this->EE->dbforge->add_key('group_id', TRUE);
            $this->EE->dbforge->create_table('blueprints_layouts');
        }
        
        if (! $this->EE->db->table_exists('blueprints_entries'))
        {
            $this->EE->dbforge->add_field(array(
                'id'            => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE, 'auto_increment' => TRUE),
                'site_id'       => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
                'entry_id'      => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
                'template_id'   => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
                'group_id'      => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE)
            ));

            $this->EE->dbforge->add_key('id', TRUE);
            $this->EE->dbforge->add_key('entry_id', TRUE);
            $this->EE->dbforge->add_key('group_id', TRUE);
            $this->EE->dbforge->create_table('blueprints_entries');
        }
    }
    
    private function debug($str, $die = false)
    {
        echo '<pre>';
        var_dump($str);
        echo '</pre>';
        
        if($die) die('debug terminated');
    }
}