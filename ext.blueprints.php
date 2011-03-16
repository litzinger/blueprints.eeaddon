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
 * ExpressionEngine Extension Class
 *
 * @package     ExpressionEngine
 * @subpackage  Extensions
 * @category    Blueprints
 * @author      Brian Litzinger
 * @copyright   Copyright 2010 to infinity and beyond! - Boldminded / Brian Litzinger
 * @link        http://boldminded.com/add-ons/blueprints
 */
 
class Blueprints_ext {

    var $settings       = array();
    var $global_settings = array();
    var $name           = BLUEPRINTS_NAME;
    var $version        = BLUEPRINTS_VERSION;
    var $description    = BLUEPRINTS_DESC;
    var $settings_exist = 'y';
    var $docs_url       = 'http://boldminded.com/add-ons/blueprints';
    var $cache;
    var $thumbnail_directory_url = '';
    var $thumbnail_directory_path = '';
    var $layout_info    = '';
    var $layout_id      = 2000; // Starting number for our fake member groups.
    
    /**
     * Constructor
     */
    function Blueprints_ext($settings = '') 
    {
        $this->EE =& get_instance();
        $settings = $this->_get_settings();
        
        // $this->debug($settings, true);
        
        // All settings
        $this->global_settings = $settings;
        
        // Site specific settings
        $site_id = $this->EE->config->item('site_id');
        $this->settings = isset($settings[$site_id]) ? $settings[$site_id] : array();

        // Create cache
        if (! isset($this->EE->session->cache[__CLASS__]))
        {
            $this->EE->session->cache[__CLASS__] = array();
        }
        $this->cache =& $this->EE->session->cache[__CLASS__];
        
        $this->thumbnail_directory_url = 'images/template_thumbnails/';
        
        // Really? I would think BASEPATH would be the absolute root of the site, not the base of the EE install?
        // Is there a variable I don't know about to get the EE webroot path?
        $images_path = str_replace('themes', 'images', PATH_THEMES);
        $this->thumbnail_directory_path = $images_path . DIRECTORY_SEPARATOR . 'template_thumbnails' . DIRECTORY_SEPARATOR;
    }
    
