<?php
session_start();
require_once 'functions.php';

$teams = getTeams();
$loggedIn = isset($_SESSION['admin']) && $_SESSION['admin'] === true;
$timer = getTimer();

// Handle adding/removing teams if admin is logged in
if ($loggedIn && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_team' && !empty($_POST['team_name'])) {
        addTeam($_POST['team_name']);
        header('Location: index.php');
        exit;
    } elseif ($_POST['action'] === 'remove_team' && isset($_POST['team_id'])) {
        removeTeam($_POST['team_id']);
        header('Location: index.php');
        exit;
    } elseif ($_POST['action'] === 'update_score' && isset($_POST['team_id']) && isset($_POST['points'])) {
        updateScore($_POST['team_id'], $_POST['points']);
        header('Location: index.php');
        exit;
    } elseif ($_POST['action'] === 'start_timer') {
        startTimer();
        header('Location: index.php');
        exit;
    } elseif ($_POST['action'] === 'pause_timer') {
        pauseTimer();
        header('Location: index.php');
        exit;
    } elseif ($_POST['action'] === 'stop_timer') {
        stopTimer();
        header('Location: index.php');
        exit;
    } elseif ($_POST['action'] === 'set_timer' && isset($_POST['hours']) && isset($_POST['minutes']) && isset($_POST['seconds'])) {
        $hours = intval($_POST['hours']);
        $minutes = intval($_POST['minutes']);
        $seconds = intval($_POST['seconds']);
        $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;
        setTimerDuration($totalSeconds);
        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Score</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .card-style {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        
        .dark .card-style {
            background: rgba(30, 30, 30, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .animate-gradient {
            background-size: 200% 200%;
            animation: gradient 15s ease infinite;
        }
        
        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        .hover-scale {
            transition: transform 0.3s ease;
        }
        
        .hover-scale:hover {
            transform: scale(1.02);
        }
        
        .btn-glow:hover {
            box-shadow: 0 0 15px rgba(102, 187, 106, 0.6);
        }
        
        .dark .btn-glow:hover {
            box-shadow: 0 0 15px rgba(102, 187, 106, 0.4);
        }
        
        .medal-gold {
            background: linear-gradient(45deg, #FFD700, #FFC800);
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        
        .medal-silver {
            background: linear-gradient(45deg, #C0C0C0, #E0E0E0);
            box-shadow: 0 0 10px rgba(192, 192, 192, 0.5);
        }
        
        .medal-bronze {
            background: linear-gradient(45deg, #CD7F32, #B87333);
            box-shadow: 0 0 10px rgba(205, 127, 50, 0.5);
        }
        
        .progress-bar {
            transition: width 1s linear;
        }
        
        .timer-display {
            font-variant-numeric: tabular-nums;
        }
    </style>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'light-green': {
                            100: '#e8f5e9',
                            200: '#c8e6c9',
                            300: '#a5d6a7',
                            400: '#81c784',
                            500: '#66bb6a',
                            600: '#4caf50',
                            700: '#43a047',
                            800: '#388e3c',
                            900: '#2e7d32',
                        },
                        'custom-gray': {
                            100: '#f5f5f5',
                            200: '#eeeeee',
                            300: '#e0e0e0',
                            400: '#bdbdbd',
                            500: '#9e9e9e',
                            600: '#757575',
                            700: '#616161',
                            800: '#424242',
                            900: '#212121',
                        }
                    },
                    gradientColorStops: theme => ({
                        ...theme('colors'),
                    }),
                    animation: {
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
    <script>
        // On page load or when changing themes, best to add inline in `head` to avoid FOUC
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
        
        function toggleDarkMode() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark')
                localStorage.theme = 'light'
            } else {
                document.documentElement.classList.add('dark')
                localStorage.theme = 'dark'
            }
        }
        
        // Timer functionality
        let timerInterval;
        let timerStatus = '<?php echo $timer['status']; ?>';
        let timerDuration = <?php echo $timer['duration']; ?>;
        let timerRemaining = <?php echo $timer['remaining']; ?>;
        
        function updateTimerDisplay() {
            const timerElement = document.getElementById('timer-display');
            const progressBar = document.getElementById('timer-progress');
            
            if (!timerElement || !progressBar) return;
            
            // Format time as HH:MM:SS
            const hours = Math.floor(timerRemaining / 3600);
            const minutes = Math.floor((timerRemaining % 3600) / 60);
            const seconds = timerRemaining % 60;
            const formattedTime = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Update timer display
            timerElement.textContent = formattedTime;
            
            // Update progress bar
            const progressPercentage = (timerRemaining / timerDuration) * 100;
            progressBar.style.width = `${progressPercentage}%`;
            
            // Change color based on remaining time
            if (progressPercentage > 66) {
                progressBar.classList.remove('bg-yellow-500', 'bg-red-500');
                progressBar.classList.add('bg-light-green-500');
            } else if (progressPercentage > 33) {
                progressBar.classList.remove('bg-light-green-500', 'bg-red-500');
                progressBar.classList.add('bg-yellow-500');
            } else {
                progressBar.classList.remove('bg-light-green-500', 'bg-yellow-500');
                progressBar.classList.add('bg-red-500');
            }
            
            // If timer reaches zero, stop it
            if (timerRemaining <= 0) {
                timerRemaining = 0;
                clearInterval(timerInterval);
                timerStatus = 'stopped';
                
                // Flash the timer when it reaches zero
                timerElement.classList.add('animate-pulse', 'text-red-600', 'dark:text-red-400');
            }
        }
        
        function startTimer() {
            if (timerStatus === 'running') return;
            
            timerStatus = 'running';
            timerInterval = setInterval(() => {
                if (timerRemaining > 0) {
                    timerRemaining--;
                    updateTimerDisplay();
                } else {
                    clearInterval(timerInterval);
                }
            }, 1000);
        }
        
        function pauseTimer() {
            if (timerStatus !== 'running') return;
            
            timerStatus = 'paused';
            clearInterval(timerInterval);
        }
        
        function stopTimer() {
            timerStatus = 'stopped';
            clearInterval(timerInterval);
            timerRemaining = timerDuration;
            updateTimerDisplay();
            
            // Remove flashing effect if present
            const timerElement = document.getElementById('timer-display');
            if (timerElement) {
                timerElement.classList.remove('animate-pulse', 'text-red-600', 'dark:text-red-400');
            }
        }
        
        // Initialize timer on page load
        document.addEventListener('DOMContentLoaded', () => {
            updateTimerDisplay();
            
            if (timerStatus === 'running') {
                startTimer();
            }
        });
    </script>
</head>
<body class="bg-gradient-to-br from-light-green-100 via-light-green-50 to-custom-gray-200 dark:from-custom-gray-900 dark:via-custom-gray-800 dark:to-custom-gray-900 min-h-screen transition-colors duration-500 animate-gradient">
    <div class="container mx-auto px-4 py-8 flex flex-col min-h-screen">
        <header class="mb-8 card-style rounded-xl p-4 shadow-lg">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <div class="bg-light-green-500 dark:bg-light-green-600 h-10 w-10 rounded-full flex items-center justify-center shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-light-green-800 dark:text-light-green-400">Tableau de Score</h1>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="toggleDarkMode()" class="p-2 rounded-full bg-custom-gray-200 dark:bg-custom-gray-700 text-custom-gray-700 dark:text-custom-gray-200 hover:shadow-md transition-all duration-300 hover:bg-custom-gray-300 dark:hover:bg-custom-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                    <?php if ($loggedIn): ?>
                        <div class="flex items-center">
                            <span class="mr-4 text-custom-gray-700 dark:text-custom-gray-300 bg-white bg-opacity-30 dark:bg-black dark:bg-opacity-30 px-3 py-1 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Admin
                            </span>
                            <a href="logout.php" class="bg-custom-gray-600 hover:bg-custom-gray-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300 btn-glow">
                                Déconnexion
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="bg-light-green-600 hover:bg-light-green-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300 btn-glow">
                            Connexion Admin
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <main class="flex-grow">
            <!-- Timer Section -->
            <div class="card-style rounded-xl shadow-lg overflow-hidden mb-8 hover-scale">
                <div class="bg-gradient-to-r from-custom-gray-600 to-custom-gray-800 dark:from-custom-gray-700 dark:to-custom-gray-900 text-white py-4 px-6 flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h2 class="text-xl font-semibold">Chronomètre</h2>
                </div>
                <div class="p-6">
                    <div class="flex flex-col items-center mb-4">
                        <div class="text-5xl font-bold timer-display mb-4 text-custom-gray-800 dark:text-custom-gray-200" id="timer-display">
                            <?php echo formatTime($timer['remaining']); ?>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="w-full h-4 bg-custom-gray-200 dark:bg-custom-gray-700 rounded-full overflow-hidden">
                            <div id="timer-progress" class="h-full bg-light-green-500 progress-bar" style="width: <?php echo ($timer['remaining'] / $timer['duration']) * 100; ?>%"></div>
                        </div>
                    </div>
                    
                    <?php if ($loggedIn): ?>
                        <div class="flex flex-wrap justify-center gap-3 mb-6">
                            <form method="post" class="inline">
                                <input type="hidden" name="action" value="start_timer">
                                <button type="submit" onclick="startTimer()" class="bg-light-green-600 hover:bg-light-green-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-200 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Démarrer
                                </button>
                            </form>
                            
                            <form method="post" class="inline">
                                <input type="hidden" name="action" value="pause_timer">
                                <button type="submit" onclick="pauseTimer()" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-lg transition-all duration-200 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Pause
                                </button>
                            </form>
                            
                            <form method="post" class="inline">
                                <input type="hidden" name="action" value="stop_timer">
                                <button type="submit" onclick="stopTimer()" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition-all duration-200 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Arrêter
                                </button>
                            </form>
                        </div>
                        
                        <!-- Timer Settings Form -->
                        <div class="mt-4 p-4 bg-custom-gray-100 dark:bg-custom-gray-800 rounded-lg">
                            <h3 class="text-lg font-semibold mb-3 text-custom-gray-800 dark:text-custom-gray-200">Régler le chronomètre</h3>
                            <form method="post" class="flex flex-wrap items-end gap-3">
                                <input type="hidden" name="action" value="set_timer">
                                
                                <div class="flex-1 min-w-[100px]">
                                    <label class="block text-sm font-medium text-custom-gray-700 dark:text-custom-gray-300 mb-1">Heures</label>
                                    <input type="number" name="hours" min="0" max="23" value="<?php echo floor($timer['duration'] / 3600); ?>" 
                                           class="w-full px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-light-green-500 bg-white dark:bg-custom-gray-700 border border-custom-gray-300 dark:border-custom-gray-600 text-custom-gray-900 dark:text-white">
                                </div>
                                
                                <div class="flex-1 min-w-[100px]">
                                    <label class="block text-sm font-medium text-custom-gray-700 dark:text-custom-gray-300 mb-1">Minutes</label>
                                    <input type="number" name="minutes" min="0" max="59" value="<?php echo floor(($timer['duration'] % 3600) / 60); ?>" 
                                           class="w-full px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-light-green-500 bg-white dark:bg-custom-gray-700 border border-custom-gray-300 dark:border-custom-gray-600 text-custom-gray-900 dark:text-white">
                                </div>
                                
                                <div class="flex-1 min-w-[100px]">
                                    <label class="block text-sm font-medium text-custom-gray-700 dark:text-custom-gray-300 mb-1">Secondes</label>
                                    <input type="number" name="seconds" min="0" max="59" value="<?php echo $timer['duration'] % 60; ?>" 
                                           class="w-full px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-light-green-500 bg-white dark:bg-custom-gray-700 border border-custom-gray-300 dark:border-custom-gray-600 text-custom-gray-900 dark:text-white">
                                </div>
                                
                                <div>
                                    <button type="submit" class="bg-light-green-600 hover:bg-light-green-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-200">
                                        Appliquer
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Scoreboard Section -->
            <div class="card-style rounded-xl shadow-lg overflow-hidden mb-8 hover-scale">
                <div class="bg-gradient-to-r from-light-green-500 to-light-green-700 dark:from-light-green-700 dark:to-light-green-900 text-white py-4 px-6 flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h2 class="text-xl font-semibold">Équipes et Scores</h2>
                </div>
                <div class="p-6">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-white bg-opacity-30 dark:bg-black dark:bg-opacity-20 border-b border-custom-gray-300 dark:border-custom-gray-600">
                                <th class="py-3 px-4 text-left dark:text-white font-semibold w-12">#</th>
                                <th class="py-3 px-4 text-left dark:text-white font-semibold">Équipe</th>
                                <th class="py-3 px-4 text-center dark:text-white font-semibold">Points</th>
                                <?php if ($loggedIn): ?>
                                    <th class="py-3 px-4 text-center dark:text-white font-semibold">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($teams)): ?>
                                <tr>
                                    <td colspan="<?php echo $loggedIn ? 4 : 3; ?>" class="py-6 px-4 text-center text-custom-gray-600 dark:text-custom-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        Aucune équipe enregistrée
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $rank = 0; ?>
                                <?php foreach ($teams as $id => $team): ?>
                                    <?php $rank++; ?>
                                    <tr class="border-b border-custom-gray-200 dark:border-custom-gray-700 hover:bg-light-green-50 hover:bg-opacity-50 dark:hover:bg-custom-gray-800 dark:hover:bg-opacity-50 transition-colors duration-150">
                                        <td class="py-4 px-4 dark:text-white">
                                            <?php if ($rank === 1): ?>
                                                <div class="medal-gold h-8 w-8 rounded-full flex items-center justify-center text-white font-bold">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                                    </svg>
                                                </div>
                                            <?php elseif ($rank === 2): ?>
                                                <div class="medal-silver h-8 w-8 rounded-full flex items-center justify-center text-white font-bold">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                                    </svg>
                                                </div>
                                            <?php elseif ($rank === 3): ?>
                                                <div class="medal-bronze h-8 w-8 rounded-full flex items-center justify-center text-white font-bold">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                                    </svg>
                                                </div>
                                            <?php else: ?>
                                                <div class="h-8 w-8 rounded-full bg-custom-gray-200 dark:bg-custom-gray-700 flex items-center justify-center font-bold text-custom-gray-700 dark:text-custom-gray-300">
                                                    <?php echo $rank; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-4 dark:text-white">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 rounded-full bg-light-green-200 dark:bg-light-green-900 flex items-center justify-center mr-3 text-light-green-800 dark:text-light-green-300 font-bold">
                                                    <?php echo substr(htmlspecialchars($team['name']), 0, 1); ?>
                                                </div>
                                                <?php echo htmlspecialchars($team['name']); ?>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                            <span class="inline-flex items-center justify-center h-8 w-16 rounded-full bg-light-green-100 dark:bg-light-green-900 text-light-green-800 dark:text-light-green-300 font-bold">
                                                <?php echo $team['points']; ?>
                                            </span>
                                        </td>
                                        <?php if ($loggedIn): ?>
                                            <td class="py-4 px-4">
                                                <div class="flex flex-wrap justify-center gap-1 md:gap-2">
                                                    <!-- Première ligne de boutons -->
                                                    <div class="flex gap-1 md:gap-2">
                                                        <form method="post" class="inline">
                                                            <input type="hidden" name="action" value="update_score">
                                                            <input type="hidden" name="team_id" value="<?php echo $id; ?>">
                                                            <input type="hidden" name="points" value="1">
                                                            <button type="submit" class="bg-light-green-500 hover:bg-light-green-600 text-white px-2 py-1 rounded-lg transition-all duration-200 hover:shadow-md text-xs md:text-sm">
                                                                +1
                                                            </button>
                                                        </form>
                                                        <form method="post" class="inline">
                                                            <input type="hidden" name="action" value="update_score">
                                                            <input type="hidden" name="team_id" value="<?php echo $id; ?>">
                                                            <input type="hidden" name="points" value="10">
                                                            <button type="submit" class="bg-light-green-600 hover:bg-light-green-700 text-white px-2 py-1 rounded-lg transition-all duration-200 hover:shadow-md text-xs md:text-sm">
                                                                +10
                                                            </button>
                                                        </form>
                                                        <form method="post" class="inline">
                                                            <input type="hidden" name="action" value="update_score">
                                                            <input type="hidden" name="team_id" value="<?php echo $id; ?>">
                                                            <input type="hidden" name="points" value="100">
                                                            <button type="submit" class="bg-light-green-700 hover:bg-light-green-800 text-white px-2 py-1 rounded-lg transition-all duration-200 hover:shadow-md text-xs md:text-sm">
                                                                +100
                                                            </button>
                                                        </form>
                                                    </div>
                                                    
                                                    <!-- Deuxième ligne de boutons -->
                                                    <div class="flex gap-1 md:gap-2">
                                                        <form method="post" class="inline">
                                                            <input type="hidden" name="action" value="update_score">
                                                            <input type="hidden" name="team_id" value="<?php echo $id; ?>">
                                                            <input type="hidden" name="points" value="-100">
                                                            <button type="submit" class="bg-custom-gray-700 hover:bg-custom-gray-800 text-white px-2 py-1 rounded-lg transition-all duration-200 hover:shadow-md text-xs md:text-sm">
                                                                -100
                                                            </button>
                                                        </form>
                                                        <form method="post" class="inline">
                                                            <input type="hidden" name="action" value="update_score">
                                                            <input type="hidden" name="team_id" value="<?php echo $id; ?>">
                                                            <input type="hidden" name="points" value="-10">
                                                            <button type="submit" class="bg-custom-gray-600 hover:bg-custom-gray-700 text-white px-2 py-1 rounded-lg transition-all duration-200 hover:shadow-md text-xs md:text-sm">
                                                                -10
                                                            </button>
                                                        </form>
                                                        <form method="post" class="inline">
                                                            <input type="hidden" name="action" value="update_score">
                                                            <input type="hidden" name="team_id" value="<?php echo $id; ?>">
                                                            <input type="hidden" name="points" value="-1">
                                                            <button type="submit" class="bg-custom-gray-500 hover:bg-custom-gray-600 text-white px-2 py-1 rounded-lg transition-all duration-200 hover:shadow-md text-xs md:text-sm">
                                                                -1
                                                            </button>
                                                        </form>
                                                    </div>
                                                    
                                                    <!-- Bouton supprimer -->
                                                    <form method="post" class="inline mt-1 md:mt-0">
                                                        <input type="hidden" name="action" value="remove_team">
                                                        <input type="hidden" name="team_id" value="<?php echo $id; ?>">
                                                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded-lg transition-all duration-200 hover:shadow-md text-xs md:text-sm" 
                                                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette équipe?')">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($loggedIn): ?>
                <div class="card-style rounded-xl shadow-lg overflow-hidden hover-scale">
                    <div class="bg-gradient-to-r from-custom-gray-500 to-custom-gray-700 dark:from-custom-gray-600 dark:to-custom-gray-800 text-white py-4 px-6 flex items-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <h2 class="text-xl font-semibold">Ajouter une équipe</h2>
                    </div>
                    <div class="p-6">
                        <form method="post" class="flex items-center space-x-4">
                            <input type="hidden" name="action" value="add_team">
                            <div class="flex-grow">
                                <input type="text" name="team_name" placeholder="Nom de l'équipe" required
                                       class="w-full px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-light-green-500 bg-white bg-opacity-70 dark:bg-custom-gray-800 dark:bg-opacity-70 border border-custom-gray-300 dark:border-custom-gray-600 text-custom-gray-900 dark:text-white transition-all duration-200">
                            </div>
                            <button type="submit" class="bg-light-green-600 hover:bg-light-green-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 btn-glow flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Ajouter
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>

        <footer class="mt-12 card-style rounded-xl p-4 text-center text-custom-gray-600 dark:text-custom-gray-400 shadow-lg">
            <div class="flex items-center justify-center space-x-2 mb-2">
                <div class="h-1 w-1 rounded-full bg-light-green-400 dark:bg-light-green-600 animate-pulse"></div>
                <div class="h-1 w-1 rounded-full bg-light-green-400 dark:bg-light-green-600 animate-pulse" style="animation-delay: 0.2s"></div>
                <div class="h-1 w-1 rounded-full bg-light-green-400 dark:bg-light-green-600 animate-pulse" style="animation-delay: 0.4s"></div>
            </div>
            <p>© <?php echo date('Y'); ?> Tableau de Score. Tous droits réservés.</p>
        </footer>
    </div>
</body>
</html>

