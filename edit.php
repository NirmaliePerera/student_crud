<?php
include 'config.php';

$createdBy = resolveOwner();
if (empty($createdBy) && !empty($_POST['createdBy'])) {
    $createdBy = $_POST['createdBy'];
    $_SESSION['createdBy'] = $createdBy;
}
$studentId = isset($_GET['id']) ? trim($_GET['id']) : trim($_POST['studentID'] ?? '');
$ownerMissing = empty($createdBy);

$errors = [
    'general' => '',
    'firstName' => '',
    'lastName' => '',
    'birthDate' => '',
    'email' => '',
    'city' => '',
    'courseName' => '',
    'enrolledYear' => ''
];

$formData = [
    'firstName' => '',
    'lastName' => '',
    'birthDate' => '',
    'email' => '',
    'city' => '',
    'courseName' => '',
    'enrolledYear' => ''
];

$existingStudent = null;

if ($ownerMissing || $studentId === '') {
    $errors['general'] = 'Invalid access.';
} else {
    $stmt = $conn->prepare("SELECT * FROM student WHERE studentID = ? AND createdBy = ?");
    $stmt->bind_param("ss", $studentId, $createdBy);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingStudent = $result->fetch_assoc();

    if (!$existingStudent) {
        $errors['general'] = 'Student not found or unauthorized.';
    } elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        foreach ($formData as $field => $value) {
            $formData[$field] = $existingStudent[$field] ?? '';
        }
    }
}

