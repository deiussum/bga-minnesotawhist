/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MinnesotaWhist implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * minnesotawhist.js
 *
 * MinnesotaWhist user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.minnesotawhist", ebg.core.gamegui, {
        constructor: function(){
            console.log('minnesotawhist constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
            
            this.cardwidth = 72;
            this.cardheight = 96;

            this.team1score_counter = new ebg.counter();
            this.team2score_counter = new ebg.counter();
            this.team1tricks_counter = new ebg.counter();
            this.team2tricks_counter = new ebg.counter();

        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );
            
            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                         
                // TODO: Setting up players boards if needed
                dojo.place(this.format_block('jstpl_teamlabel', {
                    team_label: "Team "  + player.team,
                }), 'player_board_' + player_id);

            }

            var dealer_player_id = this.gamedatas.dealer_player_id;
            console.log("Dealer is " + dealer_player_id);
            dojo.place('<span> - Dealer</span>', 'player_board_' + dealer_player_id);
            
            // TODO: Set up your game interface here, according to "gamedatas"
            this.playerHand = new ebg.stock();
            this.playerHand.create(this, $('myhand'), this.cardwidth, this.cardheight);
            this.playerHand.image_items_per_row = 13;
            dojo.connect(this.playerHand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged');

            for(var suit=1; suit <= 4; suit++) {
                for(var value=2; value <= 14; value++) {
                    var cardTypeId = this.getCardUniqueType(suit, value);
                    this.playerHand.addItemType(cardTypeId, cardTypeId, g_gamethemeurl + 'img/cards.jpg', cardTypeId);
                }
            }

            for(var i in this.gamedatas.hand) {
                var card = this.gamedatas.hand[i];
                var suit = card.type;
                var value = card.type_arg;
                this.playerHand.addToStockWithId(this.getCardUniqueType(suit, value), card.id);
            }

            for(var i in this.gamedatas.cardsontable) {
                var card = this.gamedatas.cardsontable[i];
                var suit = card.type;
                var value = card.type_arg;
                var player_id = card.location_arg;
                this.playCardOnTable(player_id, suit, value, card.id);
            }

            for(var i in this.gamedatas.bids) {
                var player_id = this.gamedatas.bids[i];
                this.playFlippedCard(player_id);
            }

            // setup counters
            this.team1score_counter.create("team1-score");
            this.team1score_counter.setValue(this.gamedatas.team1score);

            this.team2score_counter.create("team2-score");
            this.team2score_counter.setValue(this.gamedatas.team2score);

            this.team1tricks_counter.create("team1-tricks");
            this.team1tricks_counter.setValue(this.gamedatas.team1tricks);

            this.team2tricks_counter.create("team2-tricks");
            this.team2tricks_counter.setValue(this.gamedatas.team2tricks);
 
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */
        getCardUniqueType: function(suit, value) {
            return (suit - 1) * 13 + (value - 2);
        },

        playCardOnTable: function(player_id, suit, value, card_id) {
            dojo.place(this.format_block('jstpl_cardontable', {
                x: this.cardwidth * (value - 2),
                y: this.cardheight * (suit - 1),
                player_id: player_id

            }), 'playertablecard_' + player_id);

            if (player_id != this.player_id) {
                this.placeOnObject('cardontable_' + player_id, 'overall_player_board_' + player_id);
            }
            else {
                if ($('myhand_item_' + card_id)) {
                    this.placeOnObject('cardontable_' + player_id, 'myhand_item_' + card_id);
                    this.playerHand.removeFromStockById(card_id);
                }
            }

            this.slideToObject('cardontable_' + player_id, 'playertablecard_' + player_id).play();
        },

        showFlippedCard: function(player_id, suit, value, card_id) {
            dojo.place(this.format_block('jstpl_cardontable', {
                x: this.cardwidth * (value - 2),
                y: this.cardheight * (suit - 1),
                player_id: player_id

            }), 'cardontable_' + player_id);
        },

        playFlippedCard: function(player_id) {
            dojo.place(this.format_block('jstpl_flippedcard', {
                player_id: player_id
            }), 'playertablecard_' + player_id);

            this.placeOnObject('cardontable_' + player_id, 'overall_player_board_' + player_id);

            this.slideToObject('cardontable_' + player_id, 'playertablecard_' + player_id).play();
        },

        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/minnesotawhist/minnesotawhist/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        onPlayerHandSelectionChanged: function() {
            var items = this.playerHand.getSelectedItems();

            if (items.length > 0) {
                var action = 'playCard';
                if (this.checkAction(action, true)) {
                    var card_id = items[0].id;
                    console.log('on playCard ' + card_id);

                    this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                        id: card_id,
                        lock: true
                    },this
                    , function(result) { }
                    , function(is_error) { }
                    );

                    this.playerHand.unselectAll();
                }
                else if (this.checkAction('playBid')) {
                    var card_id = items[0].id;
                    console.log('on playCard ' + card_id);

                    this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/playBid.html", {
                        id: card_id,
                        lock: true
                    },this
                    , function(result) { }
                    , function(is_error) { }
                    );
                }
                else {
                    this.playerHand.unselectAll();
                }
            }
        },
        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your minnesotawhist.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
            dojo.subscribe('newHand', this, "notif_newHand");
            dojo.subscribe('playCard', this, "notif_playCard");
            dojo.subscribe('trickWin', this, "notif_trickWin");
            this.notifqueue.setSynchronous('trickWin', 1000);
            dojo.subscribe('giveAllCardsToPlayer', this, "notif_giveAllCardsToPlayer");
            dojo.subscribe('newScores', this, "notif_newScores");

            dojo.subscribe('bidCard', this, "notif_bidCard");
            dojo.subscribe('bidsShown', this, "notif_bidsShown");
            this.notifqueue.setSynchronous('bidsShown', 1000);

            dojo.subscribe('removeCard', this, 'notif_removeCard');
            dojo.subscribe('clearBids', this, "notif_clearBids");
            dojo.subscribe('returnCard', this, "notif_returnCard");
            console.log('notifications done');
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        notif_newHand: function(notif) {
            this.playerHand.removeAll();

            for(var i in notif.args.cards) {
                var card = notif.args.cards[i];
                var suit = card.type;
                var value = card.type_arg;
                this.playerHand.addToStockWithId(this.getCardUniqueType(suit, value), card.id);
            }
        },

        notif_bidCard: function(notif) {
            this.playFlippedCard(notif.args.player_id, notif.args.card_id);
        },

        notif_removeCard: function(notif) {
            this.playerHand.removeFromStockById(notif.args.card_id);
        },

        notif_bidsShown: function(notif) {
            for(var i in notif.args.bid_cards) {
                var card = notif.args.bid_cards[i];
                var suit = card.type;
                var value = card.type_arg;
                var player_id = card.location_arg;
                this.showFlippedCard(player_id, suit, value, card.id);
            }
        },

        notif_clearBids: function(notif) {
            for(var player_id in this.gamedatas.players) {
                var anim = this.slideToObject('cardontable_' + player_id, 'overall_player_board_' + player_id);
                dojo.connect(anim, 'onEnd', function(node) {
                    dojo.destroy(node);
                });
                anim.play();
            }
        },

        notif_returnCard: function(notif) {
            var card = notif.args.bid_card;
            var suit = card.type;
            var value = card.type_arg;

            this.playerHand.addToStockWithId(this.getCardUniqueType(suit, value), card.id);
        },

        notif_playCard: function(notif) {
            this.playCardOnTable(notif.args.player_id, notif.args.suit, notif.args.value, notif.args.card_id);
        },

        notif_trickWin: function(notif) {
            if (notif.args.team == 1) {
                this.team1tricks_counter.incValue(1);
            }
            else if (notif.args.team == 2) {
                this.team2tricks_counter.incValue(1);
            }
        },

        notif_giveAllCardsToPlayer: function(notif) {
            var winner_id = notif.args.player_id;

            for(var player_id in this.gamedatas.players) {
                var anim = this.slideToObject('cardontable_' + player_id, 'overall_player_board_' + winner_id);
                dojo.connect(anim, 'onEnd', function(node) {
                    dojo.destroy(node);
                });
                anim.play();
            }
        },

        notif_newScores: function(notif) {
            for(var player_id in notif.args.newScores) {
                this.scoreCtrl[player_id].toValue(notif.args.newScores[player_id]);
            }

            if (notif.args.scoring_team == 1) {
                this.team1score_counter.incValue(notif.args.points);
            }
            if (notif.args.scoring_team == 2) {
                this.team2score_counter.incValue(notif.args.points);
            }

            this.team1tricks_counter.setValue(0);
            this.team2tricks_counter.setValue(0);
        }
   });             
});
