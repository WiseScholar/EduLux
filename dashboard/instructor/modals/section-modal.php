<div class="modal fade" id="sectionModal" tabindex="-1">
  <div class="modal-dialog">
        <form id="sectionForm" method="POST">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header">
          <h5 class="modal-title">Section Title</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="section_id" id="section_id" value="0">
          <input type="text" name="title" id="section_title" class="form-control form-control-lg" required placeholder="e.g. Week 1 – Foundations">
          <input type="hidden" name="action" value="save_section">
          <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Section</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
// FIX: Add AJAX Submission for Section Form
document.getElementById('sectionForm').addEventListener('submit', function(e) {
    e.preventDefault();
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
            // Success, close modal and refresh the list
            new bootstrap.Modal(document.getElementById('sectionModal')).hide();
            window.location.reload(); 
        } else {
            alert('Error saving section.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('AJAX request failed.');
    });
});
</script>