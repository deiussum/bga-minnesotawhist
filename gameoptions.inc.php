<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MinnesotaWhist implementation : © Daniel Jenkins <deiussum@gmail.com>
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
    ),
    101 => array(
                'name' => totranslate('Use No Ace, No Face, No Play rule.'),
                'values' => array(
                            1 => array('name' => totranslate('Yes')),
                            2 => array('name' => totranslate('No'))
                ),
                'default' => 1
    )

);

$game_preferences = array(
    100 => array(
        'name' => totranslate('Card sprites'),
        'needReload' => true,
        'values' => array(
            1 => array('name' => totranslate('Design - 4-color'), 'cssPref' => 'cards_design_4color'),
            2 => array('name' => totranslate('Design - Classic'), 'cssPref' => 'cards_design_classic'),
            3 => array('name' => totranslate('Traditional - 4-color'), 'cssPref' => 'cards_traditional_4color'),
            4 => array('name' => totranslate('Traditional - Classic'), 'cssPref' => 'cards_traditional_classic')

        ),
        'default' => 4
    ),
    101 => array(
        'name' => totranslate('Auto play'),
        'needReload' => false,
        'values' => array(
            0 => array('name' => totranslate('Off')),
            1 => array('name' => totranslate('Auto play when there is only 1 playable card'))
        ),
        'default' => 1
    )
);


