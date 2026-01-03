<?php
// glicko.php

const BASE_RATING = 400.0;
const SCALE       = 173.7178;
const PI_VAL      = 3.141592653589793;

function g_func($phi) {
    return 1.0 / sqrt(1.0 + 3.0 * $phi * $phi / (PI_VAL * PI_VAL));
}

function E_func($mu, $mu_j, $phi_j) {
    $g = g_func($phi_j);
    return 1.0 / (1.0 + exp(-$g * ($mu - $mu_j)));
}

/**
 * Glicko update for one game against a single opponent.
 * - $r, $rd: player's current rating and RD
 * - $r_op, $rd_op: opponent's rating and RD
 * - $score: 1 win, 0 loss, 0.5 draw
 * - $gamesPlayed: how many games player has played BEFORE this one
 */
function glicko_update($r, $rd, $r_op, $rd_op, $score, $gamesPlayed) {
    // Convert to Glicko scale
    $mu   = ($r    - BASE_RATING) / SCALE;
    $phi  =  $rd   / SCALE;
    $mu_j = ($r_op - BASE_RATING) / SCALE;
    $phi_j = $rd_op / SCALE;

    $E = E_func($mu, $mu_j, $phi_j);
    $g = g_func($phi_j);

    // v and delta
    $v = 1.0 / ($g * $g * $E * (1.0 - $E));
    $delta = $v * $g * ($score - $E);

    // Higher volatility for first 5 games
    $multiplier = ($gamesPlayed < 5) ? 1.15 : 1.0;

    $phi_prime = 1.0 / sqrt(1.0 / ($phi * $phi) + 1.0 / $v);
    $mu_prime  = $mu + $multiplier * ($phi_prime * $phi_prime) * $g * ($score - $E);

    // Convert back
    $r_prime  = SCALE * $mu_prime + BASE_RATING;
    $rd_prime = SCALE * $phi_prime;

    // Clamp RD to avoid it becoming too small
    $rd_min = ($gamesPlayed < 5) ? 80.0 : 40.0;
    if ($rd_prime < $rd_min) {
        $rd_prime = $rd_min;
    }

    return [$r_prime, $rd_prime];
}
