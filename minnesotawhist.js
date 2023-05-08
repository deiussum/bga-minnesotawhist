/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MinnesotaWhist implementation : © Daniel Jenkins <deiussum@gmail.com>
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
            
            this.cardwidth = 70;
            this.cardheight = 96;

            this.team1score_counter = new ebg.counter();
            this.team2score_counter = new ebg.counter();
            this.team1tricks_counter = new ebg.counter();
            this.team2tricks_counter = new ebg.counter();
            this.canClaimNoAceNoFace = false;
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

            var teamLabels = [ gamedatas.team1label, gamedatas.team2label ];
            
            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                         
                // Setting up players boards 
                dojo.place(this.format_block('jstpl_teamlabel', {
                    team_label: teamLabels[player.team - 1],
                    player_id: player_id
                }), 'player_board_' + player_id);

            }

            var dealer_player_id = this.gamedatas.dealer_player_id;
            this.updateDealerIcon(dealer_player_id);

            var grand_player_id = this.gamedatas.grand_player_id;
            this.updateGrandIcon(grand_player_id);

            var cardUrl = 'img/cards_traditional_classic.jpg'
            switch (this.prefs[100].value) {
                case '1':
                    cardUrl = 'img/cards_design_4color.jpg';
                    break;
                case '2':
                    cardUrl = 'img/cards_design_classic.jpg'
                    break;
                case '3': 
                    cardUrl = 'img/cards_traditional_4color.jpg'; 
                    break;
                case '4': 
                    cardUrl = 'img/cards_traditional_classic.jpg'; 
                    break;
            }

            this.sizeCardsToWindow();
            window.addEventListener('resize', this.onWindowResize);
            
            this.playerHand = new ebg.stock();
            this.playerHand.create(this, $('myhand'), this.cardwidth, this.cardheight);
            this.playerHand.image_items_per_row = 13;
            this.playerHand.centerItems = true;
            this.playerHand.extraClasses = 'card';
            this.playerHand.setOverlap(60, 0);
            this.playerHand.setSelectionMode(1);
            this.playerHand.setSelectionAppearance('class');
            this.playerHand.onItemCreate = dojo.hitch(this, 'setupNewCard');

            dojo.connect(this.playerHand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged');

            for(var suit=1; suit <= 4; suit++) {
                for(var value=2; value <= 14; value++) {
                    var cardTypeId = this.getCardUniqueType(suit, value);
                    this.playerHand.addItemType(cardTypeId, cardTypeId, g_gamethemeurl + cardUrl, cardTypeId);
                }
            }

            for(var i in this.gamedatas.hand) {
                var card = this.gamedatas.hand[i];
                var suit = card.type;
                var value = card.type_arg;
                this.playerHand.addToStockWithId(this.getCardUniqueType(suit, value), card.id);
            }

            if (gamedatas.selected_card_id) {
                console.log('selected card id: ' + gamedatas.selected_card_id);
                this.playerHand.selectItem(gamedatas.selected_card_id);
            }
            else {
                console.log('no selected card');
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
            this.addIconToTeamTricks(1, this.gamedatas.team1tricks);
            //this.addIconToTeamTricks(1, 13); // Test full trick display

            this.team2tricks_counter.create("team2-tricks");
            this.team2tricks_counter.setValue(this.gamedatas.team2tricks);
            this.addIconToTeamTricks(2, this.gamedatas.team2tricks);
            //this.addIconToTeamTricks(2, 13); // Test full trick display

            this.updatePlayMode(this.gamedatas.hand_type);
 
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            if (this.haveCardOnTable()) {
                this.disableAllCards();
            }
            else if (this.gamedatas.current_suit != 0 && this.haveAnyCardsInSuit(this.gamedatas.current_suit)) {
                this.disableCardsNotInSuit(this.gamedatas.current_suit);
            }

            console.log('no ace, no face: ' + this.gamedatas.noace_noface);
            this.canClaimNoAceNoFace = this.gamedatas.noace_noface;

            this.initPreferencesObserver();
            console.log( "Ending game setup" );
        },

        initPreferencesObserver() {
            dojo.query('.preference_control').on('change', (e) => {
                const match = e.target.id.match(/^preference_[cf]ontrol_(\d+)$/);
                if (!match) return;

                const pref = match[1];
                const newValue = e.target.value;
                this.prefs[pref].value = newValue;
                this.onPreferenceChange(pref, newValue);
            });

            this.onPreferenceChange(101, this.prefs[101].value);
            console.log('AutoPlay preference:' + this.prefs[101].value);
        },
        onPreferenceChange(prefId, prefValue) {
            prefId = parseInt(prefId);
            if (prefId == 101) {
                this.updateAutoPlay(prefValue);
            }
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
                    case 'playBid':
                        if (this.canClaimNoAceNoFace) {
                            console.log('action button?');
                            this.addActionButton('claimNoAceNoFace_button', _('Claim No Ace, No Face, no Play.'), 'onClaimNoAceNoFace');
                        }
                        break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */
        sizeCardsToWindow: function() {
            if (window.innerWidth >= 3000) {
                this.cardwidth = 280;
                this.cardheight = 384;
            }
            else if (window.innerWidth >= 2000 ) {
                this.cardwidth = 186;
                this.cardheight = 255;
            }
            else if (window.innerWidth >= 1500) {
                this.cardwidth = 134;
                this.cardheight = 183;
            }
            else {
                this.cardwidth = 70;
                this.cardheight = 96;
            }
        },
        getCardUniqueType: function(suit, value) {
            return (suit - 1) * 13 + (value - 2);
        },

        getSuitFromCardId: function(card_id) {
            return Math.floor(card_id / 13) + 1;
        },

        getSuitName: function(suit) {
            switch(Number(suit)) {
                case 1: return _('spades');
                case 2: return _('hearts');
                case 3: return _('clubs');
                case 4: return _('diamonds');
            }
        },
       
        setupNewCard: function(card_div, card_type_id, card_id) {
            var suit = this.getSuitFromCardId(card_type_id);
            var suit_name = this.getSuitName(suit);

            card_div.classList.add(suit_name);
        },

        playCardOnTable: function(player_id, suit, value, card_id) {
            dojo.place(this.format_block('jstpl_cardontable', {
                x: (value - 2) * 100,
                y: (suit - 1) * 100,
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
                x: (value - 2) * 100,
                y: (suit - 1) * 100,
                player_id: player_id

            }), 'cardontable_' + player_id);
        },

        playFlippedCard: function(player_id, card_id) {
            dojo.place(this.format_block('jstpl_flippedcard', {
                player_id: player_id
            }), 'playertablecard_' + player_id);

            if (player_id != this.player_id ) {
                this.placeOnObject('cardontable_' + player_id, 'overall_player_board_' + player_id);
            }
            else {
                this.placeOnObject('cardontable_' + player_id, 'myhand');
            }

            this.slideToObject('cardontable_' + player_id, 'playertablecard_' + player_id).play();
        },

        removeCardFromTable: function(player_id) {
            dojo.destroy('cardontable_' + player_id);
        },

        updatePlayMode: function(handType) {
            var handTypeText = this.getHandTypeText(handType);
            var instructions = this.getInstructions(handType);
            console.log("Hand type:" + handTypeText);

            var node = dojo.byId('playmode');
            node.innerText = handTypeText;

            var wrapper = dojo.byId('playmode-wrap')
            wrapper.setAttribute('title', instructions);

            if (handType == 1) {
                node.classList.add('red-text');
            }
            else {
                node.classList.remove('red-text');
            }
        },

        getHandTypeText: function(handType) {
            switch(Number(handType)) {
                case 0:
                    return _('Bidding');
                case 1:
                    return _('Playing Low');
                case 2:
                    return _('Playing High');
                default:
                    return _('Unknown hand type: ' + handType)
            }
        },

        getInstructions: function(handType) {
            switch(Number(handType)) {
                case 0:
                    return _('If you have a hand that you think you can take a lot of tricks, you may want to bid high by playing a low black card.  If you do not think you can take many tricks, you can bid low by playing a low red card.');
                case 1:
                    return _('You are currently playing low.  You want to avoid taking tricks this hand.');
                case 2:
                    return _('You are currently playing high.  You want to try and take tricks this hand.');
            }
        },

        updateDealerIcon: function(dealer_id) {
            console.log("Dealer is " + dealer_id);

            var nodes = dojo.query(".icon-dealer");
            for(var i=0; i<nodes.length; i++) {
                var node = nodes[i];
                node.parentNode.removeChild(node);
            }

            dojo.place(this.format_block('jstpl_icon', {
                icon: 'icon-dealer',
                icon_text: 'Dealer'
            }), 'playericons_' + dealer_id);

            dojo.place(this.format_block('jstpl_icon', {
                icon: 'icon-dealer',
                icon_text: 'Dealer'
            }), 'icons_' + dealer_id);
        },

        updateGrandIcon: function(grand_player_id) {
            console.log("Grand player is " + grand_player_id);

            var nodes = dojo.query(".icon-grand");
            for(var i=0; i<nodes.length; i++) {
                var node = nodes[i];
                node.parentNode.removeChild(node);
            }

            if (grand_player_id == null || grand_player_id == undefined || grand_player_id == 0) return;

            dojo.place(this.format_block('jstpl_icon', {
                icon: 'icon-grand',
                icon_text: 'Granded'
            }), 'playericons_' + grand_player_id);

            dojo.place(this.format_block('jstpl_icon', {
                icon: 'icon-grand',
                icon_text: 'Granded'
            }), 'icons_' + grand_player_id);
        },

        addIconToTeamTricks: function(team, count) {
            if (count === undefined) count = 1;

            for(var i=0;i<count; i++) {
                dojo.place(this.format_block('jstpl_icon', {
                    icon: 'icon-cardback',
                    icon_text: 'Tricks'
                }), "team" + team + "-trick-icons");
            }
        },

        clearTeamTrickIcons: function() {
            var nodes = dojo.query(".icon-cardback");
            for(var i=0; i<nodes.length; i++) {
                var node = nodes[i];
                node.parentNode.removeChild(node);
            }
        },

        haveAnyCardsInSuit: function(suit) {
            var suitName = this.getSuitName(suit);
            var cardsInSuit = dojo.query('.stockitem.card.' + suitName);

            return cardsInSuit.length > 0;
        },

        haveCardOnTable: function() {
            var id = 'cardontable_' + this.player_id;
            var playerCard = dojo.query('#' + id);
            return playerCard.length > 0 && !dojo.hasClass(id, 'sliding');
        },

        disableCardsInSuit: function(suit) {
            var suitName = this.getSuitName(suit);
            dojo.query('.stockitem.' + suitName).addClass('disabled');
        },
        enableCardsInSuit: function(suit) {
            var suitName = this.getSuitName(suit);
            dojo.query('.stockitem.' + suitName).removeClass('disabled');
        },

        disableCardsNotInSuit: function(suit) {
            if (suit != 1) this.disableCardsInSuit(1);
            if (suit != 2) this.disableCardsInSuit(2);
            if (suit != 3) this.disableCardsInSuit(3);
            if (suit != 4) this.disableCardsInSuit(4);
            this.enableCardsInSuit(suit);
        },

        enableAllCards: function() {
            dojo.query('.stockitem').removeClass('disabled');
        },

        disableAllCards: function() {
            dojo.query('.stockitem').addClass('disabled');
        },

        updateAutoPlay: function(autoPlayValue) {
            console.log('on updateAutoPlay ' + autoPlayValue);

            this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/updateAutoPlay.html", {
                auto_play: autoPlayValue
            },this
            , function(result) { }
            , function(is_error) { }
            );
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
        onPlayerHandSelectionChanged: function() {
            var items = this.playerHand.getSelectedItems();

            if (items.length > 0) {
                var card_id = items[0].id;
                var actions = ['playBid', 'playCard'];
                var action = '';

                for(var i=0;i<actions.length;i++)
                {
                    if (this.checkAction(actions[i], false)) action = actions[i];
                }

                // Don't check the selectCard action as it can be done outside of the normal turn
                // to change bid/pre-select card.
                if (action === '') action = 'selectCard';

                console.log('on ' +action + ' ' + card_id);

                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                    id: card_id
                },this
                , function(result) { }
                , function(is_error) { }
                );
            }
            else {
                var that = this;
                // Delay this a bit as it gets called even when immediately selecting a different card
                // which causes a race condition.  Only send if there is truly nothing selected.
                window.setTimeout(function() {
                    console.log('on clearSelection ');

                    if (that.playerHand.getSelectedItems().length === 0) {
                        that.ajaxcall("/" + that.game_name + "/" + that.game_name + "/clearSelection.html", {
                        },that
                        , function(result) { }
                        , function(is_error) { }
                        );
                    }
                }, 250);
            }
        },
        onWindowResize: function() {
            gameui.sizeCardsToWindow();
            gameui.playerHand.item_width = gameui.cardwidth;
            gameui.playerHand.item_height = gameui.cardheight;
            gameui.playerHand.updateDisplay();
        },
        onClaimNoAceNoFace: function() {
            if (!this.checkAction('claimNoAceNoFace')) return;

            console.log('Claiming no ace, no face, no play');

            this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/claimNoAceNoFace.html", {
                lock: true
            },this
            , function(result) { }
            , function(is_error) { }
            );
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
            
            // associate your game notifications with local methods
            
            dojo.subscribe('newHand', this, "notif_newHand");
            dojo.subscribe('playCard', this, "notif_playCard");
            dojo.subscribe('trickWin', this, "notif_trickWin");
            this.notifqueue.setSynchronous('trickWin', 1000);
            dojo.subscribe('giveAllCardsToPlayer', this, "notif_giveAllCardsToPlayer");
            dojo.subscribe('newScores', this, "notif_newScores");

            dojo.subscribe('bidCard', this, "notif_bidCard");
            dojo.subscribe('removeBid', this, "notif_removeBid");
            dojo.subscribe('bidsShown', this, "notif_bidsShown");
            this.notifqueue.setSynchronous('bidsShown', 1000);

            dojo.subscribe('removeCard', this, 'notif_removeCard');
            dojo.subscribe('clearBids', this, "notif_clearBids");
            dojo.subscribe('returnCard', this, "notif_returnCard");
            dojo.subscribe('noAceNoFaceClaimed', this, 'notif_noAceNoFaceClaimed');
            dojo.subscribe('selectionError', this, 'notif_selectionError');
        },  
        
        // from this point and below, you can write your game notifications handling methods
        
        notif_newHand: function(notif) {
            this.playerHand.removeAll();

            for(var i in notif.args.cards) {
                var card = notif.args.cards[i];
                var suit = card.type;
                var value = card.type_arg;
                this.playerHand.addToStockWithId(this.getCardUniqueType(suit, value), card.id);
            }
            this.updatePlayMode(notif.args.hand_type);
            this.playerHand.unselectAll();

            this.canClaimNoAceNoFace = notif.args.noace_noface;
        },

        notif_bidCard: function(notif) {
            this.playFlippedCard(notif.args.player_id, notif.args.card_id);
            if (this.haveCardOnTable()) {
                this.disableAllCards();
            }
        },

        notif_removeBid: function(notif) {
            if (notif.args.player_id == this.player_id) {
                this.enableAllCards();
            }
            this.removeCardFromTable(notif.args.player_id);
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
            this.updatePlayMode(notif.args.hand_type);
            this.updateGrandIcon(notif.args.grand_player_id);
            this.enableAllCards();
        },

        notif_clearBids: function(notif) {
            for(var player_id in this.gamedatas.players) {
                var anim = this.slideToObject('cardontable_' + player_id, 'overall_player_board_' + player_id);
                dojo.connect(anim, 'onEnd', function(node) {
                    dojo.destroy(node);
                });
                anim.play();
            }
            this.playerHand.unselectAll();
        },
        notif_noAceNoFaceClaimed: function(notif) {
            var cardsOnTable = dojo.query('.cardontable');
            for(var i=0; i < cardsOnTable.length; i++) {
                var cardId = cardsOnTable[i].id;
                var playerId = cardId.replace('cardontable_', '');
                var anim = this.slideToObject(cardId, 'overall_player_board_' + playerId);
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

            if (this.haveCardOnTable()) {
                this.disableAllCards();
            }
            else if (this.haveAnyCardsInSuit(notif.args.current_suit)) {
                this.disableCardsNotInSuit(notif.args.current_suit);
            }
            else {
                this.enableAllCards();
            }
        },

        notif_trickWin: function(notif) {
            if (notif.args.team == 1) {
                this.team1tricks_counter.incValue(1);
                this.addIconToTeamTricks(1);
            }
            else if (notif.args.team == 2) {
                this.team2tricks_counter.incValue(1);
                this.addIconToTeamTricks(2);
            }
            this.enableAllCards();
        },

        notif_giveAllCardsToPlayer: function(notif) {
            var winner_id = notif.args.player_id;

            for(var player_id in this.gamedatas.players) {
                dojo.query('.cardontable').addClass('sliding');
                var anim = this.slideToObject('cardontable_' + player_id, 'cardontable_' + winner_id);
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
            this.clearTeamTrickIcons();

            var dealer_id = notif.args.dealer_id;
            this.updateDealerIcon(dealer_id);
            this.updateGrandIcon(0);
        },

        notif_selectionError: function(notif) {
            this.playerHand.unselectAll();
        }
   });             
});
