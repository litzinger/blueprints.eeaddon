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
 * ExpressionEngine Blueprints Accessory Class
 *
 * @package     ExpressionEngine
 * @subpackage  Accessories
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
 
class Blueprints_acc {

    public $name            = 'Blueprints Accessory';
    public $id              = 'blueprints';
    public $version         = BLUEPRINTS_VERSION;
    public $description     = BLUEPRINTS_DESC;
    public $sections        = array();
    public $required_by     = array('module');

    /**
     * Constructor
     */
    function Blueprints_acc()
    {
        $this->EE =& get_instance();
        $this->EE->lang->loadfile('blueprints');
        
        // Create cache
        if (! isset($this->EE->session->cache['blueprints']))
        {
            $this->EE->session->cache['blueprints'] = array();
        }
        $this->cache =& $this->EE->session->cache['blueprints'];
    }
    
    function set_sections()
    {
        $this->cache['settings'] = $this->EE->blueprints_model->get_settings(true);
        
        // Remove the tab. This is lame.
        $script = '
            $("#blueprints.accessory").remove();
            $("#accessoryTabs").find("a.blueprints").parent("li").remove();
        ';
        
        // Output JS, and remove extra white space and line breaks
        $this->EE->javascript->output('$(function(){'. preg_replace("/\s+/", " ", $script) .'});');
        $this->EE->javascript->compile();
        
        $this->_sidebar();
    }
    
