<?php
session_start();
require 'db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$currentUserEmail = $_SESSION['user_email'] ?? 'Utilisateur inconnu';
$currentUserId    = $_SESSION['user_id'];

// ─────────────────────────────────────────────
// ACTIONS POST
// ─────────────────────────────────────────────

// 1. Créer un groupe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_group') {
    $nom         = htmlspecialchars(trim($_POST['group_name'] ?? ''));
    $description = htmlspecialchars(trim($_POST['group_desc'] ?? ''));

    if ($nom !== '') {
        $stmt = $pdo->prepare("INSERT INTO chat_groups (nom, description, createur_id) VALUES (?, ?, ?)");
        $stmt->execute([$nom, $description, $currentUserId]);
        $newGroupId = $pdo->lastInsertId();

        // Le créateur rejoint automatiquement le groupe
        $pdo->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)")
            ->execute([$newGroupId, $currentUserId]);
    }

    header('Location: messagerie2.php?group=' . ($newGroupId ?? ''));
    exit();
}

// 2. Rejoindre un groupe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join_group') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    if ($groupId > 0) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)");
        $stmt->execute([$groupId, $currentUserId]);
    }
    header('Location: messagerie2.php?group=' . $groupId);
    exit();
}

// 3. Quitter un groupe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'leave_group') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    if ($groupId > 0) {
        $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?")
            ->execute([$groupId, $currentUserId]);
    }
    header('Location: messagerie2.php');
    exit();
}

// 4. Envoyer un message dans un groupe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $content = htmlspecialchars(trim($_POST['content'] ?? ''));
    $groupId = (int)($_POST['group_id'] ?? 0);

    if ($content !== '' && $groupId > 0) {
        // Vérifier que l'utilisateur est bien membre
        $check = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?");
        $check->execute([$groupId, $currentUserId]);
        if ($check->fetch()) {
            $pdo->prepare("INSERT INTO messages (expediteur_id, contenu, group_id) VALUES (?, ?, ?)")
                ->execute([$currentUserId, $content, $groupId]);
        }
    }

    header('Location: messagerie2.php?group=' . $groupId);
    exit();
}

// ─────────────────────────────────────────────
// DONNÉES
// ─────────────────────────────────────────────

// Groupe actif
$activeGroupId = isset($_GET['group']) ? (int)$_GET['group'] : 0;

