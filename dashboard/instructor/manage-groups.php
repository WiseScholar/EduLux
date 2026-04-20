<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . LOGIN_URL);
    exit;
}

$course_id = (int)($_GET['course_id'] ?? 0);
$instructor_id = $_SESSION['user_id'];

if ($course_id <= 0) {
    $stmt = $pdo->prepare("SELECT id, title, thumbnail FROM courses WHERE instructor_id = ? ORDER BY created_at DESC");
    $stmt->execute([$instructor_id]);
    $my_courses = $stmt->fetchAll();

    require_once ROOT_PATH . 'includes/header.php';
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Select Course | Group Management</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>

    <body class="bg-gradient-to-br from-slate-50 to-white">
        <div class="min-h-screen flex">
            <?php include 'sidebar.php'; ?>
            <div class="flex-1 lg:ml-72 p-8 lg:p-12">
                <div class="max-w-6xl mx-auto">
                    <!-- Header -->
                    <div class="mb-12">
                        <div class="flex items-center gap-2 text-sm text-indigo-600 mb-3">
                            <i class="fas fa-layer-group text-xs"></i>
                            <span class="font-mono text-[11px] font-semibold uppercase tracking-wider">Group Management</span>
                        </div>
                        <h1 class="text-4xl md:text-5xl font-bold tracking-tight text-slate-900">
                            Select a Course
                        </h1>
                        <p class="text-slate-500 mt-2 text-lg">Choose a course to manage student groups</p>
                    </div>

                    <!-- Course Grid -->
                    <?php if (empty($my_courses)): ?>
                        <div class="bg-white rounded-2xl border border-slate-200 p-16 text-center">
                            <i class="fas fa-chalkboard-teacher text-5xl text-slate-300 mb-4"></i>
                            <p class="text-slate-400 font-medium">You don't have any courses yet</p>
                            <a href="create-course.php" class="inline-block mt-4 text-indigo-600 text-sm font-medium hover:underline">
                                Create your first course →
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($my_courses as $c): ?>
                                <a href="?course_id=<?= $c['id'] ?>"
                                    class="group bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                                    <div class="aspect-video bg-gradient-to-br from-indigo-500 to-purple-600 relative overflow-hidden">
                                        <?php if (!empty($c['thumbnail'])): ?>
                                            <img src="<?= BASE_URL . $c['thumbnail'] ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center">
                                                <i class="fas fa-graduation-cap text-4xl text-white/30"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <span class="px-4 py-2 bg-white/20 backdrop-blur rounded-full text-white text-sm font-medium">
                                                <i class="fas fa-users mr-2"></i> Manage Groups
                                            </span>
                                        </div>
                                    </div>
                                    <div class="p-5">
                                        <h3 class="font-bold text-slate-800 group-hover:text-indigo-600 transition-colors"><?= h($c['title']) ?></h3>
                                        <p class="text-xs text-slate-400 mt-1 flex items-center gap-2">
                                            <i class="fas fa-arrow-right text-[10px]"></i>
                                            <span>Click to configure groups</span>
                                        </p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
    exit;
}

// Fetch Course Details
$c_stmt = $pdo->prepare("SELECT id, title, description FROM courses WHERE id = ? AND instructor_id = ?");
$c_stmt->execute([$course_id, $instructor_id]);
$course = $c_stmt->fetch();

if (!$course) die("Course not found or access denied.");

// Fetch ALL students enrolled in this course
$s_stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email, u.avatar 
    FROM users u
    JOIN enrollments e ON u.id = e.user_id
    WHERE e.course_id = ? AND u.role = 'student'
    ORDER BY u.first_name ASC
