<?php
session_start();
require 'db.php';

// 1. SÉCURITÉ : On vérifie d'abord la connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Affichage des erreurs pour le développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$user_id = $_SESSION['user_id'];

// 2. LOGIQUE : Traitement de la suppression
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete'], $user_id]);
    header("Location: Dashboard.php");
    exit();
}

// 3. LOGIQUE : Traitement de l'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_project'])) {
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);
    
    $stmt = $pdo->prepare("INSERT INTO projects (user_id, title, description) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $title, $description]);
    header("Location: Dashboard.php"); // Évite de renvoyer le formulaire en actualisant
    exit();
}

// 4. RÉCUPÉRATION : On récupère les projets
$stmt = $pdo->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$projets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberEDU - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="Style.css">
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
                        <li><a href="Dashboard.php" class="hover:text-blue-200 transition font-bold border-b-2 border-white">Dashboard</a></li>
                        <li><a href="Messagerie2.php" class="hover:text-blue-200 transition">Messagerie</a></li>
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

    <main class="flex-grow py-10 px-6">
        <div class="container mx-auto max-w-5xl">
            <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
                <div class="flex items-center gap-3 mb-8">
                    <i class="fa-solid fa-chart-line text-3xl text-blue-600"></i>
                    <h2 class="text-3xl font-extrabold text-slate-800">Votre Dashboard</h2>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-xl p-6 mb-10">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-plus-circle text-green-500"></i> Nouveau Projet
                    </h3>
                    <form method="POST" class="grid grid-cols-1 gap-4">
                        <input type="text" name="title" placeholder="Titre du projet" 
                               class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none" required>
                        <textarea name="description" placeholder="Description détaillée..." rows="3"
                                  class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                        <button type="submit" name="add_project" 
                                class="bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700 transition w-max">
                            Créer le projet
                        </button>
                    </form>
                </div>

                <h3 class="text-xl font-bold mb-6 text-slate-700">Liste de vos projets</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php if (empty($projets)): ?>
                        <p class="text-slate-500 italic">Vous n'avez pas encore de projet.</p>
                    <?php endif; ?>

                    <?php foreach ($projets as $p): ?>
                        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm hover:shadow-md transition relative group">
                            <a href="?delete=<?= $p['id'] ?>" 
                               onclick="return confirm('Supprimer ce projet ?')"
                               class="absolute top-4 right-4 text-slate-300 hover:text-red-500 transition">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                            <h4 class="text-lg font-bold text-blue-700 mb-2 pr-8"><?= htmlspecialchars($p['title']) ?></h4>
                            <p class="text-slate-600 text-sm mb-4"><?= nl2br(htmlspecialchars($p['description'])) ?></p>
                            <div class="flex items-center justify-between text-[10px] text-slate-400 font-medium uppercase tracking-wider">
                                <span><i class="fa-regular fa-calendar mr-1"></i> <?= date('d/m/Y', strtotime($p['created_at'])) ?></span>
                                <span class="bg-slate-100 px-2 py-1 rounded">Projet #<?= $p['id'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <footer class="bg-slate-700 text-slate-300 py-6 text-center mt-auto">
        <p class="text-xs">&copy; <?= date("Y"); ?> Justradamus - Tous droits réservés.</p>
    </footer>

</body>
</html>