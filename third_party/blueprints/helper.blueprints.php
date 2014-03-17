<?php

/**
 * ExpressionEngine Blueprints Helper Class
 *
 * @package     ExpressionEngine
 * @subpackage  Helpers
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

class Blueprints_helper
{
    private $site_id;
    private $cache;

    public $thumbnail_directory_url;
    public $thumbnail_directory_path;

    public function __construct()
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

    public function get_checkbox_options($k)
    {
        $templates = $this->EE->blueprints_model->get_templates();

        $checkbox_options = '';
        $groups = array();

        foreach($templates->result_array() as $template)
        {
            if(!in_array($template['group_name'], $groups))
            {
                $checked = ((
                        isset($template['group_name']) AND
                        isset($this->cache['settings']['channel_show_group']) AND
                        isset($this->cache['settings']['channel_show_group'][$k]) AND
                        in_array($template['group_name'], $this->cache['settings']['channel_show_group'][$k])
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
            isset($this->cache['settings']['channel_show_selected']) AND
            isset($this->cache['settings']['channel_show_selected'][$k]) AND
            $this->cache['settings']['channel_show_selected'][$k] == 'y'
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

    public function get_pages()
    {
        // Make sure pages cache is empty, and also see if we are in the CP. Since fieldtype files get loaded
        // on the front end, I don't want unecessary queries/processing to be done when not needed.
        if(!isset($this->cache['pages']))
        {
            $this->cache['pages'] = "";

            if(array_key_exists('structure', $this->EE->addons->get_installed()))
            {
                require_once $this->get_theme_folder_path().'boldminded_themes/libraries/structure_pages.php';
                $pages = Structure_Pages::get_instance();
                $this->cache['pages'] = $pages->get_pages($this->site_id);
            }
            elseif(array_key_exists('pages', $this->EE->addons->get_installed()))
            {
                require_once $this->get_theme_folder_path().'boldminded_themes/libraries/pages.php';
                $pages = Pages::get_instance();
                $this->cache['pages'] = $pages->get_pages($this->site_id);
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

    public function enable_publish_layout_takeover()
    {
        if(!isset($this->cache['enable_publish_layout_takeover']))
        {
            $this->cache['enable_publish_layout_takeover'] = (isset($this->cache['settings']['enable_publish_layout_takeover']) AND $this->cache['settings']['enable_publish_layout_takeover'] == 'y') ? true : false;
        }

        return $this->cache['enable_publish_layout_takeover'];
    }

    /**
     * Re-order the carousel layouts based on the saved settings
     * @param  array $layouts
     * @param  array $settings
     * @return array
     */
    public function order_layouts($layouts, $settings)
    {
        $new_array = array();

        foreach ($settings as $layout_id => $setting_data)
        {
            foreach ($layouts as $k => $layout_data)
            {
                if ($layout_data['template_id'] == $setting_data['template'])
                {
                    $new_array[$layout_data['template_id']] = $layout_data;
                }
            }
        }

        foreach ($layouts as $template_data)
        {
            if ( !array_key_exists($template_data['template_id'], $new_array))
            {
                $new_array[$template_data['template_id']] = $template_data;
            }
        }

        return array_values($new_array);
    }

    public function get_site_index()
    {
        $site_index = $this->EE->config->item('site_index');
        $index_page = $this->EE->config->item('index_page');

        $index = ($site_index != '') ? $site_index : (($index_page != '') ? $index_page : 'index.php');

        return reduce_double_slashes($this->EE->config->slash_item('site_url') . $index);
    }

    public function get_upload_prefs($group_id = NULL, $id = NULL)
    {
        if(!isset($this->cache['upload_prefs']))
        {
            if (version_compare(APP_VER, '2.4', '>='))
            {
                $this->EE->load->model('file_upload_preferences_model');
                $this->cache['upload_prefs'] = $this->EE->file_upload_preferences_model->get_file_upload_preferences($group_id, $id);
                return $this->cache['upload_prefs'];
            }

            if (version_compare(APP_VER, '2.1.5', '>='))
            {
                $this->EE->load->model('file_upload_preferences_model');
                $result = $this->EE->file_upload_preferences_model->get_upload_preferences($group_id, $id);
            }
            else
            {
                $this->EE->load->model('tools_model');
                $result = $this->EE->tools_model->get_upload_preferences($group_id, $id);
            }

            $this->cache['upload_prefs'] = $result->result_array();
        }

        return $this->cache['upload_prefs'];
    }

    public function get_upload_prefs_tokens()
    {
        if(!isset($this->cache['upload_prefs_tokens']))
        {
            if (!isset($this->cache['upload_prefs']))
            {
                $this->get_upload_prefs();
            }

            $this->cache['upload_prefs_tokens'] = array();

            foreach ($this->cache['upload_prefs'] as $id => $prefs)
            {
                $this->cache['upload_prefs_tokens']['{filedir_'. $prefs['id'] .'}'] = $prefs['url'];
            }
        }

        return $this->cache['upload_prefs_tokens'];
    }

    public function swap_upload_pref_token($str, $thumbnail = false)
    {
        $this->get_upload_prefs_tokens();

        $urls = array();

        if ($thumbnail)
        {
            if (strpos($str, '/') === FALSE)
            {
                $str = str_replace('}', '}_thumbs/', $str);
            }
            else
            {
                $parts = explode('/', $str);
                $last = array_pop($parts);
                $str = str_replace($last, '_thumbs/'.$last, $str);
            }
        }

        $urls = array_values($this->cache['upload_prefs_tokens']);
        $tokens = array_keys($this->cache['upload_prefs_tokens']);

        return str_replace($tokens, $urls, $str);
    }

    public function is_publish_form()
    {
        if(REQ != "CP")
        {
            return false;
        }

        if (version_compare(APP_VER, '2.8', '>='))
        {
            if($this->EE->router->class == 'content_publish' AND $this->EE->router->method == 'entry_form')
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
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
    }

    public function load_switch($target)
    {
        if(!$target)
        {
            return;
        }

        if(!isset($this->cache['pt_switch']))
        {
            $this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->get_theme_folder_url().'pt_switch/styles/pt_switch.css" />');
            $this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$this->get_theme_folder_url().'pt_switch/scripts/pt_switch.js"></script>');

            $this->cache['pt_switch'] = true;
        }

        if(is_array($target))
        {
            foreach($target as $ele)
            {
                $this->EE->cp->add_to_foot('<script type="text/javascript">new ptSwitch(jQuery("'. $ele .'"));</script>');
            }
        }
        else
        {
            $this->EE->cp->add_to_foot('<script type="text/javascript">new ptSwitch(jQuery("'. $target .'"));</script>');
        }
    }

    /**
      * Retrieve site path
      */
    public function site_path()
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