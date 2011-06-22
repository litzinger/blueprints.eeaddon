<?php

class Blueprints_helper
{
    private $EE;
    private $site_id;
    private $cache;
    
    public $settings; // Blueprints extension settings
    public $session;
    public $thumbnail_directory_url;
    public $thumbnail_directory_path;
    
    public function __construct()
    {
        $this->EE =& get_instance();
        $this->site_id = $this->EE->config->item('site_id');
        
        // Create cache
        if (! isset($session->cache[__CLASS__]))
        {
            $session->cache[__CLASS__] = array();
        }
        $this->cache =& $session->cache[__CLASS__];
    }
    
    public function get_checkbox_options($k)
    {
        $templates = $this->get_templates();
        
        $checkbox_options = '';
        $groups = array();
        
        foreach($templates->result_array() as $template)
        {
            if(!in_array($template['group_name'], $groups))
            {
                $checked = ((
                        isset($template['group_name']) AND 
                        isset($this->settings['channel_show_group']) AND
                        isset($this->settings['channel_show_group'][$k]) AND 
                        in_array($template['group_name'], $this->settings['channel_show_group'][$k])
                )) ? TRUE : FALSE;
                
                $checkbox_options .= '<p>';
                $checkbox_options .= form_checkbox(
                                        'channel_show_group['. $k .'][]', 
                                        $template['group_name'], 
                                        $checked, 
                                        'class="show_group" id="channel_show_group['. $k .']['. $template['group_name'] .']"'
                                    );
                
                $checkbox_options .= ' <label for="channel_show_group['. $k .']['. $template['group_name'] .']">Show all <i>'. $template['group_name'] .'</i> templates</label>';
            }
            $groups[] = $template['group_name'];
        }
        
        $checked = (
            isset($this->settings['channel_show_selected']) AND
            isset($this->settings['channel_show_selected'][$k]) AND 
            $this->settings['channel_show_selected'][$k] == 'y'
        ) ? TRUE : FALSE;
        
        $checkbox_options .= '<p>'. form_checkbox(
                                        'channel_show_selected['. $k .']', 
                                        'y',
                                        $checked,
                                        'id="channel_show_selected['. $k .']" class="show_selected"'
                                    );
                                    
        $checkbox_options .= ' <label for="channel_show_selected['. $k .']">Show only specific templates</label></p>';
        
        return $checkbox_options;
    }
    
    public function is_module_installed($module)
    {       
        if(!isset($this->cache[$module.'_installed']))
        {
            $qry = $this->EE->db->get_where('modules', array('module_name' => $module));
            $this->cache[$module.'_installed'] = ($qry->num_rows() == 1) ? true : false;
        }
        
        return $this->cache[$module.'_installed'];
    }
    
    public function get_pages()
    {
        // Make sure pages cache is empty, and also see if we are in the CP. Since fieldtype files get loaded
        // on the front end, I don't want unecessary queries/processing to be done when not needed.
        if(!isset($this->cache['pages']) AND REQ == 'CP')
        {
            $this->cache['pages'] = "";
            
            if($this->is_module_installed('Structure'))
            {
                require_once $this->get_theme_folder_path().'boldminded_themes/libraries/structure_pages.php';
                $pages = Structure_Pages::get_instance();
                $this->cache['pages'] = $pages->get_pages($this->EE);
            }
            elseif($this->is_module_installed('Pages'))
            {
                require_once $this->get_theme_folder_path().'boldminded_themes/libraries/pages.php';
                $pages = Pages::get_instance();
                $this->cache['pages'] = $pages->get_pages($this->EE);
            }
        }

        return $this->cache['pages'];
    }
    
    public function get_theme_folder_path()
    {
        return PATH_THEMES . 'third_party/';
    }
    
