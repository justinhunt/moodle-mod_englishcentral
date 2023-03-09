<?php
/**
 * Services definition.
 *
 * @package mod_wordcards
 * @author  Frédéric Massart - FMCorz.net
 */

$functions = array(

    'mod_englishcentral_add_video' => array(
        'classname'   => 'mod_englishcentral_external',
        'methodname'  => 'add_video',
        'description' => 'Add a video to the activity',
        'capabilities'=> 'mod/englishcentral:manage',
        'type'        => 'write',
        'ajax'        => true,
    )
);
