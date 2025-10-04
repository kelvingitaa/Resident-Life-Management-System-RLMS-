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

// Fetch apartments
$apartments = $pdo->query("SELECT * FROM apartments")->fetchAll();
?>
<h2>Manage Apartments</h2>
<table border="1" cellpadding="5">
  <tr>
    <th>ID</th>
    <th>Name</th>
    <th>Location</th>
    <th>Price</th>
    <th>Status</th>
  </tr>
  <?php foreach($apartments as $apt): ?>
    <tr>
      <td><?= $apt['id']; ?></td>
      <td><?= htmlspecialchars($apt['name']); ?></td>
      <td><?= htmlspecialchars($apt['location']); ?></td>
      <td>$<?= number_format($apt['price'], 2); ?></td>
      <td><?= htmlspecialchars($apt['status']); ?></td>
    </tr>
  <?php endforeach; ?>
</table>

<h3>Add New Apartment</h3>
<form method="POST" action="">
  <input type="text" name="name" placeholder="Name" required><br>
  <input type="text" name="location" placeholder="Location" required><br>
  <input type="number" name="price" placeholder="Price" required><br>
  <select name="status">
    <option value="available">Available</option>
    <option value="occupied">Occupied</option>
  </select><br>
  <button type="submit" name="add">Add Apartment</button>
</form>

<?php
if (isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO apartments (name, location, price, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['location'], $_POST['price'], $_POST['status']]);
    echo "<p>New apartment added!</p>";
}
?>
<?php include '../includes/footer.php'; ?>