// Tous les groupes
$allGroups = $pdo->query("
    SELECT g.*, u.email AS createur_email,
        COUNT(DISTINCT gm.user_id) AS nb_membres
    FROM chat_groups g
    JOIN users u ON g.createur_id = u.id
    LEFT JOIN group_members gm ON gm.group_id = g.id
    GROUP BY g.id
    ORDER BY g.created_at DESC
")->fetchAll();

// Groupes dont l'utilisateur est membre
$myGroupIds = $pdo->prepare("SELECT group_id FROM group_members WHERE user_id = ?");
$myGroupIds->execute([$currentUserId]);
$myGroupIds = array_column($myGroupIds->fetchAll(), 'group_id');

// Infos du groupe actif
$activeGroup   = null;
$messages      = [];
$groupMembers  = [];
$isMember      = false;

if ($activeGroupId > 0) {
    $stmt = $pdo->prepare("
        SELECT g.*, u.email AS createur_email
        FROM chat_groups g
        JOIN users u ON g.createur_id = u.id
        WHERE g.id = ?
    ");
    $stmt->execute([$activeGroupId]);
    $activeGroup = $stmt->fetch();

    if ($activeGroup) {
        $isMember = in_array($activeGroupId, $myGroupIds);

        // Messages du groupe
        $stmt = $pdo->prepare("
            SELECT m.contenu AS content, m.date_envoi AS created_at, u.email
            FROM messages m
            JOIN users u ON m.expediteur_id = u.id
            WHERE m.group_id = ?
            ORDER BY m.date_envoi ASC
            LIMIT 100
        ");
        $stmt->execute([$activeGroupId]);
        $messages = $stmt->fetchAll();

        // Membres du groupe
        $stmt = $pdo->prepare("
            SELECT u.email, gm.joined_at
            FROM group_members gm
            JOIN users u ON gm.user_id = u.id
            WHERE gm.group_id = ?
            ORDER BY gm.joined_at ASC
        ");
        $stmt->execute([$activeGroupId]);
        $groupMembers = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberEDU – Messagerie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./Module/Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .chat-scroll { scroll-behavior: smooth; }
        .group-active { background: #1d4ed8 !important; color: #fff !important; }
        .bubble-me    { background: #2563eb; color: #fff; border-radius: 1rem 1rem 0.25rem 1rem; }
        .bubble-other { background: #fff; border: 1px solid #e2e8f0; color: #1e293b; border-radius: 1rem 1rem 1rem 0.25rem; }
        .sidebar-group:hover { background: #1e3a8a22; }
        #modal-bg { transition: opacity .2s; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 flex flex-col min-h-screen">

<!-- ══════════ HEADER ══════════ -->
<header class="bg-blue-700 text-white shadow-lg z-20">
    <div class="container mx-auto px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-3">
        <img src="../Image/logo3.png" alt="Logo CyberEDU" class="logo">
        <nav>
            <ul class="flex gap-6 text-sm font-medium">
                <li><a href="Acceuil.php"    class="hover:text-blue-200 transition">Accueil</a></li>
                <li><a href="Cantine.php"    class="hover:text-blue-200 transition">Cantine</a></li>
                <li><a href="Dashboard.php"  class="hover:text-blue-200 transition">Dashboard</a></li>
                <li><a href="messagerie2.php" class="hover:text-blue-200 transition font-bold border-b-2 border-white">Messagerie</a></li>
            </ul>
        </nav>
        <div class="w-full md:w-64 relative">
            <input type="search" id="search-input" placeholder="Rechercher…"
                   class="w-full bg-blue-800/50 border border-blue-400 text-white placeholder-blue-200 text-sm rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-white/50 transition">
            <i class="fa-solid fa-magnifying-glass absolute right-3 top-2.5 text-blue-200"></i>
        </div>
    </div>
</header>

<!-- ══════════ MAIN ══════════ -->
<main class="flex-grow flex overflow-hidden" style="height: calc(100vh - 140px);">

    <!-- ── SIDEBAR GROUPES ── -->
    <aside class="w-72 bg-white border-r border-slate-200 flex flex-col shadow-sm flex-shrink-0">
        <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h2 class="font-bold text-slate-700 text-sm uppercase tracking-wide flex items-center gap-2">
                <i class="fa-solid fa-layer-group text-blue-600"></i> Groupes
            </h2>
            <button onclick="openModal()"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1.5 rounded-lg font-semibold transition flex items-center gap-1">
                <i class="fa-solid fa-plus"></i> Nouveau
            </button>
        </div>

        <!-- Mes groupes -->
        <div class="px-3 pt-3 pb-1">
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-1">Mes groupes</p>
        </div>
        <div class="overflow-y-auto flex-grow">
            <?php
            $hasMyGroups = false;
            foreach ($allGroups as $g):
                if (!in_array($g['id'], $myGroupIds)) continue;
                $hasMyGroups = true;
                $isActive = ($g['id'] == $activeGroupId);
            ?>
            <a href="messagerie2.php?group=<?= $g['id'] ?>"
               class="sidebar-group flex items-center gap-3 px-4 py-3 cursor-pointer rounded-lg mx-2 mb-1 <?= $isActive ? 'group-active' : '' ?>">
                <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-users text-blue-600 text-sm"></i>
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-sm truncate <?= $isActive ? 'text-white' : 'text-slate-700' ?>"><?= htmlspecialchars($g['nom']) ?></p>
                    <p class="text-xs <?= $isActive ? 'text-blue-100' : 'text-slate-400' ?>"><?= $g['nb_membres'] ?> membre<?= $g['nb_membres'] > 1 ? 's' : '' ?></p>
                </div>
            </a>
            <?php endforeach; ?>
            <?php if (!$hasMyGroups): ?>
                <p class="text-xs text-slate-400 italic px-4 py-2">Aucun groupe rejoint.</p>
            <?php endif; ?>

            <!-- Découvrir d'autres groupes -->
            <?php
            $otherGroups = array_filter($allGroups, fn($g) => !in_array($g['id'], $myGroupIds));
            if (!empty($otherGroups)):
            ?>
            <div class="px-3 pt-4 pb-1">
                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-1">Découvrir</p>
            </div>
            <?php foreach ($otherGroups as $g): ?>
            <a href="messagerie2.php?group=<?= $g['id'] ?>"
               class="sidebar-group flex items-center gap-3 px-4 py-3 cursor-pointer rounded-lg mx-2 mb-1 <?= ($g['id'] == $activeGroupId) ? 'group-active' : '' ?>">
                <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-users text-slate-400 text-sm"></i>
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-sm truncate text-slate-500"><?= htmlspecialchars($g['nom']) ?></p>
                    <p class="text-xs text-slate-400"><?= $g['nb_membres'] ?> membre<?= $g['nb_membres'] > 1 ? 's' : '' ?></p>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Utilisateur connecté -->
        <div class="p-3 border-t border-slate-100 bg-slate-50 flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center">
                <i class="fa-solid fa-user text-white text-xs"></i>
            </div>
            <p class="text-xs text-slate-600 truncate font-medium">
                <?= htmlspecialchars($currentUserEmail) ?>
            </p>
        </div>
    </aside>

    <!-- ── ZONE DE CHAT ── -->
    <div class="flex-grow flex flex-col overflow-hidden">

        <?php if ($activeGroup): ?>

        <!-- En-tête du groupe -->
        <div class="bg-white border-b border-slate-200 px-6 py-3 flex justify-between items-center shadow-sm flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="fa-solid fa-users text-blue-600"></i>
                </div>
                <div>
                    <h2 class="font-bold text-slate-800"><?= htmlspecialchars($activeGroup['nom']) ?></h2>
                    <p class="text-xs text-slate-400">
                        <?= count($groupMembers) ?> membre<?= count($groupMembers) > 1 ? 's' : '' ?>
                        <?php if ($activeGroup['description']): ?>
                        · <?= htmlspecialchars($activeGroup['description']) ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <!-- Membres (tooltip) -->
                <div class="relative group">
                    <button class="text-slate-500 hover:text-blue-600 text-sm px-3 py-1.5 rounded-lg border border-slate-200 hover:border-blue-300 transition flex items-center gap-1">
                        <i class="fa-solid fa-user-group"></i>
                        <span class="text-xs"><?= count($groupMembers) ?></span>
                    </button>
                    <div class="absolute right-0 top-full mt-2 w-52 bg-white rounded-xl shadow-lg border border-slate-100 p-3 hidden group-hover:block z-10">
                        <p class="text-xs font-bold text-slate-500 uppercase mb-2">Membres</p>
                        <?php foreach ($groupMembers as $m): ?>
                        <div class="flex items-center gap-2 py-1">
                            <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="fa-solid fa-user text-blue-500 text-xs"></i>
                            </div>
                            <p class="text-xs text-slate-700 truncate"><?= htmlspecialchars($m['email']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ($isMember): ?>
                <!-- Quitter le groupe -->
                <form method="POST" onsubmit="return confirm('Quitter ce groupe ?')">
                    <input type="hidden" name="action"   value="leave_group">
                    <input type="hidden" name="group_id" value="<?= $activeGroupId ?>">
                    <button type="submit"
                            class="text-xs text-red-500 hover:text-red-700 px-3 py-1.5 rounded-lg border border-red-200 hover:border-red-400 transition">
                        <i class="fa-solid fa-right-from-bracket"></i> Quitter
                    </button>
                </form>
                <?php else: ?>
                <!-- Rejoindre le groupe -->
                <form method="POST">
                    <input type="hidden" name="action"   value="join_group">
                    <input type="hidden" name="group_id" value="<?= $activeGroupId ?>">
                    <button type="submit"
                            class="text-xs text-white bg-blue-600 hover:bg-blue-700 px-4 py-1.5 rounded-lg font-semibold transition">
                        <i class="fa-solid fa-right-to-bracket"></i> Rejoindre
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Messages -->
        <div id="chat-box" class="flex-grow overflow-y-auto p-6 space-y-3 chat-scroll bg-slate-50">
            <?php if (empty($messages)): ?>
                <div class="flex flex-col items-center justify-center h-full text-slate-400">
                    <i class="fa-regular fa-comment-dots text-5xl mb-3"></i>
                    <p class="text-sm italic">Aucun message dans ce groupe. Soyez le premier !</p>
                </div>
            <?php endif; ?>

            <?php foreach ($messages as $msg):
                $isMe = ($msg['email'] === $currentUserEmail);
            ?>
            <div class="flex flex-col <?= $isMe ? 'items-end' : 'items-start' ?>">
                <?php if (!$isMe): ?>
                <p class="text-xs text-slate-400 mb-1 ml-1"><?= htmlspecialchars($msg['email']) ?></p>
                <?php endif; ?>
                <div class="max-w-[70%] px-4 py-2 <?= $isMe ? 'bubble-me' : 'bubble-other' ?> shadow-sm">
                    <p class="text-sm leading-relaxed"><?= nl2br(htmlspecialchars($msg['content'])) ?></p>
                    <p class="text-xs mt-1 <?= $isMe ? 'text-blue-200' : 'text-slate-400' ?> text-right">
                        <?= date('H:i', strtotime($msg['created_at'])) ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Zone de saisie -->
        <?php if ($isMember): ?>
        <form method="POST" class="bg-white border-t border-slate-200 px-4 py-3 flex gap-3 items-end flex-shrink-0">
            <input type="hidden" name="action"   value="send_message">
            <input type="hidden" name="group_id" value="<?= $activeGroupId ?>">
            <textarea name="content" rows="1" id="msg-input"
                      class="flex-grow border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none resize-none transition"
                      placeholder="Écrire un message…" required
                      onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault();this.form.submit();}"></textarea>
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-semibold transition flex items-center gap-2 flex-shrink-0">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
        <?php else: ?>
        <div class="bg-amber-50 border-t border-amber-200 px-6 py-4 text-center flex-shrink-0">
            <p class="text-sm text-amber-700">
                <i class="fa-solid fa-lock mr-1"></i>
                Rejoignez ce groupe pour pouvoir envoyer des messages.
            </p>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- Aucun groupe sélectionné -->
        <div class="flex-grow flex flex-col items-center justify-center text-slate-400 bg-slate-50">
            <i class="fa-solid fa-comments text-6xl mb-4 text-slate-300"></i>
            <p class="text-lg font-semibold text-slate-500">Bienvenue dans la Messagerie</p>
            <p class="text-sm mt-1">Sélectionnez un groupe ou créez-en un nouveau pour commencer.</p>
            <button onclick="openModal()"
                    class="mt-6 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold transition flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Créer un groupe
            </button>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- ══════════ MODAL CRÉER UN GROUPE ══════════ -->
<div id="modal-bg" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 mx-4">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-layer-group text-blue-600"></i> Créer un groupe
            </h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <form method="POST" class="flex flex-col gap-4">
            <input type="hidden" name="action" value="create_group">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Nom du groupe <span class="text-red-500">*</span></label>
                <input type="text" name="group_name" required maxlength="100"
                       class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"
                       placeholder="Ex: Projet Math, Classe 3ème…">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Description <span class="text-slate-400 font-normal">(optionnelle)</span></label>
                <textarea name="group_desc" rows="2" maxlength="255"
                          class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition resize-none"
                          placeholder="À quoi sert ce groupe ?"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal()"
                        class="px-5 py-2 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-50 text-sm font-semibold transition">
                    Annuler
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold transition flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Créer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════ FOOTER ══════════ -->
<footer class="bg-slate-700 text-white py-4 text-center">
    <p class="text-xs">&copy; <?= date("Y") ?> Justradamus – Tous droits réservés.</p>
</footer>

<script>
    // Scroll auto en bas du chat
    const chatBox = document.getElementById('chat-box');
    if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

    // Modal
    function openModal()  { document.getElementById('modal-bg').classList.remove('hidden'); }
    function closeModal() { document.getElementById('modal-bg').classList.add('hidden'); }
    document.getElementById('modal-bg').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // Recherche dans la sidebar
    const searchInput = document.getElementById('search-input');
    searchInput?.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        document.querySelectorAll('.sidebar-group').forEach(el => {
            const name = el.querySelector('p')?.textContent.toLowerCase() ?? '';
            el.style.display = name.includes(val) ? '' : 'none';
        });
    });
</script>
</body>
</html>