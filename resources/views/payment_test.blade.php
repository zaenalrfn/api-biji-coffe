<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <title>Midtrans Snap Sandbox Test</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Midtrans Snap JS (Sandbox) -->
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ env('MIDTRANS_CLIENT_KEY') }}">
    </script>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: #ffffff;
            padding: 24px;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin-bottom: 16px;
            font-size: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            background-color: #00a7e1;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }

        button:hover {
            background-color: #008bbd;
        }

        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        #result {
            margin-top: 15px;
            padding: 10px;
            background: #e9ecef;
            border-radius: 4px;
            font-size: 12px;
            display: none;
            word-wrap: break-word;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Midtrans Snap Test</h1>

        <div class="form-group">
            <label>Bearer Token (Login First):</label>
            <input type="text" id="token" placeholder="Paste your API Token here..." />
        </div>

        <p style="font-size: 0.9em; color: #666; text-align: center;">
            Creates a dummy order (Rp 50,000) and opens Snap.
        </p>

        <button id="pay-btn" onclick="createOrder()">Bayar Sekarang</button>

        <div id="result"></div>
    </div>

    <script>
        async function createOrder() {
            const token = document.getElementById('token').value;
            const btn = document.getElementById('pay-btn');
            const resultDiv = document.getElementById('result');

            if (!token) {
                alert('Please enter a Bearer Token!');
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Creating Order...';
            resultDiv.style.display = 'none';

            try {
                // 1. Call API to Create Order
                const response = await fetch('/api/orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify({
                        shipping_address: {
                            recipient_name: "Test User",
                            address_line: "Jl. Testing No. 1",
                            city: "Jakarta",
                            state: "DKI Jakarta",
                            country: "Indonesia",
                            zip_code: "12345"
                        },
                        payment_method: "Midtrans"
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to create order');
                }

                console.log("Order Created:", data);

                // 2. Open Snap Popup
                if (data.snap_token) {
                    snap.pay(data.snap_token, {
                        onSuccess: function (result) {
                            console.log("Payment Success:", result);
                            resultDiv.style.display = 'block';
                            resultDiv.innerText = "SUCCESS: " + JSON.stringify(result, null, 2);
                            alert("Pembayaran berhasil!");
                        },
                        onPending: function (result) {
                            console.log("Payment Pending:", result);
                            resultDiv.style.display = 'block';
                            resultDiv.innerText = "PENDING: " + JSON.stringify(result, null, 2);
                            alert("Pembayaran pending.");
                        },
                        onError: function (result) {
                            console.log("Payment Error:", result);
                            resultDiv.style.display = 'block';
                            resultDiv.innerText = "ERROR: " + JSON.stringify(result, null, 2);
                            alert("Pembayaran gagal.");
                        },
                        onClose: function () {
                            alert("Popup pembayaran ditutup.");
                        }
                    });
                } else {
                    throw new Error("No snap_token received in response.");
                }

            } catch (error) {
                console.error(error);
                alert("Error: " + error.message);
            } finally {
                btn.disabled = false;
                btn.innerText = 'Bayar Sekarang';
            }
        }
    </script>
</body>

</html>