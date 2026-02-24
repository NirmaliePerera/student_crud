<?php
include 'config.php';

$createdBy = resolveOwner();

if (!empty($createdBy)) {
    $stmt = $conn->prepare("
        SELECT * FROM student
        ORDER BY CASE WHEN createdBy = ? THEN 0 ELSE 1 END,
                 studentID DESC
    ");
    $stmt->bind_param("s", $createdBy);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM student ORDER BY studentID DESC");
}

$status = $_GET['status'] ?? '';
$statusMessages = [
    'added' => 'Student added successfully.',
    'updated' => 'Student updated successfully.',
    'deleted' => 'Student deleted successfully.'
];
$flashMessage = $statusMessages[$status] ?? '';
$addUrl = 'add.php';
if (!empty($createdBy)) {
    $addUrl .= '?createdBy=' . urlencode($createdBy);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student List</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

<h2>Student List</h2>

<?php if (!empty($flashMessage)): ?>
<div class="flash-message"><?php echo htmlspecialchars($flashMessage); ?></div>
<?php endif; ?>

<a href="<?php echo htmlspecialchars($addUrl); ?>">Add New Student</a>

<?php if ($result && $result->num_rows > 0): ?>
<table>
<tr>
    <th>ID</th>
    <th>First Name</th>
    <th>Last Name</th>
    <th>Birth Date</th>
    <th>City</th>
    <th>Course Name</th>
    <th>Enrolled Year</th>
    <th>Email</th>
    <th>Created By</th>
    <th>Actions</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)) { ?>
<?php $rowOwner = $row['createdBy'] ?? ''; $canManage = !empty($createdBy) && strcasecmp($rowOwner, $createdBy) === 0; ?>
<tr>
    <td><?php echo htmlspecialchars($row['studentID']); ?></td>
    <td><?php echo htmlspecialchars($row['firstName']); ?></td>
    <td><?php echo htmlspecialchars($row['lastName']); ?></td>
    <td><?php echo htmlspecialchars($row['birthDate']); ?></td>
    <td><?php echo htmlspecialchars($row['city']); ?></td>
    <td><?php echo htmlspecialchars($row['courseName']); ?></td>
    <td><?php echo htmlspecialchars($row['enrolledYear']); ?></td>
    <td><?php echo htmlspecialchars($row['email']); ?></td>
    <td><?php echo htmlspecialchars($rowOwner ?: '—'); ?></td>
    <td>
    <?php if ($canManage): ?>
        <a href="edit.php?id=<?php echo urlencode($row['studentID']); ?>&createdBy=<?php echo urlencode($createdBy); ?>">Edit</a>
        &nbsp;&nbsp;
        <a href="#" class="delete-link" data-delete-url="delete.php?id=<?php echo urlencode($row['studentID']); ?>&createdBy=<?php echo urlencode($createdBy); ?>">Delete</a>
    <?php else: ?>
        <span class="muted-text">Permission Required</span>
    <?php endif; ?>
</td>
</tr>
<?php } ?>

</table>
<?php else: ?>
<p class="empty-state">No students found for this account. Use "Add New Student" to create one.</p>
<?php endif; ?>

<div id="deleteModal" class="modal-overlay hidden">
    <div class="modal">
        <p>Are you sure you want to delete the student?</p>
        <div class="modal-actions">
            <button type="button" id="confirmDelete">Yes, Delete</button>
            <button type="button" class="button-secondary" id="cancelDelete">Cancel</button>
        </div>
    </div>
</div>

</div>
<?php if (!empty($flashMessage)): ?>
<script>
(function() {
    var url = new URL(window.location.href);
    url.searchParams.delete('status');
    window.history.replaceState({}, document.title, url.toString());
})();
</script>
<?php endif; ?>
<script>
(function() {
    var modal = document.getElementById('deleteModal');
    var confirmBtn = document.getElementById('confirmDelete');
    var cancelBtn = document.getElementById('cancelDelete');
    var pendingUrl = '';

    function closeModal() {
        modal.classList.add('hidden');
        pendingUrl = '';
    }

    document.querySelectorAll('.delete-link').forEach(function(link) {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            pendingUrl = link.getAttribute('data-delete-url');
            modal.classList.remove('hidden');
        });
    });

    confirmBtn.addEventListener('click', function() {
        if (pendingUrl) {
            window.location.href = pendingUrl;
        }
    });

    cancelBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });
})();
</script>
</body>
</html>