<?php
session_start();
require_once 'functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (verifyAdmin($username, $password)) {
        $_SESSION['admin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Identifiants incorrects';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - Tableau de Score</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    </script>
</head>
<body class="bg-gradient-to-br from-light-green-100 to-custom-gray-200 dark:from-custom-gray-800 dark:to-custom-gray-900 min-h-screen flex items-center justify-center transition-colors duration-200">
    <div class="max-w-md w-full bg-white dark:bg-custom-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-light-green-600 to-light-green-800 dark:from-light-green-700 dark:to-light-green-900 text-white py-4 px-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Connexion Admin</h1>
                <p class="text-light-green-100">Tableau de Score</p>
            </div>
            <button onclick="toggleDarkMode()" class="p-2 rounded-full bg-light-green-700 dark:bg-light-green-800 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>
        </div>
        
        <div class="p-6">
            <?php if ($error): ?>
                <div class="bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 mb-4" role="alert">
                    <p><?php echo $error; ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="mb-4">
                    <label for="username" class="block text-custom-gray-700 dark:text-custom-gray-300 text-sm font-bold mb-2">
                        Nom d'utilisateur
                    </label>
                    <input type="text" id="username" name="username" required
                           class="w-full px-3 py-2 border border-custom-gray-300 dark:border-custom-gray-600 rounded focus:outline-none focus:ring-2 focus:ring-light-green-500 bg-white dark:bg-custom-gray-700 text-custom-gray-900 dark:text-white">
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-custom-gray-700 dark:text-custom-gray-300 text-sm font-bold mb-2">
                        Mot de passe
                    </label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-3 py-2 border border-custom-gray-300 dark:border-custom-gray-600 rounded focus:outline-none focus:ring-2 focus:ring-light-green-500 bg-white dark:bg-custom-gray-700 text-custom-gray-900 dark:text-white">
                </div>
                
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-light-green-600 hover:bg-light-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-light-green-500">
                        Se connecter
                    </button>
                    <a href="index.php" class="text-light-green-600 dark:text-light-green-400 hover:text-light-green-800 dark:hover:text-light-green-300 text-sm">
                        Retour au tableau
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

