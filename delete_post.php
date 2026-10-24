
<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];
$postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);

if (!$postId || $postId < 1) {
    header("Location: profile.php?id=" . $userId);
    exit;
}

// Find the post and verify that it belongs to this user.
$stmt = mysqli_prepare(
    $conn,
    "SELECT image FROM posts WHERE id = ? AND user_id = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $postId, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$post = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$post) {
    header("Location: profile.php?id=" . $userId);
    exit;
}

// Delete likes and the post together.
mysqli_begin_transaction($conn);

try {
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM likes WHERE post_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $postId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM posts WHERE id = ? AND user_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "ii", $postId, $userId);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) !== 1) {
        throw new Exception("Post could not be deleted.");
    }

    mysqli_stmt_close($stmt);
    mysqli_commit($conn);

    // Remove the local image only if it is inside uploads/.
    $image = $post['image'];
    $uploadsDir = realpath(__DIR__ . '/uploads');

    if (
        $uploadsDir &&
        is_string($image) &&
        strpos($image, 'uploads/') === 0
    ) {
        $imagePath = realpath(__DIR__ . '/' . $image);

        if (
            $imagePath &&
            strpos($imagePath, $uploadsDir . DIRECTORY_SEPARATOR) === 0 &&
            is_file($imagePath)
        ) {
            unlink($imagePath);
        }
    }
} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log("Delete post failed: " . $e->getMessage());
}

header("Location: profile.php?id=" . $userId);
exit;
?>
