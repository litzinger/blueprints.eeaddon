<?php

/**
 * ExpressionEngine Blueprints Helper Class
 *
 * @package     ExpressionEngine
 * @subpackage  Models
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

class Blueprints_model
{
    private $cache;
    private $thumbnail_directory_url = '';
    private $thumbnail_directory_path = '';
    
    public $settings;
    public $entries;
    public $layouts;
    
    function __construct()
    {
        $this->EE =& get_instance();
        $this->site_id = $this->EE->config->item('site_id');

        // Create cache
        // if (! isset($this->EE->session->cache['blueprints']))
        // {
        //     $this->EE->session->cache['blueprints'] = array();
        // }
        // $this->cache =& $this->EE->session->cache['blueprints'];
        
        // if(!class_exists('Blueprints_helper'))
        // {
        //     require PATH_THIRD . 'blueprints/helper.blueprints.php';
        // }
        // $this->EE->blueprints_helper = new Blueprints_helper;
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
    
    public function find_layout_group_from_settings($template_id, $channel_id)
    {
        // Compare our saved layouts to the existing publish layouts and see if our template matches
        // up to a channel in layout_publish based on group_id, e.g. the saved Publish Layout
        $qry = $this->EE->db->select('bl.group_id')
                            ->from('blueprints_layouts AS bl')
                            ->join('layout_publish AS lp', 'lp.member_group = bl.group_id')
                            ->where('bl.template', $template_id)
                            ->where('lp.channel_id', $channel_id)
                            ->get();
        
        if($qry->row('group_id'))
        {
            return $qry->row('group_id');
        }
        
        // No existing Publish Layout was found? Lets try harder...
        
        // See if this channel shares a field group with another channel that has a publish layout
        $qry = $this->EE->db->select('field_group')
                            ->where('channel_id', $channel_id)
                            ->get('channels');

        // Get the channel_id of other channels that share the same field_group. This way, if multiple
        // channels share the same field group, the layout only needs to be created once.
        $qry = $this->EE->db->select('channel_id')
                            ->where('field_group', $qry->row('field_group'))
                            ->where('channel_id !=', $channel_id)
                            ->get('channels');
                            
        // Loop over the found channels and see if an existing Publish Layout has been used before.
        // We have sibling/similar channel = field_group setups, so clone them.
        foreach($qry->result() as $row)
        {
            // Re-run the original query until we find a match... hopefully. This means multiple
            // channels are using the same field_group and default template.
            $qry = $this->EE->db->select('bl.group_id')
                                ->from('blueprints_layouts AS bl')
                                ->join('layout_publish AS lp', 'lp.member_group = bl.group_id')
                                ->where('bl.template', $template_id)
                                ->where('lp.channel_id', $row->channel_id)
                                ->get();

            // Found a match? Return it and duplicate the Publish Layout row so it actually works.
            // Next time we won't get this far b/c it will find the row above first.
            if($group_id = $qry->row('group_id'))
            {
                $data = array(
                    'member_group'  => $group_id, 
                    'channel_id'    => $row->channel_id
                );
                
                $qry = $this->EE->db->get_where('layout_publish', $data)->result();

                $insert = array_merge($data, array(
                    'field_layout'  => $qry[0]->field_layout,
                    'site_id'       => $qry[0]->site_id,
                    'channel_id'    => $channel_id
                ));
                
                $this->EE->db->insert('layout_publish', $insert);
                
                return $group_id;
            }
        }
        
        // Did everything we could to find a working layout :(
        return false;
    }
    
    public function get_autosave_data($channel_id, $entry_id)
    {
        $qry = $this->EE->db->select('entry_data')
                            ->where('entry_id', $entry_id)
                            ->where('channel_id', $channel_id)
                            ->get('channel_entries_autosave');
                            
        return unserialize($qry->row('entry_data'));
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
            $qry = $this->EE->db->select('lp.member_group, lp.layout_id, m.group_id, m.group_title, lp.field_layout')
                                ->from('layout_publish AS lp')
                                ->join('member_groups AS m', 'lp.member_group = m.group_id')
                                ->where('lp.channel_id', $channel_id)
                                ->where('lp.site_id', $site_id)
                                ->get();
            
            // $sql = "SELECT lp.member_group, lp.layout_id, m.group_id, m.group_title, lp.field_layout 
            //     FROM exp_layout_publish lp, exp_member_groups m 
            //     WHERE lp.member_group = m.group_id
            //     AND lp.channel_id = '". $channel_id ."'
            //     AND lp.site_id = '".$site_id."'";

            // $result = $this->EE->db->query($sql);
            
            if($qry->num_rows() > 0)
            {
                foreach($qry->result() as $row)
                {
                    $this->cache['active_publish_layout'][$row->group_id] = $row->group_title;
                }
            }
            
            $qry = $this->EE->db->select('member_group, layout_id, field_layout')
                                ->where('channel_id', $channel_id)
                                ->where('site_id', $site_id)
                                ->where('member_group', $layout_preview)
                                ->get('layout_publish');
            
            // Get our fake member groups
            // $sql = "SELECT lp.member_group, lp.layout_id, lp.field_layout 
            //     FROM exp_layout_publish lp
            //     WHERE lp.member_group = '". $layout_preview ."'
            //     AND lp.channel_id = '". $channel_id ."'
            //     AND lp.site_id = '".$site_id."'";

            // $result = $this->EE->db->query($sql);
        
            if($qry->num_rows() > 0)
            {
                foreach($qry->result() as $row)
                {
                    // $key = array_search($row['member_group'], $this->settings['layout_group_ids']);
                    // $this->cache['active_publish_layout'][$row['member_group']] = $this->settings['layout_group_names'][$key];
                    
                    $this->cache['active_publish_layout'][$row->member_group] = $this->layouts[$row->member_group];
                }
            }
        }
        // We have not hi-jacked the layout_preview, so return the default assigned layouts
        else
        {
            $qry = $this->EE->db->select('lp.member_group, lp.layout_id, m.group_id, m.group_title, lp.field_layout')
                                ->from('layout_publish AS lp')
                                ->join('member_groups AS m', 'lp.member_group = m.group_id')
                                ->where('lp.channel_id', $this->EE->input->get_post('channel_id'))
                                ->where('lp.site_id', $site_id)
                                ->get();
            
            // $sql = "SELECT lp.member_group, lp.layout_id, m.group_id, m.group_title, lp.field_layout 
            //     FROM exp_layout_publish lp, exp_member_groups m 
            //     WHERE lp.member_group = m.group_id
            //     AND lp.channel_id = '". $this->EE->input->get_post('channel_id') ."'
            //     AND lp.site_id = '".$site_id."'";
                
            // $result = $this->EE->db->query($sql);
        
            if($qry->num_rows() > 0)
            {
                $group_titles = array();
                foreach($qry->result() as $row)
                {
                    $this->cache['active_publish_layout'][] = $row->group_title;
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
            $path = isset($this->cache['settings']['thumbnail_path']) ? $this->EE->blueprints_helper->site_path() . $this->cache['settings']['thumbnail_path'] : $this->thumbnail_directory_path;

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
    
    /**
    * @param $force_refresh     bool    Get the settings from the DB even if they are in the session
    * @return array                     If settings are found otherwise false. Site settings are returned by default.
    */
    public function get_settings($force_refresh = FALSE)
    {
        $settings = array();
        $this->EE->load->helper('string');
        
        // Get the settings for the extension
        if(!isset($this->cache['settings']) OR empty($this->cache['settings']) OR $force_refresh === TRUE)
        {
            // check the db for extension settings
            $query = $this->EE->db->query("SELECT settings FROM exp_extensions WHERE enabled = 'y' AND class = 'Blueprints_ext' LIMIT 1");

            // if there is a row and the row has settings
            if ($query->num_rows() > 0 && $query->row('settings') != '')
            {
                // save them to the cache
                $settings = strip_slashes(unserialize($query->row('settings')));
            }
            
            if(isset($settings[$this->site_id]))
            {
                $this->cache['settings'][$this->site_id] = $settings[$this->site_id];
            }
            
            // If path and url is set in the user's config file, use them.
            if($this->EE->config->item('blueprints.thumbnail_directory_url') AND $this->EE->config->item('blueprints.thumbnail_directory_path'))
            {
                $this->cache['settings'][$this->site_id]['thumbnail_directory_url'] = $this->EE->config->item('blueprints.thumbnail_directory_url');
                $this->cache['settings'][$this->site_id]['thumbnail_directory_path'] = $this->EE->config->item('blueprints.thumbnail_directory_path');
            }
            else
            {
                $this->cache['settings'][$this->site_id]['thumbnail_directory_url'] = 'images/template_thumbnails/';

                // If the user set the site_path var, use it.
                if($this->EE->config->item('site_path'))
                {
                    $this->cache['settings'][$this->site_id]['thumbnail_directory_path'] = 'images' . DIRECTORY_SEPARATOR . 'template_thumbnails' . DIRECTORY_SEPARATOR;
                }
                // Or fallback and try to find the site root path.
                else
                {
                    // Really? I would think BASEPATH would be the absolute root of the site, not the base of the EE install?
                    // Is there a variable I don't know about to get the EE webroot path?
                    $images_path = str_replace('themes', 'images', PATH_THEMES);
                    $this->cache['settings'][$this->site_id]['thumbnail_directory_path'] = $images_path . 'template_thumbnails' . DIRECTORY_SEPARATOR;
                }
            }
        }
        
        return isset($this->cache['settings'][$this->site_id]) ? $this->cache['settings'][$this->site_id] : array();
    }
    
    private function debug($str, $die = false)
    {
        echo '<pre>';
        var_dump($str);
        echo '</pre>';
        
        if($die) die('debug terminated');
    }
    
}