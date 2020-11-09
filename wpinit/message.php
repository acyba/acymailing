<?php

namespace AcyMailing\Init;

class acyMessage extends acyHook
{
    public function __construct()
    {
        if (!defined('WP_ADMIN') || !WP_ADMIN) {
            add_action('wp_footer', [$this, 'frontMessages']);
        }
    }

    public function frontMessages()
    {
        $sessionID = session_id();
        if (empty($sessionID)) @session_start();

        $output = '';
        $types = ['success', 'info', 'warning', 'error'];
        foreach ($types as $type) {
            if (empty($_SESSION['acymessage'.$type])) continue;

            $messages = $_SESSION['acymessage'.$type];
            if (!is_array($messages)) {
                $messages = [$messages];
            }

            $output .= '<div class="acym_callout acym__callout__front__'.$type.'">'.implode(' ', $messages).'<div class="acym_callout_close">x</div></div>';

            unset($_SESSION['acymessage'.$type]);
        }

        if (empty($output)) return;

        echo '<div id="acym__callout__container">'.$output.'</div>';

        $script = '
        function setCallouts(){
            var callouts = document.getElementsByClassName("acym_callout");
            
            for(var i = 0; i < callouts.length; i++){
                var callout = callouts[i];
                var calloutClose = callout.getElementsByClassName("acym_callout_close")[0];
                
                displayCallout(callout, i);
                
                calloutClose.onclick = function(event){
                    var eventElement = event.target;
                    var eventCallout = eventElement.closest(".acym_callout");
 
                    closeCallout(eventCallout);
                }
            }
        }
        setCallouts();
        
        function closeCallout(callout){
            callout.style["margin-left"] = "640px";
            callout.style["margin-right"] = "-640px";
            setTimeout(function(){ callout.remove() }, 1000);
        }
        
        function displayCallout(callout, i){
            setTimeout(function(){
                callout.style["margin-left"] = "0px";
                callout.style["margin-right"] = "0px";
            }, 1000 * i);         
        }';


        echo '<script type="text/javascript">'.$script.'</script>';
        echo '<link type="text/css" rel="stylesheet" href="'.ACYM_CSS.'front/messages.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'front'.DS.'messages.min.css').'">';
    }
}

$acyMessage = new acyMessage();
