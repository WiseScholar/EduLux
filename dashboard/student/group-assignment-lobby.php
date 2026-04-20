<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

$assessment_id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// 1. Fetch Assignment Details
$stmt = $pdo->prepare("
    SELECT a.*, c.title as course_title 
    FROM assessments a 
    JOIN courses c ON a.course_id = c.id 
    WHERE a.id = ? AND a.is_group_assignment = 1
");
$stmt->execute([$assessment_id]);
$assignment = $stmt->fetch();

if (!$assignment) die("Invalid assignment.");

// 2. Identify the Student's Group context
$g_stmt = $pdo->prepare("
    SELECT g.*, gm.role, gm.can_submit 
    FROM `groups` g
    JOIN group_members gm ON g.id = gm.group_id
    WHERE g.course_id = ? AND gm.user_id = ?
");
$g_stmt->execute([$assignment['course_id'], $user_id]);
$my_group = $g_stmt->fetch();

if (!$my_group) die("Access Denied: You are not in a group for this course.");

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    [x-cloak] {
        display: none !important;
    }

    .chat-container {
        height: 450px;
        scroll-behavior: smooth;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }

    .glass-dark {
        background: rgba(15, 23, 42, 0.9);
        backdrop-filter: blur(10px);
    }

    /* Animation for messages */
    .msg-enter {
        animation: slideIn 0.2s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class="min-h-screen bg-[#f8fafc] flex" x-data="groupLobby()">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 lg:ml-72 flex flex-col min-w-0">
        <main class="p-4 md:p-8 lg:p-10 pb-24">
            <div class="max-w-6xl mx-auto w-full">

                <div class="bg-white rounded-[2.5rem] p-6 md:p-10 border border-slate-200 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center gap-8 relative overflow-hidden">
                    <div class="flex items-center gap-6 relative z-10">
                        <div class="w-20 h-20 bg-indigo-600 rounded-[2rem] flex items-center justify-center text-white shadow-2xl shadow-indigo-200">
                            <i class="fas fa-rocket text-3xl"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-600 block mb-2">Group Directive</span>
                            <h1 class="text-3xl font-[900] text-slate-900 tracking-tighter uppercase italic leading-none"><?= h($assignment['title']) ?></h1>
                            <p class="text-slate-400 text-xs font-bold mt-2 uppercase tracking-widest flex items-center gap-2">
                                <i class="fas fa-layer-group"></i> <?= h($assignment['course_title']) ?>
                                <span class="text-slate-200">•</span>
                                <span class="text-indigo-500">Squad: <?= h($my_group['name']) ?></span>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 relative z-10">
                        <div class="text-right hidden xl:block">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Due Deadline</p>
                            <p class="text-sm font-black text-rose-500 italic"><?= date('M d, H:i', strtotime($assignment['due_date'])) ?></p>
                        </div>
                        <div class="h-12 w-[1px] bg-slate-100 mx-2 hidden xl:block"></div>
                        <a href="view-assessment.php?id=<?= $assessment_id ?>"
                            class="px-8 py-4 bg-indigo-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-xl hover:bg-indigo-700 transition-all">
                            Enter Workspace
                        </a>
                    </div>
                    <i class="fas fa-users-viewfinder absolute -bottom-10 -right-10 text-[15rem] opacity-[0.02] -rotate-12"></i>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                    <div class="lg:col-span-7 flex flex-col h-full">
                        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm flex flex-col overflow-hidden h-full">
                            <div class="px-8 py-6 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
                                <div>
                                    <h3 class="text-xs font-[900] uppercase tracking-widest text-slate-800">Team Comms</h3>
                                    <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">End-to-End Encrypted</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex -space-x-2">
                                        <template x-for="member in teamStatus" :key="member.user_id">
                                            <div class="w-8 h-8 rounded-full border-2 border-white bg-slate-200 overflow-hidden relative" :title="member.name">
                                                <img :src="'<?= BASE_URL ?>assets/uploads/avatars/' + (member.avatar || 'default.jpg')" class="w-full h-full object-cover">
                                                <div x-show="member.is_online" class="absolute bottom-0 right-0 w-2 h-2 bg-green-500 rounded-full border border-white"></div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="chat-container overflow-y-auto p-8 custom-scrollbar space-y-6 flex-1" x-ref="chatBox">
                                <template x-for="msg in messages" :key="msg.id">
                                    <div :class="msg.user_id == <?= $user_id ?> ? 'flex flex-col items-end' : 'flex flex-col items-start'" class="msg-enter">
                                        <div class="flex items-center gap-2 mb-1.5 px-2">
                                            <span class="text-[8px] font-black uppercase tracking-widest text-slate-400" x-text="msg.user_name"></span>
                                            <span class="text-[7px] text-slate-300 font-bold" x-text="formatTime(msg.created_at)"></span>
                                        </div>
                                        <div :class="msg.user_id == <?= $user_id ?> ? 'bg-indigo-600 text-white rounded-3xl rounded-tr-none' : 'bg-slate-100 text-slate-700 rounded-3xl rounded-tl-none'"
                                            class="px-6 py-4 text-sm font-medium max-w-[85%] shadow-sm leading-relaxed" x-text="msg.message">
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="p-6 bg-slate-50/50 border-t border-slate-100">
                                <div class="relative flex items-center gap-3">
                                    <div class="relative flex-1">
                                        <input type="text" x-model="newMessage" @keydown.enter="sendMessage"
                                            class="w-full bg-white border-none rounded-2xl py-4 pl-6 pr-12 text-sm font-medium shadow-inner focus:ring-2 focus:ring-indigo-500/20"
                                            placeholder="Broadcast to your team...">
                                        <div class="absolute right-4 top-1/2 -translate-y-1/2 flex gap-2 text-slate-300">
                                            <i class="far fa-smile hover:text-indigo-500 cursor-pointer transition-colors"></i>
                                        </div>
                                    </div>
                                    <button @click="sendMessage" class="w-14 h-14 bg-slate-900 text-white rounded-2xl shadow-xl flex items-center justify-center hover:bg-indigo-600 transition-all active:scale-95">
                                        <i class="fas fa-paper-plane text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-5 space-y-8">

                        <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white shadow-2xl relative overflow-hidden group">
                            <div class="relative z-10">
                                <div class="flex justify-between items-start mb-8">
                                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-400">Submission Node</h3>
                                    <div :class="canSubmit ? 'bg-green-500/20 text-green-400' : 'bg-rose-500/20 text-rose-400'"
                                        class="px-3 py-1 rounded-full text-[8px] font-black uppercase tracking-widest transition-colors">
                                        <span x-text="canSubmit ? 'Active' : 'Locked'"></span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between p-6 bg-white/5 rounded-3xl border border-white/10 group-hover:border-indigo-500/30 transition-all">
                                    <div>
                                        <p class="text-sm font-black uppercase italic tracking-tighter">Accept Authority?</p>
                                        <p class="text-[9px] text-slate-500 mt-1 uppercase font-bold tracking-widest">Only one per group</p>
                                    </div>

                                    <button @click="toggleSubmission"
                                        :class="canSubmit ? 'bg-indigo-500 shadow-lg shadow-indigo-500/50' : 'bg-slate-800'"
                                        class="w-16 h-9 rounded-full relative transition-all duration-300">
                                        <div :class="canSubmit ? 'translate-x-8' : 'translate-x-1'"
                                            class="w-7 h-7 bg-white rounded-full absolute top-1 transition-transform duration-300 shadow-md flex items-center justify-center">
                                            <i class="fas fa-key text-[10px] transition-colors" :class="canSubmit ? 'text-indigo-600' : 'text-slate-300'"></i>
                                        </div>
                                    </button>
                                </div>

                                <div class="mt-8 p-5 bg-indigo-500/10 rounded-2xl border border-indigo-500/20">
                                    <p class="text-[10px] text-indigo-200 leading-relaxed font-medium italic">
                                        "Activating authority enables the final submission button for you and disables it for all other teammates to prevent multi-submissions."
                                    </p>
                                </div>
                            </div>
                            <i class="fas fa-shield-halved absolute -bottom-10 -right-10 text-[10rem] opacity-[0.03] -rotate-12"></i>
                        </div>

                        <div class="bg-white rounded-[2.5rem] border border-slate-200 p-8 shadow-sm">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-8 text-center flex items-center justify-center gap-3">
                                <span class="w-8 h-[1px] bg-slate-100"></span>
                                Node Status
                                <span class="w-8 h-[1px] bg-slate-100"></span>
                            </h3>
                            <div class="space-y-4">
                                <template x-for="member in teamStatus" :key="member.user_id">
                                    <div class="flex items-center justify-between p-4 rounded-2xl transition-all"
                                        :class="member.can_submit ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-100 scale-[1.02]' : 'bg-slate-50 text-slate-600'">
                                        <div class="flex items-center gap-4">
                                            <div class="relative">
                                                <img :src="'<?= BASE_URL ?>assets/uploads/avatars/' + (member.avatar || 'default.jpg')"
                                                    class="w-10 h-10 rounded-xl object-cover"
                                                    :class="member.can_submit ? 'border-2 border-indigo-400' : 'border border-slate-200'">
                                                <div x-show="member.is_online" class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full border-2" :class="member.can_submit ? 'border-indigo-600' : 'border-slate-50'"></div>
                                            </div>
                                            <div>
                                                <p class="text-xs font-black uppercase italic tracking-tight" x-text="member.name"></p>
                                                <p class="text-[8px] font-bold uppercase tracking-widest opacity-60" x-text="member.is_online ? 'Transmission Active' : 'Offline'"></p>
                                            </div>
                                        </div>
                                        <div x-show="member.can_submit" class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                                            <i class="fas fa-check-double text-xs"></i>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
</div>

<script>
    function groupLobby() {
        return {
            messages: [],
            newMessage: '',
            teamStatus: [],
            canSubmit: <?= $my_group['can_submit'] ? 'true' : 'false' ?>,
            groupId: <?= $my_group['id'] ?>,

            init() {
                this.pulse();
                this.fetchChat();
                this.fetchTeamStatus();

                // Heartbeat & Sync: 3 seconds for UI feel
                setInterval(() => {
                    this.pulse();
                    this.fetchChat();
                    this.fetchTeamStatus();
                }, 3000);
            },

            async pulse() {
                const formData = new FormData();
                formData.append('group_id', this.groupId);
                await fetch('actions/pulse.php', {
                    method: 'POST',
                    body: formData
                });
            },

            async fetchChat() {
                const res = await fetch(`actions/get-group-chat.php?group_id=${this.groupId}`);
                const data = await res.json();
                const wasAtBottom = this.$refs.chatBox.scrollHeight - this.$refs.chatBox.clientHeight <= this.$refs.chatBox.scrollTop + 1;

                this.messages = data;

                if (wasAtBottom) {
                    this.$nextTick(() => {
                        this.$refs.chatBox.scrollTop = this.$refs.chatBox.scrollHeight;
                    });
                }
            },

            async sendMessage() {
                if (!this.newMessage.trim()) return;
                const text = this.newMessage;
                this.newMessage = '';

                await fetch('actions/send-group-chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        group_id: this.groupId,
                        message: text
                    })
                });
                this.fetchChat();
            },

            async fetchTeamStatus() {
                const res = await fetch(`actions/get-team-status.php?group_id=${this.groupId}`);
                this.teamStatus = await res.json();
                const me = this.teamStatus.find(m => m.user_id == <?= $user_id ?>);
                this.canSubmit = me ? !!parseInt(me.can_submit) : false;
            },

            async toggleSubmission() {
                await fetch('actions/toggle-submission-right.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        group_id: this.groupId,
                        state: !this.canSubmit
                    })
                });
                this.fetchTeamStatus();
            },

            formatTime(dateStr) {
                const date = new Date(dateStr);
                return date.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }
    }
</script>

<?php include 'bottom-nav.php'; ?>