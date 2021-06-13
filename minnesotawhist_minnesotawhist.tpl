{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- MinnesotaWhist implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    minnesotawhist_minnesotawhist.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->

<div id="playertables">
    <div id="team1-info-box" class="team-info-box whiteblock">
        <h3>Team 1</h3>
        <div>
            Score: <span id="team1-score">{TEAM1SCORE}</span>
        </div>
        <div>
            Tricks: <span id="team1-tricks">{TEAM1TRICKS}</span>
        </div>
    </div>

    <div id="team2-info-box" class="team-info-box whiteblock">
        <h3>Team 2</h3>
        <div>
            Score: <span id="team2-score">{TEAM2SCORE}</span>
        </div>
        <div>
            Tricks: <span id="team2-tricks">{TEAM2TRICKS}</span>
        </div>
    </div>

    <!-- BEGIN player -->
    <div class="playertable whiteblock playertable_{DIR}">
        <div class="playertablename" style="color:#{PLAYER_COLOR}">
            {PLAYER_NAME}
        </div>
        <div class="playertablecard" id="playertablecard_{PLAYER_ID}">
        </div>
    </div> 
    <!-- END player -->

    <div id="playmode-wrap" class="whiteblock">
        <span id="playmode"></span>
    </div>
</div>

<div id="myhand_wrap" class="whiteblock">
    <h3>{MY_HAND}</h3>
    <div id="myhand">
    </div>
</div>


<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/
var jstpl_cardontable = '<div class="cardontable" id="cardontable_${player_id}" style="background-position:-${x}px -${y}px"></div>';
var jstpl_flippedcard = '<div class="cardontable flipped" id="cardontable_${player_id}"></div>';
var jstpl_teamlabel = '<span>${team_label}</span>'
</script>  

{OVERALL_GAME_FOOTER}
