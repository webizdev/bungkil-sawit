<?php
/**
 * Bungkil Sawit - Backend API v1.0
 * Handles CRUD for Products, Settings, Leads, and Stats
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// --- DATABASE CONFIGURATION ---
$host = "localhost";
$db_name = "alilogis_bungkilsawit"; // Replace with your shared hosting DB name
$username = "alilogis_bungkilsawit";             // Replace with your shared hosting DB user
$password = "L7Rb45xQ6AQ5wXZ3R5mu";                 // Replace with your shared hosting DB password

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $exception) {
    echo json_encode(["success" => false, "message" => "Connection error: " . $exception->getMessage()]);
    exit;
}

// --- ROUTING LOGIC ---
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($method === 'GET') {
    if ($action === 'get_all') {
        getAllData($conn);
    } elseif ($action === 'track_visit') {
        updateStat($conn, 'visits');
    }
} elseif ($method === 'POST') {
    // Handle File Uploads first (Multipart)
    if (isset($_FILES['file'])) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileName = time() . '_' . basename($_FILES['file']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            $url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/" . $targetPath;
            echo json_encode(["success" => true, "url" => $url]);
        } else {
            echo json_encode(["success" => false, "error" => "Failed to move file"]);
        }
        exit;
    }

    $data = json_decode(file_get_contents("php://input"));
    
    if ($data->action === 'add_lead') {
        addLead($conn, $data->payload);
    } else {
        // Authenticated Actions
        if (validateAdmin($conn, $data->password)) {
            switch ($data->action) {
                case 'update_settings':
                    updateSettings($conn, $data->payload);
                    break;
                case 'update_products':
                    updateProducts($conn, $data->payload);
                    break;
                case 'sync_leads':
                    syncLeads($conn, $data->payload);
                    break;
            }
        } else {
            echo json_encode(["success" => false, "message" => "Unauthorized"]);
        }
    }
}

// --- FUNCTIONS ---

function getAllData($conn) {
    // Get Settings
    $stmt = $conn->prepare("SELECT * FROM settings LIMIT 1");
    $stmt->execute();
    $s = $stmt->fetch(PDO::FETCH_ASSOC);
    $settings = [
        "siteName" => $s['site_name'],
        "heroImage" => $s['hero_image'],
        "password" => $s['admin_password'],
        "email" => $s['email'],
        "whatsapp" => $s['whatsapp'],
        "aboutText" => $s['about_text']
    ];

    // Get Products and Variants
    $products = [];
    $stmt = $conn->prepare("SELECT * FROM products");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $p_id = $row['id'];
        $variants = [];
        $v_stmt = $conn->prepare("SELECT * FROM variants WHERE product_id = ?");
        $v_stmt->execute([$p_id]);
        while ($v = $v_stmt->fetch(PDO::FETCH_ASSOC)) {
            $variants[] = [
                "id" => $v['id'],
                "name" => $v['name'],
                "specs" => [
                    ["label" => "Protein", "value" => $v['protein']],
                    ["label" => "Fat", "value" => $v['fat']],
                    ["label" => "Moisture", "value" => $v['moisture']],
                    ["label" => "Shell Content", "value" => $v['shell_content']],
                    ["label" => "Dirt / Impurity", "value" => $v['dirt']]
                ],
                "coa" => $v['coa_url'],
                "images" => [$v['image_url']]
            ];
        }
        $products[] = [
            "id" => $row['id'],
            "title" => $row['title'],
            "short" => $row['short_name'],
            "description" => $row['description'],
            "image" => $row['image_url'],
            "variants" => $variants
        ];
    }

    // Get Leads
    $leads = [];
    $stmt = $conn->prepare("SELECT * FROM leads ORDER BY created_at DESC");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $leads[] = [
            "id" => $row['id'],
            "name" => $row['name'],
            "interest" => $row['interest'],
            "message" => $row['message'],
            "date" => $row['created_at']
        ];
    }

    // Get Stats
    $stats = [];
    $stmt = $conn->prepare("SELECT * FROM stats");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats[$row['stat_key']] = (int)$row['stat_value'];
    }

    echo json_encode(["success" => true, "data" => [
        "settings" => $settings,
        "products" => $products,
        "leads" => $leads,
        "stats" => $stats
    ]]);
}

function validateAdmin($conn, $pass) {
    $stmt = $conn->prepare("SELECT admin_password FROM settings LIMIT 1");
    $stmt->execute();
    $s = $stmt->fetch();
    return $pass === $s['admin_password'];
}

function updateSettings($conn, $p) {
    $stmt = $conn->prepare("UPDATE settings SET site_name=?, hero_image=?, admin_password=?, email=?, whatsapp=?, about_text=? WHERE id=1");
    $stmt->execute([$p->siteName, $p->heroImage, $p->password, $p->email, $p->whatsapp, $p->aboutText]);
    echo json_encode(["success" => true]);
}

function updateProducts($conn, $products) {
    // Simple sync: Clear and re-insert (For small B2B scale)
    $conn->exec("DELETE FROM variants");
    $conn->exec("DELETE FROM products");
    
    foreach ($products as $p) {
        $stmt = $conn->prepare("INSERT INTO products (id, title, short_name, description, image_url) VALUES (?,?,?,?,?)");
        $stmt->execute([$p->id, $p->title, $p->short, $p->description, $p->image]);
        
        foreach ($p->variants as $v) {
            $v_stmt = $conn->prepare("INSERT INTO variants (id, product_id, name, protein, fat, moisture, shell_content, dirt, coa_url, image_url) VALUES (?,?,?,?,?,?,?,?,?,?)");
            
            $protein = ""; $fat = ""; $moisture = ""; $shell = ""; $dirt = "";
            foreach($v->specs as $s) {
                $l = strtolower($s->label);
                if (strpos($l, 'protein') !== false) $protein = $s->value;
                if (strpos($l, 'fat') !== false) $fat = $s->value;
                if (strpos($l, 'moisture') !== false) $moisture = $s->value;
                if (strpos($l, 'shell') !== false) $shell = $s->value;
                if (strpos($l, 'dirt') !== false || strpos($l, 'impurity') !== false) $dirt = $s->value;
            }

            $v_stmt->execute([
                $v->id, $p->id, $v->name, 
                $protein, $fat, $moisture, $shell, $dirt,
                $v->coa, $v->images[0]
            ]);
        }
    }
    echo json_encode(["success" => true]);
}

function addLead($conn, $p) {
    $stmt = $conn->prepare("INSERT INTO leads (name, interest, message) VALUES (?,?,?)");
    $stmt->execute([$p->name, $p->interest, $p->message]);
    echo json_encode(["success" => true]);
}

function syncLeads($conn, $leads) {
    // For deletion/management
    $conn->exec("DELETE FROM leads");
    foreach($leads as $l) {
        $stmt = $conn->prepare("INSERT INTO leads (id, name, interest, message, created_at) VALUES (?,?,?,?,?)");
        $stmt->execute([$l->id, $l->name, $l->interest, $l->message, $l->date]);
    }
    echo json_encode(["success" => true]);
}

function updateStat($conn, $key) {
    $stmt = $conn->prepare("UPDATE stats SET stat_value = stat_value + 1 WHERE stat_key = ?");
    $stmt->execute([$key]);
    echo json_encode(["success" => true]);
}
?>
