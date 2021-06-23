<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MinnesotaWhist implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * gameoptions.inc.php
 *
 * MinnesotaWhist game options description
 * 
 * In this file, you can define your game options (= game variants).
 *   
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in minnesotawhist.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(

    
    // note: game variant ID should start at 100 (ie: 100, 101, 102, ...). The maximum is 199.
    100 => array(
                'name' => totranslate('Teams'),    
                'values' => array(
                            1 => array( 'name' => totranslate('Random')),
                            2 => array( 'name' => totranslate('1st/3rd vs 2nd/4th')),
                            3 => array( 'name' => totranslate('1st/2nd vs 3rd/4th')),
                            4 => array( 'name' => totranslate('1st/4th vs 2nd/3rd'))
                        ),
                'default' => 1
            )

);


