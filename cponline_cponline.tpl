{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- heartsOldmanwookie implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    cponline_cponline.tpl
    
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

    <!-- BEGIN playerhandblock -->
    <div class="playertable whiteblock playertable_{DIR}">
        <div class="playertablename" style="color:#{PLAYER_COLOR}">
            {PLAYER_NAME}
        </div>
        <div class="playertablecard" id="playertablecard_{PLAYER_ID}">
        </div>
    </div>
    <!-- END playerhandblock -->

</div>

<div id="myhand_wrap" class="whiteblock">
    <h3>{MY_HAND}</h3>
    <div id="myhand">
       <div class="myhand"></div>
    </div>
</div>

<script type="text/javascript">

// Javascript HTML templates
var jstpl_cardontable = '<div class="cardontable" id="cardontable_${play_id}" style="background-position:-${x}px -${y}px">\
                        </div>';
var jstpl_player_board = '<div class="cp_board">\
    <div id="no_cards_p${id}" class="cp_no_cards cp_no_cards_${color}"></div><span id="cardcount_p${id}">0</span>\
</div>';
</script>  

{OVERALL_GAME_FOOTER}
