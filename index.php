<?php
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$message = '';
$outputFileUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Error al subir el archivo de audio.';
    } else {
        $start = isset($_POST['start']) ? trim($_POST['start']) : '';
        $duration = isset($_POST['duration']) ? trim($_POST['duration']) : '';

        if ($start === '' || $duration === '' || !is_numeric($start) || !is_numeric($duration)) {
            $message = 'Ingresa valores numéricos para inicio y duración.';
        } else {
            $originalName = basename($_FILES['audio']['name']);
            $inputPath = $uploadDir . '/' . uniqid('audio_', true) . '_' . $originalName;

            if (!move_uploaded_file($_FILES['audio']['tmp_name'], $inputPath)) {
                $message = 'No se pudo guardar el archivo subido.';
            } else {
                $outputName = uniqid('recorte_', true) . '.mp3';
                $outputPath = $uploadDir . '/' . $outputName;

                $startArg = escapeshellarg($start);
                $durationArg = escapeshellarg($duration);
                $inputArg = escapeshellarg($inputPath);
                $outputArg = escapeshellarg($outputPath);

                $command = "ffmpeg -hide_banner -y -i $inputArg -ss $startArg -t $durationArg -vn -acodec libmp3lame $outputArg 2>&1";
                exec($command, $ffmpegOutput, $exitCode);

                if ($exitCode === 0 && file_exists($outputPath)) {
                    $message = 'Audio procesado correctamente.';
                    $outputFileUrl = 'uploads/' . $outputName;
                } else {
                    $message = 'Error al procesar el audio con ffmpeg.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recorte de audio con FFmpeg</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            margin: 0;
            padding: 40px;
        }
        .container {
            max-width: 640px;
            margin: 0 auto;
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        }
        h1 {
            margin-top: 0;
        }
        form {
            display: grid;
            gap: 16px;
        }
        label {
            font-weight: 600;
        }
        input[type="file"],
        input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #d5d9e0;
            border-radius: 8px;
        }
        button {
            padding: 12px 16px;
            background: #2d6cdf;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        .message {
            padding: 12px;
            border-radius: 8px;
            background: #eef2ff;
        }
        .result a {
            color: #2d6cdf;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Recorte de audio con FFmpeg</h1>
        <p>Sube un archivo de audio, define el inicio y la duración (en segundos) y obtén un recorte.</p>

        <?php if ($message): ?>
            <div class="message">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div>
                <label for="audio">Archivo de audio</label>
                <input type="file" id="audio" name="audio" accept="audio/*" required>
            </div>
            <div>
                <label for="start">Inicio (segundos)</label>
                <input type="number" id="start" name="start" min="0" step="0.1" required>
            </div>
            <div>
                <label for="duration">Duración (segundos)</label>
                <input type="number" id="duration" name="duration" min="0.1" step="0.1" required>
            </div>
            <button type="submit">Procesar audio</button>
        </form>

        <?php if ($outputFileUrl): ?>
            <div class="result">
                <p>Descarga tu recorte: <a href="<?php echo htmlspecialchars($outputFileUrl, ENT_QUOTES, 'UTF-8'); ?>" download>Descargar audio</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
