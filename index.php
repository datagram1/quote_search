<?php
// Error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection details from environment variables
$db_host = getenv('DB_HOST') ?: die("DB_HOST environment variable is not set");
$db_user = getenv('DB_USER') ?: die("DB_USER environment variable is not set");
$db_pass = getenv('DB_PASS') ?: die("DB_PASS environment variable is not set");
$db_name = getenv('DB_NAME') ?: die("DB_NAME environment variable is not set");

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$searchResults = [];
$totalResults = 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Results per page
$offset = ($page - 1) * $limit;

$searchType = '';
$searchText = '';
$searchTag = '';

if (php_sapi_name() !== 'cli') {
    // Web server execution
    if ($_SERVER["REQUEST_METHOD"] == "GET" && !empty($_GET['search_type'])) {
        $searchType = $_GET['search_type'] ?? '';
        $searchText = $_GET['search_text'] ?? '';
        $searchTag = $_GET['search_tag'] ?? '';
        performSearch($conn, $limit, $offset, $searchType, $searchText, $searchTag);
    }
} else {
    // Command-line execution
    echo "Running in CLI mode. Skipping search.\n";
}

function performSearch($conn, $limit, $offset, $searchType, $searchText, $searchTag) {
    global $searchResults, $totalResults;

    $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM quotes_big WHERE 1=1";

    if (!empty($searchText)) {
        if ($searchType == 'author') {
            $sql .= " AND author LIKE ?";
        } elseif ($searchType == 'quote') {
            $sql .= " AND quote LIKE ?";
        }
    }

    if (!empty($searchTag)) {
        $sql .= " AND FIND_IN_SET(?, category) > 0";
    }

    $sql .= " LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);

    if (!empty($searchText) && !empty($searchTag)) {
        $searchParam = "%$searchText%";
        $stmt->bind_param("ssii", $searchParam, $searchTag, $limit, $offset);
    } elseif (!empty($searchText)) {
        $searchParam = "%$searchText%";
        $stmt->bind_param("sii", $searchParam, $limit, $offset);
    } elseif (!empty($searchTag)) {
        $stmt->bind_param("sii", $searchTag, $limit, $offset);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $searchResults = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    // Get total number of results
    $totalResultsQuery = $conn->query("SELECT FOUND_ROWS()");
    $totalResults = $totalResultsQuery->fetch_row()[0];
}

$conn->close();

// Pagination logic
$totalPages = ceil($totalResults / $limit);
$currentPage = $page;
$startPage = max(1, $currentPage - 2);
$endPage = min($totalPages, $currentPage + 2);

function getPageUrl($pageNum) {
    $params = $_GET;
    $params['page'] = $pageNum;
    return '?' . http_build_query($params);
}

// Only output HTML if not in CLI mode
if (php_sapi_name() !== 'cli'):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Search</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Search</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1d2234;
            color: #fff;
        }
        .logo {
            display: block;
            margin: 0 auto;
            width: 200px;
        }
        .table {
            background-color: #1d2234;
            color: #fff;
        }
        .table thead th {
            background-color: #7d8ad1;
            color: #fff;
            border-color: #3a3f5a;
        }
        .table tbody td {
            border-color: #3a3f5a;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #252a40;
        }
        .table-striped tbody tr:nth-of-type(even) {
            background-color: #1d2234;
        }
        .table {
            background-color: #7d8ad1;
        }
        .pagination .page-link {
            background-color: #2a2f4a;
            border-color: #3a3f5a;
            color: #fff;
        }
        .pagination .page-item.active .page-link {
            background-color: #3a3f5a;
            border-color: #4a4f6a;
        }
        .btn-light {
            background-color: #3a3f5a;
            border-color: #4a4f6a;
            color: #fff;
        }
        .btn-light:hover {
            background-color: #6e77ab;
            border-color: #5a5f7a;
            color: #fff;
        }
        .form-select, .form-control {
            background-color: #4a5179;
            border-color: #3a3f5a;
            color: #fff;
        }
        .form-select:focus, .form-control:focus {
            background-color: #6e77ab;
            border-color: #4a4f6a;
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(74, 79, 106, 0.25);
        }
    </style>
</head>
<body class="container my-5">

<!--    <h1 class="mb-4 text-center">Quote Search</h1>-->
    <div class="text-center">
<img src="image.png" alt="Image Search" style="
    width: 280px;
    /* float: left; */
    /* text-align: left; */
    margin-top: -70px;
">

    </div>
    <form method="get" action="" class="row g-3 mb-4">
        <div class="col-md-4">
            <select name="search_type" class="form-select">
                <option value="author" <?php echo $searchType == 'author' ? 'selected' : ''; ?>>Author</option>
                <option value="quote" <?php echo $searchType == 'quote' ? 'selected' : ''; ?>>Quote</option>
            </select>
        </div>
        <div class="col-md-4">
            <input type="text" name="search_text" placeholder="Enter search text" value="<?php echo htmlspecialchars($searchText); ?>" class="form-control">
        </div>
        <div class="col-md-4">
            <input type="text" name="search_tag" placeholder="Enter tag to search" value="<?php echo htmlspecialchars($searchTag); ?>" class="form-control">
        </div>
        <div class="col-md-12 text-end">
            <button type="submit" class="btn btn-light">Search</button>
        </div>
    </form>

    <?php if (!empty($searchResults)): ?>
         <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Quote</th>
                    <th>Author</th>
                    <th>Tags</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($searchResults as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['ID']); ?></td>
                    <td><?php echo htmlspecialchars($row['quote']); ?></td>
                    <td><?php echo htmlspecialchars($row['author']); ?></td>
                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo getPageUrl($currentPage - 1); ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo getPageUrl($i); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo getPageUrl($currentPage + 1); ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php elseif ($_SERVER["REQUEST_METHOD"] == "GET" && !empty($_GET['search_type'])): ?>
        <div class="alert alert-warning" role="alert">No results found.</div>
    <?php endif; ?>

    <!-- Bootstrap JS (optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>
