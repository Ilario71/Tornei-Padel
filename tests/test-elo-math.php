<?php
/**
 * Test unitario logica ELO (senza WordPress).
 * Esegui: php wordpress/tornei-padel/tests/test-elo-math.php
 */

declare(strict_types=1);

const START_ELO = 1000;
const K_FACTOR_NEW = 32;
const K_FACTOR_ESTABLISHED = 24;
const ESTABLISHED_MATCHES = 20;

function expected_score(float $elo_a, float $elo_b): float
{
    return 1.0 / (1.0 + 10.0 ** (($elo_b - $elo_a) / 400.0));
}

function update_player(int $before, int $matches_played, float $opp_elo, bool $won): int
{
    $k = $matches_played >= ESTABLISHED_MATCHES ? K_FACTOR_ESTABLISHED : K_FACTOR_NEW;
    $expected = expected_score((float) $before, $opp_elo);
    $actual = $won ? 1.0 : 0.0;
    $delta = (int) round($k * ($actual - $expected));

    return max(100, $before + $delta);
}

$failures = 0;

// Match equilibrato: vincitore guadagna ~16 pt con K=32
$after_win = update_player(1000, 0, 1000.0, true);
if ($after_win !== 1016) {
    echo "FAIL equal match winner: expected 1016, got {$after_win}\n";
    $failures++;
} else {
    echo "OK  equal match winner -> {$after_win}\n";
}

$after_loss = update_player(1000, 0, 1000.0, false);
if ($after_loss !== 984) {
    echo "FAIL equal match loser: expected 984, got {$after_loss}\n";
    $failures++;
} else {
    echo "OK  equal match loser -> {$after_loss}\n";
}

// Coppia media vs coppia media (stesso calcolo dell'app)
$side1_avg = (1000 + 1200) / 2; // 1100
$side2_avg = (1000 + 1000) / 2; // 1000
$p1 = update_player(1000, 5, $side2_avg, true);
$p2 = update_player(1200, 5, $side2_avg, true);
if ($p1 <= 1000 || $p2 <= 1200) {
    echo "FAIL doubles average: winners should gain ELO (p1={$p1}, p2={$p2})\n";
    $failures++;
} else {
    echo "OK  doubles average winners gain (p1={$p1}, p2={$p2})\n";
}

// K-factor stabilito dopo 20 match
$k_new = update_player(1000, 0, 1000.0, true) - 1000;
$k_est = update_player(1000, 20, 1000.0, true) - 1000;
if ($k_new !== 16 || $k_est !== 12) {
    echo "FAIL k-factor: new delta={$k_new}, established delta={$k_est}\n";
    $failures++;
} else {
    echo "OK  k-factor new=+{$k_new}, established=+{$k_est}\n";
}

// Floor a 100
$floor = update_player(110, 0, 2000.0, false);
if ($floor !== 100) {
    echo "FAIL elo floor: expected 100, got {$floor}\n";
    $failures++;
} else {
    echo "OK  elo floor at 100\n";
}

exit($failures === 0 ? 0 : 1);
