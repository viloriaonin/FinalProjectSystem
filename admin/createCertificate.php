<?php
session_start();
require_once __DIR__ . '/../db_connection.php';

// Fetch all documents for the dropdown
$stmt = $pdo->query("SELECT document_id, doc_name FROM documents ORDER BY doc_name ASC");
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Request System</title>
    <style>
        /* Simple styling for clarity */
        body { font-family: Arial, sans-serif; margin: 20px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="text"], input[type="number"], textarea, select {
            width: 100%; max-width: 400px; padding: 8px; margin-top: 5px; box-sizing: border-box;
        }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; margin-top: 20px; }
    </style>
</head>
<body>
        
        
    <h2>Request a New Document</h2>
    
    <label for="doc-select">1. Choose Document Type:</label>
    <select id="doc-select">
        <option value="">-- Select a document --</option>
        <?php foreach ($documents as $doc): ?>
            <option value="<?php echo htmlspecialchars($doc['document_id']); ?>">
                <?php echo htmlspecialchars($doc['doc_name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <div id="dynamic-form-container"></div>

    <script>
        document.getElementById('doc-select').addEventListener('change', function() {
            const documentId = this.value;
            const formContainer = document.getElementById('dynamic-form-container');

            if (documentId) {
                // Use fetch to get the form fields from our new PHP script
                fetch('get_form_fields.php?document_id=' + documentId)
                    .then(response => response.text())
                    .then(html => {
                        // Load the generated form HTML into the container
                        formContainer.innerHTML = html;
                    })
                    .catch(error => {
                        formContainer.innerHTML = '<p>Error loading form. Please try again.</p>';
                        console.error('Error:', error);
                    });
            } else {
                formContainer.innerHTML = ''; // Clear the form if no doc is selected
            }
        });
    </script>

</body>
</html>