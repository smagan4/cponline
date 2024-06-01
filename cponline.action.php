<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * cponline implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * cponline.action.php
 *
 * cponline main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/cponline/cponline/myAction.html", ...)
 *
 */
  
  
  class action_cponline extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "cponline_cponline";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 
    /*
    public function playCard() {
      self::setAjaxMode();
      $card_id = self::getArg("id", AT_posint, true);
      $this->game->playCard($card_id);
      self::ajaxResponse();
  }
*/
  public function passTurn()
    {
    self::setAjaxMode();
    $this->game->passTurn();
    self::ajaxResponse();
  }
  
  public function playCard()
    {
        self::setAjaxMode();     

        $card_ids_raw = $this->getArg( "cards", AT_numberlist, true );
        
        // Removing last ';' if exists
        if( substr( $card_ids_raw, -1 ) == ';' )
            $card_ids_raw = substr( $card_ids_raw, 0, -1 );
        if( $card_ids_raw == '' )
            $card_ids = array();
        else
            $card_ids = explode( ';', $card_ids_raw );

        $this->game->playCard( $card_ids );
        self::ajaxResponse( );
    }
}
  	
  	// TODO: defines your action entry points there


    /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */

  

