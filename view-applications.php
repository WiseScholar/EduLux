<?php
require_once __DIR__ . '/includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

// Admin authentication required
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

// Fetch all applications
$stmt = $pdo->query("SELECT * FROM trainer_accreditation ORDER BY submission_date DESC");
$applications = $stmt->fetchAll();

require_once ROOT_PATH . 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Applications | Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-slate-50">

<div class="min-h-screen" x-data="applicationsApp()" x-init="init()">
    
    <!-- Header -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-900 text-white sticky top-0 z-30 shadow-xl">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Trainer Applications</h1>
                    <p class="text-indigo-200 text-sm mt-1">Manage and review accreditation submissions</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="bg-white/10 rounded-full px-4 py-2">
                        <i class="fas fa-users mr-2"></i>
                        <span x-text="applications.length"></span> Total
                    </div>
                    <div class="bg-white/10 rounded-full px-4 py-2">
                        <i class="fas fa-clock mr-2"></i>
                        <span x-text="pendingCount"></span> Pending
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-8">
        
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-400 text-sm">Total Applications</p>
                        <p class="text-3xl font-bold text-slate-800" x-text="applications.length"></p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-file-alt text-indigo-600 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-400 text-sm">Pending Review</p>
                        <p class="text-3xl font-bold text-amber-600" x-text="pendingCount"></p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-hourglass-half text-amber-600 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-400 text-sm">Approved</p>
                        <p class="text-3xl font-bold text-green-600" x-text="approvedCount"></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-400 text-sm">Rejected</p>
                        <p class="text-3xl font-bold text-red-600" x-text="rejectedCount"></p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-times-circle text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" x-model="searchQuery" placeholder="Search by name, email, or submission ID..."
                        class="w-full pl-11 pr-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                </div>
                <select x-model="statusFilter" class="px-4 py-3 border border-slate-200 rounded-xl focus:border-indigo-400">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>

        <!-- Applications Grid -->
        <div class="space-y-4">
            <template x-for="app in filteredApplications" :key="app.id">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-all">
                    <!-- Application Header -->
                    <div class="p-6 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <i class="fas fa-user-graduate text-indigo-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800" x-text="app.full_name"></h3>
                                    <p class="text-sm text-slate-500" x-text="app.email"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                    :class="{
                                        'bg-amber-100 text-amber-700': app.status === 'pending',
                                        'bg-green-100 text-green-700': app.status === 'approved',
                                        'bg-red-100 text-red-700': app.status === 'rejected'
                                    }">
                                    <i class="fas" :class="{
                                        'fa-clock': app.status === 'pending',
                                        'fa-check-circle': app.status === 'approved',
                                        'fa-times-circle': app.status === 'rejected'
                                    }"></i>
                                    <span x-text="app.status.toUpperCase()"></span>
                                </span>
                                <span class="text-xs text-slate-400">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    <span x-text="formatDate(app.submission_date)"></span>
                                </span>
                                <button @click="toggleDetails(app.id)" class="text-indigo-600 hover:text-indigo-700">
                                    <i class="fas" :class="expandedId === app.id ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Info -->
                    <div class="px-6 py-4 bg-slate-50/50 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-slate-400">Submission ID</p>
                            <p class="font-mono text-xs font-semibold text-slate-600" x-text="app.submission_id"></p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Experience</p>
                            <p class="font-semibold text-slate-700" x-text="`${app.years_experience} years`"></p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Current Role</p>
                            <p class="font-semibold text-slate-700" x-text="app.current_occupation"></p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Institution</p>
                            <p class="font-semibold text-slate-700 truncate" x-text="app.institution"></p>
                        </div>
                    </div>

                    <!-- Expanded Details -->
                    <div x-show="expandedId === app.id" x-collapse class="border-t border-slate-100">
                        <div class="p-6 space-y-6">
                            <!-- Personal Information -->
                            <div>
                                <h4 class="font-bold text-slate-800 mb-3 flex items-center gap-2">
                                    <i class="fas fa-user text-indigo-500 text-sm"></i> Personal Information
                                </h4>
                                <div class="grid md:grid-cols-2 gap-4 text-sm bg-slate-50 rounded-xl p-4">
                                    <div><span class="text-slate-500">Date of Birth:</span> <span x-text="app.date_of_birth"></span></div>
                                    <div><span class="text-slate-500">Nationality:</span> <span x-text="app.nationality"></span></div>
                                    <div><span class="text-slate-500">Contact:</span> <span x-text="app.contact_number"></span></div>
                                    <div class="md:col-span-2"><span class="text-slate-500">Address:</span> <span x-text="app.residential_address"></span></div>
                                </div>
                            </div>

                            <!-- Professional Background -->
                            <div>
                                <h4 class="font-bold text-slate-800 mb-3 flex items-center gap-2">
                                    <i class="fas fa-briefcase text-indigo-500 text-sm"></i> Professional Background
                                </h4>
                                <div class="bg-slate-50 rounded-xl p-4 space-y-2 text-sm">
                                    <div><span class="text-slate-500">Areas of Expertise:</span> <span x-text="app.areas_expertise"></span></div>
                                    <div><span class="text-slate-500">Highest Qualification:</span> <span x-text="app.highest_qualification"></span></div>
                                    <div><span class="text-slate-500">Professional Certifications:</span> <span x-text="app.professional_certifications || 'None provided'"></span></div>
                                </div>
                            </div>

                            <!-- Documents -->
                            <div>
                                <h4 class="font-bold text-slate-800 mb-3 flex items-center gap-2">
                                    <i class="fas fa-file-pdf text-indigo-500 text-sm"></i> Uploaded Documents
                                </h4>
                                <div class="flex flex-wrap gap-3">
                                    <a :href="app.cv_path" target="_blank" x-show="app.cv_path" class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-lg text-sm hover:bg-indigo-100 transition-all">
                                        <i class="fas fa-download mr-2"></i> Download CV
                                    </a>
                                    <a :href="app.certificates_path" target="_blank" x-show="app.certificates_path" class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-lg text-sm hover:bg-indigo-100 transition-all">
                                        <i class="fas fa-download mr-2"></i> Download Certificates
                                    </a>
                                    <a :href="app.insurance_documents_path" target="_blank" x-show="app.insurance_documents_path" class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-lg text-sm hover:bg-indigo-100 transition-all">
                                        <i class="fas fa-download mr-2"></i> Insurance Documents
                                    </a>
                                </div>
                            </div>

                            <!-- Admin Actions -->
                            <div class="border-t border-slate-200 pt-6">
                                <h4 class="font-bold text-slate-800 mb-3">Review Application</h4>
                                <div class="flex flex-wrap gap-3">
                                    <button @click="updateStatus(app.id, 'approved')" class="px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-semibold transition-all">
                                        <i class="fas fa-check mr-2"></i> Approve
                                    </button>
                                    <button @click="updateStatus(app.id, 'rejected')" class="px-6 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition-all">
                                        <i class="fas fa-times mr-2"></i> Reject
                                    </button>
                                    <button @click="markAsPending(app.id)" class="px-6 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-semibold transition-all">
                                        <i class="fas fa-clock mr-2"></i> Mark Pending
                                    </button>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Admin Notes</label>
                                    <textarea x-model="adminNotes[app.id]" rows="2" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                                        placeholder="Add notes about this application..."></textarea>
                                    <button @click="saveNotes(app.id)" class="mt-2 text-sm text-indigo-600 hover:text-indigo-700">Save Notes</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <div x-show="filteredApplications.length === 0" class="bg-white rounded-2xl p-16 text-center border border-slate-200">
                <i class="fas fa-inbox text-5xl text-slate-300 mb-4"></i>
                <p class="text-slate-400 text-lg">No applications found</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function applicationsApp() {
    return {
        applications: <?= json_encode($applications) ?>,
        searchQuery: '',
        statusFilter: 'all',
        expandedId: null,
        adminNotes: {},
        
        get pendingCount() {
            return this.applications.filter(a => a.status === 'pending').length;
        },
        get approvedCount() {
            return this.applications.filter(a => a.status === 'approved').length;
        },
        get rejectedCount() {
            return this.applications.filter(a => a.status === 'rejected').length;
        },
        get filteredApplications() {
            let result = [...this.applications];
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                result = result.filter(a => 
                    a.full_name.toLowerCase().includes(q) ||
                    a.email.toLowerCase().includes(q) ||
                    a.submission_id.toLowerCase().includes(q)
                );
            }
            if (this.statusFilter !== 'all') {
                result = result.filter(a => a.status === this.statusFilter);
            }
            return result;
        },
        
        formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        },
        
        toggleDetails(id) {
            this.expandedId = this.expandedId === id ? null : id;
        },
        
        async updateStatus(id, status) {
            if (!confirm(`Are you sure you want to mark this application as ${status.toUpperCase()}?`)) return;
            
            try {
                const response = await fetch('actions/update-application-status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, status })
                });
                const result = await response.json();
                if (result.success) {
                    const app = this.applications.find(a => a.id == id);
                    if (app) app.status = status;
                    alert(`Application ${status.toUpperCase()} successfully!`);
                } else {
                    alert('Failed to update status');
                }
            } catch (e) {
                alert('Network error');
            }
        },
        
        async saveNotes(id) {
            const notes = this.adminNotes[id] || '';
            try {
                const response = await fetch('actions/save-application-notes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, notes })
                });
                const result = await response.json();
                if (result.success) {
                    alert('Notes saved successfully!');
                }
            } catch (e) {
                alert('Failed to save notes');
            }
        }
    }
}
</script>

</body>
</html>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>