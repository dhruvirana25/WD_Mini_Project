
<?php
session_start();
require_once "db.php";

$isLoggedIn = isset($_SESSION["user_id"]);
$currentUserId = $isLoggedIn ? (int)$_SESSION["user_id"] : 0;

function pixelEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// Fetch logged-in user's details
$currentUser = null;

if ($isLoggedIn) {
    $userStmt = mysqli_prepare(
        $conn,
        "SELECT id, username, bio FROM users WHERE id = ?"
    );

    mysqli_stmt_bind_param($userStmt, "i", $currentUserId);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    $currentUser = mysqli_fetch_assoc($userResult);
    mysqli_stmt_close($userStmt);
}

// Fetch posts, owners, like counts and current user's like status
$sql = "
    SELECT
        p.id,
        p.user_id,
        p.image,
        p.caption,
        p.created_at,
        u.username,
        (
            SELECT COUNT(*)
            FROM likes l
            WHERE l.post_id = p.id
        ) AS like_count,
        EXISTS (
            SELECT 1
            FROM likes ul
            WHERE ul.post_id = p.id
              AND ul.user_id = ?
        ) AS is_liked
    FROM posts p
    INNER JOIN users u ON u.id = p.user_id
    ORDER BY p.created_at DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $currentUserId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Count all posts for the feed
