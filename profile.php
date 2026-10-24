
<?php
session_start();
require_once "db.php";

// Get the profile user ID from the URL
$profileId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$profileId || $profileId < 1) {
    header("Location: index.php");
    exit;
}

// Check whether a user is logged in
$isLoggedIn = isset($_SESSION["user_id"]);
$currentUserId = $isLoggedIn ? (int)$_SESSION["user_id"] : 0;
$isOwnProfile = $isLoggedIn && $currentUserId === (int)$profileId;

// Fetch user details
$userStmt = mysqli_prepare(
    $conn,
    "SELECT id, username, bio FROM users WHERE id = ?"
);

mysqli_stmt_bind_param($userStmt, "i", $profileId);
mysqli_stmt_execute($userStmt);
$userResult = mysqli_stmt_get_result($userStmt);
$profile = mysqli_fetch_assoc($userResult);
mysqli_stmt_close($userStmt);

if (!$profile) {
    header("Location: index.php");
    exit;
}

// Fetch this user's posts
$postStmt = mysqli_prepare(
    $conn,
    "SELECT id, user_id, image, caption, created_at
     FROM posts
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

mysqli_stmt_bind_param($postStmt, "i", $profileId);
mysqli_stmt_execute($postStmt);
$postResult = mysqli_stmt_get_result($postStmt);

$totalPosts = mysqli_num_rows($postResult);

function escapeText($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapeText($profile["username"]); ?> | PixelSpace</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #0b1020;
            color: #f5f7ff;
        }

        header {
            padding: 20px 7%;
            border-bottom: 1px solid #252d44;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: #6c8cff;
            font-size: 25px;
            font-weight: bold;
            text-decoration: none;
        }

        .back {
            color: #cbd5ff;
            text-decoration: none;
        }

        main {
            width: 90%;
            max-width: 1000px;
            margin: 40px auto;
        }

        .profile-header {
            padding: 30px;
            background: #141b30;
            border: 1px solid #28324d;
            border-radius: 18px;
            margin-bottom: 30px;
        }

        .avatar {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #5278ff, #a35bff);
            font-size: 34px;
            font-weight: bold;
            margin-bottom: 18px;
        }

        h1 {
            margin: 0 0 10px;
        }

        .bio {
            color: #b8c2df;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .stats {
            color: #9eafff;
            font-size: 14px;
        }

        h2 {
            font-size: 20px;
            margin-bottom: 20px;
        }

        .photo-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
        }

        .photo-card {
            overflow: hidden;
            background: #141b30;
            border-radius: 12px;
            border: 1px solid #28324d;
        }

        .photo-card img {
            display: block;
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
        }

        .caption {
            padding: 12px;
            color: #dce3ff;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        /* Delete post button */
        .post-footer {
            padding: 0 12px 12px;
        }

        .delete-post-btn {
            background: #2a1725;
            color: #ff8c9b;
            border: 1px solid #63303e;
            border-radius: 8px;
            padding: 9px 14px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .delete-post-btn:hover {
            background: #d93050;
            border-color: #d93050;
            color: white;
        }

        .empty {
            color: #aab4d0;
            padding: 25px 0;
        }

        @media (max-width: 600px) {
            .photo-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .profile-header {
                padding: 22px;
            }

            header {
                padding: 18px 5%;
            }
        }
    </style>
</head>

<body>

<header>
    <a class="logo" href="index.php">PixelSpace</a>
    <a class="back" href="index.php">← Back to Home</a>
</header>

<main>

    <section class="profile-header">
        <div class="avatar">
            <?php
            echo escapeText(
                strtoupper(substr($profile["username"], 0, 1))
            );
            ?>
        </div>

        <h1><?php echo escapeText($profile["username"]); ?></h1>

        <div class="bio">
            <?php
            echo !empty($profile["bio"])
                ? nl2br(escapeText($profile["bio"]))
                : "This user hasn't added a bio yet.";
            ?>
        </div>

        <div class="stats">
            <?php echo $totalPosts; ?>
            <?php echo $totalPosts === 1 ? "post" : "posts"; ?>
        </div>
    </section>

    <h2>Photos</h2>

    <?php if ($totalPosts > 0): ?>
        <div class="photo-grid">

            <?php while ($post = mysqli_fetch_assoc($postResult)): ?>
                <article class="photo-card">

                    <img
                        src="<?php echo escapeText($post["image"]); ?>"
                        alt="Photo by <?php echo escapeText($profile["username"]); ?>"
                        loading="lazy"
                    >

                    <?php if (!empty($post["caption"])): ?>
                        <div class="caption">
                            <?php echo escapeText($post["caption"]); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (
                        $isOwnProfile &&
                        (int)$post["user_id"] === $currentUserId
                    ): ?>
                        <div class="post-footer">
                            <form
                                action="delete_post.php"
                                method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this post? This action cannot be undone.');"
                            >
                                <input
                                    type="hidden"
                                    name="post_id"
                                    value="<?php echo (int)$post["id"]; ?>"
                                >

                                <button
                                    type="submit"
                                    class="delete-post-btn"
                                >
                                    Delete Post
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                </article>
            <?php endwhile; ?>

        </div>
    <?php else: ?>
        <p class="empty">No photos posted yet.</p>
    <?php endif; ?>

</main>

</body>
</html>

<?php
mysqli_stmt_close($postStmt);
?>
```