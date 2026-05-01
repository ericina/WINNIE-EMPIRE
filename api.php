<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST');

$products_file = 'products.json';
$orders_file = 'orders.json';

// Initialize JSON files if they don't exist
if (!file_exists($products_file)) file_put_contents($products_file, '[]');
if (!file_exists($orders_file)) file_put_contents($orders_file, '[]');

$action = $_GET['action'] ?? '';

// Handle GET Requests (Fetching Data)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'get_products') {
        echo file_get_contents($products_file);
        exit;
    }
    if ($action === 'get_orders') {
        echo file_get_contents($orders_file);
        exit;
    }
}

// Handle POST Requests (Saving Data)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // 1. Post a new product
    if ($action === 'add_product') {
        $products = json_decode(file_get_contents($products_file), true);
        $new_product = [
            'id' => time(),
            'name' => htmlspecialchars($input['name']),
            'category' => htmlspecialchars($input['category']),
            'price' => (float)$input['price'],
            'desc' => htmlspecialchars($input['desc']),
            'image' => htmlspecialchars($input['image']) // URL of the image
        ];
        $products[] = $new_product;
        file_put_contents($products_file, json_encode($products, JSON_PRETTY_PRINT));
        echo json_encode(["status" => "success", "message" => "Product added successfully"]);
        exit;
    }

    // 2. Save a new order (called by the main site after checkout)
    if ($action === 'add_order') {
        $orders = json_decode(file_get_contents($orders_file), true);
        $new_order = [
            'id' => 'ORD-' . strtoupper(substr(uniqid(), -6)),
            'customer' => $input['customer'],
            'items' => $input['items'],
            'total' => $input['total'],
            'status' => 'pending', // default status
            'reference' => $input['reference'],
            'date' => date('Y-m-d H:i:s')
        ];
        $orders[] = $new_order;
        file_put_contents($orders_file, json_encode($orders, JSON_PRETTY_PRINT));
        echo json_encode(["status" => "success", "message" => "Order saved"]);
        exit;
    }

    // 3. Mark an order as completed
    if ($action === 'update_order_status') {
        $orders = json_decode(file_get_contents($orders_file), true);
        $order_id = $input['id'];
        $new_status = $input['status'];
        
        foreach ($orders as &$order) {
            if ($order['id'] === $order_id) {
                $order['status'] = $new_status;
                break;
            }
        }
        file_put_contents($orders_file, json_encode($orders, JSON_PRETTY_PRINT));
        echo json_encode(["status" => "success", "message" => "Order marked as completed"]);
        exit;
    }
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
?>
