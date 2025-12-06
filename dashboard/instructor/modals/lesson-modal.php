<div class="modal fade" id="lessonModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
        <form id="lessonForm" method="POST">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header">
          <h5 class="modal-title">Lesson Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="lesson_id" id="lesson_id">
          <input type="hidden" name="section_id" id="lesson_section_id">
          <input type="hidden" name="action" value="save_lesson">
          <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

          <div class="mb-3">
            <label class="form-label">Lesson Title</label>
            <input type="text" name="title" id="lesson_title" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" id="lesson_type" class="form-select" onchange="toggleLessonFields()">
              <option value="video">Video</option>
              <option value="reading">Reading / Text</option>
              <option value="quiz">Quiz</option>
            </select>
          </div>

          <div id="video_fields">
            <div class="mb-3">
              <label class="form-label">YouTube / Vimeo URL (or direct MP4 later)</label>
              <input type="url" name="video_url" id="lesson_video_url" class="form-control" placeholder="https://youtu.be/...">
            </div>
          </div>

          <div id="content_fields" style="display:none;">
            <label class="form-label">Content</label>
            <div id="lesson-content" style="height:300px;"></div>
                        <textarea name="content" id="lesson_content_hidden" style="display:none;"></textarea>
          </div>

          <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" name="is_preview" id="is_preview">
            <label class="form-check-label">Free Preview (visible without enrollment)</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Lesson</button>
        </div>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
// Toggle visibility of video/text fields based on lesson type
function toggleLessonFields() {
    const type = document.getElementById('lesson_type').value;
    document.getElementById('video_fields').style.display   = (type === 'video') ? 'block' : 'none';
    document.getElementById('content_fields').style.display = (type !== 'video') ? 'block' : 'none';
}
toggleLessonFields();

// FIX: Add AJAX Submission for Lesson Form
document.getElementById('lessonForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // 1. Populate hidden content field from Quill Editor
    document.getElementById('lesson_content_hidden').value = quill.root.innerHTML;
    
    const formData = new FormData(this);
    const params = new URLSearchParams(formData);

    fetch('curriculum-builder.php?course_id=<?= $course_id ?>', {
        method: 'POST',
        body: params,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            new bootstrap.Modal(document.getElementById('lessonModal')).hide();
            window.location.reload(); 
        } else {
            alert('Error saving lesson.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('AJAX request failed.');
    });
});
</script>