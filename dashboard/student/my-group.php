<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Fetch ALL Groups this student belongs to
$g_stmt = $pdo->prepare("
    SELECT g.*, c.title as course_title, gm.role as my_role
    FROM `groups` g
    JOIN group_members gm ON g.id = gm.group_id
    JOIN courses c ON g.course_id = c.id
    WHERE gm.user_id = ?
    ORDER BY g.created_at DESC
");
$g_stmt->execute([$user_id]);
$all_my_groups = $g_stmt->fetchAll(PDO::FETCH_ASSOC);

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* Fix for mobile scrolling and viewport height */
    html, body {
        height: 100%;
        overflow-x: hidden;
    }
    
    .main-content {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        padding-bottom: 120px; /* Space for bottom nav on mobile */
    }

    .group-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }

    /* Animation for entering the page */
    .fade-up {
        animation: fadeUp 0.5s ease-out forwards;
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="min-h-screen bg-[#f8fafc] flex">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 lg:ml-72 flex flex-col min-w-0">
        <main class="main-content p-4 md:p-8 lg:p-12">
            <div class="max-w-6xl mx-auto w-full">

                <div class="mb-10 fade-up">
                    <div class="flex items-center gap-2 text-indigo-600 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-users-rectangle text-xs"></i>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-[0.2em]">Collaboration Portal</span>
                    </div>
                    <h1 class="text-3xl md:text-5xl font-black text-slate-900 tracking-tight uppercase italic">My Learning Teams</h1>
                    <p class="text-slate-500 mt-2 text-sm md:text-base font-medium">Access your collaborative groups and track shared assignments.</p>
                </div>

                <?php if (empty($all_my_groups)): ?>
                    <div class="bg-white rounded-[2.5rem] p-12 md:p-20 text-center border border-slate-200 shadow-xl fade-up">
                        <div class="w-20 h-20 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-layer-group text-3xl"></i>
                        </div>
                        <h2 class="text-xl font-black text-slate-900 uppercase">No Active Groups</h2>
                        <p class="text-slate-500 mt-2 max-w-sm mx-auto text-sm">Once you are assigned to a group by an instructor, your team dashboard will appear here.</p>
                    </div>
                <?php else: ?>
                    
                    <div class="space-y-8">
                        <?php foreach ($all_my_groups as $group): 
                            // Fetch Team Members
                            $m_stmt = $pdo->prepare("
                                SELECT u.first_name, u.last_name, u.avatar, gm.role 
                                FROM group_members gm
                                JOIN users u ON gm.user_id = u.id
                                WHERE gm.group_id = ?
                                ORDER BY gm.role DESC, u.first_name ASC
                            ");
                            $m_stmt->execute([$group['id']]);
                            $members = $m_stmt->fetchAll();

                            // Fetch Assignments
                            $a_stmt = $pdo->prepare("
                                SELECT a.* FROM assessments a
                                JOIN assessment_groups ag ON a.id = ag.assessment_id
                                WHERE ag.group_id = ? AND a.status = 'published'
                                ORDER BY a.due_date ASC
                            ");
                            $a_stmt->execute([$group['id']]);
                            $tasks = $a_stmt->fetchAll();
                        ?>

                        <div class="group-card bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition-all fade-up">
                            <div class="flex flex-col lg:flex-row">
                                
                                <div class="lg:w-1/3 bg-slate-900 p-6 md:p-8 text-white">
                                    <div class="mb-8">
                                        <div class="flex items-center justify-between mb-4">
                                            <span class="px-3 py-1 bg-indigo-500 text-[8px] font-black uppercase tracking-widest rounded-md">
                                                <?= h($group['my_role']) ?>
                                            </span>
                                            <?php if ($group['my_role'] === 'leader'): ?>
                                                <i class="fas fa-crown text-amber-400 text-sm"></i>
                                            <?php endif; ?>
                                        </div>
                                        <h3 class="text-[9px] font-black uppercase text-indigo-400 tracking-widest mb-1"><?= h($group['course_title']) ?></h3>
                                        <h2 class="text-2xl font-black italic tracking-tighter uppercase leading-tight"><?= h($group['name']) ?></h2>
                                    </div>

                                    <div class="pt-6 border-t border-white/10">
                                        <p class="text-[9px] font-black uppercase text-slate-500 tracking-widest mb-4">Squad Roster</p>
                                        <div class="space-y-3 max-h-[200px] overflow-y-auto custom-scrollbar pr-2">
                                            <?php foreach($members as $m): ?>
                                                <div class="flex items-center gap-3 group/member">
                                                    <div class="w-8 h-8 rounded-lg bg-slate-800 border border-white/5 overflow-hidden flex-shrink-0">
                                                        <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $m['avatar'] ?: 'default.jpg' ?>" class="w-full h-full object-cover opacity-80 group-hover/member:opacity-100 transition-opacity">
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-[11px] font-bold text-slate-200 truncate"><?= h($m['first_name'].' '.$m['last_name']) ?></p>
                                                        <p class="text-[8px] font-medium text-slate-500 uppercase"><?= $m['role'] === 'leader' ? 'Team Leader' : 'Member' ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="lg:w-2/3 p-6 md:p-8 flex flex-col">
                                    <div class="flex items-center justify-between mb-6">
                                        <h4 class="text-xs font-black uppercase tracking-widest text-slate-400">Team Directives</h4>
                                        <span class="text-[10px] font-bold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full">
                                            <?= count($tasks) ?> Assignments
                                        </span>
                                    </div>

                                    <div class="space-y-3 flex-1">
                                        <?php foreach ($tasks as $task): ?>
                                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 hover:bg-white hover:border-indigo-100 hover:shadow-sm transition-all group/task">
                                                <div class="flex items-center gap-4">
                                                    <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 group-hover/task:text-indigo-600 group-hover/task:border-indigo-100 transition-all">
                                                        <i class="fas fa-file-signature text-sm"></i>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-bold text-slate-800 leading-none"><?= h($task['title']) ?></p>
                                                        <p class="text-[10px] font-medium text-slate-400 mt-1.5 flex items-center gap-2">
                                                            <i class="far fa-clock text-rose-400"></i> 
                                                            Due: <?= date('M d, H:i', strtotime($task['due_date'])) ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                <a href="group-assignment-lobby.php?id=<?= $task['id'] ?>" 
                                                   class="px-5 py-2.5 bg-slate-900 text-white rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-lg active:scale-95">
                                                    Launch
                                                </a>
                                            </div>
                                        <?php endforeach; ?>

                                        <?php if (empty($tasks)): ?>
                                            <div class="h-full flex flex-col items-center justify-center py-10 opacity-40">
                                                <i class="fas fa-check-double text-2xl mb-2"></i>
                                                <p class="text-[10px] font-black uppercase tracking-widest">No pending tasks</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php include 'bottom-nav.php'; ?>