<?php
session_start();
require 'db.php'; // Assurez-vous que le fichier s'appelle bien db.php en minuscules

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = "";
$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage des entrées
    $nom = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    // On ajoute une valeur par défaut pour le prénom car la colonne est NOT NULL dans votre SQL
    $prenom = "Utilisateur"; 

    if ($password !== $password_confirm) {
        $message = "Les mots de passe ne correspondent pas.";
        $error = true;
    } else {
        // CORRECTION 1 : Table 'users' au lieu de 'membres', colonne 'id' au lieu de 'id_membre'
        $check = $pdo->prepare("SELECT id FROM users WHERE nom = ? OR email = ?");
        $check->execute([$nom, $email]);

        if ($check->fetch()) {
            $message = "Le nom d'utilisateur ou l'email est déjà utilisé.";
            $error = true;
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // CORRECTION 2 : Table 'users' et colonnes 'nom', 'prenom', 'email', 'password'
            $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, password) VALUES (?, ?, ?, ?)");
            
            try {
                if ($stmt->execute([$nom, $prenom, $email, $hashedPassword])) {
                    $message = "Inscription réussie ! <a href='login.php' class='text-blue-600 underline'>Connectez-vous ici</a>";
                    $error = false;
                }
            } catch (PDOException $e) {
                $message = "Erreur SQL : " . $e->getMessage();
                $error = true;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberEDU - Inscription</title>
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
                        <li><a href="Dashboard.php" class="hover:text-blue-200 transition">Dashboard</a></li>
                        <li><a href="Messagerie.php" class="hover:text-blue-200 transition">Messagerie</a></li>
                        <li><a href="login.php" class="hover:text-blue-200 transition">Connexion</a></li>
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

    <main class="flex-grow py-10 px-6 flex justify-center items-center">
        <section class="bg-white border border-slate-200 rounded-2xl shadow-xl p-8 w-full max-w-md">
            <h2 class="text-2xl font-bold text-slate-700 mb-6 text-center">Créer un compte</h2>

            <?php if ($message): ?>
                <div class="p-4 mb-4 rounded-lg <?php echo $error ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="inscription.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">Nom d'utilisateur</label>
                    <input type="text" name="username" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Email</label>
                    <input type="email" name="email" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Mot de passe</label>
                    <input type="password" name="password" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirm" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none" required>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 transition">S'inscrire</button>
            </form>

            <div class="mt-6 text-center text-sm">
                <p>Déjà membre ? <a href="login.php" class="text-blue-600 font-bold hover:underline">Connectez-vous</a></p>
            </div>
        </section>
    </main>

    <footer class="bg-slate-700 text-white py-4 text-center">
        <p class="text-xs">&copy; <?php echo date("Y"); ?> Justradamus - Tous droits réservés.</p>
    </footer>
</body>
</html>