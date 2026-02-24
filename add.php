<?php
include 'config.php';

$createdBy = resolveOwner();
$ownerMissing = empty($createdBy);

$errors = [
    'studentID' => '',
    'firstName' => '',
    'lastName' => '',
    'birthDate' => '',
    'email' => '',
    'city' => '',
    'courseName' => '',
    'enrolledYear' => ''
];

$formData = [
    'studentID' => '',
    'firstName' => '',
    'lastName' => '',
    'birthDate' => '',
    'email' => '',
    'city' => '',
    'courseName' => '',
    'enrolledYear' => ''
];

if (!$ownerMissing && $_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($formData as $field => $value) {
        $formData[$field] = trim($_POST[$field] ?? '');
    }

    if (empty($formData['studentID'])) {
        $errors['studentID'] = 'Student ID is required.';
    } elseif (!preg_match('/^[0-9]{6}$/', $formData['studentID'])) {
        $errors['studentID'] = 'Student ID must be a 6-digit number.';
    } else {
        $stmt = $conn->prepare("SELECT studentID FROM student WHERE CAST(studentID AS CHAR) = ? LIMIT 1");
        $stmt->bind_param("s", $formData['studentID']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['studentID'] = 'Student ID already exists. Please use a unique ID.';
        }
        $stmt->close();
    }

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
            // Enforce minimum age requirement (18+)
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
            INSERT INTO student 
            (studentID, firstName, lastName, birthDate, email, city, courseName, enrolledYear, createdBy)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssssssis",
            $formData['studentID'],
            $formData['firstName'],
            $formData['lastName'],
            $normalizedBirthDate,
            $formData['email'],
            $formData['city'],
            $formData['courseName'],
            $formData['enrolledYear'],
            $createdBy
        );

        try {
            $stmt->execute();
            $stmt->close();
            header("Location: index.php?createdBy=" . urlencode($createdBy) . "&status=added");
            exit();
        } catch (mysqli_sql_exception $exception) {
            $stmt->close();
            if ((int) $exception->getCode() === 1062) {
                $errors['studentID'] = 'Student ID already exists. Please use a unique ID.';
            } else {
                throw $exception;
            }
        }
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Add Student</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

<h2>Add Student</h2>

<?php if ($ownerMissing): ?>
<div class="error-text general-error">Owner could not be determined. Please access your personalized link.</div>
<?php else: ?>

<form method="POST" class="student-form">
    <div class="form-field">
        <label for="studentID">Student ID:</label>
        <input type="text" name="studentID" id="studentID" placeholder="e.g., 221001" value="<?php echo htmlspecialchars($formData['studentID']); ?>" required>
        <p class="error-text <?php echo empty($errors['studentID']) ? 'hidden' : ''; ?>">
            <?php echo htmlspecialchars($errors['studentID']); ?>
        </p>
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

    <br>

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
        <input type="submit" name="submit" value="Add Student">
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