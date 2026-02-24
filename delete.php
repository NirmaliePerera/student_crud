<?php
include 'config.php';

$createdBy = resolveOwner();
if (empty($createdBy) && !empty($_GET['createdBy'])) {
	$createdBy = $_GET['createdBy'];
	$_SESSION['createdBy'] = $createdBy;
}
$id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (!empty($createdBy) && $id !== '') {
	$stmt = $conn->prepare("DELETE FROM student WHERE studentID = ? AND createdBy = ?");
	$stmt->bind_param("ss", $id, $createdBy);
	$stmt->execute();
}

header("Location: index.php?createdBy=" . urlencode($createdBy) . "&status=deleted");
exit();
?>