    private function _sidebar()
    {
        $script = '';

        $contents = '
        <h1 class="round heading">
            <a href="#"><span class="blueprints_arrow"></span>Pages</a>
            <input type="text" id="structure_pages_search" name="structure_pages_search" placeholder="Search Pages" />
        </h1>
        <div class="blueprints_page_listing"></div>
        ';

        if (array_key_exists('structure', $this->EE->addons->get_installed()) OR 
            array_key_exists('pages', $this->EE->addons->get_installed()))
        {
            $load_pages_url = $this->EE->blueprints_helper->get_site_index() . '?ACT='. $this->EE->cp->fetch_action_id('Blueprints_mcp', 'load_pages');
             
            $script .= '
             
            $("#sidebarContent").prepend(\'<div class="structure_pages_sidebar contents">'. $contents .'</div>\');
             
            $("#sidebarContent .structure_pages_sidebar h1 a").toggle(function(){
                $(".structure_pages_sidebar ul, .structure_pages_sidebar .item_wrapper").not(".listings").slideDown();
                $(".structure_pages_sidebar h1 span.blueprints_arrow").addClass("active");
                $("#structure_pages_search").val("");
            }, function(){
                $(".structure_pages_sidebar ul").slideUp();
                $(".structure_pages_sidebar h1 span.blueprints_arrow").removeClass("active");
            });
            $("a.expand").toggle(function(){
                $(this).text(" - ");
                $(this).closest(".item_wrapper").next(".listings").slideDown();
            }, function(){
                $(this).text(" + ");
                $(this).closest(".item_wrapper").next(".listings").slideUp();
            });
            $("#structure_pages_search").keyup(function(){
                val = $(this).val().toLowerCase();
                items = $(".structure_pages_sidebar .item_wrapper");
                if(val.length > 2){
                    $(".structure_pages_sidebar ul").show();
                    items.hide();
                    items.each(function(){
                        text = $(this).find("a").text().toLowerCase();
                        if(text.search(val) != -1){
                            $(this).show();
                            $(".structure_pages_sidebar h1 span.blueprints_arrow").addClass("active");
                        }
                    });
                } else {
                    if( $(".structure_pages_sidebar h1 span.blueprints_arrow").hasClass("active") ) {
                        items.show();
                    } else {
                        items.hide();
                    }
                }
            });
             
            $.ajax({
                type: "GET",
                url: "'. $load_pages_url .'",
                success: function(html){
                    $(".blueprints_page_listing").html(html);
                }
            });';
         }

         // Fix from John D. Wells        
         // first create default settings array
         // $settings = array('enable_edit_menu_tweaks' => '');
         // 
         // // now attempt to override from DB
         // $query = $this->EE->db->get_where('extensions', array('class' => 'Blueprints_ext'), 1, 0);
         // 
         // if ($query->num_rows() > 0)
         // {
         //     $row = unserialize($query->row('settings'));
         //     $site_id = $this->EE->config->item('site_id');
         //     if($row AND array_key_exists($site_id, $row))
         //     {
         //         $settings = $row[$site_id];
         //     }
         // }

         if (isset($this->cache['settings']['enable_edit_menu_tweaks']) AND $this->cache['settings']['enable_edit_menu_tweaks'] == 'y')
         {
             $script .= '
                 var bp_ul = $("#navigationTabs li:eq(1) ul:eq(0) li.parent ul:eq(0)").html();

                 if(bp_ul)
                 {
                     var bp_pattern = new RegExp("content_publish", "gi");
                     bp_ul = bp_ul.replace(bp_pattern, "content_edit");

                     var bp_pattern = new RegExp("&amp;M=entry_form", "gi");
                     bp_ul = bp_ul.replace(bp_pattern, "");

                     $("#navigationTabs li:eq(1) ul:eq(0) li.parent + li").addClass("parent blueprints").append("<ul>"+ bp_ul +"</ul>");

                     $("#navigationTabs li.blueprints ul li").hover(function(){
                         $(this).addClass("hover active");
                     }, function(){
                         $(this).removeClass("hover active");
                     });
                 }
             ';
         }

         // Output JS, and remove extra white space and line breaks
         $this->EE->javascript->output('$(function(){'. preg_replace("/\s+/", " ", $script) .'});');
         $this->EE->javascript->compile();

         // Extra CSS just for the sidebar. Some styles come from libraries/page_styles.php
         $css = '
             #structure_pages_search {
                 width: 110px;
                 position: absolute;
                 right: 8px;
                 top: 6px;
                 padding-right: 20px;
                 background: #fff url('. $this->EE->config->item('theme_folder_url') .'third_party/boldminded_themes/images/icon-search.png) 98% 50% no-repeat;
             }
             .structure_pages_sidebar .blueprints_arrow {
                 float: left;
                 display: block;
                 width: 16px;
                 height: 16px;
                 margin-top: -2px;
                 background-color: none;
                 background-position: -32px -16px;
                 background-image: url('. $this->EE->config->item('theme_folder_url') .'cp_themes/default/images/ui-icons_ffffff_256x240.png);
             }
             .structure_pages_sidebar .blueprints_arrow.active {
                 background-position: -64px -16px;
             }
             .structure_pages_sidebar h1 {
                 /* background: #2A3940; */
                 color: #fff; 
                 font-size: 11px; 
                 padding: 5px; 
                 height: 25px;
                 margin-bottom: 2px; 
                 text-transform: uppercase;
                 -webkit-border-radius: 3px;
                 -moz-border-radius: 3px;
                 border-radius: 3px;
             }
             .structure_pages_sidebar h1 a {
                 text-decoration: none !important;
                 color: #fff !important;
                 font-size: 11px;
                 display: block;
                 width: 100px;
                 position: absolute;
                 left: 0;
                 top: 0;
                 padding: 12px 5px;
             }
             .structure_pages_sidebar h1 a span {
                 float: right;
                 text-decoration: none !important;
                 color: rgba(255,255,255,0.5) !important;
                 font-size: 11px;
             }
             .structure_pages_sidebar {
                 position: relative;
                 margin: 18px 0;
                 padding: 0;
             }
             .structure_pages_sidebar ul.structure_pages {
                 display: none;
             }
             #structure_pages_search:active,
             #structure_pages_search:focus,
             #structure_pages_search {
                 outline: none;
             }
             ul.structure_pages .item_wrapper a.expand,
             ul.structure_pages .item_wrapper a.expand:hover {
                 background-image: url('. $this->EE->config->item('theme_folder_url') .'third_party/structure/img/icon-listing.png);
                 background-position: 5px 50%;
                 background-repeat: no-repeat;
             }
         ';

         // Output CSS, and remove extra white space and line breaks
         $this->EE->cp->add_to_head('<!-- BEGIN Blueprints assets --><style type="text/css">'. preg_replace("/\s+/", " ", $css) .'</style><!-- END Blueprints assets -->');
    }

    private function debug($str, $die = false)
    {
        echo '<pre>';
        var_dump($str);
        echo '</pre>';
        
        if($die) die('debug terminated');
    }
}
// END CLASS