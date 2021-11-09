<?php
/*
Plugin Name: Debug Helper
Plugin URI: https://github.com/jjaureguiberrry/wordpress-debug-helper
Description: Print and log debug data from your code in production environment without affecting end user experience.
Version: 1.1.0
Author: Jose Jaureguiberry
Author URI: https://github.com/jjaureguiberrry
License: GNU General Public License (Version 3 - GPLv3)
*/

/*
 * USAGE:
 * Use the following actions in your code where you want to add the debug trace:
 * For logging into a file: do_action('debugger_write_log', $message, $identifier, $print_stack);
 * For printing to screen: do_action('debugger_var_dump', $message, $identifier, $print_stack, $die);
 */
if(! class_exists('Custom_Debugger') ):

    class Custom_Debugger{

        function __construct(){
            add_action( 'debugger_write_log', array ( 'Custom_Debugger', 'write_log' ), 10, 4);
            add_action( 'init', array ($this, 'test_var_dump'));

            if( !empty($_REQUEST['debug_mode'])){
                define('SCRIPT_DEBUG', true);
                add_action( 'debugger_var_dump', array ( 'Custom_Debugger', 'display_var_dump' ), 10, 4);

                if( !empty($_REQUEST['filter_plugins'])) {
                    add_filter('option_active_plugins', array($this, 'filter_active_plugins'));
                    add_filter('site_option_active_sitewide_plugins', array($this, 'filter_network_active_plugins'));
                }

                if( !empty($_REQUEST['filter_theme'])) {
                    add_filter( 'template', array($this, 'filter_template'));
                    add_filter( 'stylesheet', array($this, 'filter_stylesheet'));
                    add_filter( 'template_directory', array($this, 'filter_template_directory'), 999, 3);
                }


            }

            if( !empty($_REQUEST['search_in_folder'])){
                $search_arguments = explode('|', $_REQUEST['search_in_folder']);
                $folder = $search_arguments[0];
                $term = $search_arguments[1];
                $mode = null;
                echo '<h4>Searching for: "'.$term.'"</h4>';
                if (!empty($search_arguments[2])){
                    $mode = $search_arguments[2];
                    echo '<h4> VERBOSE MODE ON </h4>';
                } else {
                    echo '<h4> VERBOSE MODE OFF; ONLY MATCHING CASES WILL BE DISPLAYED </h4>';
                }
                if( !empty($folder) && !empty($term)){
                    $this->search_in_folder($this->resolve_path($folder), $term, $mode);
                    echo '<h4>Matching cases found: "'.$counter.'"</h4>';
                    die();
                }

            }
        }

        function filter_template_directory($template_dir, $template, $theme_root){
            if( !defined('DOING_AJAX')){
                self::display_var_dump($template_dir,'Active Template Directory',0,0);
            }

            return $template_dir;
        }

        function filter_template($active_template){
            if( !defined('DOING_AJAX')){
                self::display_var_dump($active_template,'Active Template',0,0);
            }
            $active_template = 'twentyfifteen';

            return $active_template;
        }

        function filter_stylesheet($active_stylesheet){
            if( !defined('DOING_AJAX')){
                self::display_var_dump($active_stylesheet,'Active Stylesheet',0,0);
            }
            $active_stylesheet = 'twentyfifteen';

            return $active_stylesheet;
        }

        function filter_active_plugins($active_plugins){
            if( !defined('DOING_AJAX')){
                self::display_var_dump($active_plugins,'Active Plugins',0,0);
            }
            // Change the following array to define which plugin will be active.
            $active_plugins = array(
                //'custom-sidebars/customsidebars.php',
                //'gravityforms/gravityforms.php'
            );

            if( !defined('DOING_AJAX')){
                self::display_var_dump($active_plugins,'Filtered Active Plugins',0,0);
            }
            return $active_plugins;
        }

        function filter_network_active_plugins($active_plugins){
            if( !defined('DOING_AJAX')){
                self::display_var_dump($active_plugins,'Network Active Plugins',0,0);
            }
            return $active_plugins;
        }

        static function write_log ( $message, $identifier = 'Custom Debugger', $print_stack = 0, $output_filename = null )  {
            $location = __DIR__ . '/my-errors.log';
            if( null !== $output_filename && !empty($output_filename) ){
                $location = __DIR__ . '/' . sanitize_file_name($output_filename) . '.log';
            }
            do_action('debugger_pre_write_log', $message, $identifier, $print_stack);

            error_log( PHP_EOL . date('Y-M-d H:i:s') . ': ', 3, $location);
            error_log($identifier . ': ', 3, $location);
            if ( is_array( $message ) || is_object( $message ) ) {
                error_log( print_r( $message, true ), 3, $location );
            } else {
                error_log( $message, 3, $location );
            }
            if($print_stack > 0){
                error_log( print_r( debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $print_stack), true ) );
            }
        }

        static function display_var_dump ( $message, $identifier = 'Custom Debugger', $print_stack = 0, $die = 0 )  {
            do_action('debugger_pre_var_dump', $message, $identifier, $print_stack);
            $time_start = microtime(false);
            echo $time_start . ' - ' . $identifier . ':';
            echo '<pre>';
            print_r($message);
            echo '</pre>';
            if($die){
                die();
            }
        }

        function test_var_dump(){
            do_action('debugger_var_dump', 'Enabled', 'DEBUG_MODE', 0, 0);
        }

        /*
        * Search for a $term into every file in $folder and subfolders
        *
        * When $mode = 'verbose' it will print every search, when $mode != 'verbose'
        * it will only print marching cases
        * 
        * @param $folder Folder to search into
        * @param $term Term or expresion to look for
        * @param $mode Only verbose mode is available
        *
        * @since 1.1.0
        */
        function search_in_folder($folder, $term, $mode){
            if ($mode=='verbose'){echo'<h4>Searching in:'.$folder.'</h4>';}
            $string = $term;
            $dir = new DirectoryIterator($folder);
            foreach ($dir as $fileInfo) {
                if($fileInfo->isFile()){
                    $content = file_get_contents($fileInfo->getPathname());
                    if (strpos($content, $string) !== false) {
                        echo'<h4>Found in:'.$fileInfo->getPathname().'</h4><pre>';
                        var_dump($fileInfo->getFilename());
                        echo'</pre>';
                    }
                } else if(!$fileInfo->isDot()){
                    if($fileInfo->isDir()){
                        $this->search_in_folder($fileInfo->getPathname(), $term,$mode);
                    }
                }

            }
            
        }
        
        /*
        * Resolve a valid URL.
        *
        * Returns the absolute URL by merging the current working directory (cwd)
        * and the provided directory $dir.
        * It will die() if there is no matching folder between both URLs.
        * 
        * @param $dir Folder to search into
        *
        * @since 1.1.0
        */
        function resolve_path($dir){
            if ($dir[0]=='/'){
                $arr_dir = explode('/',substr($dir,1));
            } else $arr_dir = explode('/',$dir);
            $arr_cwd = explode('/', getcwd());
            $i=sizeof($arr_cwd)-1;
            while (!($arr_cwd[$i]==$arr_dir[0])&& $i>0){
                $i--;
            }
            if ($i==0){
                echo '<h4>Could not resolve URL</h4><pre>';
                die();
            }
            return implode('/',array_merge(array_slice($arr_cwd,0,$i),$arr_dir));
        }

    }
    new Custom_Debugger;

endif;