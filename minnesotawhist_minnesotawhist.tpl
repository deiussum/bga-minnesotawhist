{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- MinnesotaWhist implementation : © Daniel Jenkins <deiussum@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    minnesotawhist_minnesotawhist.tpl
-->

<div id="playertables">
    <div id="team1-info-box" class="team-info-box whiteblock {TEAM1CLASS}">
        <h3>{TEAM1LABEL}</h3>
        <div>
            Score: <span id="team1-score">{TEAM1SCORE}</span>
        </div>
        <div>
            Tricks: <span id="team1-tricks">{TEAM1TRICKS}</span>
        </div>
        <div id="team1-trick-icons" class="team-tricks"></div>
    </div>

    <div id="team2-info-box" class="team-info-box whiteblock {TEAM2CLASS}">
        <h3>{TEAM2LABEL}</h3>
        <div>
            Score: <span id="team2-score">{TEAM2SCORE}</span>
        </div>
        <div>
            Tricks: <span id="team2-tricks">{TEAM2TRICKS}</span>
        </div>
        <div id="team2-trick-icons" class="team-tricks"></div>
    </div>

    <!-- BEGIN player -->
    <div class="playertable playertable_{DIR}">
        <div class="playerheader whiteblock" style="color:#{PLAYER_COLOR}">
            <span class="playertablename">{PLAYER_NAME}</span>
            <span id="icons_{PLAYER_ID}" class="icon-container"></span> 
        </div>
        <div class="playertablecard" id="playertablecard_{PLAYER_ID}">
        </div>
    </div> 
    <!-- END player -->

    <div id="playmode-wrap" class="whiteblock">
        <span id="playmode"></span>
    </div>
</div>

<div id="myhand-wrap" class="whiteblock">
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
var jstpl_cardontable = '<div class="cardontable card" id="cardontable_${player_id}" style="background-position:-${x}% -${y}%"></div>';
var jstpl_flippedcard = '<div class="cardontable card flipped" id="cardontable_${player_id}"></div>';
var jstpl_teamlabel = '<div>${team_label}</div><div id="playericons_${player_id}"></div>';
var jstpl_icon = '<span class="icon ${icon}" title="${icon_text}"></span>';
</script>  

{OVERALL_GAME_FOOTER}
