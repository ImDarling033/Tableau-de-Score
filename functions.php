<?php
// File paths
define('TEAMS_FILE', 'equipe.txt');
define('POINTS_FILE', 'Point.txt');
define('ADMIN_FILE', 'compte profilAdmin.txt');
define('TIMER_FILE', 'timer.txt');

/**
 * Get all teams with their points
 * @return array
 */
function getTeams() {
    $teams = [];
    
    // Read teams
    if (file_exists(TEAMS_FILE)) {
        $teamsData = file(TEAMS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($teamsData as $line) {
            list($id, $name) = explode('|', $line);
            $teams[$id] = ['name' => $name, 'points' => 0];
        }
    }
    
    // Read points
    if (file_exists(POINTS_FILE)) {
        $pointsData = file(POINTS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($pointsData as $line) {
            list($teamId, $points) = explode('|', $line);
            if (isset($teams[$teamId])) {
                $teams[$teamId]['points'] = (int)$points;
            }
        }
    }
    
    // Sort teams by points (descending)
    uasort($teams, function($a, $b) {
        return $b['points'] - $a['points'];
    });
    
    return $teams;
}

/**
 * Add a new team
 * @param string $teamName
 * @return bool
 */
function addTeam($teamName) {
    // Generate a unique ID
    $id = uniqid();
    
    // Add to teams file
    $success = file_put_contents(
        TEAMS_FILE, 
        $id . '|' . $teamName . PHP_EOL, 
        FILE_APPEND | LOCK_EX
    );
    
    // Initialize points
    if ($success) {
        file_put_contents(
            POINTS_FILE, 
            $id . '|0' . PHP_EOL, 
            FILE_APPEND | LOCK_EX
        );
    }
    
    return $success !== false;
}

/**
 * Remove a team
 * @param string $teamId
 * @return bool
 */
function removeTeam($teamId) {
    // Remove from teams file
    $teams = [];
    if (file_exists(TEAMS_FILE)) {
        $teamsData = file(TEAMS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($teamsData as $line) {
            list($id, $name) = explode('|', $line);
            if ($id !== $teamId) {
                $teams[] = $line;
            }
        }
        file_put_contents(TEAMS_FILE, implode(PHP_EOL, $teams) . (empty($teams) ? '' : PHP_EOL));
    }
    
    // Remove from points file
    $points = [];
    if (file_exists(POINTS_FILE)) {
        $pointsData = file(POINTS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($pointsData as $line) {
            list($id, $score) = explode('|', $line);
            if ($id !== $teamId) {
                $points[] = $line;
            }
        }
        file_put_contents(POINTS_FILE, implode(PHP_EOL, $points) . (empty($points) ? '' : PHP_EOL));
    }
    
    return true;
}

/**
 * Update team score
 * @param string $teamId
 * @param int $pointsToAdd
 * @return bool
 */
function updateScore($teamId, $pointsToAdd) {
    $teams = getTeams();
    
    if (!isset($teams[$teamId])) {
        return false;
    }
    
    $currentPoints = $teams[$teamId]['points'];
    $newPoints = max(0, $currentPoints + (int)$pointsToAdd); // Ensure points don't go below 0
    
    // Update points file
    $points = [];
    if (file_exists(POINTS_FILE)) {
        $pointsData = file(POINTS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($pointsData as $line) {
            list($id, $score) = explode('|', $line);
            if ($id === $teamId) {
                $points[] = $id . '|' . $newPoints;
            } else {
                $points[] = $line;
            }
        }
        file_put_contents(POINTS_FILE, implode(PHP_EOL, $points) . (empty($points) ? '' : PHP_EOL));
    }
    
    return true;
}

/**
 * Verify admin credentials
 * @param string $username
 * @param string $password
 * @return bool
 */
function verifyAdmin($username, $password) {
    if (file_exists(ADMIN_FILE)) {
        $adminsData = file(ADMIN_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($adminsData as $line) {
            list($storedUsername, $storedPassword) = explode('|', $line);
            if ($username === $storedUsername && $password === $storedPassword) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Get timer data
 * @return array
 */
function getTimer() {
    $timerData = [
        'duration' => 3600, // Default: 1 hour in seconds
        'remaining' => 3600,
        'status' => 'stopped', // 'running', 'stopped', 'paused'
        'start_time' => 0,
        'pause_time' => 0
    ];
    
    if (file_exists(TIMER_FILE)) {
        $data = file_get_contents(TIMER_FILE);
        if (!empty($data)) {
            $savedData = json_decode($data, true);
            if (is_array($savedData)) {
                $timerData = $savedData;
                
                // Calculate remaining time if timer is running
                if ($timerData['status'] === 'running' && $timerData['start_time'] > 0) {
                    $elapsed = time() - $timerData['start_time'];
                    $timerData['remaining'] = max(0, $timerData['duration'] - $elapsed);
                    
                    // Auto-stop if timer has reached zero
                    if ($timerData['remaining'] <= 0) {
                        $timerData['remaining'] = 0;
                        $timerData['status'] = 'stopped';
                        saveTimer($timerData);
                    }
                }
            }
        }
    }
    
    return $timerData;
}

/**
 * Save timer data
 * @param array $timerData
 * @return bool
 */
function saveTimer($timerData) {
    return file_put_contents(TIMER_FILE, json_encode($timerData), LOCK_EX) !== false;
}

/**
 * Start the timer
 * @return bool
 */
function startTimer() {
    $timer = getTimer();
    
    // Only start if not already running
    if ($timer['status'] !== 'running') {
        // If paused, adjust start time to account for paused duration
        if ($timer['status'] === 'paused' && $timer['pause_time'] > 0) {
            $pauseDuration = time() - $timer['pause_time'];
            $timer['start_time'] = $timer['start_time'] + $pauseDuration;
        } else {
            // Fresh start
            $timer['start_time'] = time();
        }
        
        $timer['status'] = 'running';
        $timer['pause_time'] = 0;
        
        return saveTimer($timer);
    }
    
    return false;
}

/**
 * Pause the timer
 * @return bool
 */
function pauseTimer() {
    $timer = getTimer();
    
    // Only pause if running
    if ($timer['status'] === 'running') {
        // Calculate remaining time
        $elapsed = time() - $timer['start_time'];
        $timer['remaining'] = max(0, $timer['duration'] - $elapsed);
        $timer['status'] = 'paused';
        $timer['pause_time'] = time();
        
        return saveTimer($timer);
    }
    
    return false;
}

/**
 * Stop the timer
 * @return bool
 */
function stopTimer() {
    $timer = getTimer();
    $timer['status'] = 'stopped';
    $timer['remaining'] = $timer['duration'];
    $timer['start_time'] = 0;
    $timer['pause_time'] = 0;
    
    return saveTimer($timer);
}

/**
 * Set timer duration
 * @param int $seconds
 * @return bool
 */
function setTimerDuration($seconds) {
    $timer = getTimer();
    $timer['duration'] = max(1, intval($seconds));
    
    // If timer is stopped, also update remaining time
    if ($timer['status'] === 'stopped') {
        $timer['remaining'] = $timer['duration'];
    }
    
    return saveTimer($timer);
}

/**
 * Format seconds to HH:MM:SS
 * @param int $seconds
 * @return string
 */
function formatTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}

