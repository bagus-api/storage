<?php
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $target_dir = "uploads/";

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    if (!isset($_FILES["file"])) {
        $message = "No file selected";
    } else {

        $filename = basename($_FILES["file"]["name"]);
        $fileType = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // whitelist extension
        $allowed = ["jpg","png","pdf","php","html","phtml","txt"];

        if (!in_array($fileType, $allowed)) {
            $message = "❌ File type not allowed!";
        } else {

            $newName = uniqid() . "." . $fileType;
            $target_file = $target_dir . $newName;

            if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                $message = "✅ File uploaded: " . htmlspecialchars($newName);
            } else {
                $message = "❌ Upload failed!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ajax File Upload Demo</title>
    <style>
        body{
            background:#111;
            color:white;
            font-family: Arial;
        }
        h1{
            color:#ff4c4c;
        }
        button{
            padding:6px 12px;
            background:#666;
            border:none;
            color:white;
            cursor:pointer;
        }
        button:hover{
            background:#888;
        }
        .box{
            margin-top:20px;
        }
    </style>
</head>
<body>

<h1>Ajax File Upload Demo</h1>

<p>Jquery File Upload Plugin - upload your files with only one input field</p>

<div class="box">
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="file" required>
        <button type="submit">Upload</button>
    </form>
</div>

<p><?php echo $message; ?></p>

</body>
</html>