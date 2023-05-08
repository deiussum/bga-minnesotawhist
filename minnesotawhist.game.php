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
    const TEAM_RANDOM = 1;
    const TEAM_1_3 = 2;
    const TEAM_1_2 = 3;
    const TEAM_1_4 = 4;

    const SPADES = 1;
    const HEARTS = 2;
    const CLUBS = 3;
    const DIAMONDS = 4;

    const BIDDING = 0;
    const PLAYING_LOW = 1;
    const PLAYING_HIGH = 2;

    const AUTO_PLAY = 101;
    const AUTO_PLAY_OFF = 0;
    const AUTO_PLAY_ON = 1;

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
            "team2tricks" => 17,
            "teamOptions" => 100,
            "noAceNoFaceOption" => 101
        ) );        

        $this->cards = self::getNew("module.common.deck");
        $this->cards->init("card");
        $this->useNoAceNoFace = true;
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


        $initialPlayerOrder = $this->getInitialPlayerOrder($players);
        $playerOrder = $this->getPlayerOrder();
        $playerColors = array("ff0000", "008000");
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, player_team, player_no, player_autoplay_pref) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $playerNumber = $playerOrder[$initialPlayerOrder[$player_id]];
            $color = $playerColors[$playerNumber % 2];
            $playerTeam = (($playerNumber - 1) % 2) + 1;

            $auto_play = self::AUTO_PLAY_ON;

            $playerValues = array(
                $player_id,
                "'$color'",
                '\'' . $player['player_canal'] . '\'',
                '\'' . addslashes( $player['player_name'] ) . '\'',
                '\'' . addslashes( $player['player_avatar'] ) . '\'',
                $playerTeam,
                $playerNumber,
                $auto_play
            );

            $values[] = '(' . implode($playerValues, ',') . ')';
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::initializeGameState();
        self::initializeCards();
        self::initializeDealer($players);
		$this->useNoAceNoFace = self::getGameStateValue('noAceNoFaceOption'); 

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        self::initStat('table', 'hands_played', 0);
        self::initStat('player', 'tricks_taken', 0);
        self::initStat('player', 'high_bids', 0);
        self::initStat('player', 'low_bids', 0);
        self::initStat('player', 'noace_noface', 0);
        self::initStat('player', 'was_skunked', 0);
        self::initStat('player', 'skunked_other_team', 0);
        self::initStat('player', 'perfect_hand', 0);

        // setup the initial game situation here
       

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

        $players = self::getCollectionFromDb( $sql );
        $result['players'] = $players;
  
        $result['hand'] = $this->cards->getCardsInLocation('hand', $current_player_id);
        $result['cardsontable'] = $this->cards->getCardsInLocation('cardsontable');
        $result['selected_card_id'] = self::getSelectedCard($current_player_id);

        $result['hand_type'] = $this->getGameStateValue("currentHandType");

        $bids = array();

        if ($result['hand_type'] == self::BIDDING) {
            $bids_on_table = self::getAllSelectedCards();

            foreach($bids_on_table as $bid_card) {
                $player_id = $bid_card['location_arg'];
                array_push($bids, $player_id);
            }
        }

        $result['bids'] = $bids;
        $result['dealer_player_id']  = $this->getGameStateValue("dealer");
        $result['grand_player_id']  = $this->getGameStateValue("grandPlayer");

        // Fix potential issue where bidding didn't reset trickSuit in running game.
        if ($result['hand_type'] == self::BIDDING) self::setGameStateValue('trickSuit', 0);
        $result['current_suit'] = $this->getGameStateValue("trickSuit");

        $scores = $this->getTeamScores();
        $result['team1score'] = $scores['team1score'];
        $result['team2score'] = $scores['team2score'];
        $result['team1tricks'] = $scores['team1tricks'];
        $result['team2tricks'] = $scores['team2tricks'];

        if (array_key_exists($current_player_id, $players)) {
            $current_player_team = $players[$current_player_id]['team'];
        }
        else {
            // Spectator
            $current_player_team = 0;
        }

        if ($current_player_team == 1) {
            $result['team1label'] = clienttranslate('Us');
            $result['team2label'] = clienttranslate('Them');
        }
        else if ($current_player_team == 2) {
            $result['team1label'] = clienttranslate('Them');
            $result['team2label'] = clienttranslate('Us');
        }
        else {
            $result['team1label'] = clienttranslate('Team 1');
            $result['team2label'] = clienttranslate('Team 2');
        }

        $result['noace_noface'] = $this->canClaimNoAceNoFace($current_player_id);
  
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
        $scores = $this->getTeamScores();
        $team1score = $scores['team1score'];
        $team2score = $scores['team2score'];
        $highscore = max($team1score, $team2score);

        return ($highscore / 13) * 100;
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
        self::setGameStateInitialValue('currentHandType', self::BIDDING);
        self::setGameStateInitialValue('trickSuit', 0);
        self::setGameStateInitialValue('team1score', 0);
        self::setGameStateInitialValue('team2score', 0);
        self::setGameStateInitialValue('team1tricks', 0);
        self::setGameStateInitialValue('team2tricks', 0);
    }

    // NOTE: Abandoning this as it causes a warning due to it adding more players than BGA knows about.
    //       Keeping the function here for now in case I want to play with a better method later.
    // protected function fillInZombiePlayers($players) {
    //     $zombie_id_start = 1; // TODO: Find an appropriate player_id for a zombie?

    //     $player_count = count($players);
    //     for ($player_no = $player_count; $player_no < 4; $player_no++) {
    //         $zombie_no = $player_no - $player_count + 1;
    //         $player_id = $zombie_id_start + $player_no;
    //         $zombie = array(
    //             "player_name" => "Zombie #$zombie_no",
    //             "player_no" => $player_no + 1,
    //             "is_zombie" => 1,
    //             "player_table_order" => $player_no + 1,
    //             "player_canal" => '',
    //             "player_avatar" => ''
    //         );

    //         $players[$player_id] = $zombie;
    //     }

    //     return $players;
    // }

    protected function getInitialPlayerOrder($players) {
        // Retrieve inital player order ([0=>playerId1, 1=>playerId2, ...])
		$playerInitialOrder = [];
		foreach ($players as $playerId => $player) {
			$playerInitialOrder[$player['player_table_order']] = $playerId;
		}
		ksort($playerInitialOrder);
		$playerInitialOrder = array_flip(array_values($playerInitialOrder));

        return $playerInitialOrder;
    }

    protected function getPlayerOrder() {
		// Player order based on 'playerTeams' option
		$playerOrder = [1, 2, 3, 4];
		switch (self::getGameStateValue('teamOptions')) {
			case self::TEAM_1_2:
				$playerOrder = [1, 3, 2, 4];
				break;
			case self::TEAM_1_4:
				$playerOrder = [1, 2, 4, 3];
				break;
			case self::TEAM_RANDOM:
				shuffle($playerOrder);
				break;
			case self::TEAM_1_3:
    
				break;
		}

        return $playerOrder;
    }

    protected function initializeCards() {
        $cards = array();
        foreach($this->suits as $suit_id => $suit) {
            for($value = 2; $value <= 14; $value++) {
                $cards [] = array('type' => $suit_id, 'type_arg' => $value, 'nbr' => 1, 'selected' => false);
            }
        }

        $this->cards->createCards($cards, 'deck');
    }

    public function getCardFromHand($player_id, $card_id) {
        $currentCard = $this->cards->getCard($card_id);

        if ($currentCard['location'] != 'hand' || $currentCard['location_arg'] != $player_id) {
            throw new feException("That card is not in your hand.");
        }

        return $currentCard;
    }

    public function selectCardForPlayer($player_id, $card_id) {
        $card = self::getCardFromHand($player_id, $card_id);

        self::clearSelectedCardForPlayer($player_id);

        $sql = "UPDATE card set card_selected = true WHERE card_location='hand' AND card_location_arg='$player_id' and card_id='$card_id'";
        self::DbQuery($sql);
    }

    public function clearSelectedCardForPlayer($player_id)
    {
        $sql = "UPDATE card set card_selected = false WHERE card_location='hand' AND card_location_arg='$player_id'";
        self::DbQuery($sql);
    }

    public function getSelectedCard($player_id) {
        $sql = "SELECT card_id FROM card WHERE card_location='hand' AND card_location_arg='$player_id' and card_selected = true";
        return self::getUniqueValueFromDB( $sql );
    }

    public function getAllSelectedCards() {
        $sql = "SELECT card_id id, card_location location, card_location_arg location_arg FROM card WHERE card_location='hand' and card_selected = true";
        $selected_cards = self::getCollectionFromDb( $sql );
        return $selected_cards;
    }

    public function clearAllSelectedCards() {
        $sql = "UPDATE card set card_selected = false WHERE card_selected = true";
        self::DbQuery($sql);
    }

    public function getAutoPlay($player_id) {
        $sql = "SELECT player_autoplay_pref FROM player WHERE player_id='$player_id'";
        return self::getUniqueValueFromDB( $sql );
    }

    public function setAutoPlay($player_id, $auto_play) {
        $sql = "UPDATE player SET player_autoplay_pref='$auto_play' WHERE player_id='$player_id'";
        return self::DbQuery( $sql );
    }

    protected function initializeDealer($players) {
        $firstDealer = bga_rand(0,3);
        $dealer_id = array_keys($players)[$firstDealer];
        self::setGameStateInitialValue('dealer', $dealer_id);
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

    public function getTeamLabels() {
        $current_player_id = self::getCurrentPlayerId();

        $sql = "SELECT player_team team FROM player WHERE player_id=" . $current_player_id;
        $current_team = self::getUniqueValueFromDB( $sql );

        if ($current_team == 1) {
            return array(
                "team1label" => "Us",
                "team2label" => "Them"
            );
        }
        else if ($current_team == 2) {
            return array(
                "team1label" => "Them",
                "team2label" => "Us"
            );
        }
        else {
            return array(
                "team1label" => "Team 1",
                "team2label" => "Team 2"
            );
        }
    }

    public function canClaimNoAceNoFace($player_id) {
        if (!isset($this->useNoAceNoFaceOption) || $this->useNoAceNoFaceOption != true) return false;

        $player_cards = $this->cards->getCardsInLocation("hand", $player_id);
        if (count($player_cards) != 13) return false;

        foreach($player_cards as $card) {
            if ($card['type_arg'] > 10) return false;
        }

        return true;
    }

    public function getValidCards($player_id) {
        $currentTrickSuit = self::getGameStateValue('trickSuit');
        $player_cards = $this->cards->getCardsInLocation("hand", $player_id);

        $valid_cards = array();
        $all_cards = array();
        foreach($player_cards as $card_id => $card) {
            if ($currentTrickSuit == 0 || $card['type'] == $currentTrickSuit) {
                $valid_cards[] = $card_id;
            }
            $all_cards[] = $card_id;
        }

        return count($valid_cards) > 0 ? $valid_cards : $all_cards;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in minnesotawhist.action.php)
    */

    function playCard($card_id) {
        // self::checkAction("playCard");
        $player_id = self::getActivePlayerId();

        $currentCard = self::getCardFromHand($player_id, $card_id);

        $currentTrickSuit = self::getGameStateValue('trickSuit');
        if ($currentTrickSuit == 0) {
            self::setGameStateValue('trickSuit', $currentCard['type']);
            $currentTrickSuit = $currentCard['type'];
        }
        else if ($currentTrickSuit != $currentCard['type']) {
            // Does the user have any cards in their hand of the current suit?
            $player_cards = $this->cards->getCardsInLocation("hand", $player_id);
            $valid_card = true;

            foreach($player_cards as $card) {
                if ($card['type'] == $currentTrickSuit) {
                    $valid_card = false;
                    break;
                }
            }

            if (!$valid_card) {
                $suit_name = $this->suits[$currentTrickSuit]['name'];
                throw new BgaUserException(self::_("You must play a $suit_name."), true);
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
                'suit_displayed' => $this->suits[$currentCard['type']]['name'],
                'current_suit' => $currentTrickSuit
            )
        );

        $this->gamestate->nextState('nextPlayer');
    }

    function playBid($card_id) {
        self::checkAction("playBid");

        $player_id = self::getCurrentPlayerId();

        $card = $this->cards->getCard($card_id);
        if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) {
            throw new feException("That card is not in your hand.");
        }

        $bid_high = $card['type'] == self::SPADES || $card['type'] == self::CLUBS;

        if ($bid_high) {
            self::IncStat(1, "high_bids", $player_id);
        }
        else {
            self::IncStat(1, "low_bids", $player_id);
        }

        self::selectCardForPlayer($player_id, $card_id);

        self::notifyAllPlayers("bidCard", clienttranslate('${player_name} has placed a bid.'), 
            array( 
                'player_id' => $player_id,
                'player_name' => self::getCurrentPlayerName(),
            )
        );
        $this->gamestate->setPlayerNonMultiactive($player_id, "showBids");
    }


    function fixPriorBidStats($player_id, $prior_selected_card_id) {
        $prior_selected_card = $this->cards->getCard($prior_selected_card_id);
        $prior_bid_high = $prior_selected_card['type'] == self::SPADES || $prior_selected_card['type'] == self::CLUBS; 

        if ($prior_bid_high) {
            self::IncStat(-1, "high_bids", $player_id);
        }
        else {
            self::IncStat(-1, "low_bids", $player_id);
        }
    }

    function claimNoAceNoFace() {
        self::checkAction("claimNoAceNoFace");

        $player_id = self::getCurrentPlayerId();

        if (!$this->canClaimNoAceNoFace($player_id)) {
            throw new feException("You cannot claim No Ace, No Face rule.");
        }

        self::IncStat(1, "noace_noface", $player_id);
        self::notifyAllPlayers("noAceNoFaceClaimed", clienttranslate('${player_name} invoked No Ace, No Face, No Play rule.'), 
            array(
                'player_id' => $player_id,
                'player_name' => self::getCurrentPlayerName(),
            ));

        $this->gamestate->nextState('reshuffle');
    } 

    function selectCard($card_id) {
        $this->gamestate->checkPossibleAction("selectCard");

        $player_id = self::getCurrentPlayerId();
        $card = $this->cards->getCard($card_id);
        if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) {
            throw new feException("That card is not in your hand.");
        }

        $play_mode = $this->getGameStateValue("currentHandType");

        if ($play_mode == self::BIDDING) {
            $prior_selected_card_id = self::getSelectedCard($player_id);

            if ($prior_selected_card_id) {
                self::fixPriorBidStats($player_id, $prior_selected_card_id);
            }
            $bid_high = $card['type'] == self::SPADES || $card['type'] == self::CLUBS;

            if ($bid_high) {
                self::IncStat(1, "high_bids", $player_id);
            }
            else {
                self::IncStat(1, "low_bids", $player_id);
            }
            self::selectCardForPlayer($player_id, $card_id);

            self::notifyPlayer($player_id, 'changeBid', clienttranslate('You changed your bid.'), array());
        }
        else {
            self::selectCardForPlayer($player_id, $card_id);
        }
    }

    function clearSelection() {
        $this->gamestate->checkPossibleAction("clearSelection");

        $player_id = self::getCurrentPlayerId();

        $prior_selected_card_id = self::getSelectedCard($player_id);

        if (!$prior_selected_card_id) {
            return;
        }

        $play_mode = $this->getGameStateValue("currentHandType");

        if ($play_mode == self::BIDDING) {
            $this->gamestate->setPlayersMultiactive(array($player_id), "playBid", false);

            self::notifyAllPlayers("removeBid", clienttranslate('${player_name} has removed their bid.'), 
                array( 
                    'player_id' => $player_id,
                    'player_name' => self::getCurrentPlayerName(),
                )
            );
        }
        else {
            self::clearSelectedCardForPlayer($player_id);
        }
    }

    public function updateAutoPlay($auto_play) {
        $player_id = self::getCurrentPlayerId();
        $current_auto_play = self::getAutoPlay($player_id);

        $this->setAutoPlay($player_id, $auto_play);

        $msg = $current_auto_play != $auto_play
            ? clienttranslate("AutoPlay preference updated.")
            : "";

        self::notifyPlayer($player_id, "autoPlay", $msg, array());
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
        self::incStat(1, "hands_played");

        // Take back all cards (from any location => null) to deck
        $this->cards->moveAllCardsInLocation(null, "deck");
        $this->cards->shuffle('deck');
        self::setGameStateValue('team1tricks', 0);
        self::setGameStateValue('team2tricks', 0);
        self::setGameStateValue('currentHandType', self::BIDDING);
        self::setGameStateValue('trickSuit', 0);

        $players = self::loadPlayersBasicInfos();
        foreach($players as $player_id => $player) {
            $cards = $this->cards->pickCards(13, 'deck', $player_id);
            self::notifyPlayer($player_id, 'newHand', '', array(
                'cards' => $cards,
                'hand_type' => self::BIDDING,
                'noace_noface' => $this->canClaimNoAceNoFace($player_id)
            ));
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
        $play_mode = self::PLAYING_LOW; // Default to low
        $grand_player_id = 0;

        for($i = 0; $i < 4; $i++) {
            $check_player_id = $this->getPlayerAfter($check_player_id);
            $bid_card_id = self::getSelectedCard($check_player_id);
            $this->cards->moveCard($bid_card_id, "cardsontable", $check_player_id);
            $card = $this->cards->getCard($bid_card_id);
            array_push($bid_cards, $card);

            $suit = $card['type'];

            if ($suit == 1 || $suit == 3) {
                $play_mode = self::PLAYING_HIGH;
                $grand_player_id = $check_player_id;
                break;
            }
        }

        $message = '';
        $player_name = '';
        $next_player = '';

        $players = self::loadPlayersBasicInfos();
        if ($play_mode == self::PLAYING_LOW) {
            $message = clienttranslate('All players bid low.');
            $next_player = $this->getPlayerAfter($dealer_id);
        }
        else {
            $message = clienttranslate('${player_name} bid high.');
            $player_name = $players[$grand_player_id]['player_name'];
            $next_player = $this->getPlayerBefore($grand_player_id);
        }

        $this->gamestate->changeActivePlayer($next_player);

        self::setGameStateValue('currentHandType', $play_mode);
        self::setGameStateValue('grandPlayer', $grand_player_id);

        foreach($bid_cards as $bidCard) {
            $bid_player_id = $bidCard['location_arg'];
            $bid_player_name = $players[$bid_player_id]['player_name'];
            $card_value = $this->values_label[$bidCard['type_arg']];
            $card_suit = $this->suits[$bidCard['type']]['name'];
            self::notifyAllPlayers('revealPlayerBid', '${player_name} bid ${card_value} ${card_suit}',
                array(
                    'player_name' => $bid_player_name,
                    'card_value' => $card_value,
                    'card_suit' => $card_suit
                )
            );
        }

        self::notifyAllPlayers('bidsShown', $message, 
            array(
                'bid_cards' => $bid_cards,
                'player_name' => $player_name,
                'hand_type' => $play_mode,
                'grand_player_id' => $grand_player_id,
            )
        );
        
        $this->gamestate->nextState("returnBids");
    }

    function stReturnBids() {
        $bid_cards = $this->cards->getCardsInLocation('cardsontable');
        foreach($bid_cards as $card_id => $card) {
            $player_id = $card['location_arg'];
            $this->cards->moveCard($card_id, 'hand', $player_id);

            self::notifyPlayer($player_id, 'returnCard', "", array('bid_card' => $card));
        }

        self::notifyAllPlayers('clearBids', '', array());
        self::clearAllSelectedCards();

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

            self::IncStat(1, "tricks_taken", $best_value_player_id);

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
            $this->gamestate->nextState('preSelectCheck');

        }
    }

    function stPreSelectCheck() {
        $player_id = self::getActivePlayerId();
        $player_selected_card_id = self::getSelectedCard($player_id);
        $autoplay_card_id = null;
        $player_autoplay_pref = self::getAutoPlay($player_id);

        if ($player_selected_card_id) {
            $autoplay_card_id = $player_selected_card_id;
        }
        else if ($player_autoplay_pref == self::AUTO_PLAY_ON) {
            $valid_cards = $this->getValidCards($player_id);
            if (count($valid_cards) == 1) {
                $autoplay_card_id = $valid_cards[0];
            }
        }

        if ($autoplay_card_id) {
            try {
                $this->playCard($autoplay_card_id);
            }
            catch(Exception $ex) {
                $this->gamestate->nextState('playerTurn');
                self::notifyPlayer($player_id, 'selectionError', clienttranslate('Your selected card could not be played'), array());
            }
        }
        else {
            $this->gamestate->nextState('playerTurn');
        }
    }

    function stEndHand() {
        $team1_tricks = $this->getGameStateValue("team1tricks");
        $team2_tricks = $this->getGameStateValue("team2tricks");
        $team1_grand = "no";
        $team2_grand = "no";
        $team1_double_points = "no";
        $team2_double_points = "no";
        $team1_score = 0;
        $team2_score = 0;

        $play_mode = $this->getGameStateValue("currentHandType");
        $grand_player_id = $this->getGameStateValue("grandPlayer");

        $sql = "SELECT player_id, player_name, player_score, player_team FROM player";
        $players = self::getCollectionFromDb( $sql );

        $scoring_team = 0;
        $points = max($team1_tricks, $team2_tricks)- 6;

        $maxPoints = 7;
        if ($play_mode == 1) {
            $scoring_team = $team1_tricks > $team2_tricks ? 2 : 1;
            $losing_team = $scoring_team == 1 ? 2 : 1;
        }
        else if ($play_mode == 2) {
            $scoring_team = $team1_tricks > $team2_tricks ? 1 : 2;
            $losing_team = $scoring_team == 1 ? 2 : 1;
            $grand_team = $players[$grand_player_id]['player_team'];
                
            if ($grand_team == 1) $team1_grand = "yes";
            if ($grand_team == 2) $team2_grand = "yes";

            // Double points if the team that granded did not take the points
            if ($grand_team != $scoring_team) {
                $points *= 2;
                $maxPoints *= 2;
                self::IncStat(1, "failed_grand", $grand_player_id);

                if ($scoring_team == 1) $team1_double_points = "yes";
                if ($scoring_team == 2) $team2_double_points = "yes";

                self::notifyAllPlayers("failedBid", clienttranslate('Team ${grand_team} bid high and failed to take enough tricks. Team ${scoring_team} points will be doubled.'), 
                    array(
                        'scoring_team' => $scoring_team,
                        'grand_team' => $grand_team,
                    )
                );
            }
            else {
                self::IncStat(1, "succeed_grand", $grand_player_id);
            }
        }

        if ($scoring_team == 1) $team1_score = $points;
        if ($scoring_team == 2) $team2_score = $points;

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

                if ($points == $maxPoints ) {
                    self::IncStat(1, "perfect_hand", $player_id);
                }
            }
        }

        // Move dealer status to next player
        $dealer_id = $this->getGameStateValue("dealer");
        $next_dealer_id = $this->getPlayerAfter($dealer_id);
        $this->setGameStateValue("dealer", $next_dealer_id);

        $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true);

        self::clearAllSelectedCards();
        self::notifyAllPlayers("newScores", clienttranslate('Team ${scoring_team} scored ${points} points.'), 
            array(
                'newScores' => $newScores,
                'scoring_team' => $scoring_team,
                'points' => $points,
                'dealer_id' => $next_dealer_id,
            )
        );

        // Setup end of hand scoring dialog
        $score_table = array(
            array("Team", "Tricks", "Grand", "Double", "Score"),
            array("Team 1", $team1_tricks, $team1_grand, $team1_double_points, $team1_score),
            array("Team 2", $team2_tricks, $team2_grand, $team2_double_points, $team2_score)
        );

        self::notifyAllPlayers("tableWindow", '', array(
            "id" => 'handScoring',
            "title" => clienttranslate("Scoring for this hand."),
            "table" => $score_table,
            "closing" => clienttranslate("Close")
        ));

        // Check if this is the end of the game
        if ($scoring_team_score >= 13) {
            $losing_team_score = $this->getGameStateValue("team${losing_team}score");

            if ($losing_team_score == 0) {
                foreach($players as $player_id => $player) {
                    $skunkStat = $player['player_team'] == $scoring_team ? 'skunked_other_team' : 'was_skunked';
                    self::IncStat(1, $skunkStat, $player_id);
                }
            }

            $score_table_label = "Game score";

            $this->gamestate->nextState("endGame");
        }
        else {
            $this->gamestate->nextState('nextHand');

        }
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
    	
        if ($state['type'] === "activeplayer" && $statename === "playerTurn") {
            $this->zombiePlayCard($active_player);
            return;
        }

        if ($state['type'] === "multipleactiveplayer" && $statename === "playBid") {
            $this->zombieBid($active_player);
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }

    function zombieBid($active_player) {
        $bid_card_id = $this->zombieChooseBidCard($active_player);
        $card = $this->cards->getCard($bid_card_id);

        self::selectCardForPlayer($active_player, $bid_card_id);

        self::notifyAllPlayers("bidCard", clienttranslate('${player_name} has placed a bid.'), 
            array( 
                'player_id' => $active_player,
                'player_name' => self::getPlayerNameById($active_player)
            )
        );

        self::giveExtraTime($active_player);
        $this->gamestate->setPlayerNonMultiactive($active_player, "showBids");
    }

    function zombieChooseBidCard($active_player) {
        $bid_score = $this->getZombieBidScore($active_player);

        $bid_suits = array();

        if ($bid_score >= 13) {
            $bid_suits[] = self::SPADES; 
            $bid_suits[] = self::CLUBS; 
        }
        else {
            $bid_suits[] = self::HEARTS; 
            $bid_suits[] = self::DIAMONDS;
        }

        $card_id1 = $this->getLowestCardInSuit($active_player, $bid_suits[0]);
        $card_id2 = $this->getLowestCardInSuit($active_player, $bid_suits[0]);

        $bid_cards = array();

        if ($card_id1 != 0) {
            $bid_cards[$card_id1] = $this->cards->getCard($card_id1);
        }
        if ($card_id2 != 0) {
            $bid_cards[$card_id2] = $this->cards->getCard($card_id2);
        }

        if (count($bid_cards) == 2) {
            return $bid_cards[$card_id1]['type_arg'] < $bid_cards[$card_id2]['type_arg'] 
                ? $card_id1 : $card_id2;
        }
        else if (count($bid_cards) == 1) {
            return array_keys($bid_cards)[0];
        }
        else {
            // This should be VERY rare
            return $this->getLowestCard($active_player);
        }
    }

    function getZombieBidScore($active_player) {
        $zombie_cards = $this->cards->getCardsInLocation('hand', $active_player);

        $bidScore = 0;

        foreach($zombie_cards as $card) {
            if ($card['type_arg'] == 14) $bidScore += 4;
            else if ($card['type_arg'] == 13) $bidScore += 3;
            else if ($card['type_arg'] == 12) $bidScore += 2;
            else if ($card['type_arg'] == 11) $bidScore += 1;
        }

        return $bidScore;
    }

    function zombiePlayCard($active_player) {
        $card_id = $this->zombieChooseCardToPlay($active_player);
        $currentCard = $this->cards->getCard($card_id);

        $currentTrickSuit = self::getGameStateValue('trickSuit');
        if ($currentTrickSuit == 0) {
            self::setGameStateValue('trickSuit', $currentCard['type']);
        }

        $this->cards->moveCard($card_id, 'cardsontable', $active_player);

        self::notifyAllPlayers('playCard', clienttranslate('${player_name} plays ${value_displayed} ${suit_displayed}'), 
            array(
                'i18n' =>array('suit_displayed', 'value_displayed'), 
                'card_id' => $card_id, 
                'player_id' => $active_player,
                'player_name' => self::getPlayerNameById($active_player),
                'value' => $currentCard['type_arg'], 
                'value_displayed' => $this->values_label[$currentCard ['type_arg']],
                'suit' => $currentCard['type'],
                'suit_displayed' => $this->suits[$currentCard['type']]['name']
            )
        );

        $this->gamestate->nextState('playCard');
    }

    function zombieChooseCardToPlay($active_player) {
        $currentMode = $this->getGameStateValue("currentHandType");
        $cardsOnTable = $this->cards->getCardsInLocation("cardsontable");
        $initialCard = count($cardsOnTable) == 0;
        $currentSuit = $this->getGameStateValue("trickSuit");
        $highTableCardId = $initialCard ? 0 : $this->getHighestCardOnTable();
        $highTableCard = $initialCard ? null : $this->cards->getCard($highTableCardId);

        if ($initialCard && $currentMode == self::PLAYING_HIGH) {
            return $this->getHighestCard($active_player);
        }
        else if ($initialCard && $currentMode == self::PLAYING_HIGH) {
            return $this->getLowestCard($active_player);
        }
        else if ($currentMode == self::PLAYING_HIGH) {
            $suit_card_id = $this->getHighestCardInSuit($active_player, $currentSuit);

            // If zombie doesn't have a card in the current suit, play lowest card
            if ($suit_card_id == 0) return $this->getLowestCard($active_player);

            // If zombie can beat current high card, play highest card in suit
            $suit_card = $this->cards->getCard($suit_card_id);
            if ($suit_card['type_arg'] > $highTableCard['type_arg']) return $suit_card_id;

            // Zombie can't beat current high card, so play lowest in suit
            return $this->getLowestCardInSuit($active_player, $currentSuit);
        }
        else if ($currentMode == self::PLAYING_LOW) {
            $suit_card_id = $this->getHighestCardInSuitBelowPlayedCard($active_player, $currentSuit, $highTableCard);
            
            // If zombie has a card below the current high card, play highest non-winning card in hand.
            if ($suit_card_id != 0) return $suit_card_id;

            $suit_card_id = $this->getLowestCardInSuit($active_player, $currentSuit);

            // Play lowest card in suit, or get rid of highest card if we don't have any in current suit.
            return $suit_card_id != 0 ? $suit_card_id : $this->getHighestCard($active_player);
        }

        // This should never happen
        throw new feException( "Zombie could not select a card.");
    }

    function getHighestCardOnTable() {
        $suit = $this->getGameStateValue("trickSuit");
        $high_card_id = 0;
        $table_cards = $this->cards->getCardsInLocation('cardsontable');
        $highestValue = 0;

        foreach($table_cards as $card_id => $card) {
            if ($card['type'] != $suit) continue;
            if ($high_card_id == 0 || $card['type_arg'] > $highestValue) {
                $high_card_id = $card_id;
                $highestValue = $card['type_arg'];
            }
        }

        return $high_card_id;
    }

    function getLowestCardInSuit($active_player, $suit) {
        $low_card_id = 0;
        $zombie_cards = $this->cards->getCardsInLocation('hand', $active_player);
        $lowestValue = 0;

        foreach($zombie_cards as $card_id => $card) {
            if ($card['type'] != $suit) continue;
            if ($low_card_id == 0 || $card['type_arg'] < $lowestValue) {
                $low_card_id = $card_id;
                $lowestValue = $card['type_arg'];
            }
        }

        return $low_card_id;
    }

    function getHighestCardInSuitBelowPlayedCard($active_player, $suit, $played_card) {
        $high_card_id = 0;
        $zombie_cards = $this->cards->getCardsInLocation('hand', $active_player);
        $highestValue = 0;
        $maxValue = $played_card['type_arg'];

        foreach($zombie_cards as $card_id => $card) {
            if ($card['type'] != $suit) continue;
            if ($card['type_arg'] > $maxValue) continue;
            if ($high_card_id == 0 || $card['type_arg'] > $highestValue) {
                $high_card_id = $card_id;
                $lowestValue = $card['type_arg'];
            }
        }

        return $high_card_id;
    }

    function getHighestCardInSuit($active_player, $suit) {
        $high_card_id = 0;
        $zombie_cards = $this->cards->getCardsInLocation('hand', $active_player);
        $highestValue = 0;

        foreach($zombie_cards as $card_id => $card) {
            if ($card['type'] != $suit) continue;
            if ($high_card_id == 0 || $card['type_arg'] > $highestValue) {
                $high_card_id = $card_id;
                $highestValue = $card['type_arg'];
            }
        }

        return $high_card_id;
    }
    
    function getLowestCard($active_player) {
        $low_card_id = 0;
        $zombie_cards = $this->cards->getCardsInLocation('hand', $active_player);
        $lowestValue = 0;

        foreach($zombie_cards as $card_id => $card) {
            if ($low_card_id == 0 || $card['type_arg'] < $lowestValue) {
                $low_card_id = $card_id;
                $lowestValue = $card['type_arg'];
            }
        }

        return $low_card_id;
    }

    function getHighestCard($active_player) {
        $high_card_id = 0;
        $zombie_cards = $this->cards->getCardsInLocation('hand', $active_player);
        $highestValue = 0;

        foreach($zombie_cards as $card_id => $card) {
            if ($high_card_id == 0 || $card['type_arg'] > $highestValue) {
                $high_card_id = $card_id;
                $highestValue = $card['type_arg'];
            }
        }

        return $high_card_id;
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
       if( $from_version <= 2109200323 ) {
           // ! important ! Use DBPREFIX_<table_name> for all tables

           $sql = "ALTER TABLE DBPREFIX_card add `card_selected` bit not null default 0";
           self::applyDbUpgradeToAllDB( $sql );
       }

       if ($from_version <= 2204202238) {
           $sql = "ALTER TABLE DBPREFIX_player add `player_autoplay_pref` int unsigned not null default 1";
           self::applyDbUpgradeToAllDB( $sql );
       }
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