    function sessions_end($sess)
    {
        // If user disabled publish layout takeover, then stop here
        if( ! $this->_enable_publish_layout_takeover())
            return;
            
        if(($this->_is_structure_installed() OR 
            $this->_is_pages_installed()) AND 
            $this->_is_publish_form())
        {
            // Get our basic data
            $channel_id = $this->EE->input->get_post('channel_id');
            $entry_id = $this->EE->input->get_post('entry_id');
            $site_assets = false;
            
            // If Structure is installed, get it's data
            if($this->_is_structure_installed())
            {
                require_once(PATH_THIRD.'structure/mod.structure.php');
                $structure = new Structure();

                $structure_settings = $structure->get_structure_channels();
                $site_pages = $structure->get_site_pages();
            }
            // Get Pages data
            elseif($this->_is_pages_installed())
            {   
                $site_pages = $this->EE->config->item('site_pages');
            }
            
            // Get previously set data for either Structure or Pages set to the requested entry_id
            if ($entry_id && isset($site_pages['uris'][$entry_id]))
            {
                $template_id = $site_pages['templates'][$entry_id];
            }
            // Get default Structure settings
            elseif($this->_is_structure_installed())
            {
                $template_id = $structure_settings[$channel_id]['template_id'];
            }
            // Get default Pages settings
            elseif($this->_is_pages_installed())
            {
                $query = $this->EE->db->get_where('pages_configuration', array('configuration_name' => 'template_channel_'. $channel_id), 1, 0);
                $template_id = $query->row('configuration_value');
            }
            
            /*
            This is where the magic happens, and what makes this extension possible. 
            In content_publish, there are the following 2 lines. Luckily EllisLab
            used get_post('layout_preview'), so we can inject a new value.

            $layout_group = (is_numeric($this->input->get_post('layout_preview'))) ? $this->input->get_post('layout_preview') : $this->session->userdata('group_id');
            $layout_info  = $this->member_model->get_group_layout($layout_group, $channel_id);

            What we're doing here is assigning a Structure template to a publish layout, and 
            injecting the value into $_GET['layout_preview'], so when those 2 previous lines are called, 
            it thinks we're previewing a publish layout on every page load. Genius.
            */

            // If this is a new entry, find out what template is assigned to which layout_group from our settings.
            if(!$entry_id)
            {
                $layout_group = $this->_find_layout_group($template_id, $channel_id);
            }
            // If this is an existing entry, then the template/layout_group has already been saved to our settings.
            else
            {
                
                if(isset($this->settings['template_layout'][$entry_id]['layout_group_id']))
                {
                    $layout_group = $this->settings['template_layout'][$entry_id]['layout_group_id'];
                }
                else
                {
                    $layout_group = $this->_find_layout_group($template_id, $channel_id);
                }
            }

            // And hi-jack it if we have a custom layout_group
            if($layout_group)
            {
                $_GET['layout_preview'] = isset($_GET['layout_preview']) ? $_GET['layout_preview'] : $layout_group;
            }
            
            /*
            $publish_layout = $this->EE->db->select('field_layout')
                                           ->where('member_group', $layout_group)
                                           ->where('channel_id', $channel_id)
                                           ->where('site_id', $this->EE->config->item('site_id'))
                                           ->get('layout_publish')
                                           ->row('field_layout');
            
            $publish_layout = unserialize($publish_layout);
            
            $show_fields = array();
            $hide_fields = array();
            
            foreach($publish_layout as $tab => $fields)
            {
                foreach($fields as $name => $settings)
                {
                    if(!$settings['visible'])
                    {
                        $hide_fields[$name] = $name;
                    }
                    elseif($settings['visible'])
                    {
                        $show_fields[$name] = array(
                            'collapse' => $settings['collapse'],
                            'width' => $settings['width']
                        );
                    }
                }
            }
            */
            // $this->debug($show_fields);
        }
    }
    
    private function _find_layout_group($template_id, $channel_id)
    {
        $layout_group = false;

        foreach($this->settings['template'] as $key => $setting_template_id)
        {
            if($template_id == $setting_template_id AND isset($this->settings['layout_group_ids'][$key]))
            {
                $layout_group = $this->settings['layout_group_ids'][$key];
                break;
            }
        }

        return $layout_group;
    }
    
    function entry_submission_absolute_end($entry_id, $meta, $data)
    {
        $post_template_id = false;
        
        // Save our settings to the current site ID for MSM.
        $site_id = $this->EE->config->item('site_id');
        $settings = $this->global_settings;
        
        // Look for a Structure template ID first, then default to the Pages module
        if($this->EE->input->post('structure__template_id'))
        {
            $post_template_id = $this->EE->input->post('structure__template_id');
        }
        else
        {
            $post_template_id = $this->EE->input->post('pages__pages_template_id');
        }
        
        if($post_template_id AND $this->EE->input->post('layout_preview'))
        {
            $settings[$site_id]['template_layout'][$entry_id] = array('template_id' => $post_template_id, 'layout_group_id' => $this->EE->input->post('layout_preview'));
        
            $this->EE->db->where('class', strtolower(__CLASS__));
            $this->EE->db->update('extensions', array('settings' => serialize($settings)));
        }
    }
    
