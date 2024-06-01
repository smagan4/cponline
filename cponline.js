/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * template implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * cponline.js
 *
 * template user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

 define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"],

  //  g_gamethemeurl + "modules/bga-cards.js",
function (dojo, declare) {
    return declare("bgagame.cponline", ebg.core.gamegui, {
        constructor: function(){
            console.log('hearts constructor');             
            this.cardwidth = 72;
            this.cardheight = 96;
            this.previouslySelectedItems = [];
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
        

        setup : function(gamedatas) {
            console.log("Starting game setup");
           
            // Player hand
            this.playerHand = new ebg.stock();
            this.playerHand.create(this, $('myhand'), this.cardwidth, this.cardheight);
            this.playerHand.image_items_per_row = 13;
            this.playerHand.autowidth = true;
            this.playerHand.horizontal_overlap = 40;
            this.playerHand.setSelectionAppearance('class');
            let no_cards = [];
            this.card_counters = {};
            this.strengthofPrevCardPlayed = 0;
            this.numberofcardsplayed = 0;

            if (this.gamedatas['no_cards_in_round']) {
                this.numberofcardsplayed = parseInt(this.gamedatas['no_cards_in_round'], 10);}

            // Create cards types:
            for (var color = 1; color <= 4; color++) {
                for (var value = 2; value <= 14; value++) {
                    // Build card type id
                    var card_type_id = this.getCardUniqueId(color, value);
                    //Below sorts by card weight - other option is to sort by card value
                    var card_sort= this.getCardSortValue(color, value);
                    this.playerHand.addItemType(card_sort, card_sort, g_gamethemeurl + 'img/cards.jpg', card_type_id);
                }
            }

            // Cards in player's hand
            for ( var i in this.gamedatas.hand) {
                var card = this.gamedatas.hand[i];
                var color = card.type;
                var value = card.type_arg;
                this.playerHand.addToStockWithId(this.getCardSortValue(color, value), card.id);
            }

            // Cards played on table - this calls playCardsOnTable for all cards on table at once
            for (p in this.gamedatas.players) {
                var cards = [];
                for (i in this.gamedatas.cardsontable) {
                        var player_id = this.gamedatas.cardsontable[i].location_arg;
                        if (p == player_id) {
                        //add the relevant card to the cards array
                        cards.push(this.gamedatas.cardsontable[i]);
                        }
                }
                this.playCardsOnTable(p, cards); 
                no_cards[p] = this.gamedatas.num_cards[p].no_cards;
            }

            // Set up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];              
                var player_board_div = $('player_board_'+player_id);                
                dojo.place( this.format_block('jstpl_player_board', player ), player_board_div ); 
                // create counter per player
                this.card_counters[player_id]=new ebg.counter();
                this.card_counters[player_id].create('cardcount_p' + player_id);
                this.card_counters[player_id].setValue(no_cards[player_id]);
            }
 
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
       
            ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onScreenWidthChange: function()
            {
        },
        
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
            if( this.isCurrentPlayerActive() )
            {       

                switch( stateName )
                {
                    case 'playerTurn':
                        if (this.handler) {
                            dojo.disconnect( this.handler);
                        }
                        this.selectStrongestCard();
                        this.handler = dojo.connect( this.playerHand, 'onClickOnItem', this, 'onSelectCard' );
                        this.addActionButton( 'play', _('play cards'), 'onPlayerHandSelectionChanged' );
                        this.addActionButton( 'pass', _('Choose to pass'), 'onPassTurn', null, false, 'red');
                

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
        selectStrongestCard() {
            var strength = this.strengthofPrevCardPlayed;
            var strongestCard = null;
   
            //create an array of the cards in hard ordered by strength (type) - (note this was previously using gamedatas.hand which lead to bugs arising where the array included some cards not in the player's hand)
            var handArray = Object.values(this.playerHand.getAllItems())

            //find the card that is immediately stronger than the previously played card (since handarray is already sorted by type (card strength)
            var strongestCard = handArray.find(card => card.type> strength);
            if (strongestCard) {
                this.playerHand.unselectAll();
                 //default to next highest pair selection if pairs are played 
                if (this.numberofcardsplayed === 2) { // Pairs
                    this.selectPair(strongestCard.type);
                } else {
                    this.playerHand.selectItem(strongestCard.id);
                }
            }   
        },

        getMaxCardValue : function(cards) {
          debugger;
            return Math.max.apply(Math, cards.map(function(card) { return card.card_strength; }));
        },

        getTypeFromId : function (cardId) {
            var handArray = Object.values(this.playerHand.getAllItems());
            //return corresponding type from id matching cardId in handarray
            // Iterate over the handArray
            for (let i = 0; i < handArray.length; i++) {
                // If the current item's id matches the cardId
                if (handArray[i].id === cardId) {
                    // Return the type of the current item
                    return handArray[i].type;
                }
            }
            // If no match is found, return null
            return null;
        },

        // Get card unique identifier based on its color and value 
        getCardUniqueId : function(color, value) {
          return (color - 1) * 13 + (value - 2);
        },

        // Get card sort value based on its color and value - i.e. start with 3 value and work up to 2 Max
        getCardSortValue : function(color, value) {
            const imultiple = new Array(2,4,1,3); //CP strength - spades hearts, clubs, diamonds
            var $iValue = value;
            if ($iValue = 2) {$iValue==15};
            //3 CLUBS = 1, 3 SPADES = 2 ETC
            var sortresult=(value-3) * 4 + imultiple[color-1];
            if (sortresult < 1) {
                sortresult=sortresult + 52;
            }
            return sortresult
         },

         selectPair: function(cardType) {
            // Function selects the next highest pair in hand 
            var hand = Object.values(this.playerHand.getAllItems())
            let inputCardGroup = Math.floor((cardType - 1) / 4);        // Calculate the group number of the input card

            // Iterate through the sorted hand
            for (let i = 0; i < hand.length - 1; i++) {
                // Calculate the group numbers of the current card and the next two cards
                let currentCardGroup = Math.floor((hand[i].type - 1) / 4);
                let nextCardGroup = Math.floor((hand[i + 1].type - 1) / 4);

                // If the current card and the next card are in the same group and that group is higher than the input card's group
                if (currentCardGroup === nextCardGroup && currentCardGroup >= inputCardGroup) {
                    this.playerHand.selectItem(hand[i].id);
                    this.playerHand.selectItem(hand[i+1].id);
                    return;
                }
            } 
        },

        // /////////////////////////////////////////////////
        // // Player's action
        
        /*
         * 
         * Here, you are defining methods to handle player's action (ex: results of mouse click on game objects).
         * 
         * Most of the time, these methods: _ check the action is possible at this game state. _ make a call to the game server
         * 
         */

        onPassTurn : function () {
            if (this.checkAction('passTurn', true)) {
                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/passTurn.html", {
                    lock: true}, 
                    this, function (result) {}, function (is_error) {}
                    );
            }
        },

        onSelectCard : function ( evt, item_id ) {
               
     
            if (this.numberofcardsplayed <= 2  && this.numberofcardsplayed > 0) {
                // Get the currently selected items and find the new item selected
                var selectedItems = this.playerHand.getSelectedItems();
                var last_card_selected_id = evt.target.id.replace('myhand_item_', '');
    
                //need to add code that only allows cards to be selected that are higher than the previous card played
                this.playerHand.unselectAll();

                if ( this.numberofcardsplayed == 1) {
                    if (last_card_selected_id) {
                        this.playerHand.selectItem(last_card_selected_id);
                    }
                }
                else if (this.numberofcardsplayed === 2) { // Pairs
             //       var selectedItems = this.playerHand.getSelectedItems();
                    if (last_card_selected_id) {
                        // All cards in the pair or triple are selected
                        this.selectPair(this.getTypeFromId(last_card_selected_id));
                    }
                } 
            }
        },

        onPlayerHandSelectionChanged : function() {
            var items = this.playerHand.getSelectedItems();
      
          //below is typically >0 for single card play
            if (items.length > 0) {
                var action = 'playCard';
                if (this.checkAction(action, true)) {
                    var cards = "";
                    // Can play a card
                    for(var i in items) {
                        cards += items[i].id + ";";                    
                    }
                    this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/playCard.html", {
                    cards : cards,
                    lock : true}, 
                    this, function(result) {}, function(is_error) {}
                    );
                    this.playerHand.unselectAll();

                } else {
                    this.playerHand.unselectAll();
                }
            }
        },

        playCardOnTable : function(player_id, color, value, card_id, smargin) {
            var play_id = (Date.now().toString(36) + Math.random().toString(36).substr(2, 9));
        
            dojo.place(this.format_block('jstpl_cardontable', {
                x : this.cardwidth * (value - 2),
                y : this.cardheight * (color - 1),
                play_id : play_id
            }), 'playertablecard_' + player_id); 

            if (player_id != this.player_id) {
                // Some opponent played a card so move card from player panel 
                this.placeOnObject('cardontable_' + play_id, 'overall_player_board_' + player_id);
            } else {
                // You played a card. If it exists in your hand, move card from there and remove corresponding item
                    if ($('myhand_item_' + card_id)) {
                        this.placeOnObject('cardontable_'+play_id, 'myhand_item_' + card_id);
                        this.playerHand.removeFromStockById(card_id);
                }
            }
            this.slideToObjectPos('cardontable_' + play_id, 'playertablecard_' + player_id, smargin, 0).play();

        },

        playCardsOnTable : function(player_id, items) {
            var margins = [
                ['0px'],
                ['-10px', '10px'],
                ['-22pxpx', '-0px', '22px'],
                ['-22px', '-7px', '7px', '22px'],
                ['-30px', '-15px', '0px', '15px', '30px'],
            ]; 
            var margin = margins[items.length - 1];       
            for (var i in items){           
                this.playCardOnTable(player_id, items[i].type, items[i].type_arg, items[i].id, margin[i]); 
            }
       },
 
        removeCardsFromPlayerTable : function(player_id){
                var djCards =  dojo.query(".cardontable");
                for (let i = 0; i < djCards.length; i++) {
                    var anim = this.slideToObject(djCards[i], 'playertablecard_' + player_id); //'overall_plplay_' + player_id);
                    dojo.connect(anim, 'onEnd', function(node) {dojo.destroy(node);}); 
                    anim.play();  
                }              
        },
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your template.game.php file.
        
        */
        setupNotifications : function() {
            console.log('notifications subscriptions setup');

            dojo.subscribe('newHand', this, "notif_newHand");
            dojo.subscribe('newCards', this, "notif_newCards");
            dojo.subscribe('playCard', this, "notif_playCard");        
            dojo.subscribe( 'trickWin', this, "notif_trickWin" );
            this.notifqueue.setSynchronous( 'trickWin', 1000 );
            dojo.subscribe( 'giveAllCardsToPlayer', this, "notif_giveAllCardsToPlayer");
            dojo.subscribe( 'newScores', this, "notif_newScores" );
        },

        notif_newHand : function(notif) {
            // We received a new full hand of 13 cards.
            this.playerHand.removeAll();

            for ( var i in notif.args.cards) {
                var card = notif.args.cards[i];
                var color = card.type;
                var value = card.type_arg;
                this.playerHand.addToStockWithId(this.getCardSortValue(color, value), card.id);
            }
        },

        notif_newCards : function(notif) {
            for ( var p  in this.gamedatas.players) {
                this.card_counters[p].setValue( 13);  //this.gamedatas.num_cards[p].no_cards );  
                debugger;
            }
        },

        notif_playCard : function(notif) {
            // Play a card on the table - reference to args below are those set in the notifyAllPlayers notification in game.php
            player_id = notif.args.player_id;
            cards = notif.args.cards
            this.playCardsOnTable(player_id, cards);

            //update cards remaining on player board
            this.card_counters[player_id].incValue( -1 * cards.length);
            this.numberofcardsplayed = cards.length;
            this.strengthofPrevCardPlayed = this.getMaxCardValue(cards);
 debugger;

        },
        
        notif_trickWin : function(notif) {
            //reset number of cards played (to allow up to 5 cards to be played in next round)
            this.numberofcardsplayed = 5;
            // We do nothing here (just wait in order for players to view the cards played before they are gone)
        },

        notif_giveAllCardsToPlayer : function(notif)
         {
            var winner_id = notif.args.player_id;
            // Move all cards on table to given table, then destroy them
            for ( var player_id in this.gamedatas.players) {
               this.removeCardsFromPlayerTable(winner_id); }
        },

        notif_newScores : function(notif) {
            // Update players' scores
            for ( var player_id in notif.args.newScores) {
                this.scoreCtrl[player_id].toValue(notif.args.newScores[player_id]);
            }
        },
   });             
});