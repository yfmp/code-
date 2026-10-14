<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$userStmt = $conn->prepare("SELECT name, email, role, created_at FROM users WHERE email = ? LIMIT 1");
$userStmt->bind_param("s", $_SESSION['email']);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

$profileName = $user['name'] ?? ($_SESSION['name'] ?? 'User');
$profileEmail = $user['email'] ?? ($_SESSION['email'] ?? '');
$profileRole = strtoupper($user['role'] ?? ($_SESSION['role'] ?? 'USER'));
$createdAt = $user['created_at'] ?? 'Not available';

$appStmt = $conn->prepare("
    SELECT
        aa.*,
        p.name AS pet_name,
        p.type,
        p.breed,
        p.gender,
        p.age,
        p.size,
        p.image_url,
        p.status AS pet_status,
        p.health,
        p.backstory,
        p.pet_condition,
        p.vaccinated
    FROM adoption_applications aa
    JOIN pets p ON p.id = aa.pet_id
    WHERE aa.user_email = ?
    ORDER BY aa.created_at DESC
");
$appStmt->bind_param("s", $profileEmail);
$appStmt->execute();
$applications = $appStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetPoint | My Profile</title>
    <link rel="stylesheet" href="adopt.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.2.0/css/line.css">
    <script src="adopt.js" defer></script>
</head>
<body>
    <header>
        <nav class="navbar section-content">
            <a href="index.php" class="nav-logo">
                <h2 class="logo-text">PetPoint</h2>
            </a>
            <ul class="nav-menu">
                <button id="menu-close-button" class="fas fa-times"></button>
                <li class="nav-item"><a href="index.php#home" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="adopt.php#pets" class="nav-link">Pets</a></li>
                <li class="nav-item"><a href="adopt.php#contact" class="nav-link">Contact Us</a></li>
                <?php if ($profileRole === 'ADMIN'): ?>
                    <li class="nav-item"><a href="admin.php" class="nav-link">Admin</a></li>
                <?php endif; ?>
            </ul>
            <div class="nav-auth">
                <span class="user-name"><?= e($profileName) ?></span>
                <a href="profile.php" class="profile-shortcut" title="My Profile"><i class="uil uil-user-circle"></i></a>
                <a href="logout.php" class="login-btn">Logout</a>
            </div>
            <button id="menu-open-button" class="fas fa-bars"></button>
        </nav>
    </header>

    <main class="profile-page">
        <section class="profile-hero">
            <div class="profile-avatar"><?= e(strtoupper(substr($profileName, 0, 1))) ?></div>
            <div>
                <h1><?= e($profileName) ?></h1>
                <p><?= e($profileEmail) ?></p>
                <span class="status-tag approved"><?= e($profileRole) ?></span>
            </div>
        </section>

        <section class="profile-grid">
            <article class="profile-panel">
                <h2>Account Details</h2>
                <dl class="profile-details-list">
                    <div><dt>Name</dt><dd><?= e($profileName) ?></dd></div>
                    <div><dt>Email</dt><dd><?= e($profileEmail) ?></dd></div>
                    <div><dt>Role</dt><dd><?= e($profileRole) ?></dd></div>
                    <div><dt>Registered</dt><dd><?= e($createdAt) ?></dd></div>
                </dl>
                <div class="profile-actions">
                    <a href="adopt.php#pets">Browse Pets</a>
                    <?php if ($profileRole === 'ADMIN'): ?>
                        <a href="admin.php">Admin Dashboard</a>
                    <?php endif; ?>
                </div>
            </article>

            <article class="profile-panel profile-applications">
                <h2>Adoption Applications</h2>
                <?php if ($applications->num_rows === 0): ?>
                    <p class="profile-empty">No adoption applications yet.</p>
                <?php endif; ?>

                <?php while ($application = $applications->fetch_assoc()): ?>
                    <div class="profile-application-card">
                        <img src="<?= e($application['image_url']) ?>" alt="<?= e($application['pet_name']) ?>">
                        <div class="application-content">
                            <div class="application-heading">
                                <div>
                                    <h3><?= e($application['pet_name']) ?></h3>
                                    <p><?= e($application['type']) ?> &bull; <?= e($application['breed']) ?> &bull; <?= e($application['gender']) ?></p>
                                </div>
                                <span class="status-tag <?= strtolower(e($application['status'])) ?>"><?= e($application['status']) ?></span>
                            </div>
                            <div class="application-details-grid">
                                <p><strong>Age:</strong> <?= e($application['age']) ?></p>
                                <p><strong>Size:</strong> <?= e($application['size']) ?></p>
                                <p><strong>Vaccinated:</strong> <?= $application['vaccinated'] ? 'Yes' : 'No' ?></p>
                                <p><strong>Pet Status:</strong> <?= e($application['pet_status']) ?></p>
                                <p><strong>Phone:</strong> <?= e($application['phone']) ?></p>
                                <p><strong>Home Type:</strong> <?= e($application['home_type']) ?></p>
                                <p><strong>Has Pets:</strong> <?= e($application['has_pets']) ?></p>
                                <p><strong>Applied:</strong> <?= e($application['created_at']) ?></p>
                            </div>
                            <p><strong>Address:</strong> <?= e($application['address']) ?></p>
                            <p><strong>Reason:</strong> <?= e($application['reason']) ?></p>
                            <p><strong>Health:</strong> <?= e($application['health']) ?></p>
                            <p><strong>Condition:</strong> <?= e($application['pet_condition']) ?></p>
                            <p><strong>Back Story:</strong> <?= e($application['backstory']) ?></p>
                            <?php if (!empty($application['admin_notes'])): ?>
                                <p class="admin-notes"><strong>Admin Note:</strong> <?= e($application['admin_notes']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </article>
        </section>
    </main>
</body>
</html>