    function publish_form_channel_preferences($data)
    {
        // This is kind of lame, but all the good hooks have been removed from 2.0. This is called at the 
        // beginning of a publish form load, so we'll use it to add JS to the footer.
        if($this->_is_publish_form())
        {
            $templates = array();
            $thumbnails = array();
            $thumbnail_options = array();
            $layout_groups = array();
            $layout_group_names = array();
            $channel_templates = array();
            $channel_id = $this->EE->input->get_post('channel_id');
            $thumbnail_path = isset($this->settings['thumbnail_path']) ? $this->settings['thumbnail_path'] : $this->thumbnail_directory_path;
            
            // Lets get our active layouts into a JavaScrip array to use with jQuery below
            $active_publish_layout = $this->_get_active_publish_layout($channel_id);
            $active_publish_layout_array = array();
            
            foreach($active_publish_layout as $id => $name)
            {
                $active_publish_layout_array[] = '"'. $id .'"';
            }
            $active_publish_layouts = 'new Array('. trim(implode(',', $active_publish_layout_array), ',') .')';

            
            if(isset($this->settings['template']) AND $this->settings['template'] != '')
            {
                foreach($this->settings['template'] as $k => $template)
                {
                    $thumbnail = isset($this->settings['thumbnails'][$k]) ? $this->settings['thumbnails'][$k] : '[NO THUMBNAIL DEFINED]';
                    $thumbnails[] = '"'. $template .'":"'. $thumbnail .'"';
                    $thumbnail_options[$template] = $thumbnail; 
                    
                    // Get our group names
                    $layout_group_name = isset($this->settings['layout_group_names'][$k]) ? $this->settings['layout_group_names'][$k] : '[NO LAYOUT GROUP DEFINED]';
                    // And get the ID
                    $layout_group_id = isset($this->settings['layout_group_ids'][$k]) ? $this->settings['layout_group_ids'][$k] : 0;
                    
                    $layout_groups[] = '"'. $template .'":"'. $layout_group_id .'"';
                    $layout_group_names[] = $layout_group_name;
                }
            }
            
            if(isset($this->settings['channels']))
            {
                foreach($this->settings['channels'] as $k => $channel)
                {
                    if($channel == $channel_id)
                    {
                        if(isset($this->settings['channel_show_group'][$k]))
                        {
                            $groups = array();
                            foreach($this->settings['channel_show_group'][$k] as $group)
                            {
                                $groups[] = "'". $group ."'";
                            }
                        
                            $templates_result = $this->_get_templates(implode(',', $groups));
                        
                            $channel_templates = array();
                            foreach($templates_result->result_array() as $row)
                            {
                                $channel_templates[] = $row['template_id'];
                            }
                        }
                        else
                        {
                            $channel_templates = isset($this->settings['channel_templates'][$k]) ? $this->settings['channel_templates'][$k] : '';
                        }
                    }
                }
            }
            
            if(is_array($channel_templates) AND count($channel_templates) == 1)
            {
                // Reset the index and grab first value
                sort($channel_templates);
                $channel_templates_options = $channel_templates[0];
            }
            elseif(is_array($channel_templates))
            {
                $channel_templates_options = 'new Array('. trim(implode(',', $channel_templates), ',') .')';
            }
            else
            {
                $channel_templates_options = '';
            }
            
            // If the current user is an Admin, give them a link to edit the templates that appear
            $edit_templates_link = '';
            if($this->EE->session->userdata['group_id'] == 1)
            {
                $edit_templates_link = '<br /><small style=\"display: inline-block; margin-top: 5px; color: rgba(0,0,0, 0.5);\"><a href=\"'. BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=blueprints\">Edit Available Templates</a></small>';
            }
            
            // Add our custom layout group options to the list, with "member group ids" starting at 2000
            $layout_group_options = '';
            if(count($layout_group_names) > 0)
            {
                foreach($layout_group_names as $k => $name)
                {
                    $value = (int) $this->layout_id + $k;
                    $layout_group_options .= '<label><input type=\"checkbox\" name=\"member_group[]\" value=\"'. $value .'\" class=\"toggle_member_groups\" /> '. $name .'</label><br />';
                }
            
                $layout_group_options .= '<div style=\"height: 1px; margin-bottom: 7px; border-bottom: 1px solid rgba(0,0,0,0.1);\">&nbsp;</div>';
            }
            
            $carousel_templates = $this->_get_assigned_templates($channel_templates);
            $carousel_options = array();

            foreach($carousel_templates as $template)
            {
                $carousel_options[] = array(
                    'template_id' => $template['template_id'], 
                    'template_name' => $template['template_name'], 
                    'template_thumb' => isset($thumbnail_options[$template['template_id']]) ? $thumbnail_options[$template['template_id']] : ''
                ); 
            }

            $blueprints_options = '
            var blueprints_options = {
                carousel_options: '. $this->EE->javascript->generate_json($carousel_options, TRUE) .',
                thumbnail_options: '. $this->EE->javascript->generate_json($thumbnail_options, TRUE) .',
                thumbnails: {'. implode(',', $thumbnails) .'},
                layout_groups: {'. implode(',', $layout_groups) .'},
                layout_group_options: "'. $layout_group_options .'",
                active_publish_layouts: '. $active_publish_layouts .',
                channel_templates: '. $channel_templates_options .',
                edit_templates_link: "'. $edit_templates_link .'",
                publish_layout_takeover: '. $this->_enable_publish_layout_takeover() .',
                thumbnail_path: "'. $this->EE->config->slash_item('site_url') . $thumbnail_path .'"
            };';
            
            $this->EE->cp->add_to_head('<!-- BEGIN Blueprints assets --><script type="text/javascript">'. $blueprints_options .'</script><!-- END Blueprints assets -->');
            $this->EE->cp->add_to_head('<!-- BEGIN Blueprints assets --><link type="text/css" href="'. $this->_get_theme_folder_url() .'blueprints/styles/blueprints.css" rel="stylesheet" /><!-- END Blueprints assets -->');
            $this->EE->cp->add_to_foot('<!-- BEGIN Blueprints assets --><script type="text/javascript" src="'. $this->_get_theme_folder_url() .'blueprints/scripts/jquery.jcarousel.min.js"></script><!-- END Blueprints assets -->');
            $this->EE->cp->add_to_foot('<!-- BEGIN Blueprints assets --><script type="text/javascript" src="'. $this->_get_theme_folder_url() .'blueprints/scripts/blueprints.js"></script><!-- END Blueprints assets -->');
        }
        
        return $data;
    }
    
