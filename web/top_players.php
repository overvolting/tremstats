<?php
/**
 * Project:     Tremstats
 * File:        top_players.php
 *
 * For license and version information, see /index.php
 */

require_once 'core/init.inc.php';

$last_game  = $db->GetRow("SELECT game_id FROM games ORDER BY game_id DESC LIMIT 0, 1");
$game_cutoff= $last_game['game_id'] - TRESHOLD_MAX_GAMES_PAUSED;

$custom_orders = array (
  'rank'       => 'player_rank',
  'player'     => 'player_name_uncolored',
  'score'      => 'player_score_total',
  'kills'      => 'player_kills',
  'team_kills' => 'player_teamkills',
  'deaths'     => 'player_deaths',
  'efficiency' => 'player_total_efficiency',
  'skill'      => 'skill',
  'skill_a'    => 'skill_a',
  'skill_h'    => 'skill_h'
);
$order = get_custom_sort($custom_orders, 'rank');

$db->Execute("SET @n := 0");
$db->Execute("CREATE TEMPORARY TABLE tmp (
                SELECT player_id,
                       @n := @n + 1 AS player_rank,
                       player_name,
                       player_name_uncolored,
                       player_score_total,
                       player_kills,
                       player_teamkills,
                       player_deaths,
                       player_total_efficiency,
                       t.trueskill_mu - 3 * t.trueskill_sigma AS skill,
                       t.trueskill_sigma AS skill_sigma,
                       t.trueskill_alien_mu - 3 * t.trueskill_alien_sigma AS skill_a,
                       t.trueskill_alien_sigma AS skill_a_sigma,
                       t.trueskill_human_mu - 3 * t.trueskill_human_sigma AS skill_h,
                       t.trueskill_human_sigma AS skill_h_sigma
                FROM players
                 LEFT OUTER JOIN trueskill_last t
                   ON t.trueskill_player_id = players.player_id
                WHERE player_games_played >= ?
                      AND player_last_game_id > ?
                ORDER BY player_total_efficiency DESC
              )", array(TRESHOLD_MIN_GAMES_PLAYED, $game_cutoff));

$pagelister->SetQuery("SELECT player_id,
                              player_rank,
                              player_name,
                              player_score_total,
                              player_kills,
                              player_teamkills,
                              player_deaths,
                              player_total_efficiency,
                              skill, skill_sigma,
                              skill_a, skill_a_sigma,
                              skill_h, skill_h_sigma
                       FROM tmp
                       ORDER BY ".$order);
$top = $db->GetAll($pagelister->GetQuery());

// Assign variables to template
$tpl->assign('top', $top);

// Show the template
$tpl->display('top_players.tpl.php');
?>
