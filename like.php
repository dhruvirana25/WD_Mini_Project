
<?php
session_start();
require_once "db.php";

// Only logged-in users can like posts
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$postId = filter_input(INPUT_POST, "post_id", FILTER_VALIDATE_INT);

if (!$postId || $postId < 1) {
    header("Location: index.php");
    exit;
}

// Check whether the post exists
$checkPost = mysqli_prepare(
    $conn,
    "SELECT id FROM posts WHERE id = ?"
);

mysqli_stmt_bind_param($checkPost, "i", $postId);
mysqli_stmt_execute($checkPost);
$postResult = mysqli_stmt_get_result($checkPost);
$postExists = mysqli_num_rows($postResult) > 0;
mysqli_stmt_close($checkPost);

if (!$postExists) {
    header("Location: index.php");
    exit;
}

// Check whether this user has already liked the post
$checkLike = mysqli_prepare(
    $conn,
    "SELECT id FROM likes WHERE user_id = ? AND post_id = ?"
);

mysqli_stmt_bind_param($checkLike, "ii", $userId, $postId);
mysqli_stmt_execute($checkLike);
$likeResult = mysqli_stmt_get_result($checkLike);
$alreadyLiked = mysqli_num_rows($likeResult) > 0;
mysqli_stmt_close($checkLike);

if ($alreadyLiked) {
    // Unlike the post
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM likes WHERE user_id = ? AND post_id = ?"
    );
} else {
    // Like the post
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO likes (user_id, post_id) VALUES (?, ?)"
    );
}
mysqli_stmt_bind_param($stmt, "ii", $userId, $postId);

if (!mysqli_stmt_execute($stmt)) {
    error_log("Like action failed: " . mysqli_stmt_error($stmt));
}

mysqli_stmt_close($stmt);
header("Location: index.php");
exit;
?>
