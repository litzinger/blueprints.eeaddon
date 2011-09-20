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
        // Delete old hooks
        $this->EE->db->query("DELETE FROM exp_extensions WHERE class = 'Blueprints_ext'");
        
        // Add new hooks
        $ext_template = array(
            'class'    => 'Blueprints_ext',
            'settings' => '',
            'priority' => 8,
            'version'  => $this->version,
            'enabled'  => 'y'
        );
        
        $extensions = array(
            array('hook'=>'publish_form_channel_preferences', 'method'=>'publish_form_channel_preferences'),
            array('hook'=>'sessions_end', 'method'=>'sessions_end'),
            array('hook'=>'entry_submission_ready', 'method'=>'entry_submission_ready')
        );
        
        foreach($extensions as $extension)
        {
            $ext = array_merge($ext_template, $extension);
            $this->EE->db->insert('exp_extensions', $ext);
        }
        
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
        
        // Delete records
        $this->EE->db->where('class', 'Blueprints_ext');
        $this->EE->db->delete('exp_extensions');
        
        // Remove layout from the DB
        // $this->EE->db->where('member_group', '>= 2000');
        // $this->EE->db->delete('layout_publish');
        
		$this->EE->load->dbforge();
		$this->EE->dbforge->drop_table('blueprints_layouts');
		$this->EE->dbforge->drop_table('blueprints_entries');
        
        return TRUE;
    }
    
    public function update($current = '')
    {
        if ($current == $this->version)
        {
            return FALSE;
        }
        
        if($current < '1.3.2')
        {
            // Save our settings to the current site ID for MSM.
            $site_id = $this->EE->config->item('site_id');
            $settings = $this->global_settings;

            if(!isset($settings[$site_id]) OR $settings[$site_id] == '')
            {
                $new_settings[$site_id] = $settings;
                $this->EE->db->where('class', strtolower(__CLASS__));
                $this->EE->db->update('extensions', array('settings' => serialize($new_settings)));
            }
        }
        
        if($current < '1.3.5')
        {
            $this->EE->db->where('class', strtolower(__CLASS__));
            $this->EE->db->where('method', 'submit_new_entry_start');
            $this->EE->db->update('extensions', array(
                'method' => 'entry_submission_absolute_end',
                'hook' => 'entry_submission_absolute_end'
            ));
        }
        
        if($current < '1.3.7.4')
        {
            $this->EE->db->where('class', strtolower(__CLASS__));
            $this->EE->db->where('method', 'entry_submission_absolute_end');
            $this->EE->db->update('extensions', array(
                'method' => 'entry_submission_ready',
                'hook' => 'entry_submission_ready'
            ));
        }
        
        // Update version #
        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->update('exp_extensions', array('version' => $this->version));

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