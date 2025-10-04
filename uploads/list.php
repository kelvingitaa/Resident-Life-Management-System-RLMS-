<?php
// uploads/list.php
$dir = __DIR__ . "/files/";
$files = array_diff(scandir($dir), ['.', '..']);
?>
<h2>Uploaded Files</h2>
<ul>
  <?php foreach($files as $file): ?>
    <li><a href="files/<?= urlencode($file); ?>" target="_blank"><?= htmlspecialchars($file); ?></a></li>
  <?php endforeach; ?>
</ul>
