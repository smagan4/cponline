<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * heartsla implementation : © <Your name here> <Your email address here>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * heartsla game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

/*
$this->colors = array(
    1 => array( 'name' => clienttranslate('♠'),
                'nametr' => self::_('♠') ),
    2 => array( 'name' => clienttranslate('♥'),
                'nametr' => self::_('♥') ),
    3 => array( 'name' => clienttranslate('♣'),
                'nametr' => self::_('♣') ),
    4 => array( 'name' => clienttranslate('♦'),
                'nametr' => self::_('♦') )
);
*/

$this->colors = array(
    1 => array( 'name' => '<span class="spade-color">' . clienttranslate('♠') . '</span>',
                'nametr' => self::_('♠') ),
    2 => array( 'name' => '<span class="heart-color">' . clienttranslate('♥') . '</span>',
                'nametr' => self::_('♥') ),
    3 => array( 'name' => '<span class="club-color">' . clienttranslate('♣') . '</span>',
                'nametr' => self::_('♣') ),
    4 => array( 'name' => '<span class="diamond-color">' . clienttranslate('♦') . '</span>',
                'nametr' => self::_('♦') )
);

$this->card_count_label = [
    0 => '',
    1 => '',
    2 => clienttranslate('pair'),
    3 => clienttranslate('triple'),
    4 => clienttranslate('4 of a kind'),
    5 => clienttranslate('5 card hand'),
];

$this->values_label = array(
    2 =>'2',
    3 => '3',
    4 => '4',
    5 => '5',
    6 => '6',
    7 => '7',
    8 => '8',
    9 => '9',
    10 => '10',
    11 => clienttranslate('J'),
    12 => clienttranslate('Q'),
    13 => clienttranslate('K'),
    14 => clienttranslate('A')
);