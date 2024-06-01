<?php
 /***
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * template implementation : © <Your name here> <Your email address here>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * cponline.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class cponline extends Table {

	function __construct() {
        	 
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        
	    parent::__construct();
	    self::initGameStateLabels( array(
	            "currentHandType" => 10,
                "numberOfCardsPlayed" => 11,
                "endHand" => 12,
	            //      ...
	            //    "my_first_game_variant" => 100,
	    ) );
	    
	    $this->cards = self::getNew( "module.common.deck" );
	    $this->cards->init( "card" );        
	}
	
    protected function getGameName( )
    {return "cponline";  }      // Used for translations and stuff. Please do not modify.
    	
    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/purple/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $default_colors = array( "ff0000", "008000", "0000ff", "800080", "773300" );
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        $player[3] = "Steve";

        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( ',', $values);
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, array(  "ff0000", "008000", "0000ff", "800080", "773300" ) );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
    
        // currentHandType not used
        self::setGameStateInitialValue( 'currentHandType', 0 );
        self::setGameStateInitialValue( 'endHand', 0 );
     
        // Init game statistics
        // (note: statistics used in this file are defined in stats.json)
        self::initStat('table', 'no_of_passes', 0);
        self::initStat('table', 'no_of_cards_in_round', 0);

        // Create cards
        $cards = array ();
     
        foreach ( $this->colors as $color_id => $color ) {
            // spade, heart, diamond, club
            for ($value = 2; $value <= 14; $value ++) {    
                //  2, 3, 4, ... K, A 
                $cards [] = array ('type' => $color_id,'type_arg' => $value,'nbr' => 1 );
            }
        }
        
        $this->cards->createCards( $cards, 'deck' );

        //add card strength field for each card in deck
        $cards = $this->cards->getCardsInLocation('deck');

        foreach ($cards as $card) {
            $cardstrength = $this->getCardSortValue($card['type'], $card['type_arg']);
            $cardid = $card['id'];
            $sql = "UPDATE card SET card_strength='$cardstrength' WHERE card_id = '$cardid'";
            self::DbQuery( $sql );
        }

        // Shuffle deck
        $this->cards->shuffle('deck');
        $players = self::loadPlayersBasicInfos();
        foreach ( $players as $player_id => $player ) {
            $cards = $this->cards->pickCards(13, 'deck', $player_id);             // Deal 13 cards to each players
        } 
        
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
        $result = array( 'players' => array() );
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // Cards in player hand -  replaced below with SQL due to some errors occuring
        $result['hand'] = $this->cards->getCardsInLocation( 'hand', $current_player_id );

        //Number of cards in each players hand
        $sql = "SELECT `card_location_arg`, count(*) as `no_cards` FROM `card` WHERE `card_location` = 'hand' GROUP by card_location_arg"; 
        $result['num_cards'] = self::getCollectionFromDb( $sql );

        // Cards played on the table - CHECK IF USED
        $result['cardsontable'] = $this->cards->getCardsInLocation( 'cardsontable' );

        // Number of cards played in round and max strength of last card played
        $result['no_cards_in_round'] = self::getStat( 'no_of_cards_in_round');
        $result['strength_of_last_card_played']= self::getGameStateValue('currentHandType');

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
     * In this space, you can put any utility methods useful for your game logic
     */
        //////////////////////////////////////////////////////////////////////////////
        //////////// Player actions
        //////////// 
        /*
     * Each time a player is doing some game action, one of the methods below is called.
     * (note: each method below must match an input method in template.action.php)
     */
    function checkIfPlayerHasFinished($player_id) 
    {
        if (empty($this->cards->getPlayerHand($player_id))) {
                self::notifyAllPlayers('playerFinished', clienttranslate('${player_name} wins round'), [
                'player_name' => self::getActivePlayerName(),
            ]);
        }
    }
