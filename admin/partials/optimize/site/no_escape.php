<?php

defined('ABSPATH') || exit;

if (!class_exists('Npcink_Toolbox_No_Escape')) {
    class Npcink_Toolbox_No_Escape implements Npcink_Toolbox_Module_Interface
    {
        public static function run($config = array())
        {
            add_filter('document_title_parts', array(__CLASS__, 'disable_title_escaping'), 99);
        }

        public static function disable_title_escaping($title)
        {
            foreach ($title as $key => $value) {
                $title[ $key ] = wp_specialchars_decode($value, ENT_QUOTES);
            }
            return $title;
        }
    }
}