    function settings_form($vars)
    {
        $this->EE->lang->loadfile('blueprints');
        $this->EE->load->library('javascript');

        // Set vars
        $fields = array();
        $channels = array();
        $template_options = array();
        $template_channel_options = array();
        $channel_fields = array();
        
        // Get our data
        $templates = $this->_get_templates();
        $thumbnails = $this->_get_thumbnails();
        $channels = $this->_get_channels();
        
        // $vars sent from core are basically the settings, 
        // but to make it MSM compat, we need to grab our settings instead.
        $vars = $this->settings;

        // Get the actual field values (if they exist)
        if($templates->num_rows() == 0)
        {
            $template_options = array('' => 'No templates found');
        }
        else
        {
            $template_options[''] = '-- Select --';
            foreach($templates->result_array() as $row) {
                $file = $row['group_name'] .'/'. $row['template_name'];
                $template_options[$row['template_id']] = $file;
            }
        }
        
        if(count($thumbnails) == 0)
        {
            $thumbnail_options = array('' => 'No thumbnails found');
        }
        else
        {
            $thumbnail_options[''] = '-- Select --';
            foreach($thumbnails as $k => $thumb) {
                $thumbnail_options[$thumb['file_name']] = $thumb['file_name'];
            }
        }
        
        if(count($channels) == 0)
        {
            $channel_options = array('' => 'No channels found');
        }
        else
        {
            $channel_options[''] = '-- Select --';
            foreach($channels->result_array() as $row) {
                $channel_options[$row['channel_id']] = $row['channel_title'];
            }
        }

        if($this->settings)
        {
            foreach($this->settings as $key => $value)
            {
                // Only if settings are saved, and layouts are created the first save
                if($key == 'template' AND count($value) > 0)
                {
                    foreach($value as $k => $row)
                    {
                        // Create fields
                        $fields[] = array(
                            'tmpl_name' => 'template['. $k .']',
                            'tmpl_options' => $template_options,
                            'tmpl_options_selected' => isset($vars['template'][$k]) ? $vars['template'][$k] : '',
                            
                            'thb_name' => 'thumbnails['. $k .']',
                            'thb_options' => $thumbnail_options,
                            'thb_options_selected' => isset($vars['thumbnails'][$k]) ? $vars['thumbnails'][$k] : '',
                            
                            'layout_group_id' => 'layout_group_ids['. $k .']',
                            'layout_group_id_value' => isset($vars['layout_group_ids'][$k]) ? $vars['layout_group_ids'][$k] : (int) $this->layout_id + $k,
                            'layout_group_name' => 'layout_group_names['. $k .']',
                            'layout_group_name_value' => isset($vars['layout_group_names'][$k]) ? $vars['layout_group_names'][$k] : ''
                        );
                    }
                }
                // This can happen if settings are saved, but not layouts are created
                elseif($key == 'template')
                {
                    $k = '0';
                    $fields[] = array(
                        'tmpl_name' => 'template['. $k .']',
                        'tmpl_options' => $template_options,
                        'tmpl_options_selected' => isset($vars['template'][$k]) ? $vars['template'][$k] : '',
                        
                        'thb_name' => 'thumbnails['. $k .']',
                        'thb_options' => $thumbnail_options,
                        'thb_options_selected' => isset($vars['thumbnails'][$k]) ? $vars['thumbnails'][$k] : '',

                        'layout_group_id' => 'layout_group_ids['. $k .']',
                        'layout_group_id_value' => isset($vars['layout_group_ids'][$k]) ? $vars['layout_group_ids'][$k] : $this->layout_id,
                        'layout_group_name' => 'layout_group_names['. $k .']',
                        'layout_group_name_value' => isset($vars['layout_group_names'][$k]) ? $vars['layout_group_names'][$k] : ''
                    );
                }
                
                if($key == 'channels')
                {
                    foreach($value as $k => $row)
                    {
                        $channel_fields[] = array(
                            'channel_name' => 'channels['. $k .']',
                            'channel_options' => $channel_options,
                            'channel_options_selected' => isset($vars['channels'][$k]) ? $vars['channels'][$k] : '',
                            
                            'channel_templates_name' => 'channel_templates['. $k .'][]',
                            'channel_templates_options' => $template_options,
                            'channel_templates_options_selected' => isset($vars['channel_templates'][$k]) ? $vars['channel_templates'][$k] : '',
                            
                            'channel_checkbox_options' => $this->_get_checkbox_options($k)
                        );
                    }
                }
            }
        } 
        else
        {
            $k = '0';
            $fields[] = array(
                'tmpl_name' => 'template['. $k .']',
                'tmpl_options' => $template_options,
                'tmpl_options_selected' => (isset($vars['template']) AND isset($vars['template'][$k])) ? $vars['template'][$k] : '',

                'thb_name' => 'thumbnails['. $k .']',
                'thb_options' => $thumbnail_options,
                'thb_options_selected' => (isset($vars['thumbnails']) AND isset($vars['thumbnails'][$k])) ? $vars['thumbnails'][$k] : '',
                
                'layout_group_id' => 'layout_group_ids['. $k .']',
                'layout_group_id_value' => (isset($vars['layout_group_ids']) AND isset($vars['layout_group_ids'][$k])) ? $vars['layout_group_ids'][$k] : $this->layout_id,
                'layout_group_name' => 'layout_group_names['. $k .']',
                'layout_group_name_value' => (isset($vars['layout_group_names']) AND isset($vars['layout_group_names'][$k])) ? $vars['layout_group_names'][$k] : ''
            );
            
            $channel_fields[] = array(
                'channel_name' => 'channels['. $k .']',
                'channel_options' => $channel_options,
                'channel_options_selected' => (isset($vars['channels']) AND isset($vars['channels'][$k])) ? $vars['channels'][$k] : '',
                
                'channel_templates_name' => 'channel_templates['. $k .'][]',
                'channel_templates_options' => $template_options,
                'channel_templates_options_selected' => (isset($vars['channel_templates']) AND isset($vars['channel_templates'][$k])) ? $vars['channel_templates'][$k] : '',
                
                'channel_checkbox_options' => $this->_get_checkbox_options($k)
            );
        }

        $vars['enable_publish_layout_takeover'] = isset($this->settings['enable_publish_layout_takeover']) ? $this->settings['enable_publish_layout_takeover'] : 'n';
        $vars['enable_edit_menu_tweaks'] = isset($this->settings['enable_edit_menu_tweaks']) ? $this->settings['enable_edit_menu_tweaks'] : 'n';
        $vars['thumbnail_path'] = isset($this->settings['thumbnail_path']) ? $this->settings['thumbnail_path'] : $this->thumbnail_directory_url;
        $vars['site_path'] = $this->site_path();
        $vars['hidden'] = array('file' => 'blueprints');
        $vars['structure_installed'] = $this->_is_structure_installed();
        $vars['pages_installed'] = $this->_is_pages_installed();
        
        $vars = array_merge($vars, array('fields' => $fields, 'channels' => $channel_fields));

        // Load it up and return it to addons_extensions.php for rendering
        return $this->EE->load->view('settings_form', $vars, TRUE);
    }
    
