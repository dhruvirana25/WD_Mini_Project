
<?php
session_start();
require_once "db.php";

$search = trim($_GET["q"] ?? "");
$results = [];
$searchPerformed = ($search !== "");

function searchEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

if ($searchPerformed) {
    $term = "%" . $search . "%";

    // Search for users and their posts
    $sql = "
        SELECT
            p.id AS post_id,
            p.user_id,
            p.image,
            p.caption,
            p.created_at,
            u.username
        FROM posts p
        INNER JOIN users u ON p.user_id = u.id
        WHERE u.username LIKE ? OR p.caption LIKE ?
        ORDER BY p.created_at DESC
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $term, $term);
    mysqli_stmt_execute($stmt);
    $queryResult = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($queryResult)) {
        $results[] = $row;
    }

    mysqli_stmt_close($stmt);

    // Also find matching users, including users without posts
    $userSql = "
        SELECT id, username, bio
        FROM users
        WHERE username LIKE ?
        ORDER BY username ASC
    ";

    $userStmt = mysqli_prepare($conn, $userSql);
    mysqli_stmt_bind_param($userStmt, "s", $term);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);

    $users = [];

    while ($user = mysqli_fetch_assoc($userResult)) {
        $users[] = $user;
    }

    mysqli_stmt_close($userStmt);
} else {
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Search | PixelSpace</title>

    <link rel="stylesheet" href="assets/style.css">

    <style>
        body {
            margin: 0;
        }

        .search-page {
            width: 90%;
            max-width: 1050px;
            margin: 35px auto;
        }

        .search-form {
            display: flex;
            gap: 10px;
            margin: 25px 0 35px;
        }

        .search-input {
            flex: 1;
            min-width: 0;
            padding: 13px 16px;
            border: 1px solid #34405e;
            border-radius: 10px;
            background: #141b30;
            color: #fff;
            font-size: 15px;
        }

        .search-button {
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            background: #526fff;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
        }

        .search-section {
            margin-top: 35px;
        }

        .user-results {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .user-result {
            display: block;
            padding: 14px 18px;
            border: 1px solid #34405e;
            border-radius: 12px;
            background: #141b30;
            color: inherit;
            text-decoration: none;
            min-width: 180px;
        }

        .user-result:hover {
            border-color: #6c8cff;
        }

        .user-name {
            color: #8da4ff;
            font-weight: bold;
        }

        .user-bio {
            margin-top: 7px;
            font-size: 13px;
            color: #aab4d0;
        }

        .photo-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .photo-card {
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid #34405e;
            background: #141b30;
            min-width: 0;
        }

        .photo-card img {
            display: block;
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
        }

        .photo-details {
            padding: 12px;
            overflow-wrap: anywhere;
        }

        .photo-details a {
            color: #8da4ff;
            text-decoration: none;
            font-weight: bold;
        }

        .photo-details p {
            margin-bottom: 0;
        }

        .empty-message {
            color: #aab4d0;
            padding: 20px 0;
        }

        @media (max-width: 650px) {
            .photo-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .search-form {
                flex-wrap: wrap;
            }

            .search-button {
                flex: 1;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <a href="index.php" class="logo">PixelSpace</a>
    <a href="index.php" style="color:inherit;text-decoration:none;">
        ← Home
    </a>
</header>

<main class="search-page">

    <h1>Search PixelSpace</h1>
    <p>Find people and discover photos.</p>

    <form class="search-form" action="search.php" method="GET">
        <input
            class="search-input"
            type="search"
            name="q"
            placeholder="Search usernames or captions..."
            value="<?php echo searchEscape($search); ?>"
            required
        >

        <button class="search-button" type="submit">
            Search
        </button>
    </form>

    <?php if (!$searchPerformed): ?>

        <p class="empty-message">
            Enter a username or a word from a photo caption to get started.
        </p>

    <?php else: ?>

        <p>
            Results for:
            <strong><?php echo searchEscape($search); ?></strong>
        </p>

        <section class="search-section">
            <h2>People</h2>

            <?php if (count($users) > 0): ?>

                <div class="user-results">
                    <?php foreach ($users as $user): ?>

                        <a
                            class="user-result"
                            href="profile.php?id=<?php echo (int)$user["id"]; ?>"
                        >
                            <div class="user-name">
                                @<?php echo searchEscape($user["username"]); ?>
                            </div>

                            <div class="user-bio">
                                <?php
                                echo !empty($user["bio"])
                                    ? searchEscape($user["bio"])
                                    : "PixelSpace member";
                                ?>
                            </div>
                        </a>

                    <?php endforeach; ?>
                </div>

            <?php else: ?>

                <p class="empty-message">No matching users found.</p>

            <?php endif; ?>
        </section>

        <section class="search-section">
            <h2>Photos</h2>

            <?php if (count($results) > 0): ?>

                <div class="photo-grid">
                    <?php foreach ($results as $post): ?>

                        <article class="photo-card">

                            <a href="profile.php?id=<?php
                                echo (int)$post["user_id"];
                            ?>">
                                <img
                                    src="<?php echo searchEscape($post["image"]); ?>"
                                    alt="Photo by <?php echo searchEscape($post["username"]); ?>"
                                    loading="lazy"
                                >
                            </a>

                            <div class="photo-details">
                                <a href="profile.php?id=<?php
                                    echo (int)$post["user_id"];
                                ?>">
                                    @<?php echo searchEscape($post["username"]); ?>
                                </a>

                                <?php if (!empty($post["caption"])): ?>
                                    <p>
                                        <?php echo searchEscape($post["caption"]); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                        </article>

                    <?php endforeach; ?>
                </div>

            <?php else: ?>

                <p class="empty-message">
                    No matching photos found.
                </p>

            <?php endif; ?>
        </section>

    <?php endif; ?>

</main>

</body>
</html>
