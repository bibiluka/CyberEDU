<?php
session_start();
require 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, email, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        header('Location: Acceuil.php');
        exit();
    } else {
        $error_message = "Identifiants invalides.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberEDU</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-100 text-slate-800 flex flex-col min-h-screen">

<header class="bg-blue-700 text-white shadow-lg z-20">
        <div class="container mx-auto px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4">
                <img src="../Image/logo.png" alt="Logo CyberEDU" class="logo">
            <div class="flex items-center gap-8">
                <nav>
                    <ul class="flex gap-6 text-sm font-medium">
                        <li><a href="Acceuil.php" class="hover:text-blue-200 transition">Accueil</a></li>
                        <li><a href="Cantine.php" class="hover:text-blue-200 transition">Cantine</a></li>
                        <li><a href="Dashboard.php" class="hover:text-blue-200 transition">Dashboard</a></li>
                        <li><a href="Messagerie.php" class="hover:text-blue-200 transition">Messagerie</a></li>
                        <li><a href="inscription.php" class="hover:text-blue-200 transition">Inscription</a></li>
                    </ul>
                </nav>
            </div>

            <div class="w-full md:w-64">
                <div class="relative">
                    <input type="search" id="search-input" placeholder="Rechercher..." 
                           class="w-full bg-blue-800/50 border border-blue-400 text-white placeholder-blue-200 text-sm rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-white/50 transition">
                    <i class="fa-solid fa-magnifying-glass absolute right-3 top-2.5 text-blue-200"></i>
                </div>
            </div>
        </div>
    </header>

    <main class="main-gradient flex-grow py-10 px-6">
        <div class="container mx-auto">
            <section class="bg-blue-50/80 border border-blue-100 rounded-2xl shadow-sm p-8 min-h-[500px]">
                <section class="intro-section">
                    <h2>Connexion</h2>
                    <?php if ($error_message): ?>
                        <p style="color:red;"><?php echo $error_message; ?></p>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="email" name="email" placeholder="Email" required>
                        <input type="password" name="password" placeholder="Mot de passe" required>
                        <button type="submit">Se connecter</button>
                    </form>
                    <p>Pas de compte ? <a href="inscription.php">S'inscrire</a></p>
                </section>
                <div id="apps-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
                    </div>
            </section>
        </div>
    </main>

    <footer class="bg-slate-700 text-slate-300 py-4 text-center">
        <p class="text-xs">&copy; <span id="currentYear"></span> Justradamus - Tous droits réservés.</p>
    </footer>

    <script src="./Module/Script.js"></script>
    <script>document.getElementById('currentYear').textContent = new Date().getFullYear();</script>
</body>
</html>