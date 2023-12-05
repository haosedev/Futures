<?php 
/**
 *
 */
namespace Server;

class Types{
    
    public static $typesToString = array(
            'Messages' => array(
                0 => 'SYSTEM',
                1 => 'HELLO',
                2 => 'WELCOME',
                3 => 'MESSAGE',
                
                10 => 'OFFER',
            ),
            
    );
    public static function getMessageTypeAsString($type){
        return isset(self::$typesToString['Messages'][$type]) ? self::$typesToString['Messages'][$type] : 'UNKNOWN';
    }

}
