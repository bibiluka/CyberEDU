<?php
session_start();
require 'db.php'; 

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Vérification de connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Sécurisation de l'email pour l'affichage (évite l'Undefined array key)
$currentUserEmail = $_SESSION['user_email'] ?? 'Utilisateur inconnu';

// 1. Traitement de l'envoi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['content'])) {
    $content = htmlspecialchars($_POST['content']);
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO messages (expediteur_id, contenu) VALUES (?, ?)");
    $stmt->execute([$user_id, $content]);
    
    header('Location: Messagerie.php');
    exit();
}

// 2. Récupération des messages
$query = $pdo->query("
    SELECT m.contenu as content, m.date_envoi as created_at, u.email 
    FROM messages m 
    JOIN users u ON m.expediteur_id = u.id 
    ORDER BY m.date_envoi DESC 
    LIMIT 50
");
$messages = $query->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberEDU - Messagerie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./Module/Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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
                        <li><a href="Messagerie.php" class="hover:text-blue-200 transition font-bold border-b-2 border-white">Messagerie</a></li>
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
            <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8 min-h-[500px]">
                <h2 class="text-2xl font-bold text-slate-700 mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-comments text-blue-600"></i> Messagerie
                </h2>
                
                <div class="mb-6 p-3 bg-blue-50 rounded-lg">
                    <p>Connecté en tant que : <span class="font-bold text-blue-700"><?php echo htmlspecialchars($currentUserEmail); ?></span></p>
                </div>

                <div class="space-y-4 mb-6 max-h-[400px] overflow-y-auto p-4 border rounded-lg bg-slate-50">
                    <?php if (empty($messages)): ?>
                        <p class="text-center text-slate-500 italic">Aucun message pour le moment.</p>
                    <?php endif; ?>

                    <?php foreach ($messages as $msg): ?>
                        <div class="flex flex-col <?php echo ($msg['email'] === ($$_SESSION['user_email'] ?? '')) ? 'items-end' : 'items-start'; ?>">
                            <div class="max-w-[80%] rounded-2xl px-4 py-2 <?php echo ($msg['email'] === ($$_SESSION['user_email'] ?? '')) ? 'bg-blue-600 text-white' : 'bg-white border text-slate-800'; ?>">
                                <p class="text-xs mb-1 opacity-75"><?php echo $msg['email']; ?> - <?php echo date('H:i', strtotime($msg['created_at'])); ?></p>
                                <p class="text-sm"><?php echo nl2br(htmlspecialchars($msg['content'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="POST" class="flex flex-col gap-3">
                    <textarea name="content" rows="3" class="w-full border rounded-xl p-3 focus:ring-2 focus:ring-blue-500 outline-none transition" placeholder="Votre message..." required></textarea>
                    <button type="submit" class="self-end bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-bold transition">
                        Envoyer <i class="fa-solid fa-paper-plane ml-2"></i>
                    </button>
                </form>
            </section>
        </div>
    </main>

    <footer class="bg-slate-700 text-white py-4 text-center mt-auto">
        <p class="text-xs">&copy; <?php echo date("Y"); ?> Justradamus - Tous droits réservés.</p>
    </footer>
</body>
</html>