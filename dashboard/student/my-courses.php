<?php
require_once __DIR__ . '/../../includes/config.php';
require_once ROOT_PATH . 'includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// Refined Query to match your DB structure (Modules/Lessons)
$courses_stmt = $pdo->prepare("
  SELECT 
    c.id, c.title, c.short_description, c.thumbnail, 
    u.first_name, u.last_name, u.avatar as instructor_avatar,
    e.enrolled_at,
    COALESCE(
      ROUND(
        (SELECT COUNT(p.id) 
         FROM course_progress p 
         JOIN course_lessons l ON p.lesson_id = l.id 
         JOIN course_sections s ON l.section_id = s.id 
         WHERE s.course_id = c.id AND p.user_id = e.user_id
        ) * 100 / 
        NULLIF((SELECT COUNT(l.id) 
                FROM course_lessons l 
                JOIN course_sections s ON l.section_id = s.id 
                WHERE s.course_id = c.id), 0)
      ), 0
    ) AS progress_percentage
  FROM enrollments e
  JOIN courses c ON e.course_id = c.id
  JOIN users u ON c.instructor_id = u.id
  WHERE e.user_id = ? AND c.status = 'published'
  ORDER BY e.enrolled_at DESC
");
$courses_stmt->execute([$student_id]);
$enrolled_courses = $courses_stmt->fetchAll();
$total_enrolled = count($enrolled_courses);

require_once ROOT_PATH . 'includes/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { 
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    brand: { 900: '#002d72', 500: '#eab308' }
                }
            }
        }
    }
</script>

<style>
    /* Global Premium Scrollbar */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.2); border-radius: 10px; }
    
    @media (min-width: 1024px) {
        .main-content-wrapper { margin-left: 18rem; }
    }

    @media (max-width: 1024px) {
        main { 
            padding-bottom: calc(120px + env(safe-area-inset-bottom)) !important; 
        }
    }
    
    .course-card-float {
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .course-card-float:hover {
        transform: translateY(-10px);
        box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.15);
    }
    
    .progress-glow {
        box-shadow: 0 0 15px rgba(99, 102, 241, 0.4);
    }

    /* Animation for empty state */
    @keyframes vault-pulse {
        0% { transform: scale(1); opacity: 0.2; }
        50% { transform: scale(1.1); opacity: 0.4; }
        100% { transform: scale(1); opacity: 0.2; }
    }
    .vault-pulse { animation: vault-pulse 3s infinite ease-in-out; }
</style>

