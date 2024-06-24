<?php
session_start();

// Check authentication
if (!isset($_SESSION['auth'])) {
    $_SESSION['message'] = "Please login first";
    header('Location: index.php');
    exit();
}

// INCLUDE NECESSARY FILES
include('includes/header.php');
include('includes/orderbar.php');
include('functions/userFunctions.php');

// GET USER ID FROM SESSION
$userId = $_SESSION['user_id'];

// FUNCTION TO GET USER DETAILS
function getOrderDetaild($con, $order_id) {
    // Prepare the SQL query
    $query = "SELECT * FROM order_transac WHERE order_id = ?";
    $stmt = $con->prepare($query);

    // Bind parameters and execute the statement
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    // Get result
    $orderResult = $stmt->get_result();

    // Check if the query executed successfully
    if ($orderResult && $orderResult->num_rows > 0) {
        // Fetch the order details
        $orderDetails = $orderResult->fetch_assoc();

        // Extract the required fields
        $orderId = $orderDetails['id'];
        $orderStatus = $orderDetails['status'];
        $orderSubTotal = $orderDetails['subtotal'];
        $orderAddFee = $orderDetails['additional_fee'];
        $orderGrandTotal = $orderDetails['grand_total'];
    } else {
        // If no results found, set default values
        $orderId = "Not available";
        $orderStatus = "Not available";
        $orderSubTotal = "Not available";
        $orderAddFee = "Not available";
        $orderGrandTotal = "Not available";
    }

    $stmt->close(); // Close the statement

    // Return all variables as an associative array
    return array(
        'orderId' => $orderId,
        'orderStatus' => $orderStatus,
        'orderSubTotal' => $orderSubTotal,
        'orderAddFee' => $orderAddFee,
        'orderGrandTotal' => $orderGrandTotal
    );
}
function getUserDetails($userId) {
    global $con;

    // Check if $con is a valid MySQLi connection
    if (!$con) {
        return false; // Better error handling should be implemented here
    }

    // QUERY TO SELECT USER DETAILS FOR A SPECIFIC USER
    $query = "SELECT * FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if the query executed successfully
    if (!$result) {
        return false; // Better error handling should be implemented here
    }

    // Check if any rows were returned
    if (mysqli_num_rows($result) > 0) {
        $userDetails = mysqli_fetch_assoc($result);
        return $userDetails;
    } else {
       echo "No user details found for user ID: $userId";
        return false;
    }
}

// Reconnect to the database if necessary
if (mysqli_connect_errno()) {
    include('config/dbconnect.php'); // Assuming dbconnect.php contains the connection code
}

$userDetails = getUserDetails($userId);

