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
    
    /*
        Based on template_id and channel_id try to figure out which 
        layout group to use for the entry.
    */
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
        
        // Uncomment to disable layout cloning.
        // return false;
        
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
                $field_display = array(
                    'visible'       => TRUE,
                    'collapse'      => FALSE, 
                    'html_buttons'  => TRUE,
                    'is_hidden'     => FALSE,
                    'width'         => '100%'
                );
                
                $data = array(
                    'member_group'  => $group_id, 
                    'channel_id'    => $row->channel_id
                );
                
                $qry = $this->EE->db->get_where('layout_publish', $data);

                $insert = array_merge($data, array(
                    'field_layout'  => $qry->row('field_layout'),
                    'site_id'       => $qry->row('site_id'),
                    'channel_id'    => $channel_id
                ));
                
                // See if it's a Structure listing channel first.
                if(array_key_exists('structure', $this->EE->addons->get_installed()))
                {
                    $settings = $this->get_structure_settings();
                    $channel_data = $settings[$channel_id];
                    
                    // If it's unmanaged or a listing, we need to clean up the field_layout.
                    if($channel_data['type'] != 'page')
                    {
                        $field_layout = unserialize($insert['field_layout']);
                        
                        // Remove Structure tab entirely
                        if($channel_data['type'] == 'unmanaged')
                        {
                            unset($field_layout['structure']);
                        }
                        // Just remove a few of fields
                        elseif($channel_data['type'] == 'listing')
                        {
                            unset($field_layout['structure']['structure__listing_channel']);
                            unset($field_layout['structure']['structure__parent_id']);
                            unset($field_layout['structure']['structure__hidden']);
                        }
                        
                        $insert['field_layout'] = serialize($field_layout);
                    }
                }
                
                // Going to do a few channel specific things here so the publish layouts
                // don't blow up and throw a bunch of PHP errors.
                
                // Get channel settings
                $qry = $this->EE->db->get_where('channels', array('channel_id' => $channel_id));
                
                $field_layout = unserialize($insert['field_layout']);
                
                // See if Revisions are enabled for the channel, if not, make sure it's not a set tab
                if($qry->row('enable_versioning') == 'n')
                {
                    unset($field_layout['revisions']);
                }
                
                // Remove the comment exp date field if it's in the layout and commenting is off
                if($qry->row('comment_system_enabled') == 'n')
                {
                    unset($field_layout['date']['comment_expiration_date']);
                }
                // If commenting is on, give the field the default display
                else
                {
                    $field_layout['date']['comment_expiration_date'] = $field_display;
                }
                
                $insert['field_layout'] = serialize($field_layout);
                
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

        return $qry->num_rows() == 0 ? '' : unserialize($qry->row('entry_data'));
    }
    
    public function get_active_publish_layout($channel_id = false)
    {
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
                                ->where('lp.site_id', $this->site_id)
                                ->get();
            
            if($qry->num_rows() > 0)
            {
                foreach($qry->result() as $row)
                {
                    $this->cache['active_publish_layout'][$row->group_id] = $row->group_title;
                }
            }
            
            $qry = $this->EE->db->select('member_group, layout_id, field_layout')
                                ->where('channel_id', $channel_id)
                                ->where('site_id', $this->site_id)
                                ->where('member_group', $layout_preview)
                                ->get('layout_publish');
            
            if($qry->num_rows() > 0)
            {
                foreach($qry->result() as $row)
                {
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
                                ->where('lp.site_id', $this->site_id)
                                ->get();
            
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
            $this->EE->db->select('template_groups.group_name, templates.template_name, templates.template_id')
                         ->where('template_groups.site_id', $this->site_id)
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
            $groups = $groups ? " AND tg.group_name IN (". $groups .")" : '';

            $sql = "SELECT tg.group_name, t.template_name, t.template_id
                    FROM exp_template_groups tg, exp_templates t
                    WHERE tg.group_id = t.group_id
                    AND tg.site_id = '".$this->site_id."'". $groups ."
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
            $qry = $this->EE->db->select('channel_id, channel_name, channel_title')
                                ->where('site_id', $this->site_id)
                                ->get('channels');
            
            $channels = array();
                
            foreach($qry->result_array() as $row)
            {
                $channels[$row['channel_id']] = $row;
            }

            $this->cache['channels'] = $channels;
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
                $qry = $this->EE->db->where(array('site_id' => $this->EE->config->item('site_id')))
                                    ->order_by('order', 'asc')
                                    ->get('blueprints_layouts');

                $layouts = array();

                foreach($qry->result_array() as $row)
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
            // Get Structure Channel Data
            $sql = "SELECT ec.channel_id, ec.channel_title, esc.template_id, esc.type, ec.site_id
                    FROM exp_channels AS ec 
                    LEFT JOIN exp_structure_channels AS esc ON ec.channel_id = esc.channel_id
                    WHERE ec.site_id = '$this->site_id'";

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
    
    public function update_field_settings($action, $session = false)
    {
        if(!$session)
        {
            $session = $this->EE->session;
        }
        
        $channel_id = $this->EE->input->get_post('channel_id', TRUE);
        $entry_id = $this->EE->input->get_post('entry_id', TRUE);
        
        // Get the field group assigned to the channel.
        $qry = $this->EE->db->select('field_group')
                            ->where('channel_id', $channel_id)
                            ->get('channels');
        
        $field_group_id = $qry->row('field_group');
        
        // If it's unset, then we're saving the required fields data before unsetting them in the db
        // so we can restore them after the autosave refresh page load.
        if($action == 'unset')
        {
            $qry = $this->EE->db->select('cf.field_id, cf.field_required')
                                ->from('channel_fields AS cf')
                                ->join('channels AS c', 'cf.group_id = c.field_group')
                                ->where('cf.site_id', $this->site_id)
                                ->where('group_id', $field_group_id)
                                ->get();

            $data = array(
                'site_id'       => $this->site_id,
                'member_id'     => $session->userdata['member_id'],
                'session_id'    => $session->userdata['session_id'],
                'channel_id'    => $channel_id,
                'entry_id'      => $entry_id,
                'timestamp'     => $this->EE->localize->now,
                'group_id'      => $field_group_id,
                'settings'      => serialize($qry->result_array())
            );

            $where = array(
                'site_id'       => $this->site_id,
                'member_id'     => $session->userdata['member_id'],
                'session_id'    => $session->userdata['session_id'],
                'channel_id'    => $channel_id,
                'entry_id'      => $entry_id
            );

            $this->EE->blueprints_model->insert_or_update('blueprints_field_settings', $data, $where);

            $data = array(
                'field_required' => 'n'
            );

            $where = array(
                'site_id'   => $this->site_id,
                'group_id'  => $field_group_id
            );

            $result = $this->EE->db->where($where)->update('channel_fields', $data);
        }
        elseif($action == 'set')
        {
            $errors = array();
            
            // Get our real entry ID. What we get in POST is the autosave entry_id
            $qry = $this->EE->db->select('original_entry_id')
                                ->where('entry_id', $entry_id)
                                ->get('channel_entries_autosave');
                                
            if($qry->num_rows())
            {
                $entry_id = $qry->row('original_entry_id');
            }
            else
            {
                $errors[] = 1;
            }
            
            $where = array(
                'site_id'       => $this->site_id,
                'member_id'     => $session->userdata['member_id'],
                'session_id'    => $session->userdata['session_id'],
                'channel_id'    => $channel_id,
                'entry_id'      => $entry_id,
            );
            
            $qry = $this->EE->db->select('settings')
                                ->where($where)
                                ->get('blueprints_field_settings');

            if($qry->num_rows())
            {
                $settings = unserialize($qry->row('settings'));
                
                foreach($settings as $k => $setting)
                {
                    $result = $this->EE->db->where('field_id', $setting['field_id'])
                                           ->update('channel_fields', array('field_required' => $setting['field_required']));
                                           
                    if(!$result)
                    {
                        $errors[] = $k;
                    }
                }
            }
            
            $result = empty($errors) ? true : false;
            
            if($result)
            {
                $this->EE->db->where($where)->delete('blueprints_field_settings');
            }
        }
        
        return $result;
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