<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-500 flex">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 main-content-wrapper">
        
        <main class="p-6 lg:p-12 max-w-7xl mx-auto w-full">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-16">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.4em] text-indigo-600 dark:text-brand-500 mb-2 block">Personal Registry</span>
                    <h1 class="text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tighter uppercase italic leading-none">
                        Course <span class="text-indigo-600 dark:text-indigo-400">Library</span>
                    </h1>
                    <div class="flex items-center gap-3 mt-4">
                        <span class="h-1 w-12 bg-indigo-600 dark:bg-brand-500 rounded-full"></span>
                        <p class="text-slate-500 dark:text-slate-400 font-medium tracking-wide">
                            You have <span class="text-slate-900 dark:text-slate-200 font-bold"><?= $total_enrolled ?></span> active enrollments
                        </p>
                    </div>
                </div>
                
                <a href="<?= BASE_URL ?>pages/courses" class="group flex items-center gap-3 bg-white dark:bg-slate-800 px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] text-slate-900 dark:text-white border border-slate-200 dark:border-slate-700 hover:border-indigo-600 dark:hover:border-brand-500 transition-all shadow-sm">
                    <i class="fas fa-search text-indigo-600 dark:text-brand-500 group-hover:rotate-12 transition-transform"></i> Browse Catalog
                </a>
            </div>

            <?php if ($total_enrolled > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-10">
                    <?php foreach ($enrolled_courses as $course): 
                        $progress = (int)$course['progress_percentage'];
                        $is_completed = $progress >= 100;
                    ?>
                        <div class="course-card-float group bg-white dark:bg-slate-800 rounded-[3rem] p-5 border border-slate-100 dark:border-slate-700/50 flex flex-col">
                            
                            <div class="relative h-56 mb-6 overflow-hidden rounded-[2.5rem] shadow-inner">
                                <img src="<?= BASE_URL ?>assets/uploads/courses/thumbnails/<?= $course['thumbnail'] ?>" 
                                     class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110">
                                
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/20 to-transparent"></div>
                                
                                <div class="absolute top-4 right-4 bg-white/10 backdrop-blur-md border border-white/20 text-white text-[9px] font-black px-4 py-2 rounded-full uppercase tracking-widest">
                                    <?= $is_completed ? 'Finished' : $progress . '% Complete' ?>
                                </div>

                                <div class="absolute bottom-6 left-6 right-6">
                                    <div class="flex justify-between items-center text-white mb-2">
                                        <span class="text-[8px] font-black uppercase tracking-[0.2em] opacity-80">Track Progression</span>
                                        <span class="text-[10px] font-bold"><?= $progress ?>%</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-white/20 backdrop-blur-md rounded-full overflow-hidden">
                                        <div class="bg-white h-full rounded-full transition-all duration-1000 progress-glow" 
                                             style="width: <?= $progress ?>%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="px-3 flex-grow flex flex-col">
                                <h3 class="text-xl font-black text-slate-900 dark:text-white leading-tight mb-4 line-clamp-2 min-h-[3.5rem] group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                    <?= htmlspecialchars($course['title']) ?>
                                </h3>
                                
                                <div class="flex items-center gap-3 mb-10">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($course['first_name']) ?>&background=random" 
                                         class="w-7 h-7 rounded-full border-2 border-white dark:border-slate-700 shadow-sm">
                                    <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                                        Instructor: Prof. <?= htmlspecialchars($course['first_name']) ?>
                                    </p>
                                </div>

                                <div class="mt-auto space-y-3">
                                    <a href="course-player.php?course_id=<?= $course['id'] ?>" 
                                       class="flex items-center justify-center gap-3 w-full py-5 bg-slate-900 dark:bg-indigo-600 text-white rounded-[1.5rem] font-black text-xs uppercase tracking-[0.2em] hover:opacity-90 transition-all shadow-xl shadow-slate-200 dark:shadow-none">
                                        <?php if ($is_completed): ?>
                                            REPLAY MODULE <i class="fas fa-redo-alt text-[8px]"></i>
                                        <?php else: ?>
                                            RESUME LEARNING <i class="fas fa-play text-[8px]"></i>
                                        <?php endif; ?>
                                    </a>
                                    
                                    <div class="grid grid-cols-2 gap-3 pt-2">
                                        <a href="<?= BASE_URL ?>pages/courses/detail.php?id=<?= $course['id'] ?>" 
                                           class="py-3 px-4 bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-400 rounded-xl text-[9px] font-black uppercase text-center border border-slate-100 dark:border-slate-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:text-indigo-600 transition-all">
                                            SYLLABUS
                                        </a>
                                        <button class="py-3 px-4 bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-400 rounded-xl text-[9px] font-black uppercase text-center border border-slate-100 dark:border-slate-700 hover:text-red-500 transition-all">
                                            ARCHIVE
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white dark:bg-slate-800 rounded-[4rem] border-2 border-dashed border-slate-200 dark:border-slate-700 p-20 text-center relative overflow-hidden">
                    <div class="relative z-10">
                        <div class="relative w-32 h-32 mx-auto mb-10">
                            <div class="absolute inset-0 bg-indigo-500/20 rounded-full vault-pulse"></div>
                            <div class="relative w-full h-full bg-white dark:bg-slate-900 rounded-full flex items-center justify-center shadow-2xl">
                                <i class="fas fa-university text-4xl text-indigo-600"></i>
                            </div>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tighter italic mb-4">Your Knowledge Vault is Empty</h3>
                        <p class="text-slate-500 dark:text-slate-400 max-w-md mx-auto mb-10 font-medium text-lg leading-relaxed">
                            No active programs found. Expand your horizons by exploring our world-class curriculum.
                        </p>
                        <a href="<?= BASE_URL ?>pages/courses" class="inline-flex items-center gap-4 bg-slate-900 dark:bg-brand-500 text-white dark:text-slate-900 px-12 py-6 rounded-3xl font-black text-sm uppercase tracking-[0.3em] shadow-2xl hover:scale-105 transition-all">
                            BEGIN DISCOVERY <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'bottom-nav.php'; ?>

<script>
    // Inherit the same Dark Mode logic from index.php
    const html = document.documentElement;
    if (localStorage.getItem('theme') === 'dark') {
        html.classList.add('dark');
    }
</script>
</body>
</html>