$totalPosts = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PixelSpace | Share Your Moments</title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="app-layout">

    <!-- Sidebar -->
    <aside class="sidebar">

        <a href="index.php" class="brand">
            <span class="brand-icon">P</span>
            <span>PixelSpace</span>
        </a>

        <nav class="sidebar-nav">

            <a href="index.php" class="nav-link active">
                <span class="nav-icon">⌂</span>
                <span>Home</span>
            </a>

            <a href="search.php" class="nav-link">
                <span class="nav-icon">⌕</span>
                <span>Search</span>
            </a>

            <a href="#photo-feed" class="nav-link">
                <span class="nav-icon">▦</span>
                <span>Explore</span>
            </a>

            <a href="upload.php" class="nav-link">
                <span class="nav-icon">＋</span>
                <span>Create Post</span>
            </a>

            <?php if ($isLoggedIn && $currentUser): ?>
                <a
                    href="profile.php?id=<?php echo $currentUserId; ?>"
                    class="nav-link"
                >
                    <span class="nav-icon">◎</span>
                    <span>My Profile</span>
                </a>
            <?php else: ?>
                <a href="login.php" class="nav-link">
                    <span class="nav-icon">◎</span>
                    <span>Profile</span>
                </a>
            <?php endif; ?>

        </nav>

        <div class="sidebar-bottom">

            <?php if ($isLoggedIn): ?>
                <div class="sidebar-user">
                    <div class="avatar-small">
                        <?php
                        echo pixelEscape(
                            strtoupper(
                                substr(
                                    $currentUser["username"] ?? "U",
                                    0,
                                    1
                                )
                            )
                        );
                        ?>
                    </div>

                    <div class="sidebar-user-info">
                        <strong>
                            <?php
                            echo pixelEscape(
                                $currentUser["username"] ?? "User"
                            );
                            ?>
                        </strong>
                        <span>PixelSpace member</span>
                    </div>
                </div>

                <a href="logout.php" class="nav-link logout-link">
                    <span class="nav-icon">↪</span>
                    <span>Log Out</span>
                </a>
            <?php else: ?>
                <a href="login.php" class="nav-link">
                    <span class="nav-icon">↪</span>
                    <span>Login</span>
                </a>

                <a href="register.php" class="nav-link">
                    <span class="nav-icon">＋</span>
                    <span>Sign Up</span>
                </a>
            <?php endif; ?>

        </div>

    </aside>

    <!-- Main content -->
    <div class="main-area">

        <!-- Top bar -->
        <header class="topbar">

            <div class="topbar-heading">
                <span class="eyebrow">YOUR CREATIVE SPACE</span>
                <h1>Home</h1>
            </div>

            <div class="topbar-actions">
                <a href="search.php" class="search-button">
                    <span>⌕</span>
                    <span>Search</span>
                </a>

                <?php if ($isLoggedIn && $currentUser): ?>
                    <a
                        href="profile.php?id=<?php echo $currentUserId; ?>"
                        class="topbar-avatar"
                        title="My Profile"
                    >
                        <?php
                        echo pixelEscape(
                            strtoupper(
                                substr(
                                    $currentUser["username"] ?? "U",
                                    0,
                                    1
                                )
                            )
                        );
                        ?>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="login-button">Log In</a>
                <?php endif; ?>
            </div>

        </header>

        <main class="page-content">

            <!-- Welcome / profile section -->
            <section class="welcome-section">

                <div class="welcome-copy">
                    <span class="section-label">WELCOME TO PIXELSPACE</span>

                    <?php if ($isLoggedIn && $currentUser): ?>
                        <h2>
                            Hey,
                            <?php
                            echo pixelEscape($currentUser["username"]);
                            ?>!
                        </h2>
                        <p>
                            Your space to share moments, explore ideas,
                            and discover people.
                        </p>
                    <?php else: ?>
                        <h2>A little space for your moments.</h2>
                        <p>
                            Share your world and discover moments
                            from the PixelSpace community.
                        </p>
                    <?php endif; ?>
                </div>

                <div class="welcome-actions">
                    <?php if ($isLoggedIn): ?>
                        <a href="upload.php" class="primary-button">
                            + Create Post
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="primary-button">
                            Join PixelSpace
                        </a>
                    <?php endif; ?>
                </div>

            </section>

            <!-- Photo feed -->
            <section class="feed-section" id="photo-feed">

                <div class="section-heading">
                    <div>
                        <span class="section-label">COMMUNITY</span>
                        <h2>Explore the feed</h2>
                        <p>Moments shared by the community.</p>
                    </div>

                    <span class="post-count">
                        <?php echo $totalPosts; ?>
                        <?php echo $totalPosts === 1 ? "post" : "posts"; ?>
                    </span>
                </div>

                <?php if ($totalPosts > 0): ?>

                    <div class="photo-grid">

                        <?php while ($post = mysqli_fetch_assoc($result)): ?>

                            <article class="photo-card">

                                <a
                                    class="photo-image-link"
                                    href="profile.php?id=<?php
                                        echo (int)$post["user_id"];
                                    ?>"
                                    aria-label="View <?php
                                        echo pixelEscape($post["username"]);
                                    ?>'s profile"
                                >
                                    <img
                                        class="post-image"
                                        src="<?php
                                            echo pixelEscape($post["image"]);
                                        ?>"
                                        alt="Photo shared by <?php
                                            echo pixelEscape($post["username"]);
                                        ?>"
                                        loading="lazy"
                                    >
                                </a>

                                <div class="photo-details">

                                    <div class="post-author-row">

                                        <div class="post-avatar">
                                            <?php
                                            echo pixelEscape(
                                                strtoupper(
                                                    substr(
                                                        $post["username"],
                                                        0,
                                                        1
                                                    )
                                                )
                                            );
                                            ?>
                                        </div>

                                        <a
                                            class="profile-link"
                                            href="profile.php?id=<?php
                                                echo (int)$post["user_id"];
                                            ?>"
                                        >
                                            @<?php
                                            echo pixelEscape($post["username"]);
                                            ?>
                                        </a>

                                    </div>

                                    <?php if (!empty($post["caption"])): ?>
                                        <p class="post-caption">
                                            <?php
                                            echo pixelEscape($post["caption"]);
                                            ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="post-actions">

                                        <?php if ($isLoggedIn): ?>

                                            <form
                                                action="like.php"
                                                method="POST"
                                                class="like-form"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="post_id"
                                                    value="<?php
                                                        echo (int)$post["id"];
                                                    ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="creative-like <?php
                                                        echo $post["is_liked"]
                                                            ? "liked"
                                                            : "";
                                                    ?>"
                                                >
                                                    <?php
                                                    echo $post["is_liked"]
                                                        ? "♥ Liked"
                                                        : "♡ Appreciate";
                                                    ?>
                                                </button>
                                            </form>

                                        <?php else: ?>

                                            <a
                                                href="login.php"
                                                class="creative-like"
                                            >
                                                ♡ Appreciate
                                            </a>

                                        <?php endif; ?>

                                        <span class="like-count">
                                            ♥
                                            <?php
                                            echo (int)$post["like_count"];
                                            ?>
                                            <?php
                                            echo (int)$post["like_count"] === 1
                                                ? "like"
                                                : "likes";
                                            ?>
                                        </span>

                                    </div>

                                </div>

                            </article>

                        <?php endwhile; ?>

                    </div>

                <?php else: ?>

                    <div class="empty-feed">

                        <div class="empty-icon">▧</div>

                        <h3>Your feed starts here</h3>

                        <p>
                            No photos have been shared yet.
                            Be the first to post a moment!
                        </p>

                        <?php if ($isLoggedIn): ?>
                            <a href="upload.php" class="primary-button">
                                Create your first post
                            </a>
                        <?php else: ?>
                            <a href="register.php" class="primary-button">
                                Join PixelSpace
                            </a>
                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            </section>

            <!-- Footer -->
            <footer class="site-footer">
                <a href="index.php">PixelSpace</a>
                <span>Made for moments worth sharing.</span>
            </footer>

        </main>

    </div>

</div>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>