if ($userDetails) {
    // Fetch order details from session
    if (isset($_GET['id'])) {
        $order_id = $_GET['id'];

        // Call getOrderDetails to fetch order details
        $orderDetails = getOrderDetaild($con, $order_id);

        $orderStatus = $orderDetails['status'];
        $subtotal = $orderDetails['subtotal'];
        $additional_fee = $orderDetails['additional_fee'];
        $grandtotal = $orderDetails['grand_total'];

        // Check if the status is available
        if ($orderStatus !== "Not available") {
            // Display the order status
            echo "Order Status: " . $orderStatus;
        } else {
            // Display a message if the status is not available
            echo "Order status is not available.";
        }
?>

<!-- HTML Section for Payment Details -->
<section class="p-5 p-md-5 mt-4 text-sm-start" style="font-family: 'Poppins'">
    <div class="container">
        <div class="row">
            <div class="col-md-10">
                <h1 style="font-family: 'Suez One', sans-serif; color: #013D67;"><i class="fas fa-coins"></i> Payment Details</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 text-center">
                <!-- Delivery Details Card -->
                <div class="card shadow-sm rounded-3 p-3 mt-4">
                    <h4>Order Status</h4>
                    <div class="p-1">
                        <h6><?= $orderStatus ?></h6>
                    </div>
                </div>
                <div class="card shadow-sm rounded-3 p-3 mt-2">
                    <h5>Delivery Details</h5>
                    <div class="p-1">
                        <h6>Customer Name: <br><?= $userDetails['name'] ?></h6>
                    </div>
                    <div class="p-1">
                        <h6>Contact Number: <br><?= $userDetails['phone'] ?></h6>
                    </div>
                    <div class="p-1">
                        <h6>Address: <br><?= $userDetails['address'] ?></h6>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <!-- Order Summary Card -->
                <div class="card shadow-sm rounded-3 p-3 mt-4 text-center">
                    <div class="row align-items-center">
                        <div class="col-6 col-md-2">
                            <h5>Quantity</h5>
                        </div>
                        <div class="col-6 col-md-4">
                            <h5>Items</h5>
                        </div>
                        <div class="col-md-3 d-none d-md-block">
                            <h5>Price</h5>
                        </div>
                        <div class="col-md-3 d-none d-md-block">
                            <h5>Total</h5>
                        </div>
                    </div>
                </div>

                <!-- Cart Items -->
                <?php
                $cartItems = getProductsByOrderId($order_id);
                foreach ($cartItems as $cartItem) {
                    // Check if product exists
                    if (!isset($cartItem['product_id'])) {
                        ?>
                        <div class="card shadow-sm rounded-3 p-3 mt-2 text-center" style="font-family: 'Poppins'">
                            <span>Product not found</span>
                        </div>
                        <?php
                        continue; // Skip this iteration and proceed to the next item
                    }

                    $itemTotal = $cartItem['quantity'] * $cartItem['price'];
                ?>
                <div class="card shadow-sm rounded-3 p-3 mt-2 text-center">
                    <div class="row align-items-center">
                        <div class="col-6 col-md-2">
                            <h5><?= $cartItem['quantity'] ?></h5>
                        </div>
                        <div class="col-6 col-md-2">
                            <img src="uploads/<?= $cartItem['product_image'] ?>" width="80px" alt="<?= $cartItem['product_name'] ?>" class="rounded-3">
                        </div>
                        <div class="col-md-2 d-none d-md-block">
                            <h5><?= $cartItem['product_name'] ?></h5>
                        </div>
                        <div class="col-md-3 d-none d-md-block">
                            <h5><?= $cartItem['price'] ?></h5>
                        </div>
                        <div class="col-md-3 d-none d-md-block">
                            <h5><span style="font-family: 'Poppins', sans-serif;">₱<?= $itemTotal ?>.00</span></h5>
                        </div>
                    </div>
                </div>
            <?php } ?>

                <div class="card shadow-sm rounded-3 p-3 mt-2">
                    <h5>Order Summary</h5>
                    <!-- Display subtotal, delivery fee, and grand total -->
                    <div class="row align-items-center justify-content-between">
                        <div class="col-6 col-md-6 text-start">
                            <h5>Subtotal:</h5>
                        </div>
                        <div class="col-6 col-md-6 text-end">
                        <h5><span style="font-family: 'Poppins', sans-serif;">₱<?= $subtotal ?>.00</span></h5>
                        </div>
                    </div>
                    <div class="row align-items-center justify-content-between">
                        <div class="col-6 col-md-6 text-start">
                            <h5>Additional Fee:</h5>
                        </div>
                        <div class="col-6 col-md-6 text-end">
                            <h5><span style="font-family: 'Poppins', sans-serif;">₱<?= $additional_fee ?>.00</span></h5>
                        </div>
                    </div>
                    <div class="row align-items-center justify-content-between">
                        <div class="col-6 col-md-6 text-start">
                            <h5>Grand Total:</h5>
                        </div>
                        <div class="col-6 col-md-6 text-end">
                        <h5><span style="font-family: 'Poppins', sans-serif;">₱<?= $grandtotal ?>.00</span></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
    } else {
        echo "<p>No order details found.</p>";
    }
} else {
    echo "<p>No user details found.</p>";
}

// INCLUDE FOOTER
include('includes/footer.php');
?>