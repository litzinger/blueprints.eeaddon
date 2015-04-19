<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if (! defined('BLUEPRINTS_VERSION'))
{
    // get the version from config.php
    require PATH_THIRD.'blueprints/config.php';
    define('BLUEPRINTS_VERSION', $config['version']);
    define('BLUEPRINTS_NAME', $config['name']);
    define('BLUEPRINTS_DESC', $config['description']);
    define('BLUEPRINTS_DOCS', $config['docs_url']);
}

if ( ! defined('BLUEPRINTS_DOCS') )
{
    require PATH_THIRD.'blueprints/config.php';
    define('BLUEPRINTS_DOCS', $config['docs_url']);
}

/**
 * ExpressionEngine Extension Class
 *
 * @package     ExpressionEngine
 * @subpackage  Extensions
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

class Blueprints_ext {

    public $settings        = array();
    public $global_settings = array();
    public $name            = BLUEPRINTS_NAME;
    public $version         = BLUEPRINTS_VERSION;
    public $description     = BLUEPRINTS_DESC;
    public $docs_url        = BLUEPRINTS_DOCS;
    public $settings_exist  = 'y';
    public $required_by     = array('module');

    public $layout_info     = '';
    public $layout_id       = 2000; // Starting number for our fake member groups.

    private $site_id;
    private $layouts;
    private $cache;

    /**
     * Constructor
     */
    public function __construct($settings = '')
    {
        if (REQ != 'CP') return;

        $this->EE =& get_instance();

        $this->site_id = $this->EE->config->item('site_id');

        // Create cache
        if (! isset($this->EE->session->cache['blueprints']))
        {
            $this->EE->session->cache['blueprints'] = array();
        }
        $this->cache =& $this->EE->session->cache['blueprints'];

        // Because I don't like how CI helpers work...
        if(!class_exists('Blueprints_helper'))
        {
            require PATH_THIRD . 'blueprints/helper.blueprints.php';
        }
        $this->EE->blueprints_helper = new Blueprints_helper;

        // Load up our model
        $this->EE->load->add_package_path(PATH_THIRD.'blueprints/');
        $this->EE->load->library('addons');
        $this->EE->load->model('blueprints_model');

        // Get our settings
        $this->cache['settings'] = $this->EE->blueprints_model->get_settings(true);
        $this->cache['layouts'] = $this->EE->blueprints_model->get_layouts();
        $this->cache['entries'] = $this->EE->blueprints_model->get_entries();

        // So I don't have to change a bunch of references below
        $this->settings = $this->cache['settings'];
    }

    /*
        Determine what we should set $_GET['layout_preview'] to for hi-jacking.
    */
    public function sessions_end($session)
    {
        // Stop here if we shouldn't be hi-jacking the publish layouts
        if(
            REQ != 'CP' OR
            !$this->EE->blueprints_helper->enable_publish_layout_takeover() OR
            (!array_key_exists('structure', $this->EE->addons->get_installed()) AND !array_key_exists('pages', $this->EE->addons->get_installed())) OR
            !$this->EE->blueprints_helper->is_publish_form()
        ){
            return $session;
        }

        // Clean up any field_required settings. Autosave will potentially unset
        // all required fields for the entry/field group so the autosave does not
        // bomb when a required field is blank. This will detect any temporarily
        // saved settings in our table and restore them. User doesn't know what happened.
        if($this->EE->input->get('use_autosave') == 'y')
        {
            $this->EE->blueprints_model->update_field_settings('set', $session);
        }

        // Get our basic data
        $channel_id = $this->EE->input->get_post('channel_id');
        $entry_id = $this->EE->input->get_post('entry_id');
        $site_assets = false;

        // If for some reason channel_id is not in $_GET/$_POST
        if ( !$channel_id)
        {
            $qry = $this->EE->db->where('entry_id', $entry_id)
                                ->get('channel_titles');

            if ($qry->num_rows())
            {
                $channel_id = $qry->row('channel_id');
            }
        }

        // If Structure is installed, get it's data
        if (array_key_exists('structure', $this->EE->addons->get_installed()))
        {
            require_once(PATH_THIRD.'structure/mod.structure.php');
            $structure = new Structure();

            $structure_settings = $this->EE->blueprints_model->get_structure_settings();
            $site_pages = $structure->get_site_pages();
        }
        // Get Pages data
        elseif (array_key_exists('pages', $this->EE->addons->get_installed()))
        {
            $site_pages = $this->EE->config->item('site_pages');
        }

        // B/c BASE isn't defined yet apparently...
        $s = ($this->EE->config->item('admin_session_type') != 'c') ? $session->userdata('session_id') : 0;
        $base_url = SELF.'?S='.$s.'&amp;D=cp'.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP;

        // Get previously set data for either Structure or Pages set to the requested entry_id
        if ($entry_id && isset($site_pages['uris'][$entry_id]))
        {
            $template_id = $site_pages['templates'][$entry_id];
        }
        // If viewing autosaved entry, find out what template it is using. It may not be the
        // default one, and it won't exist in $site_pages yet.
        elseif ($this->EE->input->get('use_autosave') == 'y')
        {
            $entry_data = $this->EE->blueprints_model->get_autosave_data($channel_id, $entry_id);

            if (array_key_exists('structure', $this->EE->addons->get_installed()))
            {
                $template_id = isset($entry_data['structure__template_id']) ? $entry_data['structure__template_id'] : false;
            }
            else
            {
                $template_id = isset($entry_data['pages__pages_template_id']) ? $entry_data['pages__pages_template_id'] : false;
            }
        }
        // Get default Structure settings
        elseif (array_key_exists('structure', $this->EE->addons->get_installed()))
        {
            $template_id = FALSE;

            if (isset($structure_settings[$channel_id]['template_id']))
            {
                $template_id = $structure_settings[$channel_id]['template_id'];
            }

            if( ! $template_id AND ($structure_settings[$channel_id]['type'] == 'page' OR $structure_settings[$channel_id]['type'] == 'listing'))
            {
                $channels = $this->EE->blueprints_model->get_channels();
                $link = $base_url.'module=structure'.AMP.'method=channel_setings';
                show_error('Please define a default template for the <b>'. $channels[$channel_id]['channel_title'] .'</b> channel in the <a href="'. $link .'">Structure module configuration</a>.');
            }
        }
        // Get default Pages settings
        else
        {
            $query = $this->EE->db->get_where('pages_configuration', array('configuration_name' => 'template_channel_'. $channel_id), 1, 0);

            if($query->num_rows() == 0)
            {
                $channels = $this->EE->blueprints_model->get_channels();
                $link = $base_url.'module=pages'.AMP.'method=configuration';
                show_error('Please define a default template for the <b>'. $channels[$channel_id]['channel_title'] .'</b> channel in the <a href="'. $link .'">Pages module configuration</a>.');
            }
            else
            {
                $template_id = $query->row('configuration_value');
            }
        }

        // And hi-jack it if we have a custom layout_group
        if($layout_group = $this->_find_layout_group($template_id, $channel_id, $entry_id, $session))
        {
            $_GET['layout_preview'] = isset($_GET['layout_preview']) ? $_GET['layout_preview'] : $layout_group;
        }

        return $session;
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
    private function _find_layout_group($template_id, $channel_id, $entry_id = false, $session = false)
    {
        // If this is a new entry, find out what template is assigned to which layout_group from our settings.
        if(!$entry_id)
        {
            $layout_group = $this->EE->blueprints_model->find_layout_group_from_settings($template_id, $channel_id);
        }
        // If this is an existing entry, then the template/layout_group has already been saved to our settings.
        else
        {
            // Easy find
            if(isset($this->cache['entries'][$entry_id]['group_id']))
            {
                $layout_group = $this->cache['entries'][$entry_id]['group_id'];
            }
            // Uh oh, more work is needed :(
            else
            {
                $layout_group = $this->EE->blueprints_model->find_layout_group_from_settings($template_id, $channel_id);
            }
        }

        // Set our cache with the found layout_group
        if($session)
        {
            $session->cache['blueprints']['layout_group'] = $layout_group;
        }
        else
        {
            $this->cache['layout_group'] = $layout_group;
        }

        return $layout_group;
    }

    /*
        Only this hook is called when Autosave, Save Revision and Submit are clicked.
    */
    public function entry_submission_ready($meta, $data, $autosave)
    {
        // Fixed to work with the Seed module, and any other module possibly using this hook.
        $entry_id = isset($data['entry_id']) ? $data['entry_id'] : false;
        if ( ! $entry_id) return;

        $this->cache['entry_submission_ready_called'] = true;
        $this->_entry_submission($entry_id, $data);
    }

    /*
        Only called when Submit is clicked.
    */
    public function entry_submission_absolute_end($entry_id, $meta, $data)
    {
        // if ( ! isset($this->cache['entry_submission_ready_called']))
        // {
            $this->_entry_submission($entry_id, $data);
        // }
    }

    private function _entry_submission($entry_id, $data)
    {
        if(REQ != 'CP' OR $this->EE->input->is_ajax_request() OR $entry_id == 0)
        {
            return;
        }

        $post_template_id = false;

        // Save our settings to the current site ID for MSM.
        $site_id = $this->EE->config->item('site_id');

        // Look for a Structure template ID first, then default to the Pages module
        $post_template_id = $this->EE->input->post('structure__template_id') ?
                            $this->EE->input->post('structure__template_id') :
                            $this->EE->input->post('pages__pages_template_id');

        // Save our entry
        if($post_template_id AND $this->EE->input->post('new_layout_preview') AND $this->EE->input->post('new_layout_preview') != "NULL")
        {
            $data = array(
                'site_id'       => $this->site_id,
                'group_id'      => $this->EE->input->post('new_layout_preview'),
                'entry_id'      => $entry_id,
                'template_id'   => $post_template_id
            );

            $where = array(
                'site_id'       => $this->site_id,
                'entry_id'      => $entry_id
            );

            $this->EE->blueprints_model->insert_or_update('blueprints_entries', $data, $where);
        }

        // If the new template does not have a publish layout, delete from our entries table, otherwise it
        // will reload with the old layout preview set.
        if($this->EE->input->post('old_layout_preview') AND $this->EE->input->post('new_layout_preview') == "NULL")
        {
            $this->EE->db->delete('blueprints_entries', array('site_id' => $this->site_id, 'entry_id' => $entry_id));
        }
    }

    /*
        This is kind of lame, but all the good hooks have been removed from 2.0. This is called at the
        beginning of a publish form load, so we'll use it to add our JS config settings to the footer.
        Could use session_end and check the router or check the $_GET vars, but meh, doing it this way.
    */
    public function publish_form_channel_preferences($data)
    {
        $templates = array();

        $thumbnails = array();
        $thumbnail_options = array();

        $layouts = array();
        $layout_options = array();

        $channel_templates = array();
        $channel_id = $this->EE->input->get_post('channel_id');

        $entry_id = $this->EE->input->get_post('entry_id');
        $thumbnail_path = isset($this->settings['thumbnail_path']) ? $this->settings['thumbnail_path'] : $this->cache['settings']['thumbnail_directory_path'];

        $enable_carousel = isset($this->settings['enable_carousel']) ? $this->settings['enable_carousel'] : 'n';

        $has_thumbnails = FALSE;

        // Lets get our active layouts into a JavaScript array to use with jQuery below
        $active_publish_layout = $this->EE->blueprints_model->get_active_publish_layout($channel_id);
        $active_publish_layout_array = array();

        foreach($active_publish_layout as $id => $name)
        {
            $active_publish_layout_array[] = '"'. $id .'"';
        }
        $active_publish_layouts = 'new Array('. trim(implode(',', $active_publish_layout_array), ',') .')';

        if(!empty($this->cache['layouts']))
        {
            foreach($this->cache['layouts'] as $layout)
            {
                $thumbnail = ($layout['thumbnail']) ? $this->EE->blueprints_helper->swap_upload_pref_token($layout['thumbnail']) : '';
                $thumbnails[] = '"'. $layout['template'] .'":"'. $thumbnail .'"';
                $thumbnail_options[$layout['template']] = $thumbnail;

                $layout_name = ($layout['name']) ? $layout['name'] : '';
                $layout_id = $layout['group_id'];

                $layouts[] = '"'. $layout['template'] .'":"'. $layout_id .'"';
                $layout_options[$layout_id] = $layout_name;

                // For use in the Carousel below
                $layout_carousel_ids[$layout['template']] = $layout_id;
                $layout_carousel_names[$layout['template']] = $layout_name;
            }
        }

        // Only show templates assigned to a publish layout
        if(
            isset($this->settings['enable_detailed_template']) AND
            $this->settings['enable_detailed_template'] != 'y' AND
            isset($this->settings['enable_publish_layout_takeover']) AND
            $this->settings['enable_publish_layout_takeover'] == 'y' AND
            count($this->cache['layouts']) > 0
        ){
            foreach($this->cache['layouts'] as $group_id => $layout)
            {
                $channel_templates[] = $layout['template'];
            }
        }
        // Show specific templates depending on settings
        elseif(isset($this->settings['channels']))
        {
            if (empty($this->settings['channels']))
            {
                foreach($this->cache['layouts'] as $group_id => $layout)
                {
                    $channel_templates[] = $layout['template'];
                }
            }

            foreach($this->settings['channels'] as $k => $channel)
            {
                if($channel == $channel_id OR $channel == '*')
                {
                    // If neither of these are true then the user didn't complete the settings form entirely.
                    // If that is the case then all templates will be visible.
                    if(isset($this->settings['channel_show_group'][$k]))
                    {
                        $groups = array();
                        foreach($this->settings['channel_show_group'][$k] as $group)
                        {
                            $groups[] = "'". $group ."'";
                        }

                        $templates_result = $this->EE->blueprints_model->get_templates(implode(',', $groups));

                        $channel_templates = array();
                        foreach($templates_result->result_array() as $row)
                        {
                            if ( ! in_array($row['template_id'], $channel_templates))
                            {
                                $channel_templates[] = $row['template_id'];
                            }
                        }
                    }
                    elseif(isset($this->settings['channel_templates']) && isset($this->settings['channel_templates'][$k]))
                    {
                        foreach($this->settings['channel_templates'][$k] as $row)
                        {
                            if ( ! in_array($row, $channel_templates))
                            {
                                $channel_templates[] = $row;
                            }
                        }
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
            $channel_templates_options = '""';
        }

        // If the current user is an Admin, give them a link to edit the templates that appear
        $edit_templates_link = '';
        if($this->EE->session->userdata['group_id'] == 1)
        {
            $edit_templates_link = '<br /><small style=\"display: inline-block; margin-top: 5px; color: rgba(0,0,0, 0.5);\"><a href=\"'. BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=blueprints\">Edit Available Templates</a></small>';
        }

        // Add our custom layout group options to the list, with "member group ids" starting at 2000
        $layout_checkbox_options = '';
        if(count($layout_options) > 0)
        {
            foreach($layout_options as $k => $name)
            {
                $layout_checkbox_options .= '<label><input type=\"checkbox\" name=\"member_group[]\" value=\"'. $k .'\" class=\"toggle_member_groups blueprints_member_groups\" /> '. $name .'</label><br />';
            }

            $layout_checkbox_options .= '<div style=\"height: 1px; margin-bottom: 7px; border-bottom: 1px solid rgba(0,0,0,0.1);\">&nbsp;</div>';
        }

        $carousel_templates = $this->EE->blueprints_model->get_assigned_templates($channel_templates);
        $carousel_options = array();

        foreach($carousel_templates as $template)
        {
            $layout_name = '';

            // When upgrading from 1.x its possible the thumbnail value might be an integer
            $thumbnail = (isset($thumbnail_options[$template['template_id']]) AND ! is_numeric($thumbnail_options[$template['template_id']])) ? $thumbnail_options[$template['template_id']] : '';

            if ($thumbnail && ! is_numeric($thumbnail) && $thumbnail != '')
            {
                $has_thumbnails = TRUE;
            }

            // If layout names are defined, set the layout_name, otherwise default to the actual template_name
            if (
                isset($layout_carousel_names[$template['template_id']]) &&
                $layout_carousel_names[$template['template_id']] != ''
            ){
                // The carousel gets this special little dot.
                if ($enable_carousel == 'y')
                {
                    $layout_name = '<span class="is_publish_layout">&#9679;</span>';
                }

                $layout_name .= $layout_carousel_names[$template['template_id']];
            }
            else
            {
                $layout_name = $template['group_name'].'/'.$template['template_name'];
            }

            $carousel_options[] = array(
                'template_id' => $template['template_id'],
                'template_name' => $template['template_name'],
                'template_thumb' => $thumbnail,
                'layout_preview' => isset($layout_carousel_ids[$template['template_id']]) ? $layout_carousel_ids[$template['template_id']] : '',
                'layout_name' => $layout_name
            );
        }

        $carousel_options = $this->EE->blueprints_helper->order_layouts($carousel_options, $this->cache['layouts']);

        // Used in the first ajax request when changing the publish layout.
        $ajax_params = 'entry_id='.$entry_id.'&channel_id='.$channel_id;

        // Create global config to use in our JS file
        $blueprints_config = '
        if (typeof window.Blueprints == \'undefined\') window.Blueprints = {};

        Blueprints.config = {
            active_publish_layouts: '. $active_publish_layouts .',
            action_url_update_field_settings: "'. $this->EE->blueprints_helper->get_site_index() . '?ACT='. $this->EE->cp->fetch_action_id('Blueprints_mcp', 'update_field_settings') .'",
            autosave_entry_id: "'. ($this->EE->input->get('use_autosave') == 'y' ? $this->EE->input->get_post('entry_id') : '') .'",
            ajax_params: "'. $ajax_params .'",
            carousel_options: '. json_encode($carousel_options) .',
            channel_templates: '. $channel_templates_options .',
            edit_templates_link: "'. $edit_templates_link .'",
            enable_carousel: "'. $enable_carousel .'",
            hash: "'. $this->settings['hash'] .'",
            has_thumbnails: '. ($has_thumbnails ? 'true' : 'false') .',
            layouts: {'. implode(',', $layouts) .'},
            layout_checkbox_options: "'. $layout_checkbox_options .'",
            layout_preview: "'. $this->EE->input->get_post('layout_preview') .'",
            layout_group: "'. (isset($this->cache['layout_group']) ? $this->cache['layout_group'] : '') .'",
            member_group_id: "'. $this->EE->session->userdata['group_id']. '",
            publish_layout_takeover: '. ($this->EE->blueprints_helper->enable_publish_layout_takeover() ? 'true' : 'false') .',
            theme_url: "'. $this->EE->blueprints_helper->get_theme_folder_url() .'",
            thumbnails: {'. implode(',', $thumbnails) .'},
            thumbnail_options: '. json_encode($thumbnail_options) .',
            thumbnail_path: "'. $this->EE->config->slash_item('site_url') . $thumbnail_path .'"
        };';

        $this->EE->cp->add_to_head('<!-- BEGIN Blueprints assets --><script type="text/javascript">(function($){'. $blueprints_config .'})(jQuery);</script><!-- END Blueprints assets -->');
        $this->EE->cp->add_to_head('<!-- BEGIN Blueprints assets --><link type="text/css" href="'. $this->EE->blueprints_helper->get_theme_folder_url() .'blueprints/styles/blueprints.css" rel="stylesheet" /><!-- END Blueprints assets -->');
        $this->EE->cp->add_to_foot('<!-- BEGIN Blueprints assets --><script type="text/javascript" src="'. $this->EE->blueprints_helper->get_theme_folder_url() .'blueprints/scripts/blueprints.js"></script><!-- END Blueprints assets -->');

        // Return the unmodified data
        return $data;
    }

    /*
       Parse template variables.
       Contribution from Tim Kelty
    */
    public function channel_entries_tagdata($tagdata, $row, $obj)
    {
        $this->EE = &get_instance();
        
        if($this->EE->extensions->last_call)
        {
            $tagdata = $this->EE->extensions->last_call;
        }

        $entry_id = $row['entry_id'];

        // Get templates
        $site_pages = ee()->config->item('site_pages');
        $templates  = $site_pages[ee()->config->item('site_id')]['templates'];

        $template_id = isset($templates[$entry_id]) ? $templates[$entry_id] : FALSE;
        $template_path = FALSE;

        if ($template_id)
        {
            $qry = ee()->db->get_where('templates', array('template_id' => $template_id));
            $template = $qry->row();

            $qry = ee()->db->get_where('template_groups', array('group_id' => $template->group_id));
            $group = $qry->row();

            $template_path = $group->group_name . '/' . $template->template_name;
        }

        $tagdata = ee()->TMPL->parse_variables_row($tagdata, array(
            'pages_template_id' => $template_id,
            'pages_template_path' => $template_path,
        ));

        return $tagdata;
    }

    /*
        Settings form. Turn on/off options and save Publish Layout names.
    */
    public function settings_form($vars)
    {
        $this->EE->lang->loadfile('blueprints');
        $this->EE->load->library('javascript');

        $this->EE->load->library('filemanager');
        $this->EE->load->library('file_field');

        // Set vars
        $fields = array();
        $channels = array();
        $template_options = array();
        $template_channel_options = array();
        $channel_fields = array();

        $upload_prefs = $this->EE->blueprints_helper->get_upload_prefs();

        // Get our data
        $templates  = $this->EE->blueprints_model->get_templates();
        $thumbnails = $this->EE->blueprints_model->get_thumbnails();
        $channels   = $this->EE->blueprints_model->get_channels();

        // See if P&T Switch is installed, if so, add some style...
        $this->EE->load->library('api');
        $this->EE->api->instantiate('channel_fields');
        $fts = $this->EE->api_channel_fields->fetch_installed_fieldtypes();

        if(array_key_exists('pt_switch', $fts))
        {
            // Apply P&T Switch to specific fields
            $this->EE->blueprints_helper->load_switch(array(
                '#enable_edit_menu_tweaks',
                '#enable_publish_layout_takeover',
                '#enable_carousel',
                '#enable_detailed_template'
            ));
        }

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

        if(count($channels) == 0)
        {
            $channel_options = array('' => 'No channels found');
        }
        else
        {
            $channel_options[''] = '-- Select --';
            $channel_options['*'] = '* All Channels';
            foreach($channels as $channel_id => $channel) {
                $channel_options[$channel_id] = $channel['channel_title'];
            }
        }

        $k = 0;

        // Create our fields from our saved settings in the DB
        if(!empty($this->cache['layouts']))
        {
            foreach($this->cache['layouts'] as $layout)
            {
                // Recreate saved fields
                $fields[] = array(
                    'row_id' => $layout['id'],
                    'tmpl_name' => 'template['. $k .']',
                    'tmpl_options' => $template_options,
                    'tmpl_options_selected' => isset($layout['template']) ? $layout['template'] : '',

                    'thb_name' => 'thumbnails['. $k .']',
                    'thb_options_selected' => isset($layout['thumbnail']) ? $layout['thumbnail'] : '',

                    'layout_group_id' => 'layout_group_ids['. $k .']',
                    'layout_group_id_value' => isset($layout['group_id']) ? $layout['group_id'] : (int) $this->layout_id + $k,
                    'layout_group_name' => 'layout_group_names['. $k .']',
                    'layout_group_name_value' => isset($layout['name']) ? $layout['name'] : ''
                );

                $k++;
            }
        }
        else
        {
            $fields[] = array(
                'row_id' => 0,
                'tmpl_name' => 'template['. $k .']',
                'tmpl_options' => $template_options,
                'tmpl_options_selected' => isset($layout['template']) ? $layout['template'] : '',

                'thb_name' => 'thumbnails['. $k .']',
                'thb_options_selected' => isset($layout['thumbnail']) ? $layout['thumbnail'] : '',

                'layout_group_id' => 'layout_group_ids['. $k .']',
                'layout_group_id_value' => isset($layout['group_id']) ? $layout['group_id'] : (int) $this->layout_id + $k,
                'layout_group_name' => 'layout_group_names['. $k .']',
                'layout_group_name_value' => isset($layout['name']) ? $layout['name'] : ''
            );
        }

        // Create our fields from the serialized save settings in the extension
        if(!empty($this->settings['channels']))
        {
            foreach($this->settings['channels'] as $k => $row)
            {
                $channel_fields[] = array(
                    'channel_name' => 'channels['. $k .']',
                    'channel_options' => $channel_options,
                    'channel_options_selected' => isset($vars['channels'][$k]) ? $vars['channels'][$k] : '',

                    'channel_templates_name' => 'channel_templates['. $k .'][]',
                    'channel_templates_options' => $template_options,
                    'channel_templates_options_selected' => (isset($vars['channel_templates']) AND isset($vars['channel_templates'][$k])) ? $vars['channel_templates'][$k] : '',

                    'channel_checkbox_options' => $this->EE->blueprints_helper->get_checkbox_options($k)
                );
            }
        }
        else
        {
            $k = 0;

            $channel_fields[] = array(
                'channel_name' => 'channels['. $k .']',
                'channel_options' => $channel_options,
                'channel_options_selected' => (isset($vars['channels']) AND isset($vars['channels'][$k])) ? $vars['channels'][$k] : '',

                'channel_templates_name' => 'channel_templates['. $k .'][]',
                'channel_templates_options' => $template_options,
                'channel_templates_options_selected' => (isset($vars['channel_templates']) AND isset($vars['channel_templates'][$k])) ? $vars['channel_templates'][$k] : '',

                'channel_checkbox_options' => $this->EE->blueprints_helper->get_checkbox_options($k)
            );
        }

        $vars['app_version'] = $this->EE->config->item('app_version');
        $vars['enable_publish_layout_takeover'] = isset($this->settings['enable_publish_layout_takeover']) ? $this->settings['enable_publish_layout_takeover'] : 'n';
        $vars['enable_edit_menu_tweaks'] = isset($this->settings['enable_edit_menu_tweaks']) ? $this->settings['enable_edit_menu_tweaks'] : 'n';
        $vars['enable_carousel'] = isset($this->settings['enable_carousel']) ? $this->settings['enable_carousel'] : 'n';
        $vars['enable_detailed_template'] = isset($this->settings['enable_detailed_template']) ? $this->settings['enable_detailed_template'] : 'n';
        $vars['thumbnail_path'] = isset($this->settings['thumbnail_path']) ? $this->settings['thumbnail_path'] : $this->cache['settings']['thumbnail_directory_url'];
        $vars['site_path'] = $this->EE->blueprints_helper->site_path();
        $vars['structure_installed'] = array_key_exists('structure', $this->EE->addons->get_installed());
        $vars['pages_installed'] = array_key_exists('pages', $this->EE->addons->get_installed());
        $vars['theme_folder_url'] = $this->EE->blueprints_helper->get_theme_folder_url();

        $max_group_id = $this->EE->db->select_max('group_id')->get('blueprints_layouts')->row('group_id');

        $vars['max_group_id'] = $max_group_id ? $max_group_id : $this->layout_id;

        $vars['hidden'] = array(
            'file' => 'blueprints',
            // Check for hash and set it if it doesn't exist... just incase
            'hash' => (isset($this->settings['hash']) ? $this->settings['hash'] : $this->EE->functions->random('encrypt', 32))
        );

        $vars = array_merge($vars, array('fields' => $fields, 'channels' => $channel_fields));

        $is_assets_installed = array_key_exists('assets', $this->EE->addons->get_installed()) ? "yes" : "no";

        // Create global config to use in our JS file
        $blueprints_config = '
        if (typeof window.Blueprints == \'undefined\') window.Blueprints = {};

        Blueprints.config = {
            blueprints_total_templates: '. $templates->num_rows() .',
            blueprints_total_channels: '. count($channels) .',
            ee_version: "'. $this->EE->config->item('app_version') .'",
            upload_prefs: '. json_encode($upload_prefs) .',
            is_assets_installed: "'. $is_assets_installed .'"
        };';

        $this->EE->cp->add_to_head('<!-- BEGIN Blueprints assets --><script type="text/javascript">'. $blueprints_config .'</script><!-- END Blueprints assets -->');
        $this->EE->cp->add_to_head('<!-- BEGIN Blueprints assets --><link type="text/css" href="'. $this->EE->blueprints_helper->get_theme_folder_url() .'blueprints/styles/blueprints.css" rel="stylesheet" /><!-- END Blueprints assets -->');
        $this->EE->cp->add_to_head('<!-- BEGIN Blueprints assets --><script type="text/javascript" src="'. $this->EE->blueprints_helper->get_theme_folder_url() .'blueprints/scripts/blueprints_settings.js"></script><!-- END Blueprints assets -->');

        // Add our File Manager and the first row event binding
        if ($is_assets_installed == "yes")
        {
            // Load Assets' assets
            if ( ! isset($this->EE->session->cache['assets']['included_sheet_resources']))
            {
                if (! class_exists('Assets_helper'))
                {
                    require PATH_THIRD.'assets/helper.php';
                }

                $assets_helper = new Assets_helper;
                $assets_helper->include_sheet_resources();
            }

            foreach( $fields as $k => $field)
            {
                $script = '$("#thumbnail_trigger_'. $k .'").click(function(e){
                    var sheet = new Assets.Sheet({
                        filedirs: "all",
                        kinds: "any",
                        onSelect: function(file) { blueprints_set_thumbnail(file[0], "'. $k .'"); }
                    });

                    e.preventDefault();
                    sheet.show();
                });';

                $this->EE->cp->add_to_foot('<!-- BEGIN Blueprints assets --><script type="text/javascript">'. $script .'</script><!-- END Blueprints assets -->');
            }
        }
        else
        {
            foreach( $fields as $k => $field)
            {
                $config = array(
                    'trigger' => '#thumbnail_trigger_'. $k,
                    'field_name' => '"#thumbnail_'. $k .'"',
                    'settings' => '{content_type: "img", directory: "all"}',
                    'callback' => 'function(file, e) { blueprints_set_thumbnail(file, "'. $k .'"); }'
                );

                // Note this is required for the FM to even work, its assets can only be loaded through this method.
                $this->EE->file_field->browser($config);
            }
        }

        // Load it up and return it to addons_extensions.php for rendering
        return $this->EE->load->view('settings_form', $vars, TRUE);
    }

    /*
        Save the form settings
    */
    public function save_settings()
    {
        $channels = $this->EE->input->post('channels');
        $channel_show_selected = $this->EE->input->post('channel_show_selected');
        $channel_templates = $this->EE->input->post('channel_templates');
        $channel_show_group = $this->EE->input->post('channel_show_group');
        $delete = $this->EE->input->post('delete', false);

        // Save to ext settings
        $save = array();

        // Insert into custom tables
        $insert = array();

        $save['enable_publish_layout_takeover'] = $this->EE->input->post('enable_publish_layout_takeover');
        $save['enable_edit_menu_tweaks'] = $this->EE->input->post('enable_edit_menu_tweaks');
        $save['enable_carousel'] = $this->EE->input->post('enable_carousel');
        $save['enable_detailed_template'] = $this->EE->input->post('enable_detailed_template');
        $save['thumbnail_path'] = $this->EE->input->post('thumbnail_path');
        $save['hash'] = $this->EE->input->post('hash');

        $insert['template'] = $this->EE->input->post('template');
        $insert['thumbnails'] = $this->EE->input->post('thumbnails');
        $insert['layout_group_ids'] = $this->EE->input->post('layout_group_ids');
        $insert['layout_group_names'] = $this->EE->input->post('layout_group_names');

        if($channels)
        {
            $channels = array_values($channels);
            $channel_show_selected = !empty($channel_show_selected) ? $channel_show_selected : array();
            $channel_templates = !empty($channel_templates) ? array_values($channel_templates) : array();

            // Figure out what templates to show for each channel
            foreach($channels as $k => $channel_id)
            {
                if(count($channels) > 0)
                {
                    $save['channels'][$k] = $channel_id;
                }

                if(isset($channel_show_group[$k]))
                {
                    $save['channel_show_group'][$k] = $channel_show_group[$k];
                }
                elseif(isset($channel_show_selected[$k]) AND $channel_show_selected[$k] == 'y' AND isset($channel_templates[$k]))
                {
                    $save['channel_show_selected'][$k] = $channel_show_selected[$k];
                    $save['channel_templates'][$k] = $channel_templates[$k];
                }
                // If adding a new row in the detailed settings the key is set to new_ for show selected
                // but not the other rows. Not sure how it came to this, but just rolling with it to make it work.
                elseif(isset($channel_show_selected['new_'.$k]) AND $channel_show_selected['new_'.$k] == 'y' AND isset($channel_templates[$k]))
                {
                    $save['channel_show_selected'][$k] = $channel_show_selected['new_'.$k];
                    $save['channel_templates'][$k] = $channel_templates[$k];
                }
            }
        }

        // If the user decided to remove the publish layout data from EE entirely
        if($delete)
        {
            // Remove entry so when the entry is loaded in the
            // publish page we don't try to load a layout for it.
            $this->EE->db->where_in('group_id', $delete);
            $this->EE->db->delete('blueprints_entries');

            // Remove layout from the DB entirely
            $this->EE->db->where_in('member_group', $delete);
            $this->EE->db->delete('layout_publish');
        }

        // Loop through our existing layouts, if a layout/group_id is not present
        // in what is to be the newly submitted array, then the user must have deleted the row.
        foreach($this->cache['layouts'] as $group_id => $layout)
        {
            if(!in_array($layout['group_id'], $insert['layout_group_ids']))
            {
                $this->EE->db->where('group_id', $layout['group_id'])->delete('blueprints_layouts');
            }
        }

        $template_order = array_values($insert['template']);

        foreach($insert['template'] as $k => $v)
        {
            if($insert['layout_group_names'][$k])
            {
                $order = array_search($insert['template'][$k], $template_order);

                $data = array(
                    'site_id'       => $this->site_id,
                    'group_id'      => $insert['layout_group_ids'][$k],
                    'template'      => $insert['template'][$k],
                    'thumbnail'     => $insert['thumbnails'][$k],
                    'name'          => $insert['layout_group_names'][$k],
                    'order'         => $order
                );

                $where = array(
                    'site_id'       => $this->site_id,
                    'group_id'      => $insert['layout_group_ids'][$k]
                );

                $this->EE->blueprints_model->insert_or_update('blueprints_layouts', $data, $where);
            }
        }

        // Save our settings to the current site ID for MSM.
        $site_id = $this->EE->config->item('site_id');

        $qry = $this->EE->db->limit(1)->get_where('extensions', array('class' => 'Blueprints_ext'));

        if ($qry->num_rows())
        {
            $settings = unserialize($qry->row('settings'));
        }
        else
        {
            $settings = array();
        }

        $settings[$site_id] = $save;

        $this->EE->db->where('class', 'Blueprints_ext');
        $this->EE->db->update('extensions', array('settings' => serialize($settings)));

        $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('preferences_updated'));
    }

    public function activate_extension() {}
    public function update_extension($current = '') {
        // Is there a better way to do this??
        require_once PATH_THIRD .'blueprints/upd.blueprints.php';
        $upd = new Blueprints_upd;
        $upd->update($current);
    }
    public function disable_extension() {}

    private function debug($str, $die = false)
    {
        echo '<pre>';
        var_dump($str);
        echo '</pre>';

        if($die) die('debug terminated');
    }
}