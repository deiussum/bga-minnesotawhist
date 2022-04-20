<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MinnesotaWhist implementation : © Daniel Jenkins <deiussum@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * minnesotawhist.action.php
 *
 * MinnesotaWhist main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/minnesotawhist/minnesotawhist/myAction.html", ...)
 *
 */
  
  
  class action_minnesotawhist extends APP_GameAction
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
            $this->view = "minnesotawhist_minnesotawhist";
            self::trace( "Complete reinitialization of board game" );
        } 
  	} 
  	
  	// defines your action entry points there

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
    public function playCard() {
        self::setAjaxMode();
        $card_id = self::getArg("id", AT_posint, true);
        $this->game->playCard($card_id);
        self::ajaxResponse();
    }

    public function playBid() {
      self::setAjaxMode();
      $card_id = self::getArg("id", AT_posint, true);
      $this->game->playBid($card_id);
      self::ajaxResponse();
    }

    public function claimNoAceNoFace() {
      self::setAjaxMode();
      $this->game->claimNoAceNoFace();
      self::ajaxResponse();
    }

    public function selectCard() {
      self::setAjaxMode();
      $card_id = self::getArg("id", AT_posint, true);
      $this->game->selectCard($card_id);
      self::ajaxResponse();
    }

    public function clearSelection() {
      self::setAjaxMode();
      $this->game->clearSelection();
      self::ajaxResponse();
    }
    
    public function updateAutoPlay() {
      self::setAjaxMode();
      $auto_play = self::getArg("auto_play", AT_posint, true);
      $this->game->updateAutoPlay($auto_play);
      self::ajaxResponse();
    }
  }
  

