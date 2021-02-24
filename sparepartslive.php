<?php

/**
 * @package sparepartslive
 * /
/*
Plugin Name: Spareparts.Live
Plugin URI: https://spareparts.live/plugins 
Description: Extends your Wordpress with visual navigation layer for spare parts
Version: 1.0.0
Author: Lynkworx 
Author URI: https://spareparts.one 
License: GPLv2 or later
Text Domain: spareparts.live 
*/

if(!defined('ABSPATH')) {
    die;
}
 
class SPLPlugin
{
    public $plugin; 
    public $token = '';
    public $showTab = true;
    public $lateTab = false;
    public $configFile = '';
    
    function __construct() {
        $this->plugin = plugin_basename(__FILE__);
        $this->configFile = plugin_dir_path(__FILE__).'/splconfig.json';
        // Add Save button action 
        add_action('admin_post_spl_save_config', array($this, 'SaveConfig'));
        add_action('admin_post_nopriv_spl_save_config', array($this, 'SaveConfig'));
        
    }

    function RegisterPlugin() {
        // Add configuration page
        add_action('admin_menu', array($this, 'add_admin_pages'));
		
        // Add "Spareparts.Live" to Wordpress Admin Sidebar
        add_filter('plugin_action_links_'.$this->plugin,  array($this, 'settings_link'));
    }
    
    public function settings_link($links) {
        $settings_link = '<a href="options-general.php?page=sparepartslive_plugin">Settings</a>';
        array_push($links, $settings_link);
        return $links;
    }
    
    public function add_admin_pages() {
		add_options_page('Spareparts.Live Plugin', 'Spareparts.Live', 'manage_options', 'sparepartslive_plugin', array($this, 'admin_index'), 'dashicons-admin-tools', 80);
    }
    
    public function admin_index() {
        require_once plugin_dir_path(__FILE__).'/visiblepage.php';
    }
    
    function ActivatePlugin() {
        flush_rewrite_rules();
        
    }
    
    function DeactivatePlugin() {
        flush_rewrite_rules();
        $this->RemoveLayerScriptTag();
    }


    function SaveConfig() {
        // Save config as JSON and update the header.php of the current theme
        $this->token = trim($_POST['token']);
        $this->showTab = isset($_POST['showtab']) && $_POST['showtab'] == 'on';
        $this->lateTab = isset($_POST['latetab']) && $_POST['latetab'] == 'on';
        file_put_contents($this->configFile, json_encode(['token' => $this->token,
                                                        'showtab' => $this->showTab,
                                                        'latetab' => $this->lateTab]));
    

        $this->UpdateLayerScriptTag();        
        
        // Now redirect to the config page again
        wp_redirect(admin_url('admin.php?page=sparepartslive_plugin'));
        die();
    }

    function UpdateLayerScriptTag() {
        $scriptTag = '<script src="https://layer.spareparts.live/layer.js" data-token="'.$this->token.'"></script>';
		
        // $scriptTag = '<script src="https://layer.spareparts.live/layer.js" data-token="'.$this->token.'"'.($this->showTab ? '' : ' data-nohandle="true"').($this->lateTab ? ' data-latehandle="true"' : '').'></script>';
        $headerFile = get_template_directory().'/header.php';
        if(file_exists($headerFile)) {
            // Header.php exists. Load it
            $header = file_get_contents($headerFile);
            // Search for script tag 
            $p1 = $this->FindStartOfLayerScriptTag($header);
            if($p1 == -1) {
                // Layer script not in there yet. Look for </head>
                $p1 = strpos($header, '</head>');
                if($p1 !== false) {
                    // Found </head>. Insert script tag before it 
                    $header = substr($header, 0, $p1).$scriptTag.substr($header, $p1);
                    file_put_contents($headerFile, $header);
                    
                }
            } else {
                // Layer script found -> Find end of tag and replace 
                $p2 = $this->FindEndOfLayerScriptTag($header, $p1);
                if($p2 != -1) {
                    $header = substr($header, 0, $p1).$scriptTag.substr($header, $p2 + 1);
                    file_put_contents($headerFile, $header);
                }
            }
        }

    }    
    
    
    public function LoadConfig() {
        // Loads the config from either the layer (if available), if not then from the local splconfig.json file (if available)
        $headerFile = get_template_directory().'/header.php';
        if(file_exists($headerFile)) {
            $header = file_get_contents($headerFile);
            $p1 = $this->FindStartOfLayerScriptTag($header);
            if($p1 != -1) {
                // Found layer script tag
                $p2 = $this->FindEndOfLayerScriptTag($header, $p1);
                if($p2 != -1) {
                    // There is an end of <script> tag
                    // Find data-token
                    $p3 = strpos($header, 'data-token="', $p1);
                    if($p3 !== false && $p3 > $p1 && $p3 < $p2) {
                        $this->token = substr($header, $p3 + 12, 16);
                    }

                    // Find data-nohandle
                    $p3 = strpos($header, 'data-nohandle="true"', $p1);
                    $this->showTab = !($p3 !== false && $p3 > $p1 && $p3 < $p2);

                    // Find data-latehandle
                    $p3 = strpos($header, 'data-latehandle="true"', $p1);
                    $this->lateTab = $p3 !== false && $p3 > $p1 && $p3 < $p2;

                }
            } else {
                // Did not find layer script tag. Try to read it from the config file instead.
                // If found from the config file: Apply to layer script + set for configuration
                if(file_exists($this->configFile)) {
                    $config = json_decode(file_get_contents($this->configFile));
                    $this->token = $config->token;
                    $this->showTab = $config->showtab;
                    $this->lateTab = $config->latetab;
                    $this->UpdateLayerScriptTag();
                }
            }
        }
    }
    
    function FindStartOfLayerScriptTag($header) {
        // Returns position of the layer script tag (or -1 if not found)
        $result = strpos($header, '<script src="https://layer.spareparts.live');
        return $result !== false ? $result : -1;
    }
    
    function FindEndOfLayerScriptTag($header, $start) {
        // Returns position of end of script tag, given start position: "</script>" or "/>", whichever is first (or -1 if not found)
        $result = -1;
        $p2a = strpos($header, '</script>', $start);
        $p2b = strpos($header, '/>', $start);
        if($p2a === false && $p2b !== false) return $p2b + 1; 
        else if($p2a !== false && $p2b === false) return $p2a + 8;
        else if($p2a !== false && $p2b !== false) return min($p2a + 8, $p2b + 1);
        return -1;
    }
    
    function RemoveLayerScriptTag() {
        // Removes the script tag from the header.php of the current theme
        $headerFile = get_template_directory().'/header.php';
        if(file_exists($headerFile)) {
            // Header.php exists. Load it
            $header = file_get_contents($headerFile);
            // Search for script tag 
            $p1 = $this->FindStartOfLayerScriptTag($header);
            if($p1 != -1) {
                // Layer script found -> Find end of tag and replace 
                $p2 = $this->FindEndOfLayerScriptTag($header, $p1);
                if($p2 != -1) {
                    $header = substr($header, 0, $p1).substr($header, $p2 + 1);
                    file_put_contents($headerFile, $header);
                }
            }
        }
    }
    

}

$splPlugin = new SPLPlugin();
$splPlugin->RegisterPlugin();
$splPlugin->LoadConfig();

// Activation 
register_activation_hook(__FILE__, array($splPlugin, 'ActivatePlugin'));

// Deactivation 
register_deactivation_hook(__FILE__, array($splPlugin, 'DeactivatePlugin'));