");
$s_stmt->execute([$course_id]);
$all_students = $s_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Existing Groups for this course
// Fetch Existing Groups for this course with their members
$g_stmt = $pdo->prepare("
    SELECT g.*, 
           COALESCE(json_arrayagg(
               json_object(
                   'id', u.id,
                   'first_name', u.first_name,
                   'last_name', u.last_name,
                   'email', u.email,
                   'avatar', u.avatar
               )
           ), '[]') as members
    FROM `groups` g
    LEFT JOIN group_members gm ON g.id = gm.group_id
    LEFT JOIN users u ON gm.user_id = u.id
    WHERE g.course_id = ?
    GROUP BY g.id
    ORDER BY g.name ASC
");
$g_stmt->execute([$course_id]);
$existing_groups = $g_stmt->fetchAll(PDO::FETCH_ASSOC);

// Parse the members JSON for each group
foreach ($existing_groups as &$group) {
    $group['members'] = json_decode($group['members'], true) ?: [];
}

require_once ROOT_PATH . 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Manager | <?= h($course['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        [x-cloak] {
            display: none !important;
        }

        .drag-over {
            border-color: #6366f1 !important;
            background-color: rgba(99, 102, 241, 0.05) !important;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .group-card {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .group-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-50 to-white">

    <div class="min-h-screen flex" x-data="groupManager(<?= htmlspecialchars(json_encode($all_students)) ?>, <?= htmlspecialchars(json_encode($existing_groups)) ?>)" x-init="initGroups()">

        <?php include 'sidebar.php'; ?>

        <div class="flex-1 lg:ml-72">
            <main class="p-6 lg:p-10">

                <!-- Header Section -->
                <div class="mb-8">
                    <div class="flex items-center gap-2 text-sm text-indigo-600 mb-3">
                        <i class="fas fa-layer-group text-xs"></i>
                        <span class="font-mono text-[11px] font-semibold uppercase tracking-wider">Group Management System</span>
                    </div>
                    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <a href="manage-groups.php" class="text-slate-400 hover:text-indigo-600 transition-colors">
                                    <i class="fas fa-arrow-left text-sm"></i>
                                </a>
                                <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-slate-900">
                                    <?= h($course['title']) ?>
                                </h1>
                            </div>
                            <p class="text-slate-500 mt-1">Organize students into collaborative learning groups</p>
                        </div>
                        <div class="flex gap-3">
                            <button @click="openAutoModal"
                                class="px-5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
                                <i class="fas fa-magic mr-2 text-indigo-500"></i>
                                Auto-generate
                            </button>
                            <button @click="addGroup()"
                                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition-all shadow-sm">
                                <i class="fas fa-plus mr-2"></i>
                                New group
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Bar -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white rounded-xl border border-slate-200 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-slate-400">Total Students</p>
                                <p class="text-2xl font-bold text-slate-800" x-text="allStudents.length"></p>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center">
                                <i class="fas fa-users text-indigo-500"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-slate-400">Groups Created</p>
                                <p class="text-2xl font-bold text-slate-800" x-text="groups.length"></p>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center">
                                <i class="fas fa-layer-group text-emerald-500"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-slate-400">Assigned</p>
                                <p class="text-2xl font-bold text-slate-800" x-text="assignedCount"></p>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-500"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-slate-400">Unassigned</p>
                                <p class="text-2xl font-bold text-amber-600" x-text="unassigned.length"></p>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center">
                                <i class="fas fa-user-clock text-amber-500"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Grid: Unassigned Students + Groups -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                    <!-- Unassigned Students Panel -->
                    <div class="lg:col-span-3">
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden sticky top-24">
                            <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                                        <i class="fas fa-user-plus text-indigo-500 text-xs"></i>
                                        Unassigned Students
                                    </h3>
                                    <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full" x-text="unassigned.length"></span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1">Drag & drop to assign</p>
                            </div>
                            <div class="p-3 space-y-2 max-h-[60vh] overflow-y-auto custom-scrollbar">
                                <template x-for="student in unassigned" :key="student.id">
                                    <div draggable="true"
                                        @dragstart="dragStart($event, student)"
                                        @dragend="dragEnd()"
                                        class="student-card p-3 bg-slate-50 rounded-xl flex items-center gap-3 cursor-grab active:cursor-grabbing hover:bg-indigo-50 transition-all group border border-transparent hover:border-indigo-200">
                                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center text-indigo-600 font-bold text-xs">
                                            <span x-text="getInitials(student.first_name, student.last_name)"></span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-slate-700 truncate" x-text="student.first_name + ' ' + student.last_name"></p>
                                            <p class="text-[10px] text-slate-400 truncate" x-text="student.email"></p>
                                        </div>
                                        <i class="fas fa-grip-vertical text-slate-300 text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                    </div>
                                </template>
                                <div x-show="unassigned.length === 0" class="py-12 text-center">
                                    <i class="fas fa-check-circle text-3xl text-emerald-200 mb-2"></i>
                                    <p class="text-xs text-slate-400">All students assigned to groups</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Groups Grid -->
                    <div class="lg:col-span-9">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <template x-for="(group, gIdx) in groups" :key="gIdx">
                                <div class="group-card bg-white rounded-2xl border-2 border-slate-200 shadow-sm overflow-hidden"
                                    @dragover.prevent="dragOver($event)"
                                    @dragleave="dragLeave($event)"
                                    @drop="dropOnGroup($event, gIdx)">

                                    <!-- Group Header -->
                                    <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50/50 to-white flex items-center justify-between">
                                        <div class="flex-1">
                                            <input type="text" x-model="group.name"
                                                class="text-lg font-bold text-slate-800 bg-transparent border-0 p-0 focus:ring-0 w-full"
                                                placeholder="Group name">
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-semibold text-slate-400 bg-slate-100 px-2 py-1 rounded-full">
                                                <i class="fas fa-users mr-1"></i> <span x-text="group.members.length"></span>/<span x-text="group.max_capacity || 10"></span>
                                            </span>
                                            <button @click="removeGroup(gIdx)" class="text-slate-300 hover:text-red-500 transition-colors">
                                                <i class="fas fa-trash-alt text-sm"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Group Members -->
                                    <div class="p-4 min-h-[200px] max-h-[300px] overflow-y-auto custom-scrollbar">
                                        <template x-for="(member, mIdx) in group.members" :key="member.id">
                                            <div class="flex items-center justify-between p-2 mb-2 bg-slate-50 rounded-xl group/member">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-7 h-7 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-[10px]">
                                                        <span x-text="getInitials(member.first_name, member.last_name)"></span>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium text-slate-700" x-text="member.first_name + ' ' + member.last_name"></p>
                                                    </div>
                                                </div>
                                                <button @click="removeMember(gIdx, mIdx)"
                                                    class="opacity-0 group-hover/member:opacity-100 text-slate-400 hover:text-red-500 transition-all">
                                                    <i class="fas fa-times-circle text-sm"></i>
                                                </button>
                                            </div>
                                        </template>
                                        <div x-show="group.members.length === 0"
                                            class="h-32 flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-xl text-slate-400">
                                            <i class="fas fa-arrow-down text-sm mb-2"></i>
                                            <p class="text-xs">Drop students here</p>
                                        </div>
                                    </div>

                                    <!-- Group Footer -->
                                    <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/30 flex justify-between items-center text-[10px]">
                                        <span class="text-slate-400">
                                            <i class="far fa-clock mr-1"></i> Drag & drop to assign
                                        </span>
                                        <button @click="clearGroup(gIdx)" class="text-slate-400 hover:text-amber-600 transition-colors">
                                            <i class="fas fa-eraser mr-1"></i> Clear
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Empty State for Groups -->
                            <div x-show="groups.length === 0" class="md:col-span-2">
                                <div class="bg-white rounded-2xl border-2 border-dashed border-slate-300 p-16 text-center">
                                    <i class="fas fa-layer-group text-4xl text-slate-300 mb-4"></i>
                                    <p class="text-slate-400 font-medium">No groups created yet</p>
                                    <button @click="addGroup()" class="mt-4 text-indigo-600 text-sm font-medium hover:underline">
                                        Create your first group →
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="fixed bottom-8 right-8 z-50">
                    <button @click="saveGroups" :disabled="saving"
                        class="group px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-bold shadow-lg shadow-indigo-200 transition-all disabled:opacity-50 flex items-center gap-3">
                        <i class="fas fa-save" :class="saving ? 'animate-pulse' : 'group-hover:scale-110 transition-transform'"></i>
                        <span x-text="saving ? 'Saving...' : 'Save Group Structure'"></span>
                    </button>
                </div>

                <!-- Auto-Generate Modal -->
                <div x-show="showAutoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                    x-cloak x-transition.opacity @click.away="showAutoModal = false">
                    <div class="bg-white rounded-3xl max-w-md w-full p-8 shadow-2xl" @click.stop>
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-magic text-2xl text-indigo-600"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-slate-800">Auto-Generate Groups</h3>
                            <p class="text-slate-500 text-sm mt-2">Let AI distribute students evenly into groups</p>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Number of Groups</label>
                                <input type="number" x-model="autoGroupCount" min="1" :max="Math.ceil(allStudents.length / 2)"
                                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Max Students per Group</label>
                                <input type="number" x-model="autoMaxCapacity" min="1"
                                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                            </div>
                        </div>

                        <div class="flex gap-3 mt-8">
                            <button @click="showAutoModal = false"
                                class="flex-1 px-4 py-3 border border-slate-200 rounded-xl text-slate-600 font-medium hover:bg-slate-50 transition-all">
                                Cancel
                            </button>
                            <button @click="runAutoGenerator"
                                class="flex-1 px-4 py-3 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition-all">
                                Generate Groups
                            </button>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        function groupManager(allStudentsData, existingGroupsData) {
            return {
                allStudents: allStudentsData,
                groups: [],
                draggedStudent: null,
                showAutoModal: false,
                autoGroupCount: 3,
                autoMaxCapacity: 5,
                saving: false,

                get unassigned() {
                    const assignedIds = this.groups.flatMap(g => g.members.map(m => m.id));
                    return this.allStudents.filter(s => !assignedIds.includes(s.id));
                },

                get assignedCount() {
                    return this.allStudents.length - this.unassigned.length;
                },

                initGroups() {
                    if (existingGroupsData && existingGroupsData.length > 0) {
                        this.groups = existingGroupsData.map(g => ({
                            id: g.id,
                            name: g.name,
                            members: g.members || [],
                            max_capacity: g.max_capacity || 10
                        }));
                    } else if (this.allStudents.length > 0) {
                        // Suggest creating groups based on student count
                        const suggestedGroups = Math.ceil(this.allStudents.length / 5);
                        if (suggestedGroups > 0 && suggestedGroups <= 6) {
                            this.autoGroupCount = suggestedGroups;
                            this.autoMaxCapacity = Math.ceil(this.allStudents.length / suggestedGroups);
                        }
                    }
                },

                getInitials(first, last) {
                    return ((first || '').charAt(0) + (last || '').charAt(0)).toUpperCase();
                },

                dragStart(event, student) {
                    this.draggedStudent = student;
                    event.dataTransfer.effectAllowed = 'move';
                    event.target.classList.add('opacity-50');
                },

                dragEnd() {
                    this.draggedStudent = null;
                    document.querySelectorAll('.student-card').forEach(card => {
                        card.classList.remove('opacity-50');
                    });
                },

                dragOver(event) {
                    event.preventDefault();
                    event.currentTarget.classList.add('drag-over');
                },

                dragLeave(event) {
                    event.currentTarget.classList.remove('drag-over');
                },

                dropOnGroup(event, groupIndex) {
                    event.preventDefault();
                    event.currentTarget.classList.remove('drag-over');

                    if (!this.draggedStudent) return;

                    const group = this.groups[groupIndex];
                    if (group.members.length >= (group.max_capacity || 10)) {
                        alert(`Group "${group.name}" has reached maximum capacity (${group.max_capacity} students).`);
                        return;
                    }

                    if (group.members.some(m => m.id === this.draggedStudent.id)) {
                        alert('This student is already in the group.');
                        return;
                    }

                    group.members.push(this.draggedStudent);
                    this.draggedStudent = null;
                },

                addGroup() {
                    const groupName = `Group ${String.fromCharCode(65 + this.groups.length)}`;
                    this.groups.push({
                        id: null,
                        name: groupName,
                        members: [],
                        max_capacity: 10
                    });
                },

                removeMember(groupIndex, memberIndex) {
                    this.groups[groupIndex].members.splice(memberIndex, 1);
                },

                removeGroup(index) {
                    if (confirm(`Remove group "${this.groups[index].name}"? Members will become unassigned.`)) {
                        this.groups.splice(index, 1);
                    }
                },

                clearGroup(index) {
                    if (confirm(`Clear all members from group "${this.groups[index].name}"?`)) {
                        this.groups[index].members = [];
                    }
                },

                openAutoModal() {
                    this.autoGroupCount = Math.min(Math.ceil(this.allStudents.length / 4), 8) || 2;
                    this.autoMaxCapacity = Math.ceil(this.allStudents.length / this.autoGroupCount) || 5;
                    this.showAutoModal = true;
                },

                runAutoGenerator() {
                    if (this.autoGroupCount < 1 || this.autoGroupCount > 20) {
                        alert('Please enter a valid number of groups (1-20).');
                        return;
                    }

                    // Shuffle students
                    const shuffled = [...this.allStudents].sort(() => Math.random() - 0.5);

                    // Create empty groups
                    this.groups = [];
                    for (let i = 0; i < this.autoGroupCount; i++) {
                        this.groups.push({
                            id: null,
                            name: `Team ${String.fromCharCode(65 + i)}`,
                            members: [],
                            max_capacity: this.autoMaxCapacity
                        });
                    }

                    // Distribute students evenly
                    let groupIdx = 0;
                    shuffled.forEach(student => {
                        const group = this.groups[groupIdx];
                        if (group.members.length < group.max_capacity) {
                            group.members.push(student);
                        }
                        groupIdx = (groupIdx + 1) % this.autoGroupCount;
                    });

                    this.showAutoModal = false;
                },

                async saveGroups() {
                    if (this.groups.length === 0) {
                        alert('Please create at least one group before saving.');
                        return;
                    }

                    this.saving = true;

                    try {
                        const response = await fetch('actions/save-groups.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                course_id: <?= $course_id ?>,
                                groups: this.groups.map(g => ({
                                    id: g.id,
                                    name: g.name,
                                    max_members: g.max_capacity,
                                    members: g.members.map(m => ({ id: m.id }))
                                }))
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            // Show success message
                            const successMsg = document.createElement('div');
                            successMsg.className = 'fixed bottom-24 right-8 bg-green-500 text-white px-6 py-3 rounded-xl shadow-lg z-50 animate-fade-in';
                            successMsg.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Groups saved successfully!';
                            document.body.appendChild(successMsg);
                            setTimeout(() => successMsg.remove(), 3000);

                            // Refresh to get group IDs
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            alert('Error: ' + (result.message || 'Failed to save groups'));
                        }
                    } catch (error) {
                        alert('Network error. Please try again.');
                    } finally {
                        this.saving = false;
                    }
                }
            }
        }
    </script>

    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
    </style>

</body>

</html>