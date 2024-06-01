<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * CPOnline implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * states.inc.php
 *
 * CPOnline game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: $this->checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "newHand" => 20 )
    ),

    /// New hand
    20 => array(
        "name" => "newHand",
        "description" => "",
        "type" => "game",
        "action" => "stNewHand",
        "updateGameProgression" => true,
        "transitions" => array( "playerTurn" => 31, "presidentSwapTurn" => 50)
    ),

    // Trick
    30 => array(
        "name" => "newRound",
        "description" => "",
        "type" => "game",
        "action" => "stNewRound",
        "transitions" => array( "playerTurn" => 31)
    ),

    31 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play or pass'),
        "descriptionmyturn" => clienttranslate('${you} must play or pass'),
        "type" => "activeplayer",
        "possibleactions" => array( "playCard", "passTurn" ),
        "transitions" => array( "newRound" => 30, "nextPlayer" => 32, "passTurn" => 33 )
    ),

    32 => array(
        "name" => "nextPlayer",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => array( "playerTurn" => 31, "newRound" => 30, "endHand" => 40 )
    ),

    33 => array(
        "name" => "passTurn",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => array( "nextPlayer" => 31, "newRound" => 30, "endHand" => 40 )
    ),

    // End of the hand (scoring, etc...)
    40 => array(
        "name" => "endHand",
        "description" => "",
        "type" => "game",
        "action" => "stEndHand",
        "transitions" => array( "newHand" => 20, "endGame" => 99 )
    ),

    50 => array(
        "name" => "presidentSwapTurn",
        "description" => clienttranslate("Other players must swap cards"),
        "descriptionmyturn" => clienttranslate('${you} must swap 2 cards'),
        "possibleactions" => array( "swapCards"),
        "type" => "activeplayer",
        "args" => "argSwapCards",
        "transitions" => array( "endPresidentSwap" => 51)
    ),

    51 => array(
        "name" => "endPresidentSwap",
        "description" => "",
        "type" => "game",
        "action" => "stSwapCards",
        "transitions" => array( "primeMinisterSwapTurn" => 52)
    ),

    52 => array(
        "name" => "primeMinisterSwapTurn",
        "description" => clienttranslate("Other players must swap cards"),
        "descriptionmyturn" => clienttranslate('${you} must swap 1 card'),
        "possibleactions" => array( "swapCards"),
        "type" => "activeplayer",
        "args" => "argSwapCards",
        "transitions" => array( "endPrimeMinisterSwapTurn" => 53)
    ),

    53 => array(
        "name" => " ",
        "description" => "",
        "type" => "game",
        "action" => "stSwapCards",
        "transitions" => array( "playerTurn" => 31)
    ),

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);