    function save_settings()
    {
        $channels = $this->EE->input->post('channels');
        $channel_show_selected = $this->EE->input->post('channel_show_selected');
        $channel_templates = $this->EE->input->post('channel_templates');
        $channel_show_group = $this->EE->input->post('channel_show_group');
        $delete = $this->EE->input->post('delete', false);
        
        $insert['enable_publish_layout_takeover'] = $this->EE->input->post('enable_publish_layout_takeover');
        $insert['enable_edit_menu_tweaks'] = $this->EE->input->post('enable_edit_menu_tweaks');
        $insert['enable_template_multi_channel'] = $this->EE->input->post('enable_template_multi_channel');
        $insert['template'] = $this->EE->input->post('template');
        $insert['thumbnails'] = $this->EE->input->post('thumbnails');
        $insert['layout_group_ids'] = $this->EE->input->post('layout_group_ids');
        $insert['layout_group_names'] = $this->EE->input->post('layout_group_names');
        $insert['thumbnail_path'] = $this->EE->input->post('thumbnail_path');
        
        // If no name is given, but the row exists, unset everything for that row so it isn't saved
        if(isset($insert['layout_group_names']) AND $insert['layout_group_names'] != '')
        {
            foreach($insert['layout_group_names'] as $k => $value)
            {
                if($value == "")
                {
                    unset($insert['layout_group_names'][$k]);
                    unset($insert['thumbnails'][$k]);
                    unset($insert['template'][$k]);
                    unset($insert['layout_group_ids'][$k]);
                }
            }
        }
        
        // Figure out what templates to show for each channel
        foreach($channels as $k => $channel_id)
        {
            if(count($channels) > 0)
            {
                $insert['channels'][$k] = $channel_id;
            }
            
            if(isset($channel_show_group[$k]))
            {
                $insert['channel_show_group'][$k] = $channel_show_group[$k];
            }
            elseif(isset($channel_show_selected[$k]) AND $channel_show_selected[$k] == 'y' AND $channel_templates)
            {
                $insert['channel_show_selected'][$k] = $channel_show_selected[$k];
                $insert['channel_templates'][$k] = $channel_templates[$k];
            }
        }
        
        if($delete)
        {
            // Remove from all existing entries
            foreach($this->settings['template_layout'] as $entry_id => $data)
            {
                if(isset($data['layout_group_id']) AND in_array($data['layout_group_id'], $delete))
                {
                    unset($this->settings['template_layout'][$entry_id]);
                }
            }
            
            // Remove layout from the DB
            $this->EE->db->where_in('member_group', $delete);
            $this->EE->db->delete('layout_publish');
        }

        // Settings page will want to delete this key, lets make sure it hangs around, it is kind of important
        // Our settings form does not actually update this data, it is set when saving an entry
        $insert['template_layout'] = isset($this->settings['template_layout']) ? $this->settings['template_layout'] : array();

        // Save our settings to the current site ID for MSM.
        $site_id = $this->EE->config->item('site_id');
        $settings = $this->global_settings;
        $settings[$site_id] = $insert;

        $this->EE->db->where('class', strtolower(__CLASS__));
        $this->EE->db->update('extensions', array('settings' => serialize($settings)));
        
        $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('preferences_updated'));
    }
    
    /**
     * Install the extension
     */
    function activate_extension()
    {
        // Delete old hooks
        $this->EE->db->query("DELETE FROM exp_extensions WHERE class = '". __CLASS__ ."'");
        
        // Add new hooks
        $ext_template = array(
            'class'    => __CLASS__,
            'settings' => '',
            'priority' => 8,
            'version'  => $this->version,
            'enabled'  => 'y'
        );
        
        $extensions = array(
            array('hook'=>'publish_form_channel_preferences', 'method'=>'publish_form_channel_preferences'),
            array('hook'=>'sessions_end', 'method'=>'sessions_end'),
            array('hook'=>'entry_submission_absolute_end', 'method'=>'entry_submission_absolute_end')
        );
        
        foreach($extensions as $extension)
        {
            $ext = array_merge($ext_template, $extension);
            $this->EE->db->insert('exp_extensions', $ext);
        }       
    }

    /**
     * @param string $current currently installed version
     */
    function update_extension($current = '') 
    {
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
        
        // Update version #
        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->update('exp_extensions', array('version' => $this->version));
    }

    /**
     * Uninstalls extension
     */
    function disable_extension() 
    {
        // Delete records
        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->delete('exp_extensions');
        
        // Remove layout from the DB
        $this->EE->db->where('member_group', '>= '. $this->layout_id);
        $this->EE->db->delete('layout_publish');
    }
    
    
    
    
    
    
    
    /**
     * PRIVATE METHODS
     */
    
    
    private function _get_checkbox_options($k)
    {
        $templates = $this->_get_templates();
        
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
    
    function _is_structure_installed()
    {        
        if(!isset($this->cache['structure_installed']))
        {
            $sql = "SELECT * FROM exp_modules WHERE module_name = 'Structure'";
            $result = $this->EE->db->query($sql);
            $this->cache['structure_installed'] = ($result->num_rows() == 1) ? true : false;
        }
        
        return $this->cache['structure_installed'];
    }
    
    function _is_pages_installed()
    {        
        if(!isset($this->cache['pages_installed']))
        {
            $sql = "SELECT * FROM exp_modules WHERE module_name = 'Pages'";
            $result = $this->EE->db->query($sql);
            $this->cache['pages_installed'] = ($result->num_rows() == 1) ? true : false;
        }

        return $this->cache['pages_installed'];
    }
    
    function _is_taxonomy_installed()
    {        
        if(!isset($this->cache['taxonomy_installed']))
        {
            $sql = "SELECT * FROM exp_modules WHERE module_name = 'Taxonomy'";
            $result = $this->EE->db->query($sql);
            $this->cache['taxonomy_installed'] = ($result->num_rows() == 1) ? true : false;
        }
        
        return $this->cache['taxonomy_installed'];
    }
    
    function _get_pages()
    {
        // Make sure pages cache is empty, and also see if we are in the CP. Since fieldtype files get loaded
        // on the front end, I don't want unecessary queries/processing to be done when not needed.
        if(!isset($this->cache['pages']) AND REQ == 'CP')
        {
            $this->cache['pages'] = "";
            
            if($this->_is_structure_installed())
            {
                require_once $this->_get_theme_folder_path().'boldminded_themes/libraries/structure_pages.php';
                $pages = Structure_Pages::get_instance();
                $this->cache['pages'] = $pages->get_pages($this->EE);
            }
            elseif($this->_is_pages_installed())
            {
                require_once $this->_get_theme_folder_path().'boldminded_themes/libraries/pages.php';
                $pages = Pages::get_instance();
                $this->cache['pages'] = $pages->get_pages($this->EE);
            }
        }

        return $this->cache['pages'];
    }
    
    private function _get_theme_folder_path()
    {
        return $this->EE->config->slash_item('theme_folder_path') .'third_party/';
    }
    
    protected function _get_theme_folder_url()
    {
        return $this->EE->config->slash_item('theme_folder_url') .'third_party/';
    }
    
    private function _get_active_publish_layout($channel_id = false)
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
                    $key = array_search($row['member_group'], $this->settings['layout_group_ids']);
                    $this->cache['active_publish_layout'][$row['member_group']] = $this->settings['layout_group_names'][$key];
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
    
    private function _get_layouts()
    {
        if(!isset($this->cache['layouts']))
        {
            // Get the current Site ID
            $site_id = $this->EE->config->item('site_id');

            $sql = "SELECT lp.member_group, lp.layout_id, m.group_id, m.group_title, lp.field_layout 
                    FROM exp_layout_publish lp, exp_member_groups m 
                    WHERE lp.member_group = m.group_id
                    AND lp.site_id = '".$site_id."'";

            $this->cache['layouts'] = $this->EE->db->query($sql);
        }
        
        return $this->cache['layouts'];
    }
    
    private function _get_assigned_templates($ids)
    {
        if(!isset($this->cache['assigned_templates']))
        {
            // Get the current Site ID
            $site_id = $this->EE->config->item('site_id');
            
            if($ids)
            {
                $query = $this->EE->db->select('template_groups.group_name, templates.template_name, templates.template_id')
                                      ->where_in('templates.template_id', $ids)
                                      ->where('template_groups.site_id', $site_id)
                                      ->order_by('template_groups.group_name, templates.template_name')
                                      ->join('template_groups', 'template_groups.group_id = templates.group_id')
                                      ->get('templates');

                $this->cache['assigned_templates'] = $query->result_array();
            }
            else
            {
                $this->cache['assigned_templates'] = array();
            }
        }
        
        return $this->cache['assigned_templates'];
    }
    
    private function _get_templates($groups = false)
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
    
    private function _get_thumbnails()
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
    
    private function _get_channels()
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
    
    private function _enable_publish_layout_takeover()
    {
        if(!isset($this->cache['enable_publish_layout_takeover']))
        {
            $this->cache['enable_publish_layout_takeover'] = (isset($this->settings['enable_publish_layout_takeover']) AND $this->settings['enable_publish_layout_takeover'] == 'y') ? true : false;
        }
        
        return $this->cache['enable_publish_layout_takeover'];
    }
    
    private function _is_publish_form()
    {
        if($this->EE->input->get('C') == 'content_publish' AND $this->EE->input->get('M') == 'entry_form')
        {
            return true;
        }
        else
        {
            return false;
        }
    }
        
    /**
    * Get the site specific settings from the extensions table
    * Originally written by Leevi Graham? Modified for EE2.0
    *
    * @param $force_refresh     bool    Get the settings from the DB even if they are in the session
    * @return array                     If settings are found otherwise false. Site settings are returned by default.
    */
    private function _get_settings($force_refresh = FALSE)
    {
        // assume there are no settings
        $settings = FALSE;
        $this->EE->load->helper('string');

        // Get the settings for the extension
        if(isset($this->cache['settings']) === FALSE || $force_refresh === TRUE)
        {
            // check the db for extension settings
            $query = $this->EE->db->query("SELECT settings FROM exp_extensions WHERE enabled = 'y' AND class = '" . __CLASS__ . "' LIMIT 1");

            // if there is a row and the row has settings
            if ($query->num_rows() > 0 && $query->row('settings') != '')
            {
                // save them to the cache
                $this->cache['settings'] = strip_slashes(unserialize($query->row('settings')));
            }
        }

        // check to see if the session has been set
        // if it has return the session
        // if not return false
        if(empty($this->cache['settings']) !== TRUE)
        {
            $settings = $this->cache['settings'];
        }

        return $settings;
    }
    
    /**
      * Retrieve site path
      */
    private function site_path()
    {
        $site_url = $this->EE->config->slash_item('site_path');
        return $site_url ? $site_url : str_replace('themes/', '', PATH_THEMES);
    }
    
    private function debug($str, $die = false)
    {
        echo '<pre>';
        var_dump($str);
        echo '</pre>';
        
        if($die) die('debug terminated');
    }
}