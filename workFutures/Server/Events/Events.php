<?php
/**
 * Events
 * 延时事件
 */
namespace Server\Events;

abstract class Events{
    
    public $triggerTime = null;
    
    public function __construct($time){
        $this->triggerTime = $time;
    }

    public function trigger($time){
        
        if ($this->triggerTime<=$time){
            $this->doTrigger($time);
            return true;
        }
        return false;
    }
    
    abstract public function doTrigger($time);      //必须在子类中实现
}