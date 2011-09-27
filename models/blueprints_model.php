<?php

class Blueprints_model
{
    public $settings;
    public $entries;
    public $layouts;
    
    function __construct()
    {
        $this->EE =& get_instance();
        $this->site_id = $this->EE->config->item('site_id');

        // Create cache
        if (! isset($this->EE->session->cache['blueprints']))
        {
            $this->EE->session->cache['blueprints'] = array();
        }
        $this->cache =& $this->EE->session->cache['blueprints'];
    }
    
    /*
    @param - string
    @param - array of data to be inserted, key => value pairs
    @param - array of data used to find the row to update, key => value pairs
    
    _insert_or_update('some_table', array('foo' => 'bar'), array('id' => 1, 'something' => 'another-thing'))
    
    */
    public function insert_or_update($table, $data, $where)
    {
        $query = $this->EE->db->get_where($table, $where);

        // No records were found, so insert
        if($query->num_rows() == 0)
        {
            $this->EE->db->insert($table, $data);
            return $this->EE->db->insert_id();
        }
        // Update existing record
        elseif($query->num_rows() == 1)
        {
            $this->EE->db->where($where)->update($table, $data);
            return false;
        }
    }
    
    public function get_active_publish_layout($channel_id = false)
    {
        // Get the current Site ID
        $site_id = $this->EE->config->item('site_id');
        // See if we've hi-jacked the layout_preview
        $layout_preview = (isset($_GET['layout_preview']) AND $_GET['layout_preview'] !== false) ? $_GET['layout_preview'] : false;
        // Set default value to return if nothing else is found
        $this->cache['active_publish_layout'] = array();

        // If we have hi-jacked the layout_preview
        if($layout_preview)
        {
            // Get normal member groups
            $sql = "SELECT lp.member_group, lp.layout_id, m.group_id, m.group_title, lp.field_layout 
                FROM exp_layout_publish lp, exp_member_groups m 
                WHERE lp.member_group = m.group_id
                AND lp.channel_id = '". $channel_id ."'
                AND lp.site_id = '".$site_id."'";

            $result = $this->EE->db->query($sql);
        
            if($result->num_rows() > 0)
            {
                foreach($result->result_array() as $row)
                {
                    $this->cache['active_publish_layout'][$row['group_id']] = $row['group_title'];
                }
            }
            
            // Get our fake member groups
            $sql = "SELECT lp.member_group, lp.layout_id, lp.field_layout 
                FROM exp_layout_publish lp
                WHERE lp.member_group = '". $layout_preview ."'
                AND lp.channel_id = '". $channel_id ."'
                AND lp.site_id = '".$site_id."'";

            $result = $this->EE->db->query($sql);
        
            if($result->num_rows() > 0)
            {
                foreach($result->result_array() as $row)
                {
                    // $key = array_search($row['member_group'], $this->settings['layout_group_ids']);
                    // $this->cache['active_publish_layout'][$row['member_group']] = $this->settings['layout_group_names'][$key];
                    
                    $this->cache['active_publish_layout'][$row['member_group']] = $this->layouts[$row['member_group']];
                }
            }
        }
        // We have not hi-jacked the layout_preview, so return the default assigned layouts
        else
        {
            $sql = "SELECT lp.member_group, lp.layout_id, m.group_id, m.group_title, lp.field_layout 
                FROM exp_layout_publish lp, exp_member_groups m 
                WHERE lp.member_group = m.group_id
                AND lp.channel_id = '". $this->EE->input->get_post('channel_id') ."'
                AND lp.site_id = '".$site_id."'";
                
            $result = $this->EE->db->query($sql);
        
            if($result->num_rows() > 0)
            {
                $group_titles = array();
                foreach($result->result_array() as $row)
                {
                    $this->cache['active_publish_layout'][] = $row['group_title'];
                }
            }
        }

        return $this->cache['active_publish_layout'];
    }
    
    public function get_assigned_templates($ids)
    {
        if(!isset($this->cache['assigned_templates']))
        {
            // Get the current Site ID
            $site_id = $this->EE->config->item('site_id');
            
            $this->EE->db->select('template_groups.group_name, templates.template_name, templates.template_id')
                         ->where('template_groups.site_id', $site_id)
                         ->order_by('template_groups.group_name, templates.template_name')
                         ->join('template_groups', 'template_groups.group_id = templates.group_id');
            
            if($ids)
            {
                $this->EE->db->where_in('templates.template_id', $ids);
            }
            
            $query = $this->EE->db->get('templates');

            $this->cache['assigned_templates'] = $query->result_array();
        }
        
        return $this->cache['assigned_templates'];
    }
    