if (!$ownerMissing && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($formData as $field => $value) {
        $formData[$field] = trim($_POST[$field] ?? '');
    }

    if (empty($errors['general'])) {
        if (empty($formData['firstName'])) {
            $errors['firstName'] = 'First name is required.';
        }

        if (empty($formData['lastName'])) {
            $errors['lastName'] = 'Last name is required.';
        }

        $birthDateInput = $formData['birthDate'];
        $normalizedBirthDate = $birthDateInput;

        if (empty($birthDateInput)) {
            $errors['birthDate'] = 'Birth date is required.';
        } else {
            $birthDateObj = DateTime::createFromFormat('Y-m-d', $birthDateInput);
            if (!$birthDateObj || $birthDateObj->format('Y-m-d') !== $birthDateInput) {
                $errors['birthDate'] = 'Please enter a valid birth date.';
            } else {
                $age = (new DateTime())->diff($birthDateObj)->y;
                if ($age < 18) {
                    $errors['birthDate'] = 'Student must be at least 18 years old.';
                } else {
                    $normalizedBirthDate = $birthDateObj->format('Y-m-d');
                }
            }
        }

        if (empty($formData['email'])) {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if (empty($formData['city'])) {
            $errors['city'] = 'City is required.';
        }

        if (empty($formData['courseName'])) {
            $errors['courseName'] = 'Course name is required.';
        }

        $currentYear = (int) date('Y');
        $enrolledYearInput = $formData['enrolledYear'];
        if ($enrolledYearInput === '') {
            $errors['enrolledYear'] = 'Enrolled year is required.';
        } elseif (!ctype_digit($enrolledYearInput)) {
            $errors['enrolledYear'] = 'Enrolled year must be a number.';
        } else {
            $year = (int) $enrolledYearInput;
            if ($year < 1950 || $year > $currentYear + 1) {
                $errors['enrolledYear'] = 'Enrolled year must be between 1950 and ' . ($currentYear + 1) . '.';
            }
        }

        if (!array_filter($errors)) {
            $stmt = $conn->prepare("
                UPDATE student SET
                    firstName = ?,
                    lastName = ?,
                    birthDate = ?,
                    email = ?,
                    city = ?,
                    courseName = ?,
                    enrolledYear = ?
                WHERE studentID = ? AND createdBy = ?
            ");

            $stmt->bind_param(
                "ssssssiss",
                $formData['firstName'],
                $formData['lastName'],
                $normalizedBirthDate,
                $formData['email'],
                $formData['city'],
                $formData['courseName'],
                $formData['enrolledYear'],
                $studentId,
                $createdBy
            );

            $stmt->execute();

            header("Location: index.php?createdBy=" . urlencode($createdBy) . "&status=updated");
            exit();
        }
    }
} elseif ($existingStudent) {
    foreach ($formData as $field => $value) {
        $formData[$field] = $existingStudent[$field] ?? '';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Student</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

<h2>Edit Student</h2>

<?php if (!empty($errors['general'])): ?>
<div class="error-text general-error"><?php echo htmlspecialchars($errors['general']); ?></div>
<?php endif; ?>

<?php if ($ownerMissing): ?>
<div class="error-text general-error">Owner could not be determined. Please use your personalized link.</div>
<?php elseif (!$existingStudent && empty($errors['general'])): ?>
<div class="error-text general-error">Student record could not be found.</div>
<?php else: ?>

<form method="POST" class="student-form">

    <input type="hidden" name="studentID" value="<?php echo htmlspecialchars($studentId); ?>">
    <input type="hidden" name="createdBy" value="<?php echo htmlspecialchars($createdBy); ?>">

    <div class="form-field">
        <label for="studentIDDisplay">Student ID:</label>
        <input type="text" id="studentIDDisplay" value="<?php echo htmlspecialchars($studentId); ?>" disabled placeholder="e.g., 221001">
    </div>

    <div class="form-field">
        <label for="firstName">First Name:</label>
        <input type="text" name="firstName" id="firstName" placeholder="e.g., John" value="<?php echo htmlspecialchars($formData['firstName']); ?>" required>
        <p class="error-text <?php echo empty($errors['firstName']) ? 'hidden' : ''; ?>">
            <?php echo htmlspecialchars($errors['firstName']); ?>
        </p>
    </div>

    <div class="form-field">
        <label for="lastName">Last Name:</label>
        <input type="text" name="lastName" id="lastName" placeholder="e.g., Doe" value="<?php echo htmlspecialchars($formData['lastName']); ?>" required>
        <p class="error-text <?php echo empty($errors['lastName']) ? 'hidden' : ''; ?>">
            <?php echo htmlspecialchars($errors['lastName']); ?>
        </p>
    </div>

    <div class="form-row">
        <div class="form-field">
            <label for="birthDate">Birth Date:</label>
            <input type="date" name="birthDate" id="birthDate" placeholder="e.g., 2000-05-12" value="<?php echo htmlspecialchars($formData['birthDate']); ?>" required>
            <p class="error-text <?php echo empty($errors['birthDate']) ? 'hidden' : ''; ?>">
                <?php echo htmlspecialchars($errors['birthDate']); ?>
            </p>
        </div>

        <div class="form-field">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" placeholder="e.g., john@example.com" value="<?php echo htmlspecialchars($formData['email']); ?>">
            <p class="error-text <?php echo empty($errors['email']) ? 'hidden' : ''; ?>">
                <?php echo htmlspecialchars($errors['email']); ?>
            </p>
        </div>
    </div>

    <div class="form-field">
        <label for="city">City:</label>
        <input type="text" name="city" id="city" placeholder="e.g., Colombo" value="<?php echo htmlspecialchars($formData['city']); ?>">
        <p class="error-text <?php echo empty($errors['city']) ? 'hidden' : ''; ?>">
            <?php echo htmlspecialchars($errors['city']); ?>
        </p>
    </div>

    <div class="form-field">
        <label for="courseName">Course Name:</label>
        <input type="text" name="courseName" id="courseName" placeholder="e.g., Computer Science" value="<?php echo htmlspecialchars($formData['courseName']); ?>">
        <p class="error-text <?php echo empty($errors['courseName']) ? 'hidden' : ''; ?>">
            <?php echo htmlspecialchars($errors['courseName']); ?>
        </p>
    </div>

    <div class="form-field">
        <label for="enrolledYear">Enrolled Year:</label>
        <input type="text" name="enrolledYear" id="enrolledYear" placeholder="e.g., 2020" value="<?php echo htmlspecialchars($formData['enrolledYear']); ?>">
        <p class="error-text <?php echo empty($errors['enrolledYear']) ? 'hidden' : ''; ?>">
            <?php echo htmlspecialchars($errors['enrolledYear']); ?>
        </p>
    </div>

    <div class="form-field">
        <input type="submit" value="Update Student">
    </div>
</form>

<?php endif; ?>

</div>
<script>
document.querySelectorAll('.student-form input').forEach(function (input) {
    input.addEventListener('focus', function () {
        var field = input.closest('.form-field');
        var errorEl = field ? field.querySelector('.error-text') : null;
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.add('hidden');
        }
    });
});
</script>
</body>
</html>