    public function get_theme_folder_url()
    {
        return $this->EE->config->slash_item('theme_folder_url') .'third_party/';
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
    
    // Isn't used anymore
    // public function _get_publish_layouts()
    // {
    //     if(!isset($this->cache['layouts']))
    //     {
    //         // Get the current Site ID
    //         $site_id = $this->EE->config->item('site_id');
    // 
    //         $sql = "SELECT lp.member_group, lp.layout_id, m.group_id, m.group_title, lp.field_layout 
    //                 FROM exp_layout_publish lp, exp_member_groups m 
    //                 WHERE lp.member_group = m.group_id
    //                 AND lp.site_id = '".$site_id."'";
    // 
    //         $this->cache['layouts'] = $this->EE->db->query($sql);
    //     }
    //     
    //     return $this->cache['layouts'];
    // }
    
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
    
    public function enable_publish_layout_takeover()
    {
        if(!isset($this->cache['enable_publish_layout_takeover']))
        {
            $this->cache['enable_publish_layout_takeover'] = (isset($this->settings['enable_publish_layout_takeover']) AND $this->settings['enable_publish_layout_takeover'] == 'y') ? true : false;
        }
        
        return $this->cache['enable_publish_layout_takeover'];
    }
    
    public function is_publish_form()
    {
        if(REQ != "CP")
        {
            return false;
        }
        
        if($this->EE->input->get('C') == 'content_publish' AND $this->EE->input->get('M') == 'entry_form')
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public function get_layouts($force_refresh = FALSE)
    {
        // Get the settings for the extension
        if(isset($this->cache['layouts']) === FALSE || $force_refresh === TRUE)
        {
            if($this->is_module_installed('Blueprints'))
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
            if($this->is_module_installed('Blueprints'))
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
    
    /**
      * Retrieve site path
      */
    public function site_path()
    {
        $site_url = $this->EE->config->slash_item('site_path');
        return $site_url ? $site_url : str_replace('themes/', '', PATH_THEMES);
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
    
    /*
        Allow config overrides
    */
    public function set_paths()
    {
        // If path and url is set in the user's config file, use them.
        if($this->EE->config->item('blueprints.thumbnail_directory_url') AND $this->EE->config->item('blueprints.thumbnail_directory_path'))
        {
            $this->thumbnail_directory_url = $this->EE->config->item('blueprints.thumbnail_directory_url');
            $this->thumbnail_directory_path = $this->EE->config->item('blueprints.thumbnail_directory_path');
        }
        else
        {
            $this->thumbnail_directory_url = 'images/template_thumbnails/';
            
            // If the user set the site_path var, use it.
            if($this->EE->config->item('site_path'))
            {
                $this->thumbnail_directory_path = 'images' . DIRECTORY_SEPARATOR . 'template_thumbnails' . DIRECTORY_SEPARATOR;
            }
            // Or fallback and try to find the site root path.
            else
            {
                // Really? I would think BASEPATH would be the absolute root of the site, not the base of the EE install?
                // Is there a variable I don't know about to get the EE webroot path?
                $images_path = str_replace('themes', 'images', PATH_THEMES);
                $this->thumbnail_directory_path = $images_path . DIRECTORY_SEPARATOR . 'template_thumbnails' . DIRECTORY_SEPARATOR;
            }
        }
    }
    
    /**
    * Get the site specific settings from the extensions table
    * Originally written by Leevi Graham? Modified for EE2.0
    *
    * @param $force_refresh     bool    Get the settings from the DB even if they are in the session
    * @return array                     If settings are found otherwise false. Site settings are returned by default.
    */
    public function get_settings($force_refresh = FALSE)
    {
        // assume there are no settings
        $settings = FALSE;
        $this->EE->load->helper('string');

        // Get the settings for the extension
        if(isset($this->cache['settings']) === FALSE || $force_refresh === TRUE)
        {
            // check the db for extension settings
            $query = $this->EE->db->query("SELECT settings FROM exp_extensions WHERE enabled = 'y' AND class = 'Blueprints_ext' LIMIT 1");

            // if there is a row and the row has settings
            if ($query->num_rows() > 0 && $query->row('settings') != '')
            {
                // save them to the cache
                $this->cache['settings'] = strip_slashes(unserialize($query->row('settings')));
            }
        }

        return isset($this->cache['settings']) ? $this->cache['settings'] : array();
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
    
    private function debug($str, $die = false)
    {
        echo '<pre>';
        var_dump($str);
        echo '</pre>';
        
        if($die) die('debug terminated');
    }
    
}