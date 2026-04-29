<?php
session_start();
require 'db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['api'])) { header('Content-Type: application/json'); echo json_encode(['error'=>'Non connecté']); exit(); }
    header('Location: login.php'); exit();
}

$currentUserId    = $_SESSION['user_id'];
$currentUserEmail = $_SESSION['user_email'] ?? 'Utilisateur inconnu';

if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $action = $_GET['api'];

    // Récupérer messages
    if ($action === 'get_messages') {
        $groupId = (int)($_GET['group_id'] ?? 0);
        $lastId  = (int)($_GET['last_id']  ?? 0);
        $check = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id=? AND user_id=?");
        $check->execute([$groupId, $currentUserId]);
        if (!$check->fetch()) { echo json_encode(['messages'=>[], 'allowed'=>false]); exit(); }
        $stmt = $pdo->prepare("SELECT m.id, m.contenu AS content, m.date_envoi AS created_at, u.email
            FROM messages m JOIN users u ON m.expediteur_id=u.id
            WHERE m.group_id=? AND m.id>? ORDER BY m.date_envoi ASC LIMIT 50");
        $stmt->execute([$groupId, $lastId]);
        echo json_encode(['messages'=>$stmt->fetchAll(PDO::FETCH_ASSOC), 'allowed'=>true]);
        exit();
    }

    // Envoyer un message
    if ($action === 'send_message' && $_SERVER['REQUEST_METHOD']==='POST') {
        $data    = json_decode(file_get_contents('php://input'), true);
        $content = htmlspecialchars(trim($data['content'] ?? ''));
        $groupId = (int)($data['group_id'] ?? 0);
        if ($content==='' || $groupId===0) { echo json_encode(['ok'=>false]); exit(); }
        $check = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id=? AND user_id=?");
        $check->execute([$groupId, $currentUserId]);
        if (!$check->fetch()) { echo json_encode(['ok'=>false,'reason'=>'non membre']); exit(); }
        $pdo->prepare("INSERT INTO messages (expediteur_id, contenu, group_id) VALUES (?,?,?)")
            ->execute([$currentUserId, $content, $groupId]);
        $newId = $pdo->lastInsertId();
        echo json_encode(['ok'=>true,'id'=>$newId]);
        exit();
    }

    // Créer un groupe
    if ($action === 'create_group' && $_SERVER['REQUEST_METHOD']==='POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $nom  = htmlspecialchars(trim($data['nom']  ?? ''));
        $desc = htmlspecialchars(trim($data['desc'] ?? ''));
        if ($nom==='') { echo json_encode(['ok'=>false]); exit(); }
        $pdo->prepare("INSERT INTO chat_groups (nom, description, createur_id) VALUES (?,?,?)")
            ->execute([$nom, $desc, $currentUserId]);
        $newId = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?,?)")->execute([$newId, $currentUserId]);
        echo json_encode(['ok'=>true,'id'=>$newId,'nom'=>$nom]);
        exit();
    }

    // Rejoindre un groupe
    if ($action === 'join_group' && $_SERVER['REQUEST_METHOD']==='POST') {
        $data    = json_decode(file_get_contents('php://input'), true);
        $groupId = (int)($data['group_id'] ?? 0);
        $pdo->prepare("INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?,?)")->execute([$groupId, $currentUserId]);
        echo json_encode(['ok'=>true]);
        exit();
    }

    // Quitter un groupe
    if ($action === 'leave_group' && $_SERVER['REQUEST_METHOD']==='POST') {
        $data    = json_decode(file_get_contents('php://input'), true);
        $groupId = (int)($data['group_id'] ?? 0);
        $pdo->prepare("DELETE FROM group_members WHERE group_id=? AND user_id=?")->execute([$groupId, $currentUserId]);
        echo json_encode(['ok'=>true]);
        exit();
    }

    // Membres d'un groupe
    if ($action === 'get_members') {
        $groupId = (int)($_GET['group_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT u.email, gm.joined_at FROM group_members gm JOIN users u ON gm.user_id=u.id WHERE gm.group_id=?");
        $stmt->execute([$groupId]);
        echo json_encode(['members'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit();
    }

    echo json_encode(['error'=>'action inconnue']); exit();
}

// ─────────────────────────────────────────────
// DONNÉES INITIALES pour le rendu HTML
// ─────────────────────────────────────────────
$allGroups = $pdo->query("
    SELECT g.*, u.email AS createur_email, COUNT(DISTINCT gm.user_id) AS nb_membres
    FROM chat_groups g
    JOIN users u ON g.createur_id=u.id
    LEFT JOIN group_members gm ON gm.group_id=g.id
    GROUP BY g.id ORDER BY g.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$myIdsStmt = $pdo->prepare("SELECT group_id FROM group_members WHERE user_id=?");
$myIdsStmt->execute([$currentUserId]);
$myGroupIds = array_column($myIdsStmt->fetchAll(), 'group_id');
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
  html,body{height:100%;overflow:hidden;margin:0}
  .app{display:flex;flex-direction:column;height:100vh}
  .body-row{display:flex;flex:1;overflow:hidden}
  #sidebar{width:260px;flex-shrink:0;display:flex;flex-direction:column;background:#fff;border-right:1px solid #e2e8f0}
  #chat-panel{flex:1;display:flex;flex-direction:column;overflow:hidden}
  #messages-box{flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:10px;background:#f8fafc}
  .bubble-me   {background:#2563eb;color:#fff;border-radius:1rem 1rem .2rem 1rem;padding:8px 14px;max-width:70%;box-shadow:0 1px 3px rgba(0,0,0,.15)}
  .bubble-other{background:#fff;border:1px solid #e2e8f0;color:#1e293b;border-radius:1rem 1rem 1rem .2rem;padding:8px 14px;max-width:70%;box-shadow:0 1px 2px rgba(0,0,0,.07)}
  .group-item{display:flex;align-items:center;gap:10px;padding:10px 12px;margin:2px 8px;border-radius:12px;cursor:pointer;transition:background .15s}
  .group-item:hover{background:#dbeafe}
  .group-item.active{background:#1d4ed8}
  .group-item.active .g-name{color:#fff}
  .group-item.active .g-sub{color:#bfdbfe}
  #sidebar-scroll{flex:1;overflow-y:auto;padding:6px 0}
  #sidebar-scroll::-webkit-scrollbar,#messages-box::-webkit-scrollbar{width:4px}
  #sidebar-scroll::-webkit-scrollbar-thumb,#messages-box::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:9px}
  .spinner{width:22px;height:22px;border:3px solid #e2e8f0;border-top-color:#2563eb;border-radius:50%;animation:spin .7s linear infinite;margin:40px auto}
  @keyframes spin{to{transform:rotate(360deg)}}
  #modal-bg{transition:opacity .2s}
</style>
</head>
<body>
<div class="app">

<!-- HEADER -->
<header class="bg-blue-700 text-white shadow-lg flex-shrink-0" style="z-index:10">
  <div class="container mx-auto px-6 py-3 flex justify-between items-center gap-4">
    <img src="../Image/logo2.png" alt="Logo CyberEDU" class="logo" style="height:60px">
    <nav>
      <ul class="flex gap-6 text-sm font-medium" style="list-style:none;margin:0;padding:0">
        <li><a href="Acceuil.php"    class="hover:text-blue-200 transition">Accueil</a></li>
        <li><a href="Cantine.php"    class="hover:text-blue-200 transition">Cantine</a></li>
        <li><a href="Dashboard.php"  class="hover:text-blue-200 transition">Dashboard</a></li>
        <li><a href="Messagerie.php" class="font-bold border-b-2 border-white">Messagerie</a></li>
      </ul>
    </nav>
    <div style="position:relative;width:220px">
      <input type="search" id="search-input" placeholder="Rechercher…"
             class="w-full bg-blue-800/50 border border-blue-400 text-white placeholder-blue-200 text-sm rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-white/50">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute;right:12px;top:9px;color:#bfdbfe"></i>
    </div>
  </div>
</header>

<!-- CORPS -->
<div class="body-row">

  <!-- SIDEBAR -->
  <div id="sidebar">
    <!-- En-tête sidebar -->
    <div style="padding:12px 14px;border-bottom:1px solid #f1f5f9;background:#f8fafc;display:flex;justify-content:space-between;align-items:center;flex-shrink:0">
      <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;display:flex;align-items:center;gap:6px">
        <i class="fa-solid fa-layer-group" style="color:#2563eb"></i> Groupes
      </span>
      <button onclick="openCreateModal()"
              style="background:#2563eb;color:#fff;font-size:11px;padding:5px 11px;border-radius:8px;border:none;cursor:pointer;font-weight:600;display:flex;align-items:center;gap:4px">
        <i class="fa-solid fa-plus"></i> Nouveau
      </button>
    </div>

    <!-- Recherche -->
    <div style="padding:8px 10px;border-bottom:1px solid #f1f5f9;flex-shrink:0">
      <input id="sidebar-search" type="text" placeholder="Filtrer…"
             style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:6px 10px;font-size:12px;outline:none;box-sizing:border-box">
    </div>

    <!-- Liste groupes -->
    <div id="sidebar-scroll">
      <p style="font-size:10px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.07em;padding:8px 16px 2px">Mes groupes</p>
      <div id="my-groups-list">
        <?php foreach($allGroups as $g): if(!in_array($g['id'],$myGroupIds)) continue; ?>
        <div class="group-item" data-id="<?=$g['id']?>" data-name="<?=htmlspecialchars($g['nom'])?>" data-member="1" onclick="selectGroup(this)">
          <div style="width:36px;height:36px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fa-solid fa-users" style="color:#2563eb;font-size:13px"></i>
          </div>
          <div style="min-width:0;flex:1">
            <p class="g-name" style="font-size:13px;font-weight:600;color:#334155;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=htmlspecialchars($g['nom'])?></p>
            <p class="g-sub" style="font-size:11px;color:#94a3b8"><?=$g['nb_membres']?> membre<?=$g['nb_membres']>1?'s':''?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php $others=array_filter($allGroups,fn($g)=>!in_array($g['id'],$myGroupIds)); ?>
      <?php if(!empty($others)): ?>
      <p style="font-size:10px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.07em;padding:10px 16px 2px">Découvrir</p>
      <div id="other-groups-list">
        <?php foreach($others as $g): ?>
        <div class="group-item" data-id="<?=$g['id']?>" data-name="<?=htmlspecialchars($g['nom'])?>" data-member="0" onclick="selectGroup(this)" style="opacity:.7">
          <div style="width:36px;height:36px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fa-solid fa-users" style="color:#94a3b8;font-size:13px"></i>
          </div>
          <div style="min-width:0;flex:1">
            <p class="g-name" style="font-size:13px;font-weight:600;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=htmlspecialchars($g['nom'])?></p>
            <p class="g-sub" style="font-size:11px;color:#94a3b8"><?=$g['nb_membres']?> membre<?=$g['nb_membres']>1?'s':''?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Pied sidebar -->
    <div style="padding:10px 12px;border-top:1px solid #f1f5f9;background:#f8fafc;display:flex;align-items:center;gap:8px;flex-shrink:0">
      <div style="width:28px;height:28px;border-radius:50%;background:#2563eb;display:flex;align-items:center;justify-content:center">
        <i class="fa-solid fa-user" style="color:#fff;font-size:11px"></i>
      </div>
      <p style="font-size:11px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=htmlspecialchars($currentUserEmail)?></p>
    </div>
  </div>

  <!-- PANNEAU CHAT -->
  <div id="chat-panel">

    <!-- En-tête groupe -->
    <div id="chat-header" style="display:none;background:#fff;border-bottom:1px solid #e2e8f0;padding:12px 20px;display:none;justify-content:space-between;align-items:center;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,.05)">
      <div style="display:flex;align-items:center;gap:12px">
        <div style="width:40px;height:40px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center">
          <i class="fa-solid fa-users" style="color:#2563eb"></i>
        </div>
        <div>
          <h2 id="chat-title" style="font-size:15px;font-weight:700;color:#1e293b;margin:0"></h2>
          <p id="chat-subtitle" style="font-size:11px;color:#94a3b8;margin:0"></p>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <!-- Membres -->
        <div style="position:relative" id="members-wrap">
          <button onclick="toggleMembers()" style="font-size:12px;padding:6px 12px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;display:flex;align-items:center;gap:5px;color:#64748b">
            <i class="fa-solid fa-user-group"></i>
            <span id="member-count">0</span>
          </button>
          <div id="members-dropdown" style="display:none;position:absolute;right:0;top:110%;width:220px;background:#fff;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);border:1px solid #f1f5f9;padding:12px;z-index:30">
            <p style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:8px">Membres</p>
            <div id="members-inner" style="max-height:180px;overflow-y:auto"></div>
          </div>
        </div>
        <!-- Rejoindre / Quitter -->
        <button id="join-leave-btn" onclick="toggleJoinLeave()" style="display:none;font-size:11px;padding:6px 14px;border-radius:8px;font-weight:600;cursor:pointer;transition:.15s"></button>
      </div>
    </div>

    <!-- Écran d'accueil -->
    <div id="welcome-screen" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#f8fafc;color:#94a3b8">
      <i class="fa-solid fa-comments" style="font-size:60px;color:#cbd5e1;margin-bottom:16px"></i>
      <p style="font-size:17px;font-weight:600;color:#64748b">Bienvenue dans la Messagerie</p>
      <p style="font-size:13px;margin-top:4px">Sélectionnez un groupe ou créez-en un nouveau.</p>
      <button onclick="openCreateModal()" style="margin-top:24px;background:#2563eb;color:#fff;padding:12px 24px;border-radius:12px;border:none;font-weight:700;cursor:pointer;font-size:14px;display:flex;align-items:center;gap:8px">
        <i class="fa-solid fa-plus"></i> Créer un groupe
      </button>
    </div>

    <!-- Messages -->
    <div id="messages-box" style="display:none"></div>

    <!-- Barre de saisie -->
    <div id="input-bar" style="display:none;background:#fff;border-top:1px solid #e2e8f0;padding:12px 16px;flex-shrink:0">
      <div style="display:flex;gap:10px;align-items:flex-end">
        <textarea id="msg-input" rows="1"
                  style="flex:1;border:1px solid #cbd5e1;border-radius:14px;padding:10px 16px;font-size:13px;outline:none;resize:none;font-family:inherit;max-height:120px;overflow-y:auto;transition:border-color .2s"
                  placeholder="Écrire un message… (Entrée pour envoyer)"></textarea>
        <button onclick="sendMessage()"
                style="background:#2563eb;color:#fff;border:none;border-radius:12px;width:42px;height:42px;cursor:pointer;font-size:15px;flex-shrink:0;transition:background .15s"
                onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
          <i class="fa-solid fa-paper-plane"></i>
        </button>
      </div>
    </div>

    <!-- Bannière non-membre -->
    <div id="non-member-bar" style="display:none;background:#fffbeb;border-top:1px solid #fde68a;padding:12px 24px;text-align:center;flex-shrink:0">
      <p style="font-size:13px;color:#92400e"><i class="fa-solid fa-lock" style="margin-right:5px"></i>Rejoignez ce groupe pour pouvoir envoyer des messages.</p>
    </div>

  </div><!-- /chat-panel -->
</div><!-- /body-row -->
</div><!-- /app -->

<!-- MODAL CRÉER GROUPE -->
<div id="modal-bg" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:50;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:20px;box-shadow:0 24px 60px rgba(0,0,0,.2);width:100%;max-width:420px;padding:28px;margin:0 16px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h3 style="font-size:17px;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:8px;margin:0">
        <i class="fa-solid fa-layer-group" style="color:#2563eb"></i> Créer un groupe
      </h3>
      <button onclick="closeCreateModal()" style="background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div style="display:flex;flex-direction:column;gap:14px">
      <div>
        <label style="display:block;font-size:13px;font-weight:600;color:#475569;margin-bottom:4px">Nom <span style="color:red">*</span></label>
        <input id="new-group-name" type="text" maxlength="100"
               style="width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:10px 14px;font-size:13px;outline:none;box-sizing:border-box"
               placeholder="Ex : Projet Math, Classe 3ème…">
      </div>
      <div>
        <label style="display:block;font-size:13px;font-weight:600;color:#475569;margin-bottom:4px">Description <span style="font-weight:400;color:#94a3b8">(optionnelle)</span></label>
        <textarea id="new-group-desc" rows="2" maxlength="255"
                  style="width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:10px 14px;font-size:13px;outline:none;resize:none;box-sizing:border-box"
                  placeholder="À quoi sert ce groupe ?"></textarea>
      </div>
      <p id="create-error" style="display:none;color:#ef4444;font-size:12px">Le nom est obligatoire.</p>
      <div style="display:flex;justify-content:flex-end;gap:10px;padding-top:4px">
        <button onclick="closeCreateModal()" style="padding:9px 20px;border-radius:10px;border:1px solid #cbd5e1;background:#fff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer">Annuler</button>
        <button onclick="createGroup()"      style="padding:9px 20px;border-radius:10px;border:none;background:#2563eb;color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px">
          <i class="fa-solid fa-plus"></i> Créer
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ══════════ JAVASCRIPT ══════════ -->
<script>
const ME = <?= json_encode($currentUserEmail) ?>;
let activeGroupId  = null;
let activeIsMember = false;
let lastMsgId      = 0;
let pollTimer      = null;

// ── Sélectionner un groupe ──
function selectGroup(el) {
    document.querySelectorAll('.group-item').forEach(i => i.classList.remove('active'));
    el.classList.add('active');

    activeGroupId  = parseInt(el.dataset.id);
    activeIsMember = el.dataset.member === '1';
    lastMsgId      = 0;

    // Afficher les bons éléments
    document.getElementById('welcome-screen').style.display  = 'none';
    document.getElementById('chat-header').style.display     = 'flex';
    document.getElementById('messages-box').style.display    = 'flex';
    document.getElementById('messages-box').innerHTML        = '<div class="spinner"></div>';
    document.getElementById('input-bar').style.display       = activeIsMember ? 'block' : 'none';
    document.getElementById('non-member-bar').style.display  = activeIsMember ? 'none'  : 'block';

    document.getElementById('chat-title').textContent = el.dataset.name;
    updateJoinLeaveBtn();
    loadMembers();

    if (pollTimer) clearInterval(pollTimer);
    loadMessages(true);
    pollTimer = setInterval(() => loadMessages(false), 3000);
}

// ── Charger les messages ──
async function loadMessages(initial) {
    if (!activeGroupId) return;
    const res  = await fetch(`Messagerie.php?api=get_messages&group_id=${activeGroupId}&last_id=${lastMsgId}`);
    const data = await res.json();
    if (!data.allowed) return;

    const box = document.getElementById('messages-box');
    if (initial) {
        box.innerHTML = '';
        if (data.messages.length === 0) {
            box.innerHTML = '<p style="text-align:center;color:#94a3b8;font-style:italic;font-size:13px;margin-top:40px">Aucun message. Soyez le premier !</p>';
            return;
        }
    }
    if (!data.messages.length) return;

    data.messages.forEach(msg => {
        const isMe = msg.email === ME;
        const wrap = document.createElement('div');
        wrap.style.cssText = `display:flex;flex-direction:column;align-items:${isMe ? 'flex-end' : 'flex-start'}`;

        if (!isMe) {
            const who = document.createElement('p');
            who.style.cssText = 'font-size:11px;color:#94a3b8;margin:0 0 3px 4px';
            who.textContent = msg.email;
            wrap.appendChild(who);
        }

        const bubble = document.createElement('div');
        bubble.className = isMe ? 'bubble-me' : 'bubble-other';
        bubble.innerHTML = `
            <p style="font-size:13px;line-height:1.5;margin:0">${esc(msg.content).replace(/\n/g,'<br>')}</p>
            <p style="font-size:10px;margin:4px 0 0;text-align:right;opacity:.65">${fmtTime(msg.created_at)}</p>`;
        wrap.appendChild(bubble);
        box.appendChild(wrap);
        lastMsgId = Math.max(lastMsgId, parseInt(msg.id));
    });

    box.scrollTop = box.scrollHeight;
}

// ── Envoyer un message ──
async function sendMessage() {
    const input = document.getElementById('msg-input');
    const text  = input.value.trim();
    if (!text || !activeGroupId || !activeIsMember) return;
    input.value = '';
    input.style.height = 'auto';

    const res  = await fetch('Messagerie.php?api=send_message', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({content: text, group_id: activeGroupId})
    });
    const data = await res.json();
    if (data.ok) loadMessages(false);
}

// ── Rejoindre / Quitter ──
async function toggleJoinLeave() {
    if (activeIsMember && !confirm('Quitter ce groupe ?')) return;
    const api = activeIsMember ? 'leave_group' : 'join_group';
    await fetch(`Messagerie.php?api=${api}`, {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({group_id: activeGroupId})
    });
    activeIsMember = !activeIsMember;
    const el = document.querySelector(`.group-item[data-id="${activeGroupId}"]`);
    if (el) el.dataset.member = activeIsMember ? '1' : '0';
    document.getElementById('input-bar').style.display      = activeIsMember ? 'block' : 'none';
    document.getElementById('non-member-bar').style.display = activeIsMember ? 'none'  : 'block';
    updateJoinLeaveBtn();
    loadMembers();
    if (activeIsMember) loadMessages(true);
}

function updateJoinLeaveBtn() {
    const btn = document.getElementById('join-leave-btn');
    btn.style.display = 'block';
    if (activeIsMember) {
        btn.textContent = '⬅ Quitter';
        btn.style.cssText = 'display:block;font-size:11px;padding:6px 14px;border-radius:8px;font-weight:600;cursor:pointer;border:1px solid #fca5a5;background:#fff;color:#ef4444';
    } else {
        btn.textContent = '→ Rejoindre';
        btn.style.cssText = 'display:block;font-size:11px;padding:6px 14px;border-radius:8px;font-weight:600;cursor:pointer;border:none;background:#2563eb;color:#fff';
    }
}

// ── Membres ──
async function loadMembers() {
    const res  = await fetch(`Messagerie.php?api=get_members&group_id=${activeGroupId}`);
    const data = await res.json();
    const n = data.members.length;
    document.getElementById('member-count').textContent = n;
    document.getElementById('chat-subtitle').textContent = `${n} membre${n>1?'s':''}`;
    document.getElementById('members-inner').innerHTML = data.members.map(m => `
        <div style="display:flex;align-items:center;gap:7px;padding:4px 0">
          <div style="width:22px;height:22px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center">
            <i class="fa-solid fa-user" style="color:#2563eb;font-size:9px"></i>
          </div>
          <p style="font-size:11px;color:#334155;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:0">${esc(m.email)}</p>
        </div>`).join('');
}

function toggleMembers() {
    const d = document.getElementById('members-dropdown');
    d.style.display = d.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', e => {
    const w = document.getElementById('members-wrap');
    if (w && !w.contains(e.target)) document.getElementById('members-dropdown').style.display = 'none';
});

// ── Créer un groupe ──
function openCreateModal()  {
    const m = document.getElementById('modal-bg');
    m.style.display = 'flex';
}
function closeCreateModal() {
    document.getElementById('modal-bg').style.display = 'none';
    document.getElementById('new-group-name').value = '';
    document.getElementById('new-group-desc').value = '';
    document.getElementById('create-error').style.display = 'none';
}
async function createGroup() {
    const nom  = document.getElementById('new-group-name').value.trim();
    const desc = document.getElementById('new-group-desc').value.trim();
    if (!nom) { document.getElementById('create-error').style.display = 'block'; return; }

    const res  = await fetch('Messagerie.php?api=create_group', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({nom, desc})
    });
    const data = await res.json();
    if (!data.ok) return;

    closeCreateModal();

    const list = document.getElementById('my-groups-list');
    const div  = document.createElement('div');
    div.className = 'group-item';
    div.dataset.id     = data.id;
    div.dataset.name   = data.nom;
    div.dataset.member = '1';
    div.onclick = function(){ selectGroup(this); };
    div.innerHTML = `
        <div style="width:36px;height:36px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="fa-solid fa-users" style="color:#2563eb;font-size:13px"></i>
        </div>
        <div style="min-width:0;flex:1">
        <p class="g-name" style="font-size:13px;font-weight:600;color:#334155;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(data.nom)}</p>
        <p class="g-sub" style="font-size:11px;color:#94a3b8">1 membre</p>
        </div>`;
    list.appendChild(div);
    selectGroup(div);
}

// ── Touches clavier ──
document.getElementById('msg-input').addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});
document.getElementById('msg-input').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// ── Filtre sidebar ──
document.getElementById('sidebar-search').addEventListener('input', function() {
    const v = this.value.toLowerCase();
    document.querySelectorAll('.group-item').forEach(el => {
        el.style.display = (el.dataset.name||'').toLowerCase().includes(v) ? '' : 'none';
    });
});
document.getElementById('search-input').addEventListener('input', function() {
    document.getElementById('sidebar-search').value = this.value;
    document.getElementById('sidebar-search').dispatchEvent(new Event('input'));
});

// ── Fermer modal en cliquant dehors ──
document.getElementById('modal-bg').addEventListener('click', function(e) {
    if (e.target === this) closeCreateModal();
});

// ── Helpers ──
function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmtTime(dt) {
    return new Date(dt.replace(' ','T')).toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'});
}
</script>
</body>
</html>