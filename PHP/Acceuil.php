<?php
session_start();
require 'db.php'; // Décommenter quand le fichier est prêt

// Sécurité : redirection si non connecté
if (!isset($_SESSION['user_id'])) {
    // header("Location: login.php");
    // exit;
}

$message = "";

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer_signalement'])) {
    $type = htmlspecialchars($_POST['type-incident']);
    $desc = htmlspecialchars($_POST['description']);

    if (!empty($type) && !empty($desc)) {
        // Code d'insertion PDO ici
        $message = "Le signalement a été envoyé avec succès.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberEDU - Dashboard & Signalement</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Ajustement pour tes classes personnalisées du fichier Style.css */
        .progress-bar-bg { @apply w-full bg-slate-200 rounded-full h-2 mt-2; }
        .progress-bar-fill { @apply bg-blue-600 h-2 rounded-full; }
        .kpi-value { @apply text-2xl font-bold mt-1; }
        .kpi-trend { @apply text-xs font-medium mt-1; }
        .kpi-trend.up { @apply text-green-600; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 flex flex-col min-h-screen">

    <header class="bg-blue-700 text-white shadow-lg z-20">
        <div class="container mx-auto px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4">
            <img src="../Image/logo.png" alt="Logo CyberEDU" class="logo">
            <nav>
                <ul class="flex gap-6 text-sm font-medium">
                    <li><a href="Acceuil.php" class="hover:text-blue-200 transition font-bold border-b-2 border-white">Accueil</a></li>
                    <li><a href="Cantine.php" class="hover:text-blue-200 transition">Cantine</a></li>
                    <li><a href="Dashboard.php" class="hover:text-blue-200 transition">Dashboard</a></li>
                    <li><a href="Messagerie.php" class="hover:text-blue-200 transition">Messagerie</a></li>
                </ul>
            </nav>
            <div class="w-full md:w-64 relative">
                <input type="search" id="search-input" placeholder="Rechercher..." 
                       class="w-full bg-blue-800/50 border border-blue-400 text-white placeholder-blue-200 text-sm rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-white/50 transition">
                <i class="fa-solid fa-magnifying-glass absolute right-3 top-2.5 text-blue-200"></i>
            </div>
        </div>
    </header>

    <main class="flex-grow py-10 px-6">
        <div class="container mx-auto max-w-6xl">
            
            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <article class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <h3 class="text-sm font-semibold text-slate-500 uppercase">Score PT — semaine</h3>
                    <div class="kpi-value" style="color:#4F46E5;">847</div>
                    <div class="kpi-trend up">↑ +120 pts</div>
                </article>

                <article class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <h3 class="text-sm font-semibold text-slate-500 uppercase">Cours sans téléphone</h3>
                    <div class="kpi-value">12/14</div>
                    <div class="kpi-trend up">↑ Bonne semaine !</div>
                </article>

                <article class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <h3 class="text-sm font-semibold text-slate-500 uppercase">Prochaine cantine</h3>
                    <div class="kpi-value text-blue-600">Auj. 12h15</div>
                    <div class="kpi-trend text-slate-400">Confirmé</div>
                </article>

                <article class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <h3 class="text-sm font-semibold text-slate-500 uppercase">Devoirs à rendre</h3>
                    <div class="kpi-value text-red-500">3</div>
                    <div class="kpi-trend text-red-400 font-bold">Cette semaine</div>
                </article>
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                
                <section class="lg:col-span-1 bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold text-slate-700 underline decoration-blue-500">Mon score PT — progression</h2>
                        <a href="#" class="text-blue-600 text-xs font-bold hover:underline">Voir détails →</a>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="progression-item">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-slate-600">Lundi</span>
                                <span class="font-bold text-blue-600">150 pts</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2.5">
                                <div class="bg-blue-500 h-2.5 rounded-full" style="width: 49%"></div>
                            </div>
                        </div>

                        <div class="progression-item">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-slate-600">Jeudi</span>
                                <span class="font-bold text-blue-600">307 pts</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2.5">
                                <div class="bg-green-500 h-2.5 rounded-full" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
                    <h2 class="text-2xl font-bold text-slate-700 mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-red-600"></i> Signaler une alerte
                    </h2>

                    <?php if ($message): ?>
                        <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg border border-green-200">
                            <?= $message ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="POST" class="space-y-6">
                        <div>
                            <label for="type-incident" class="block text-sm font-semibold mb-2">Type d'incident</label>
                            <input type="text" name="type-incident" id="type-incident" 
                                   placeholder="Ex: Harcèlement, problème technique, vol..." 
                                   class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition" required>
                        </div>
                        
                        <div>
                            <label for="description" class="block text-sm font-semibold mb-2">Description détaillée</label>
                            <textarea name="description" id="description" rows="4" 
                                      placeholder="Expliquez la situation en quelques phrases..." 
                                      class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition" required></textarea>
                        </div>
                        
                        <button type="submit" name="envoyer_signalement" 
                                class="w-full bg-red-600 text-white font-bold py-3 rounded-lg hover:bg-red-700 transition shadow-md">
                            Envoyer le signalement
                        </button>
                    </form>
                </section>

            </div>
        </div>
    </main>

    <footer class="bg-slate-700 text-slate-300 py-4 text-center">
        <p class="text-xs">&copy; <?= date("Y"); ?> Justradamus - Tous droits réservés.</p>
    </footer>

</body>
</html>