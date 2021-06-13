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
  * minnesotawhist.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class MinnesotaWhist extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels( array( 
            "currentHandType" => 10, // High/low
            "trickSuit" => 11,
            "dealer" => 12,
            "team1score" => 13,
            "team2score" => 14,
            "grandPlayer" => 15,
            "team1tricks" => 16,
            "team2tricks" => 17
        ) );        

        $this->cards = self::getNew("module.common.deck");
        $this->cards->init("card");
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "minnesotawhist";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::initializeGameState();
        self::initializeCards();
        self::initializeTeams();
        self::initializeDealer($players);

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here
       

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_team team FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        $result['hand'] = $this->cards->getCardsInLocation('hand', $current_player_id);
        $result['cardsontable'] = $this->cards->getCardsInLocation('cardsontable');

        $bids = array();
        $bids_on_table = $this->cards->getCardsInLocation('bidcards');

        foreach($bids_on_table as $bid_card) {
            $player_id = $bid_card['location_arg'];
            array_push($bids, $player_id);
        }
        $result['bids'] = $bids;
        $result['hand_type'] = $this->getGameStateValue("currentHandType");
        $result['hand_type_text'] = $this->getHandTypeText();
        $result['dealer_player_id']  = $this->getGameStateValue("dealer");

        $scores = $this->getTeamScores();
        $result['team1score'] = $scores['team1score'];
        $result['team2score'] = $scores['team2score'];
        $result['team1tricks'] = $scores['team1tricks'];
        $result['team2tricks'] = $scores['team2tricks'];
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */
    protected function initializeGameState() {
        // Note: hand types: 0 = bidding
        //                   1 = playing low
        //                   2 = playing high
        self::setGameStateInitialValue('currentHandType', 0);
        self::setGameStateInitialValue('trickSuit', 0);
        self::setGameStateInitialValue('team1score', 0);
        self::setGameStateInitialValue('team2score', 0);
        self::setGameStateInitialValue('team1tricks', 0);
        self::setGameStateInitialValue('team2tricks', 0);
    }

    protected function initializeCards() {
        $cards = array();
        foreach($this->suits as $suit_id => $suit) {
            for($value = 2; $value <= 14; $value++) {
                $cards [] = array('type' => $suit_id, 'type_arg' => $value, 'nbr' => 1);
            }
        }

        $this->cards->createCards($cards, 'deck');
    }

    protected function initializeTeams() {
        // TODO: Implement option to change teams.  For now keep it simple.  
        //      Team 1: Players 1 & 3
        //      Team 2: Players 2 & 4

        foreach(array(0,1,2,3) as $player_index) {
            $player_no = $player_index + 1;
            $team_no = ($player_index % 2) + 1;

            $sql = "UPDATE player SET player_team=$team_no WHERE player_no=$player_no";
            self::DbQuery($sql);
        }
    }

    protected function initializeDealer($players) {
        // TODO: Randomize initial dealer?
        $dealer_id = array_keys($players)[0];
        self::setGameStateInitialValue('dealer', $dealer_id);
    }

    public function getPlayerAfter($player_id) {
        $players = self::loadPlayersBasicInfos();
        $player = $players[$player_id];
        $player_no = $player['player_no'];
        $next_player_no = ($player_no % 4) + 1;
        $next_player_id = 0;

        foreach($players as $player_id => $next_player) {
            if ($next_player['player_no'] == $next_player_no)
            {
                $next_player_id = $player_id;
            }
        }

        return $next_player_id;
    }

    public function getPlayerBefore($player_id) {
        $players = self::loadPlayersBasicInfos();
        $player = $players[$player_id];
        $player_no = $player['player_no'];
        $prev_player_no = (($player_no - 1) % 4);
        if ($prev_player_no == 0) $prev_player_no = 4;
        $prev_player_id = 0;

        foreach($players as $player_id => $next_player) {
            if ($next_player['player_no'] == $prev_player_no)
            {
                $prev_player_id = $player_id;
            }
        }

        return $prev_player_id;
    }

    public function getPlayerDirections() {
        $result = array();

        $players = self::loadPlayersBasicInfos();
        $nextPlayer = self::createNextPlayerTable(array_keys($players));

        $current_player_id = self::getCurrentPlayerId();
        $directions = array('S', 'W', 'N', 'E');

        if (!isset($nextPlayer[$current_player_id])) {
            // Spectator mode: take any place for south
            $current_player_id = $nextPlayer[0];
        }

        $current_player_no = $players[$current_player_id]['player_no'];

        foreach($players as $player_id => $player) {
          $player_no = $player['player_no'];

          // Use a bit of math to determine which direction to place the cards
          $dir_index = (4 - ($current_player_no - $player_no)) % 4;
          $dir = $directions[$dir_index];
          $result[$player_id] = $dir;
        }

        return $result;
    }

    public function getTeamScores() {
        return array(
            "team1score" => self::getGameStateValue("team1score"),
            "team2score" => self::getGameStateValue("team2score"),
            "team1tricks" => self::getGameStateValue("team1tricks"),
            "team2tricks" => self::getGameStateValue("team2tricks"),
        );
    }

    public function getHandTypeText() {
        $hand_type = self::getGameStateValue("currentHandType");
         
        $messages = array(
            0 => "Bidding",
            1 => "Playing Low",
            2 => "Playing High"
        );

        return $messages[$hand_type];
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in minnesotawhist.action.php)
    */

    function playCard($card_id) {
        self::checkAction("playCard");
        $player_id = self::getActivePlayerId();

        // TODO: check rules here
        $currentCard = $this->cards->getCard($card_id);

        $currentTrickSuit = self::getGameStateValue('trickSuit');
        if ($currentTrickSuit == 0) {
            self::setGameStateValue('trickSuit', $currentCard['type']);
        }
        else if ($currentTrickSuit != $currentCard['type']) {
            // Does the user have any cards in their hand of the current suit?
            $player_cards = $this->cards->getCardsInLocation("hand", $player_id);
            $valid_card = true;

            foreach($player_cards as $card_id => $card) {
                if ($card['type'] == $currentTrickSuit) {
                    $valid_card = false;
                    break;
                }
            }

            if (!$valid_card) {
                $suit_name = $this->suits[$currentTrickSuit]['name'];
                throw new feException(self::_("You must play a ${suit_name}."), true);
            }
        }

        $this->cards->moveCard($card_id, 'cardsontable', $player_id);

        self::notifyAllPlayers('playCard', clienttranslate('${player_name} plays ${value_displayed} ${suit_displayed}'), 
            array(
                'i18n' =>array('suit_displayed', 'value_displayed'), 
                'card_id' => $card_id, 
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'value' => $currentCard['type_arg'], 
                'value_displayed' => $this->values_label[$currentCard ['type_arg']],
                'suit' => $currentCard['type'],
                'suit_displayed' => $this->suits[$currentCard['type']]['name']
            )
        );

        $this->gamestate->nextState('playCard');
    }

    function playBid($card_id) {
        self::checkAction("playBid");

        $player_id = self::getCurrentPlayerId();

        $card = $this->cards->getCard($card_id);

        if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) {
            throw new feException("That card is not in your hand.");
        }

        $this->cards->moveCard($card_id, 'bidcards', $player_id);

        self::notifyAllPlayers("bidCard", clienttranslate('${player_name} has placed a bid.'), 
            array( 
                'player_id' => $player_id,
                'player_name' => self::getCurrentPlayerName(),
            )
        );

        self::notifyPlayer($player_id, "removeCard", "", 
            array(
                'card_id' => $card_id
            )
        );

        self::giveExtraTime($player_id);
        $this->gamestate->setPlayerNonMultiactive($player_id, "showBids");
    }

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

    function stNewHand() {
        // Take back all cards (from any location => null) to deck
        $this->cards->moveAllCardsInLocation(null, "deck");
        $this->cards->shuffle('deck');
        self::setGameStateValue('team1tricks', 0);
        self::setGameStateValue('team2tricks', 0);

        $players = self::loadPlayersBasicInfos();
        foreach($players as $player_id => $player) {
            $cards = $this->cards->pickCards(13, 'deck', $player_id);
            self::notifyPlayer($player_id, 'newHand', '', array('cards' => $cards));
        }
        $this->gamestate->nextState("");
    }

    function stPlayBid() {
        $this->gamestate->setAllPlayersMultiactive();
    }

    function stShowBids() {
        $bid_cards = array();

        $dealer_id = $this->getGameStateValue('dealer');
        $check_player_id = $dealer_id;
        $play_mode = 1; // Default to low
        $grand_player_id = 0;

        for($i = 0; $i < 4; $i++) {
            $check_player_id = $this->getPlayerAfter($check_player_id);
            $player_bid_cards = $this->cards->getCardsInLocation('bidCards', $check_player_id);
            $bid_card_id = array_keys($player_bid_cards)[0];
            $this->cards->moveCard($bid_card_id, "cardsontable", $check_player_id);
            $card = array_shift($player_bid_cards);
            array_push($bid_cards, $card);

            $suit = $card['type'];

            if ($suit == 1 || $suit == 3) {
                $play_mode = 2;
                $grand_player_id = $check_player_id;
                break;
            }
        }

        $message = '';
        $player_name = '';
        $next_player = '';

        if ($play_mode == 1) {
            $message = 'All players bid low.';
            $next_player = $this->getPlayerAfter($dealer_id);
        }
        else {
            $message = '${player_name} bid high.';
            $players = self::loadPlayersBasicInfos();
            $player_name = $players[$grand_player_id]['player_name'];
            $next_player = $this->getPlayerBefore($grand_player_id);
        }

        $this->gamestate->changeActivePlayer($next_player);

        self::setGameStateValue('currentHandType', $play_mode);
        self::setGameStateValue('grandPlayer', $grand_player_id);

        self::notifyAllPlayers('bidsShown', clienttranslate($message), 
            array(
                'bid_cards' => $bid_cards,
                'player_name' => $player_name,
                'hand_type' => $play_mode,
                'hand_type_text' => clienttranslate($this->getHandTypeText()),
                'grand_player_id' => $grand_player_id
            )
        );
        
        $this->gamestate->nextState();
    }

    function stReturnBids() {
        $bid_cards = $this->cards->getCardsInLocation('cardsontable');
        foreach($bid_cards as $card_id => $card) {
            $player_id = $card['location_arg'];
            $this->cards->moveCard($card_id, 'hand', $player_id);

            self::notifyPlayer($player_id, 'returnCard', "", array('bid_card' => $card));
        }

        $bid_cards = $this->cards->getCardsInLocation('bidcards');
        foreach($bid_cards as $card_id => $card) {
            $player_id = $card['location_arg'];
            $this->cards->moveCard($card_id, 'hand', $player_id);

            self::notifyPlayer($player_id, 'returnCard', "", array('bid_card' => $card));
        }

        self::notifyAllPlayers('clearBids', '', array());

        $this->gamestate->nextState();
    }

    function stNewTrick() {
        self::setGameStateValue('trickSuit', 0);
        $this->gamestate->nextState();
    }

    function stNextPlayer() {
        if ($this->cards->countCardInLocation('cardsontable') == 4) {

            $cards_on_table = $this->cards->getCardsInLocation('cardsontable');
            $best_value=0;
            $best_value_player_id = null; 
            $currentTrickSuit = self::getGameStateValue('trickSuit');
            foreach($cards_on_table as $card) {
                if ($card['type'] == $currentTrickSuit) {
                    if ($best_value_player_id == null || $card['type_arg'] > $best_value) {
                        $best_value_player_id = $card['location_arg'];
                        $best_value = $card['type_arg'];
                    }
                }
            }

            $this->gamestate->changeActivePlayer($best_value_player_id);

            $sql = "SELECT player_id, player_name, player_team FROM player";
            $players = self::getCollectionFromDb( $sql );

            $team = $players[$best_value_player_id]['player_team'];

            $team_tricks_label = "team${team}tricks";
            $team_tricks = $this->getGameStateValue($team_tricks_label) + 1;
            $this->setGameStateValue($team_tricks_label, $team_tricks);

            $this->cards->moveAllCardsInLocation('cardsontable', 'cardswon', null, $best_value_player_id);

            self::notifyAllPlayers('trickWin', clienttranslate('${player_name} wins the trick'), array(
                'player_id' => $best_value_player_id,
                'player_name' => $players[$best_value_player_id]['player_name'],
                'team' => $team
            ));
            self::notifyAllPlayers('giveAllCardsToPlayer', '', array(
                'player_id' => $best_value_player_id
            ));

            if ($this->cards->countCardInLocation('hand') == 0) {
                $this->gamestate->nextState("endHand");
            }
            else {
                $this->gamestate->nextState("nextTrick");
            }
            self::giveExtraTime($best_value_player_id);
        }
        else {
            $player_id = self::activeNextPlayer();
            self::giveExtraTime($player_id);
            $this->gamestate->nextState('nextPlayer');
        }
    }

    function stEndHand() {
        $team1_tricks = $this->getGameStateValue("team1tricks");
        $team2_tricks = $this->getGameStateValue("team2tricks");
        $play_mode = $this->getGameStateValue("currentHandType");
        $grand_player_id = $this->getGameStateValue("grandPlayer");

        $sql = "SELECT player_id, player_name, player_score, player_team FROM player";
        $players = self::getCollectionFromDb( $sql );

        $scoring_team = 0;
        $points = max($team1_tricks, $team2_tricks)- 6;

        if ($play_mode == 1) {
            $scoring_team = $team1_tricks > $team2_tricks ? 2 : 1;
        }
        else if ($play_mode == 2) {
            $scoring_team = $team1_tricks > $team2_tricks ? 1 : 2;
            $grand_team = $players[$grand_player_id]['player_team'];

            // Double points if the team that granded did not take the points
            if ($grand_team != $scoring_team) $points *= 2;
        }

        // Update game state scores
        $scoring_team_score_label = "team${scoring_team}score";
        $scoring_team_score = $this->getGameStateValue($scoring_team_score_label);
        $scoring_team_score += $points;
        $this->setGameStateValue($scoring_team_score_label, $scoring_team_score);

        // Update individual player scores
        foreach($players as $player_id => $player) {
            if ($player['player_team'] == $scoring_team) {
                $sql = "UPDATE player SET player_score = player_score + $points WHERE player_id='$player_id'";
                self::DbQuery($sql);
            }
        }

        $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true);
        self::notifyAllPlayers("newScores", clienttranslate('Team ${scoring_team} scored ${points} points.'), 
            array(
                'newScores' => $newScores,
                'scoring_team' => $scoring_team,
                'points' => $points
            )
        );

        // Check if this is the end of the game
        if ($scoring_team_score >= 13) {
            $this->gamestate->nextState("endGame");
            return;
        }

        // Move dealer status to next player
        $dealer_id = $this->getGameStateValue("dealer");
        $next_dealer_id = $this->getPlayerAfter($dealer_id);
        $this->setGameStateValue("dealer", $next_dealer_id);
        
        $this->gamestate->nextState('nextHand');
    }



//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//
    }    
}
