<?php

/**
 * Decide whether the current operator owns the scoresheet recording role.
 *
 * Preliminary rounds belong to the venue host. Finals belong to the club
 * explicitly assigned as the finals scoresheet recorder. A scoresheet without
 * a venue (for example a sandbox) falls back to its recorded host club.
 */
function cotswold_user_can_access_scoresheet_venue(array $row, bool $is_super_admin, int $current_club_id): bool
{
    if ($is_super_admin) {
        return true;
    }

    if ($current_club_id <= 0) {
        return false;
    }

    $is_final = ((int)($row['round_number'] ?? 0) === 99)
        || in_array($row['gala_type'] ?? 'round', ['a_final', 'b_final', 'c_final'], true);

    if ($is_final) {
        return !empty($row['final_scoresheet_club_id'])
            && (int)$row['final_scoresheet_club_id'] === $current_club_id;
    }

    $host_club_id = (int)($row['venue_host_club_id'] ?? $row['club_id'] ?? $row['host_club_id'] ?? 0);
    return $host_club_id > 0 && $host_club_id === $current_club_id;
}

