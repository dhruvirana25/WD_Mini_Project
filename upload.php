
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";
$captionValue = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $caption = trim($_POST["caption"] ?? "");
    $captionValue = $caption;

    if (
        !isset($_FILES["image"]) ||
        $_FILES["image"]["error"] !== UPLOAD_ERR_OK
    ) {
        $message = "Please select an image to upload.";
    } elseif ($_FILES["image"]["size"] > 5 * 1024 * 1024) {
        $message = "Image size must be 5 MB or less.";
    } else {
        $tmpPath = $_FILES["image"]["tmp_name"];
        $imageInfo = getimagesize($tmpPath);

        if ($imageInfo === false) {
            $message = "Please upload a valid image.";
        } else {
            $allowedTypes = [
                IMAGETYPE_JPEG => "jpg",
                IMAGETYPE_PNG  => "png",
                IMAGETYPE_GIF  => "gif",
                IMAGETYPE_WEBP => "webp"
            ];

            $imageType = $imageInfo[2];

            if (!isset($allowedTypes[$imageType])) {
                $message = "Only JPG, PNG, GIF, and WEBP are allowed.";
            } else {
                $uploadDir = __DIR__ . "/uploads/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileName = bin2hex(random_bytes(16))
                    . "." . $allowedTypes[$imageType];

                $destination = $uploadDir . $fileName;
                $relativePath = "uploads/" . $fileName;

                if (move_uploaded_file($tmpPath, $destination)) {
                    $userId = (int) $_SESSION["user_id"];

                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO posts (user_id, image, caption)
                         VALUES (?, ?, ?)"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "iss",
                        $userId,
                        $relativePath,
                        $caption
                    );

                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);
                        header("Location: index.php?uploaded=1");
                        exit;
                    } else {
                        mysqli_stmt_close($stmt);
                        unlink($destination);
                        $message = "Could not save your post. Please try again.";
                    }
                } else {
                    $message = "Image upload failed. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post | PixelSpace</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background:
                radial-gradient(
                    circle at 15% 10%,
                    rgba(82, 120, 255, 0.12),
                    transparent 30%
                ),
                #0b1020;
            color: #f5f7ff;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .topbar {
            height: 76px;
            padding: 0 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #252d44;
            background: rgba(11, 16, 32, 0.85);
        }

        .logo {
            color: #7894ff;
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -0.8px;
        }

        .back-link {
            color: #cbd5ff;
            font-size: 14px;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #7894ff;
        }

        .page-container {
            width: 92%;
            max-width: 1080px;
            margin: 48px auto;
        }

        .page-heading {
            margin-bottom: 30px;
        }

        .eyebrow {
            display: inline-block;
            padding: 7px 12px;
            border: 1px solid #303e6b;
            border-radius: 20px;
            background: #141e3b;
            color: #9eafff;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .page-heading h1 {
            margin: 17px 0 10px;
            font-size: clamp(30px, 4vw, 42px);
            letter-spacing: -1.2px;
        }

        .page-heading p {
            margin: 0;
            color: #aab4d0;
            font-size: 15px;
            line-height: 1.7;
        }

        .create-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(300px, 0.85fr);
            gap: 24px;
            align-items: start;
        }

        .panel {
            background: #141b30;
            border: 1px solid #28324d;
            border-radius: 18px;
            padding: 26px;
            min-width: 0;
        }

        .panel h2 {
            margin: 0 0 7px;
            font-size: 19px;
        }

        .panel-description {
            color: #98a5c7;
            font-size: 13px;
            margin: 0 0 23px;
            line-height: 1.6;
        }

        .upload-zone {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 290px;
            padding: 24px;
            border: 1.5px dashed #43558c;
            border-radius: 14px;
            background: #0f162b;
            text-align: center;
            cursor: pointer;
            overflow: hidden;
            transition: border-color 0.2s, background 0.2s;
        }

        .upload-zone:hover,
        .upload-zone.drag-over {
            border-color: #7894ff;
            background: #121d39;
        }

        .upload-icon {
            width: 62px;
            height: 62px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: #202e55;
            color: #91a6ff;
            font-size: 29px;
            margin-bottom: 17px;
        }

        .upload-zone strong {
            font-size: 16px;
            margin-bottom: 8px;
        }

        .upload-zone .hint {
            color: #9ba8c9;
            font-size: 13px;
            line-height: 1.6;
        }

        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .preview-image {
            display: none;
            width: 100%;
            max-height: 420px;
            object-fit: contain;
            border-radius: 10px;
        }

        .preview-image.visible {
            display: block;
        }

        .preview-image.visible + .preview-placeholder {
            display: none;
        }

        .field-label {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin: 24px 0 10px;
            font-size: 14px;
            font-weight: 600;
        }

        .counter {
            color: #8f9cbe;
            font-size: 12px;
            font-weight: normal;
        }

        textarea {
            display: block;
            width: 100%;
            min-height: 125px;
            padding: 14px;
            border: 1px solid #303c5b;
            border-radius: 11px;
            outline: none;
            resize: vertical;
            background: #0f162b;
            color: #f5f7ff;
            font-family: inherit;
            font-size: 14px;
            line-height: 1.6;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        textarea::placeholder {
            color: #7885a5;
        }

        textarea:focus {
            border-color: #7894ff;
            box-shadow: 0 0 0 3px rgba(108, 140, 255, 0.12);
        }

        .message {
            margin-bottom: 20px;
            padding: 13px 15px;
            border: 1px solid #743849;
            border-radius: 10px;
            background: #2a1725;
            color: #ffb4c0;
            font-size: 14px;
            line-height: 1.5;
        }

        .submit-button {
            width: 100%;
            margin-top: 20px;
            padding: 15px 18px;
            border: none;
            border-radius: 11px;
            background: linear-gradient(110deg, #5278ff, #8b5cf6);
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 8px 22px rgba(82, 120, 255, 0.18);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 11px 27px rgba(82, 120, 255, 0.3);
        }

        .submit-button:disabled {
            opacity: 0.7;
            cursor: wait;
            transform: none;
        }

        .tips {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #29365a;
            border-radius: 11px;
            background: #10182e;
            color: #aebbe0;
            font-size: 13px;
            line-height: 1.7;
        }

        .tips strong {
            display: block;
            margin-bottom: 4px;
            color: #dce3ff;
        }

        .tips p {
            margin: 0;
        }

        .preview-panel {
            position: sticky;
            top: 24px;
        }

        .preview-card {
            margin-top: 22px;
            overflow: hidden;
            border: 1px solid #303b5a;
            border-radius: 14px;
            background: #0f162b;
        }

        .preview-card-header {
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 11px;
            border-bottom: 1px solid #29334d;
        }

        .preview-avatar {
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: linear-gradient(135deg, #5278ff, #a35bff);
            color: white;
            font-size: 17px;
            font-weight: bold;
        }

        .preview-user {
            color: #f5f7ff;
            font-size: 14px;
            font-weight: bold;
        }

        .preview-label {
            margin-top: 4px;
            color: #8997ba;
            font-size: 12px;
        }

        .preview-placeholder {
            min-height: 250px;
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: #8492b5;
        }

        .preview-placeholder span {
            margin-bottom: 12px;
            font-size: 32px;
        }

        .preview-placeholder p {
            margin: 0;
            font-size: 13px;
            line-height: 1.7;
        }

        .preview-card .preview-image {
            width: 100%;
            max-height: 360px;
            aspect-ratio: 1 / 1;
            object-fit: contain;
            background: #090e1c;
            border-radius: 0;
        }

        .preview-caption {
            min-height: 48px;
            padding: 15px;
            color: #c8d2ef;
            font-size: 13px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .preview-caption.empty-caption {
            color: #7583a4;
            font-style: italic;
        }

        @media (max-width: 760px) {
            .create-layout {
                grid-template-columns: 1fr;
            }

            .preview-panel {
                position: static;
            }

            .page-container {
                margin: 32px auto;
            }

            .panel {
                padding: 20px;
            }

            .upload-zone {
                min-height: 240px;
            }
        }

        @media (max-width: 480px) {
            .topbar {
                height: 65px;
                padding: 0 5%;
            }

            .logo {
                font-size: 22px;
            }

            .back-link {
                font-size: 12px;
            }

            .page-container {
                width: 92%;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <a class="logo" href="index.php">PixelSpace</a>
    <a class="back-link" href="index.php">← Back to Home</a>
</header>

<main class="page-container">

    <section class="page-heading">
        <span class="eyebrow">Your creative space</span>
        <h1>Create something worth sharing.</h1>
        <p>Choose a photo, add a caption, and share your moment with PixelSpace.</p>
    </section>

    <div class="create-layout">

        <section class="panel">
            <h2>New post</h2>
            <p class="panel-description">
                Your next favourite memory starts here.
            </p>

            <?php if ($message !== ""): ?>
                <div class="message" role="alert">
                    <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <form
                id="postForm"
                action="upload.php"
                method="POST"
                enctype="multipart/form-data"
            >
                <label class="upload-zone" id="uploadZone" for="image">
                    <img
                        id="mainPreview"
                        class="preview-image"
                        src=""
                        alt="Selected photo preview"
                    >

                    <div class="preview-placeholder">
                        <div class="upload-icon">↑</div>
                        <strong id="uploadTitle">Drop your photo here</strong>
                        <span class="hint" id="uploadHint">
                            Or click to browse your files<br>
                            JPG, PNG, GIF or WEBP · Max 5 MB
                        </span>
                    </div>

                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        required
                    >
                </label>

                <label class="field-label" for="caption">
                    <span>Caption</span>
                    <span class="counter">
                        <span id="charCount">0</span>/2000
                    </span>
                </label>

                <textarea
                    id="caption"
                    name="caption"
                    maxlength="2000"
                    rows="4"
                    placeholder="What's the story behind this moment?"
                ><?= htmlspecialchars($captionValue, ENT_QUOTES, "UTF-8") ?></textarea>

                <button type="submit" class="submit-button" id="submitButton">
                    Share Post ↗
                </button>

                <div class="tips">
                    <strong>✦ A little tip</strong>
                    <p>Choose a clear photo and a caption that makes the moment yours.</p>
                </div>
            </form>
        </section>

        <aside class="panel preview-panel">
            <h2>Live preview</h2>
            <p class="panel-description">
                This is how your post will look.
            </p>

            <div class="preview-card">
                <div class="preview-card-header">
                    <div class="preview-avatar">
                        <?= htmlspecialchars(
                            strtoupper(substr($_SESSION["username"] ?? "U", 0, 1)),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </div>

                    <div>
                        <div class="preview-user">
                            <?= htmlspecialchars(
                                $_SESSION["username"] ?? "Your profile",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </div>
                        <div class="preview-label">Preview of your post</div>
                    </div>
                </div>

                <div class="preview-placeholder" id="sidePlaceholder">
                    <span>▧</span>
                    <p>Your selected photo<br>will appear here.</p>
                </div>

                <img
                    id="sidePreview"
                    class="preview-image"
                    src=""
                    alt="Live post preview"
                >

                <div class="preview-caption empty-caption" id="captionPreview">
                    Your caption will appear here...
                </div>
            </div>
        </aside>

    </div>
</main>

<script>
    const imageInput = document.getElementById("image");
    const mainPreview = document.getElementById("mainPreview");
    const sidePreview = document.getElementById("sidePreview");
    const sidePlaceholder = document.getElementById("sidePlaceholder");
    const uploadTitle = document.getElementById("uploadTitle");
    const uploadHint = document.getElementById("uploadHint");
    const uploadZone = document.getElementById("uploadZone");
    const captionInput = document.getElementById("caption");
    const captionPreview = document.getElementById("captionPreview");
    const charCount = document.getElementById("charCount");
    const postForm = document.getElementById("postForm");
    const submitButton = document.getElementById("submitButton");

    let currentPreviewUrl = null;

    imageInput.addEventListener("change", function () {
        const file = this.files[0];

        if (!file) {
            return;
        }

        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/gif",
            "image/webp"
        ];

        if (!allowedTypes.includes(file.type)) {
            alert("Please choose a JPG, PNG, GIF, or WEBP image.");
            this.value = "";
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            alert("Image size must be 5 MB or less.");
            this.value = "";
            return;
        }

        if (currentPreviewUrl) {
            URL.revokeObjectURL(currentPreviewUrl);
        }

        currentPreviewUrl = URL.createObjectURL(file);

        mainPreview.src = currentPreviewUrl;
        mainPreview.classList.add("visible");

        sidePreview.src = currentPreviewUrl;
        sidePreview.classList.add("visible");

        sidePlaceholder.style.display = "none";
        uploadTitle.textContent = file.name;
        uploadHint.textContent = "Click here to choose a different photo";

        uploadZone.style.padding = "12px";
    });

    captionInput.addEventListener("input", function () {
        charCount.textContent = this.value.length;

        const caption = this.value.trim();

        if (caption) {
            captionPreview.textContent = caption;
            captionPreview.classList.remove("empty-caption");
        } else {
            captionPreview.textContent = "Your caption will appear here...";
            captionPreview.classList.add("empty-caption");
        }
    });

    uploadZone.addEventListener("dragover", function (event) {
        event.preventDefault();
        uploadZone.classList.add("drag-over");
    });

    uploadZone.addEventListener("dragleave", function () {
        uploadZone.classList.remove("drag-over");
    });

    uploadZone.addEventListener("drop", function (event) {
        event.preventDefault();
        uploadZone.classList.remove("drag-over");

        const files = event.dataTransfer.files;

        if (files.length) {
            try {
                const transfer = new DataTransfer();
                transfer.items.add(files[0]);
                imageInput.files = transfer.files;
                imageInput.dispatchEvent(new Event("change"));
            } catch (error) {
                alert("Please click the upload area to choose your photo.");
            }
        }
    });

    postForm.addEventListener("submit", function () {
        if (imageInput.files.length && postForm.checkValidity()) {
            submitButton.disabled = true;
            submitButton.textContent = "Sharing your post...";
        }
    });

    charCount.textContent = captionInput.value.length;
    captionInput.dispatchEvent(new Event("input"));
</script>

</body>
</html>
```