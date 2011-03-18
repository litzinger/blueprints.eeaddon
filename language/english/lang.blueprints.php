<?php

// get the version from config.php
require PATH_THIRD.'blueprints/config.php';

$lang = array(
    
"blueprints_module_name" => $config['name'],
"blueprints_module_description" => $config['description'],
    
"blueprint_directory_label" =>
"Thumbnail Directory",

"blueprint_directory_detail" =>
"Enter the path to the directory where your template thumbnails/previews exist.",

"blueprint_template_heading" =>
"Template",

"blueprint_thumbnail_heading" =>
"Thumbnail",

"blueprint_layout_heading" =>
"Publish Layout Name",

"blueprint_channel_heading" =>
"Channel",

"thumbnail_path" =>
"Thumbnails Path",

"thumbnail_path_detail" =>
"Change the path to your thumbnail files. If changed to something other than the default, the settings must be saved and reloaded. Path must be below your web root. Images will be displayed at 200px wide.",

"enable_publish_layout_takeover" =>
"Enable Publish Layout Takeover?",

"enable_template_multi_channel" =>
"Enable assigning templates to multiple Channels?",

"enable_template_multi_channel_detail" => 
"If enabled, you will be able to assign a template, thus a Publish Layout, to more than 1 Channel. By default, if you choose a template that has a Publish Layout attached to it, but in a Channel other than the one the Publish Layout was created in, the Publish Layout will not be loaded. This option allows for such behavior.",

"enable_edit_menu_tweaks" =>
"Enabled Edit Menu Tweaks?",

"enable_edit_menu_tweaks_detail" =>
"If enabled, the Content > Edit menu in the main navigation will directly link to Channels just like the Publish option.<br /><b>The Blueprints Accessory must be installed.</b>",

"enable_carousel" =>
"Enable Template Carousel?",

"enable_carousel_detail" => 
"The Pages or Structure template dropdown menu will be replaced with a carousel of templates with the preview thumbnails defined below.",

"template_display_header" =>
"Visible Templates",

"template_display_detail" =>
"Select which templates you would like to be visible in the Structure Publish Tab. If a channel is not defined, all templates will be displayed in the Structure Publish Tab.",

"enable_publish_layout_takeover_detail" =>
"If disabled, changing templates will not change the Publish Layout. You will only be able to use the template thumbnail preview feature.",


// IGNORE
''=>'');

