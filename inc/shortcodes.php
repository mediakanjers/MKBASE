<?php
    /* [mk_theme_module folder="openingstijden" file="vandaag"] */
    function get_mk_module_theme( $atts ) {
        $atts = shortcode_atts(
            array(
                'folder' => '',
                'file' => '',
                'cat' => '',
                'id' => '',
            ),
            $atts
        );

        if( $atts['cat'] != "") {
            global $mk_theme_module_categorie;
            $mk_theme_module_categorie = ""; 
            $mk_theme_module_categorie = $atts['cat'];
        } if( $atts['id'] != "") {
            global $mk_theme_module_id;
            $mk_theme_module_id = "";
            $mk_theme_module_id = $atts['id'];
        }
        
        if( $atts['folder'] != "" && $atts['file'] != "")  {
            if (file_exists( get_stylesheet_directory(). '/template-parts/'. $atts['folder'] .'/'. $atts['file'] . ".php" ))  {
                ob_start();
                include( get_stylesheet_directory(). '/template-parts/'. $atts['folder'] .'/'. $atts['file'] . ".php");
                return ob_get_clean();
            }
        }
    }
    add_shortcode( 'mk_theme_module', 'get_mk_module_theme' );
?>