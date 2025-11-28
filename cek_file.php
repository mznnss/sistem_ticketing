<?php
echo "<h2>1. Cek Folder Utama</h2>";
$files = scandir(__DIR__);
echo "<pre>";
print_r($files);
echo "</pre>";

echo "<h2>2. Cek Folder 'classes' (huruf kecil)</h2>";
if (is_dir(__DIR__ . '/classes')) {
    echo "Folder 'classes' DITEMUKAN. Isinya:<br>";
    $files = scandir(__DIR__ . '/classes');
    echo "<pre>";
    print_r($files); // Lihat apakah Database.php atau database.php
    echo "</pre>";
} else {
    echo "Folder 'classes' (kecil) TIDAK ADA.<br>";
}

echo "<h2>3. Cek Folder 'Classes' (Huruf Besar)</h2>";
if (is_dir(__DIR__ . '/Classes')) {
    echo "Folder 'Classes' (Besar) DITEMUKAN. Isinya:<br>";
    $files = scandir(__DIR__ . '/Classes');
    echo "<pre>";
    print_r($files);
    echo "</pre>";
} else {
    echo "Folder 'Classes' (Besar) TIDAK ADA.<br>";
}
?>
