<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Real-Time Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e0eafc, #cfdef3);
            color: #333;
            min-height: 100vh;
        }
        .navbar {
            background: #1a1a2e !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 1px;
        }
        .container {
            max-width: 1400px;
            margin-top: 30px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .btn-fetch {
            background: #ff6b6b;
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-fetch:hover {
            background: #ff4d4d;
            transform: translateY(-2px);
        }
        .success-msg, .error-msg {
            font-weight: 500;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .success-msg {
            background: #d4edda;
            color: #155724;
        }
        .error-msg {
            background: #f8d7da;
            color: #721c24;
        }
        .table {
            background: white;
            margin-bottom: 0;
        }
        .table thead th {
            background: #16213e;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            padding: 15px;
        }
        .table tbody td {
            vertical-align: middle;
            padding: 15px;
        }
        .table tbody tr {
            transition: all 0.3s ease;
        }
        .table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
        }
        .spinner {
            display: none;
            margin: 20px auto;
        }
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .new-row {
            animation: fadeIn 0.5s ease;
        }
    </style>
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
</head>
<body>
    {{-- Nabrr --}}
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-store me-2"></i>Product Dashboard</a>
        </div>
    </nav>


    <div class="container">
        <div class="card">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="fs-3 fw-bold"><i class="fas fa-boxes me-2"></i>Product List</h1>
                    <a href="{{ route('products.fetch') }}" class="btn btn-fetch" id="fetch-btn">
                        <i class="fas fa-sync-alt me-2"></i>Fetch Products
                    </a>
                </div>

                @if (session('success'))
                    <p class="success-msg"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</p>
                @endif
                @if (session('error'))
                    <p class="error-msg"><i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}</p>
                @endif

                <!-- Loading Spinner -->
                <div class="spinner-border text-primary spinner" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>

                <!-- Product Table -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col"><i class="fas fa-list-ol me-2"></i>Ser. No</th>
                                <th scope="col"><i class="fas fa-tag me-2"></i>Name</th>
                                <th scope="col"><i class="fas fa-info-circle me-2"></i>Description</th>
                                <th scope="col"><i class="fas fa-dollar-sign me-2"></i>Price</th>
                            </tr>
                        </thead>
                        <tbody id="product-list">
                            @foreach ($products as $product)
                                <tr>
                                    <td>{{ $loop->iteration + $products->firstItem() - 1 }}</td>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ Str::limit($product->description, 50) }}</td>
                                    <td>${{ number_format($product->price, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Links -->
                <div class="pagination">
                    {{ $products->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        Pusher.logToConsole = true;


        var pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
            cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
            forceTLS: true
        });


        var channel = pusher.subscribe('products');
        var serialNo = document.querySelectorAll('#product-list tr').length + {{ $products->firstItem() - 1 }};


        channel.bind('product.updated', function(data) {
            var product = data.product;
            var tbody = document.getElementById('product-list');
            var row = document.createElement('tr');
            row.classList.add('new-row');
            row.innerHTML = `
                <td>${serialNo++}</td>
                <td>${product.name}</td>
                <td>${product.description.substring(0, 50)}${product.description.length > 50 ? '...' : ''}</td>
                <td>$${parseFloat(product.price).toFixed(2)}</td>
            `;
            tbody.appendChild(row);
        });


        document.getElementById('fetch-btn').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.spinner').style.display = 'block';

            fetch('{{ route('products.fetch') }}', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                document.querySelector('.spinner').style.display = 'none';
                if (data.success) {
                    document.querySelector('.success-msg')?.remove();
                    var msg = document.createElement('p');
                    msg.className = 'success-msg';
                    msg.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + data.success;
                    document.querySelector('.card-body').insertBefore(msg, document.querySelector('.spinner'));
                }
            })
            .catch(() => {
                document.querySelector('.spinner').style.display = 'none';
                alert('Error fetching products');
            });
        });
    </script>
</body>
</html>