    public function get_templates($groups = false)
    {
        if(!isset($this->cache['templates']))
        {
            // Get the current Site ID
            $site_id = $this->EE->config->item('site_id');

            $groups = $groups ? " AND tg.group_name IN (". $groups .")" : '';

            $sql = "SELECT tg.group_name, t.template_name, t.template_id
                    FROM exp_template_groups tg, exp_templates t
                    WHERE tg.group_id = t.group_id
                    AND tg.site_id = '".$site_id."'". $groups ."
                    ORDER BY tg.group_name, t.template_name";

            $this->cache['templates'] = $this->EE->db->query($sql);
        }
        
        return $this->cache['templates'];
    }
    
    public function get_thumbnails()
    {
        if(!isset($this->cache['thumbnails']))
        {
            $thumbnails = array();
            $path = isset($this->settings['thumbnail_path']) ? $this->site_path() . $this->settings['thumbnail_path'] : $this->thumbnail_directory_path;

            if( ! class_exists('Image_lib')) 
            {
                $this->EE->load->library('image_lib');
            }
            
            if($handle = @opendir($path)) 
            {
                while (false !== ($file = readdir($handle))) 
                {
                    if(strncmp($file, '.', 1) !== 0)
                    {    
                        $properties = $this->EE->image_lib->get_image_properties($path.$file, true);
                        $thumbnails[] = array_merge($properties, array('file_name' => $file));
                    }
                }

                closedir($handle);
            }
            $this->cache['thumbnails'] = $thumbnails;
        } 

        return $this->cache['thumbnails'];
    }
    
    public function get_channels()
    {
        if(!isset($this->cache['channels']))
        {
            // Get the current Site ID
            $site_id = $this->EE->config->item('site_id');

            $sql = "SELECT channel_id, channel_name, channel_title FROM exp_channels WHERE site_id = '".$site_id."'";

            $this->cache['channels'] = $this->EE->db->query($sql);
        }
        
        return $this->cache['channels'];
    }
    
    public function get_layouts($force_refresh = FALSE)
    {
        // Get the settings for the extension
        if(isset($this->cache['layouts']) === FALSE || $force_refresh === TRUE)
        {
            if(array_key_exists('blueprints', $this->EE->addons->get_installed()))
            {
                // check the db for extension settings
                $query = $this->EE->db->get_where('blueprints_layouts', array('site_id' => $this->EE->config->item('site_id')));

                $layouts = array();

                foreach($query->result_array() as $row)
                {
                    $layouts[$row['group_id']] = $row;
                }

                // save them to the cache
                $this->cache['layouts'] = $layouts;
            }
        }
        
        return isset($this->cache['layouts']) ? $this->cache['layouts'] : array();
    }
    
    public function get_entries($force_refresh = FALSE)
    {
        // Get the settings for the extension
        if(isset($this->cache['entries']) === FALSE || $force_refresh === TRUE)
        {
            if(array_key_exists('blueprints', $this->EE->addons->get_installed()))
            {
                // check the db for extension settings
                $query = $this->EE->db->get_where('blueprints_entries', array('site_id' => $this->EE->config->item('site_id')));

                $entries = array();
            
                foreach($query->result_array() as $entry)
                {
                    $entries[$entry['entry_id']] = $entry;
                }

                // save them to the cache
                $this->cache['entries'] = $entries;
            }
        }

        return isset($this->cache['entries']) ? $this->cache['entries'] : array();
    }
    
    public function get_structure_settings()
    {
        if(!isset($this->cache['structure_settings']))
        {
            $site_id = $this->EE->config->item('site_id');

            // Get Structure Channel Data
            $sql = "SELECT ec.channel_id, ec.channel_title, esc.template_id, esc.type, ec.site_id
                    FROM exp_channels AS ec 
                    LEFT JOIN exp_structure_channels AS esc ON ec.channel_id = esc.channel_id
                    WHERE ec.site_id = '$site_id'";

            $results = $this->EE->db->query($sql);
            
            // Format the array nicely
            $channel_data = array();
            foreach($results->result_array() as $key => $value)
            {
                $channel_data[$value['channel_id']] = $value;
                unset($channel_data[$value['channel_id']]['channel_id']);
            }

            $this->cache['structure_settings'] = $channel_data;
        }
        
        return $this->cache['structure_settings'];
    }
    
}