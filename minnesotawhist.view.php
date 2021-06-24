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
 * minnesotawhist.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in minnesotawhist_minnesotawhist.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  
  class view_minnesotawhist_minnesotawhist extends game_view
  {
    function getGameName() {
        return "minnesotawhist";
    }    
  	function build_page( $viewArgs )
  	{		
  	    // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/

        $template = self::getGameName() . "_" . self::getGameName();

        $player_directions = $this->game->getPlayerDirections();

        // this will inflate our player block with actual players data
        $this->page->begin_block($template, "player");
        foreach($players as $player_id => $info) {
          $dir = $player_directions[$player_id];
          $this->page->insert_block("player", array(
            "PLAYER_ID" => $player_id,
            "PLAYER_NAME"  => $players[$player_id]['player_name'],
            "PLAYER_COLOR"  => $players[$player_id]['player_color'],
            "DIR" => $dir
          ));
        }
        $this->tpl['MY_HAND'] = self::_("My hand");

        $scores = $this->game->getTeamScores();
        $this->tpl['TEAM1SCORE'] = $scores['team1score'];
        $this->tpl['TEAM2SCORE'] = $scores['team2score'];
        $this->tpl['TEAM1TRICKS'] = $scores['team1tricks'];
        $this->tpl['TEAM2TRICKS'] = $scores['team2tricks'];

        $labels = $this->game->getTeamLabels();
        $this->tpl['TEAM1LABEL'] = $labels['team1label'];
        $this->tpl['TEAM2LABEL'] = $labels['team2label'];
        $this->tpl['TEAM1CLASS'] = 'team-' . strtolower($labels['team1label']);
        $this->tpl['TEAM2CLASS'] = 'team-' . strtolower($labels['team2label']);

        // Fix up for spectator
        if ($labels['team1label'] == "Team 1") {
          $this->tpl['TEAM1CLASS'] = 'team-us';
        }
        if ($labels['team2label'] == "Team 2") {
          $this->tpl['TEAM2CLASS'] = 'team-them';
        }


        /*********** Do not change anything below this line  ************/
  	}
  }
  

