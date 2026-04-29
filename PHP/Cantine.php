<?php
session_start();
require 'db.php';

// Affichage des erreurs pour le développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberEDU - Cantine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Styles pour les points de couleur si non présents dans Style.css */
        .dot { height: 12px; width: 12px; border-radius: 50%; display: inline-block; margin-right: 10px; }
        .green { background-color: #22c55e; }
        .indigo { background-color: #6366f1; }
        .orange { background-color: #f97316; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 flex flex-col min-h-screen">

    <header class="bg-blue-700 text-white shadow-lg z-20">
        <div class="container mx-auto px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4">
            <img src="../Image/logo.png" alt="Logo CyberEDU" class="logo">
            <div class="flex items-center gap-8">
                <nav>
                    <ul class="flex gap-6 text-sm font-medium">
                        <li><a href="Acceuil.php" class="hover:text-blue-200 transition">Accueil</a></li>
                        <li><a href="Cantine.php" class="hover:text-blue-200 transition font-bold border-b-2 border-white">Cantine</a></li>
                        <li><a href="Dashboard.php" class="hover:text-blue-200 transition">Dashboard</a></li>
                        <li><a href="Messagerie.php" class="hover:text-blue-200 transition">Messagerie</a></li>
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
                <h2 class="text-2xl font-bold text-slate-700 mb-8 flex items-center gap-3">
                    <i class="fa-solid fa-utensils text-blue-600"></i> Menu du jour
                </h2>
                
                <div id="apps-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    
                    <article class="bg-slate-50 border border-slate-100 p-6 rounded-xl hover:shadow-md transition">
                        <div class="flex items-center mb-4">
                            <span class="dot green"></span>
                            <h3 class="font-bold text-slate-500 uppercase text-xs tracking-wider">Entrée</h3>
                        </div>
                        <p class="text-lg font-semibold text-slate-700">Salade niçoise (Végé)</p>
                    </article>

                    <article class="bg-slate-50 border border-slate-100 p-6 rounded-xl hover:shadow-md transition">
                        <div class="flex items-center mb-4">
                            <span class="dot indigo"></span>
                            <h3 class="font-bold text-slate-500 uppercase text-xs tracking-wider">Plat de résistance</h3>
                        </div>
                        <p class="text-lg font-semibold text-slate-700">Poulet rôti & légumes de saison</p>
                    </article>

                    <article class="bg-slate-50 border border-slate-100 p-6 rounded-xl hover:shadow-md transition">
                        <div class="flex items-center mb-4">
                            <span class="dot orange"></span>
                            <h3 class="font-bold text-slate-500 uppercase text-xs tracking-wider">Dessert</h3>
                        </div>
                        <p class="text-lg font-semibold text-slate-700">Pomme au four caramélisée</p>
                    </article>

                </div>

                <div class="mt-12 p-4 bg-blue-50 border-l-4 border-blue-500 rounded-r-lg">
                    <p class="text-sm text-blue-700 italic">
                        <i class="fa-solid fa-info-circle mr-2"></i> Les menus sont susceptibles d'être modifiés en fonction des arrivages.
                    </p>
                </div>
            </section>
        </div>
    </main>

    <footer class="bg-slate-700 text-slate-300 py-4 text-center">
        <p class="text-xs">&copy; <span id="currentYear"></span> Justradamus - Tous droits réservés.</p>
    </footer>

    <script>document.getElementById('currentYear').textContent = new Date().getFullYear();</script>
</body>
</html>