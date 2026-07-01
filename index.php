<?php
require 'config.php';

// Form field values (kept blank unless a search/edit populates them)
$isbn = $title = $copyright = $edition = $price = $quantity = "";
$msg = "";
$focusIsbn = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Always start from whatever was submitted in the form
    $isbn      = trim($_POST['isbn'] ?? '');
    $title     = trim($_POST['title'] ?? '');
    $copyright = trim($_POST['copyright'] ?? '');
    $edition   = trim($_POST['edition'] ?? '');
    $price     = trim($_POST['price'] ?? '');
    $quantity  = trim($_POST['quantity'] ?? '');

    // ---------------- SEARCH ----------------
    if ($action === 'search') {
        $stmt = $db->prepare("SELECT * FROM Books WHERE ISBN = ?");
        $stmt->execute([$isbn]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $title     = $row['Title'];
            $copyright = $row['Copyright'];
            $edition   = $row['Edition'];
            $price     = $row['Price'];
            $quantity  = $row['Quantity'];
            $msg = "ITEM IS FOUND";
        } else {
            // Do not display anything in the rest of the fields
            $title = $copyright = $edition = $price = $quantity = "";
            $msg = "ITEM NOT FOUND";
        }
    }

    // ---------------- EDIT ----------------
    elseif ($action === 'edit') {
        // Blank form check (everything besides ISBN empty = nothing to edit)
        if ($title === '' && $copyright === '' && $edition === '' && $price === '' && $quantity === '') {
            $msg = "NO RECORD TO EDIT";
        } else {
            $check = $db->prepare("SELECT ISBN FROM Books WHERE ISBN = ?");
            $check->execute([$isbn]);

            if (!$check->fetch()) {
                $msg = "ISBN# IS NOT FOUND";
            } else {
                $stmt = $db->prepare("UPDATE Books SET Title=?, Copyright=?, Edition=?, Price=?, Quantity=? WHERE ISBN=?");
                $stmt->execute([$title, $copyright, $edition, $price, $quantity, $isbn]);
                $msg = "RECORD SUCCESSFULLY UPDATED";
            }
        }
    }

    // ---------------- DELETE ----------------
    elseif ($action === 'delete') {
        if ($isbn === '') {
            $focusIsbn = true;
            $msg = "ISBN# IS NOT FOUND";
        } else {
            $check = $db->prepare("SELECT ISBN FROM Books WHERE ISBN = ?");
            $check->execute([$isbn]);

            if (!$check->fetch()) {
                $msg = "ISBN# IS NOT FOUND";
            } else {
                $stmt = $db->prepare("DELETE FROM Books WHERE ISBN = ?");
                $stmt->execute([$isbn]);
                $msg = "RECORD SUCCESSFULLY DELETED";
                $isbn = $title = $copyright = $edition = $price = $quantity = "";
            }
        }
    }

    // ---------------- ADD ----------------
    elseif ($action === 'add') {
        if ($isbn === '' && $title === '' && $copyright === '' && $edition === '' && $price === '' && $quantity === '') {
            $msg = "NO RECORD TO ADD";
            $focusIsbn = true;
        } else {
            $check = $db->prepare("SELECT ISBN FROM Books WHERE ISBN = ?");
            $check->execute([$isbn]);

            if ($check->fetch()) {
                $msg = "RECORD ALREADY EXISTS";
                $focusIsbn = true;
            } else {
                $stmt = $db->prepare("INSERT INTO Books (ISBN, Title, Copyright, Edition, Price, Quantity) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$isbn, $title, $copyright, $edition, $price, $quantity]);
                $msg = "RECORD SUCCESSFULLY SAVED";
                $isbn = $title = $copyright = $edition = $price = $quantity = "";
            }
        }
    }
}

// ---------------- LIST (always refreshed) ----------------
$books = $db->query("SELECT * FROM Books ORDER BY ISBN")->fetchAll(PDO::FETCH_ASSOC);
$grandQty = 0;
$grandTotal = 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Book Management System</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .form-group { margin-bottom: 8px; }
        label { display: inline-block; width: 100px; font-weight: bold; }
        input { padding: 5px; width: 200px; }
        button { padding: 8px 18px; margin: 4px; cursor: pointer; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 6px 10px; text-align: left; }
        th { background: #f2f2f2; }
        .prompt { margin-top: 15px; padding: 10px; border: 1px solid #ccc; background: #fffacd; font-weight: bold; }
        .totals { font-weight: bold; background: #e8e8e8; }
    </style>
</head>
<body>

<h3>Book Management System</h3>

<form method="POST">
    <div class="form-group">
        <label>ISBN #:</label>
        <input type="text" name="isbn" id="isbn" value="<?php echo htmlspecialchars($isbn); ?>">
    </div>
    <div class="form-group">
        <label>Title:</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>">
    </div>
    <div class="form-group">
        <label>Copyright:</label>
        <input type="text" name="copyright" value="<?php echo htmlspecialchars($copyright); ?>">
    </div>
    <div class="form-group">
        <label>Edition:</label>
        <input type="text" name="edition" value="<?php echo htmlspecialchars($edition); ?>">
    </div>
    <div class="form-group">
        <label>Price:</label>
        <input type="text" name="price" value="<?php echo htmlspecialchars($price); ?>">
    </div>
    <div class="form-group">
        <label>Quantity:</label>
        <input type="text" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>">
    </div>

    <div style="margin-top: 15px;">
        <button type="submit" name="action" value="search">SEARCH</button>
        <button type="submit" name="action" value="edit">EDIT</button>
        <button type="submit" name="action" value="delete">DELETE</button>
        <button type="submit" name="action" value="add">ADD</button>
    </div>
</form>

<div class="prompt"><?php echo $msg ?: '&nbsp;'; ?></div>

<table>
    <thead>
        <tr>
            <th>ISBN</th><th>Title</th><th>Copyright</th><th>Edition</th>
            <th>Price</th><th>Quantity</th><th>TOTAL</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($books) > 0): ?>
            <?php foreach ($books as $b):
                $rowTotal = $b['Price'] * $b['Quantity'];
                $grandQty += $b['Quantity'];
                $grandTotal += $rowTotal;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($b['ISBN']); ?></td>
                <td><?php echo htmlspecialchars($b['Title']); ?></td>
                <td><?php echo htmlspecialchars($b['Copyright']); ?></td>
                <td><?php echo htmlspecialchars($b['Edition']); ?></td>
                <td><?php echo number_format($b['Price'], 2); ?></td>
                <td><?php echo $b['Quantity']; ?></td>
                <td><?php echo number_format($rowTotal, 2); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7">No records found</td></tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr class="totals">
            <td colspan="5" style="text-align:right;">TOTALS:</td>
            <td><?php echo $grandQty; ?></td>
            <td><?php echo number_format($grandTotal, 2); ?></td>
        </tr>
    </tfoot>
</table>

<?php if ($focusIsbn): ?>
<script>document.getElementById('isbn').focus();</script>
<?php endif; ?>

</body>
</html>
