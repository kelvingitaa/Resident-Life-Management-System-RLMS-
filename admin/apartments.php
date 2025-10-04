<?php
// admin/apartments.php
include '../includes/header.php';
include '../includes/navbar.php';
include '../config/database.php';
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$apartments = $pdo->query("SELECT * FROM apartments")->fetchAll();
?>

<div class="container mt-5">
    <div class="dashboard-header">
        <h2>🏢 Manage Apartments</h2>
    </div>

    <table class="table table-striped mt-3">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Location</th>
                <th>Price</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($apartments as $apt): ?>
                <tr>
                    <td><?= $apt['id']; ?></td>
                    <td><?= htmlspecialchars($apt['name']); ?></td>
                    <td><?= htmlspecialchars($apt['location']); ?></td>
                    <td>$<?= number_format($apt['price'], 2); ?></td>
                    <td><?= htmlspecialchars($apt['status']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="dashboard-card mt-4">
        <h3>Add New Apartment</h3>
        <form method="POST" action="" id="apartmentForm">
            <input type="text" name="name" placeholder="Name" class="form-control mb-2" required>
            <input type="text" name="location" placeholder="Location" class="form-control mb-2" required>
            <input type="number" name="price" placeholder="Price" class="form-control mb-2" required>
            <select name="status" class="form-control mb-2" required>
                <option value="available">Available</option>
                <option value="occupied">Occupied</option>
            </select>
            <button type="submit" name="add" class="btn btn-primary">Add Apartment</button>
        </form>
    </div>
</div>

<?php
if (isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO apartments (name, location, price, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['location'], $_POST['price'], $_POST['status']]);
    echo "<div class='alert alert-success mt-3'>New apartment added!</div>";
}
include '../includes/footer.php';
?>
