<?php
/**
 * Debug AJAX Response - DELETE AFTER TESTING
 */

// Test AJAX response format
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test'])) {
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => true,
        'message' => 'Test response working correctly',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AJAX Test</title>
</head>
<body>
    <h2>AJAX Response Test</h2>
    <button onclick="testAjax()">Test AJAX Response</button>
    <div id="result"></div>

    <script>
    async function testAjax() {
        try {
            const formData = new FormData();
            formData.append('test', '1');
            
            const response = await fetch('ajax_test.php', {
                method: 'POST',
                body: formData
            });
            
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            const responseText = await response.text();
            console.log('Raw response:', responseText);
            
            const result = JSON.parse(responseText);
            console.log('Parsed result:', result);
            
            document.getElementById('result').innerHTML = 
                'Success: ' + result.success + '<br>' +
                'Message: ' + result.message + '<br>' +
                'Time: ' + result.timestamp;
                
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('result').innerHTML = 'Error: ' + error.message;
        }
    }
    </script>
</body>
</html>