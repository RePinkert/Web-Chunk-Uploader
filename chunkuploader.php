<?php
// chunk_upload.php
// 
// Configure upload paths (modify according to actual usage)
$uploadDir = __DIR__ . '/uploads/';       // Final file storage directory
$tempDir = __DIR__ . '/temp/';            // Temporary chunk storage directory

// Automatically create directories
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

// Handle chunk upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle chunk upload
    if (isset($_POST['action']) && $_POST['action'] === 'chunk') {
        $uuid = $_POST['uuid'];
        $chunkIndex = (int)$_POST['chunkIndex'];
        $tempDirPath = $tempDir . $uuid;

        if (!file_exists($tempDirPath)) {
            mkdir($tempDirPath, 0777, true);
        }

        $chunkPath = $tempDirPath . '/' . $chunkIndex;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $chunkPath)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success']);
            exit;
        }
    }

    // Merge chunks
    if (isset($_POST['action']) && $_POST['action'] === 'merge') {
        $uuid = $_POST['uuid'];
        $fileName = basename($_POST['fileName']); // Safely get the filename
        $tempDirPath = $tempDir . $uuid;
        $finalPath = $uploadDir . $fileName;

        $fp = fopen($finalPath, 'wb');
        for ($i = 0; $i < (int)$_POST['totalChunks']; $i++) {
            $chunkFile = $tempDirPath . '/' . $i;
            if (file_exists($chunkFile)) {
                $chunk = fopen($chunkFile, 'rb');
                stream_copy_to_stream($chunk, $fp);
                fclose($chunk);
                unlink($chunkFile);
            }
        }
        fclose($fp);
        rmdir($tempDirPath);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'path' => $finalPath]);
        exit;
    }
}

// Frontend example
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chunk Upload Demo</title>
    <style>
    .progress {
        width: 300px; height: 20px; border: 1px solid #ccc;
        margin: 20px 0; display: none;
    }
    .progress-bar {
        height: 100%; width: 0%; background: #4CAF50;
        transition: width 0.3s ease;
    }
    </style>
</head>
<body>
    <input type="file" id="fileInput">
    <button onclick="upload()">Upload</button>
    
    <div class="progress">
        <div class="progress-bar"></div>
        <div class="progress-text" style="text-align: center; color: white;">0%</div>
    </div>

<script>
async function upload() {
    const file = document.getElementById('fileInput').files[0];
    if (!file) return alert('Please select a file');

    const chunkSize = 1 * 1024 * 1024; // 1MB chunk
    const totalChunks = Math.ceil(file.size / chunkSize);
    const uuid = Date.now() + '-' + Math.random().toString(36).slice(2);
    
    // Show progress bar
    const progressBar = document.querySelector('.progress-bar');
    const progressText = document.querySelector('.progress-text');
    document.querySelector('.progress').style.display = 'block';

    try {
        // Upload chunks
        for (let i = 0; i < totalChunks; i++) {
            const chunk = file.slice(i * chunkSize, (i+1)*chunkSize);
            const formData = new FormData();
            formData.append('action', 'chunk');
            formData.append('file', chunk);
            formData.append('uuid', uuid);
            formData.append('chunkIndex', i);

            await fetch(location.href, {
                method: 'POST',
                body: formData
            });
            
            // Update progress
            const progress = ((i + 1) / totalChunks * 100).toFixed(1);
            progressBar.style.width = progress + '%';
            progressText.textContent = progress + '%';
        }

        // Merge request
        const res = await fetch(location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'merge',
                uuid: uuid,
                fileName: file.name,
                totalChunks: totalChunks
            })
        });
        
        const result = await res.json();
        if (result.status === 'success') {
            alert(`File uploaded successfully! Path: ${result.path}`);
        }
    } catch(e) {
        console.error('Upload failed:', e);
        alert('Upload failed');
    }
}
</script>
</body>
</html>
<?php } ?>