//below function needs updating before being called:  Player table needs player_has_passed field and the game state nextPlayer needs 
// to be setup (if not already)  action buttons with this function call are also required
  function passTurn() 
    {
        self::checkAction("passTurn");
        $player_id = self::getActivePlayerId();
        self::notifyAllPlayers('passTurn', clienttranslate('${player_name} chooses to pass'), [
            'player_name' => self::getActivePlayerName(),
            'player_id' => $player_id,
        ]);

        // add stat player passes 
        self::incStat(1,'no_of_passes');
        
        $sql = "UPDATE player SET player_has_passed='1' WHERE player_id='$player_id'";
        self::DbQuery( $sql );

        // Next player
        $this->gamestate->nextState('nextPlayer');
    }

    //Below has been updated to ensure multiple cards played
    function playCard($card_ids) {
        self::checkAction("playCard");
        $player_id = self::getActivePlayerId();
        $no_cards = count($card_ids);
        $cards = [];
        $currentCard = null;
        $numberOfCardsInRound = 0; // Initialize to 0
      
        //reset number of passes counter (for table) and set number of cards played in round
        self::setStat(0, 'no_of_passes');
        if (self::getStat( 'no_of_cards_in_round') ==  0) {
            self::setStat( $no_cards,'no_of_cards_in_round'); }
        else {$numberOfCardsInRound = self::getStat('no_of_cards_in_round');
        }
       
        //Flag end of hand if highest card has been played
        if ($no_cards < 4 and $card_ids[$no_cards-1] == $this->findBestCard()) {
            // end round
             self::setGameStateValue( 'endHand', 1);
        }
   
        //first move existing players cards out to cardswon
        $this->cards->moveAllCardsInLocation('cardsontable', 'cardswon', $player_id);
        $this->cards->moveCards($card_ids, 'cardsontable', $player_id);

        //populate $cards array (and add card strength to the array)
        foreach ($card_ids as $card_id) {
            $currentCard = $this->cards->getCard($card_id);
            $currentCard['card_id'] = $card_id;
            $sql = "SELECT card_strength FROM `card` WHERE card_id = '$card_id'";
            $card_strength =  self::getObjectFromDb( $sql);  
            $currentCard['card_strength'] = $card_strength['card_strength']; // Add 'card_strength' to the cards array
            $cards[] = $currentCard;
        }

        // XXX check rules here
        // Make sure player plays the correct number of cards (which is determined by the # of cards first played in round)
        if ($no_cards != $numberOfCardsInRound && $numberOfCardsInRound > 0 || $no_cards > 5) {
            throw new BgaUserException(self::_("The number of cards played must match the number of cards in play."));
        }
        if ($no_cards == 4) {
            throw new BgaUserException(self::_("You can't play 4 cards."));
        }    
        //If 2 cards are played they must be a pair (and same for 3 cards being a triple)
        if ($no_cards == 2) {
            if ($cards[0]['type_arg'] != $cards[1]['type_arg']) {
                throw new BgaUserException(self::_("You can't play 2 cards unless you play a pair."));
            }
        }
        if ($no_cards == 3) {
            if ($cards[0]['type_arg'] != $cards[1]['type_arg'] || $cards[0]['type_arg'] != $cards[2]['type_arg']) {
                throw new BgaUserException(self::_("You have to play a triple if playing 3 cards."));
            }
        }  

        //check if the card(s) played (for singles and pairs) are higher than those already played
        $cardStrengths = array_column($cards, 'card_strength');
        $maxCardStrength = max($cardStrengths);
        if ($maxCardStrength < self::getGameStateValue('currentHandType') && count($cards) < 3) {
            throw new BgaUserException(self::_("You must play a higher card."));
        }
        self::setGameStateValue( 'currentHandType', $maxCardStrength );

        //List value and color of all cards played in cards array and put in string (separated by commas with and before the last card)
        $cardDetails = "";

        $cardCount = count($cards);
        foreach ($cards as $index => $card) {
            $cardDetails .= $this->values_label[$card['type_arg']] . " " . $this->colors[$card['type']]['name'];
            if ($index === $cardCount - 2) {
                $cardDetails .= " and ";
            } elseif ($index !== $cardCount - 1) {
                $cardDetails .= ", ";
            }
        }

        // And notify 
        self::notifyAllPlayers('playCard', clienttranslate('${player_name} played ${value_displayed}'), array (
                'i18n' => array ('color_displayed', 'value_displayed'),
                'cards' => $cards,
                'card_id' => $card_id,
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'value' => $currentCard ['type_arg'],
                'value_displayed' => $cardDetails,  
                'color' => $currentCard ['type'],
                'color_displayed' => $this->colors [$currentCard ['type']] ['name'],
                'no_cards' => $this->card_count_label[$no_cards],
            ));

        // Next player
        if (($this->cards->countCardInLocation('hand') == 0)) {
            $this->gamestate->nextState('endHand');}
        else {
            $this->gamestate->nextState('nextPlayer');}
    }
    
    function trickWin($player_id){
          // reset counters
          self::setStat(0,'no_of_passes');
          self::setGameStateValue( 'endHand', 0);
          self::setGameStateValue( 'currentHandType', 0 );

          // Move all cards to "cardswon" of the given player
          $this->cards->moveAllCardsInLocation('cardsontable', 'cardswon', null, $player_id);
          $player_name = self::getPLayerNameById($player_id);

          // Notify
          // Note: we use 2 notifications - the first in order to pause the display at end of the trick
          //  the second to move all cards to the winner
          $players = self::loadPlayersBasicInfos();
          self::notifyAllPlayers( 'trickWin', clienttranslate('${player_name} wins the trick'), array(
                  'player_id' => $player_id,
                  'player_name' => $player_name
          ) );

          self::notifyAllPlayers( 'giveAllCardsToPlayer','', array(
              'player_id' => $player_id
          ) );
          self::setStat( 0,'no_of_cards_in_round');
    }
        //////////////////////////////////////////////////////////////////////////////
        //////////// Game state arguments
        ////////////
        /*
     * Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
     * These methods function is to return some additional information that is specific to the current
     * game state.
     */
 //   function argGiveCards() {
 //       return array ();
 //   }

        //////////////////////////////////////////////////////////////////////////////
        //////////// Game state actions
        ////////////
        /*
     * Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
     * The action method of state X is called everytime the current game state is set to X.
     */
    function stNewHand() {
        // Take back all cards (from any location => null) to deck
        $this->cards->moveAllCardsInLocation(null, "deck");
        $this->cards->shuffle('deck');
        // Deal 13 cards to each players
        // Create deck, shuffle it and give 13 initial cards
        $players = self::loadPlayersBasicInfos();
        foreach ( $players as $player_id => $player ) {
            $cards = $this->cards->pickCards(13, 'deck', $player_id);
            // Notify player about his cards
            self::notifyPlayer($player_id, 'newHand', '', array ('cards' => $cards ));
        }

        //notify Players (to update card counter) 
        self::notifyAllPlayers( 'newCards','', array(
            'player_id' => $player_id
        ) );

        // New hand: activate the player who has the 3 of clubs
        $cards = $this->cards->getCardsInLocation("hand");

        //start with player who has 3 of clubs 
        foreach ( $cards as $card ) {       
            if ($card['type_arg'] == 3 AND $card['type'] == '3') 
                {$firstPlayer = $card['location_arg'];}
        }
        $this->gamestate->changeActivePlayer( $firstPlayer );
        $this->gamestate->nextState("");
    }

    function stNewTrick() {
       $this->gamestate->nextState("");
    }

    function stNextPlayer() {
        $current_player_id = self::getCurrentPlayerId();  
        $cardsleft = $this->cards->countCardInLocation('hand', $current_player_id); 
        $player_id = self::getActivePlayerID();

        if ($cardsleft == 0) {
            self::trickWin($player_id);     
            // End of the hand 
            $this->gamestate->nextState("endHand");  
        } 
        else {      
            if(self::getStat('no_of_passes')==3) {
                $player_id = self::activeNextPlayer(); 
                self::trickWin($player_id);
                $this->gamestate->nextState('nextPlayer');
            }
            elseif(self::getGameStateValue('endHand')){
                self::trickWin($player_id);
                $this->gamestate->nextState('nextPlayer');
            }
            else {

                $player_id = self::activeNextPlayer();
                //get number of cards left for next player
                $cardsleftnextplayer = $this->cards->countCardInLocation('hand', $player_id);
               // echo 'Player ID: ' . $player_id;

                //check if the next player has enough cards to play (and go to next player if not)
                if ($cardsleftnextplayer < self::getStat('no_of_cards_in_round')) {
                    self::notifyAllPlayers('playerPassed', clienttranslate('${player_name} passed'), [
                        'player_name' => self::getActivePlayerName(),
                    ]);
                    self::incStat(1,'no_of_passes');
                    $this->gamestate->nextState('nextPlayer');
                    $this->gamestate->nextState('nextPlayer');
                }
                else {
                    // Standard case (not the end of the trick)
                    self::giveExtraTime($player_id);
                    $this->gamestate->nextState('nextPlayer');
                }
            
            }
        }
    }

    function stEndHand() {
            // Count and score points, then end the game or go to the next hand.
        $players = self::loadPlayersBasicInfos(); 
        $player_to_points = array ();
    
        foreach ( $players as $player_id => $player ) {
            $player_to_points [$player_id] = 0;
        }
        $cards = $this->cards->getCardsInLocation("hand");
        foreach ( $cards as $card ) {
            $player_id = $card ['location_arg'];
            $player_to_points [$player_id] ++;
        } 

        // Apply scores to player
        foreach ( $player_to_points as $player_id => $points ) {
            //Double scores (for 9, 10, and 11) and triple (for 12 and 13)
            if ($player_to_points [$player_id] >8 and $player_to_points [$player_id] <12){
                $points = $points * 2;
            }
            if ($player_to_points [$player_id] >11){
                $points = $points * 3;
            }

            if ($points != 0) {
                $sql = "UPDATE player SET player_score=player_score-$points  WHERE player_id='$player_id'";
                self::DbQuery($sql);
                $pt_number = $player_to_points [$player_id];
                self::notifyAllPlayers("points", clienttranslate('${player_name} had ${nbr} cards left'), array (
                        'player_id' => $player_id,'player_name' => $players [$player_id] ['player_name'],
                        'nbr' => $pt_number ));
            } else {
                // No point lost (just notify)
                self::notifyAllPlayers("points", clienttranslate('${player_name} won the round'), array (
                        'player_id' => $player_id,'player_name' => $players [$player_id] ['player_name'] ));
            }
        }
        $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true );
        self::notifyAllPlayers( "newScores", '', array( 'newScores' => $newScores ) );
            
        ///// Test if this is the end of the game
        foreach ( $newScores as $player_id => $score ) {
            if ($score <= -50) {
                // Trigger the end of the game !
                $this->gamestate->nextState("endGame");
                return;
            }
        }    
        $this->gamestate->nextState("nextHand");
    }
    

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] == "activeplayer") {
            switch ($statename) {
                default:
                $this->passTurn();
                break;
            }

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
    function findBestCard()   //return the highest card ID remaining
    {    
        $sql = "SELECT card_id FROM card WHERE `card_location` = 'hand' ORDER BY card_strength DESC LIMIT 1";
        $row =  self::getObjectFromDb( $sql);  
        return $row['card_id'];
    }


      // Get card sort value based on its color and value - i.e. start with 3 value and work up to 2 Max
    function getCardSortValue ($color, $value) {
        $imultiple =  Array(2,4,1,3); //CP strength - spades, hearts, clubs, diamonds
        $iValue = $value;
        if ($iValue = 2) {$iValue==15;}
        //3 CLUBS = 1, 3 SPADES = 2 ETC
        $sortresult=($value-3) * 4 + $imultiple[$color-1];
        if ($sortresult < 1) {
            $sortresult = $sortresult + 52;
        }
        return $sortresult;
     }

    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            $sql = "ALTER TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            $sql = "CREATE TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}