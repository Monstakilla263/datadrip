<?php
require_once 'session_check.php';
require_once 'db_connection.php';
require_once 'ensure_cart_table.php'; // Ensure cart table structure is correct
// Check if either user or admin is logged in
if (!isset($_SESSION['username']) && !isset($_SESSION['admin_username'])) {
    header('Location: index.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalog | DataDrip</title>
    <link rel="stylesheet" href="styles.css">
    <style>
    /* Copy all the styles from catalog.html */
    body {
        background: #fafafa;
    }
    header {
        background: #333;
        color: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 100;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 2rem;
    }
    .logo-container img {
        height: 400px;
        width: auto;
        position: static;
        margin-top: 24px;
    }

    .nav-auth {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    nav ul {
        display: flex;
        list-style: none;
        gap: 20px;
        align-items: center;
    }
    nav ul li a {
        color: #fff;
        font-size: 20px;
        text-decoration: none;
        transition: color 0.2s;
    }
    nav ul li a:hover {
        color: #d48e0b;
    }
    .catalog-main {
        width: 100%;
        margin: 120px 0 0 0;
        display: flex;
        justify-content: center;
        gap: 48px;
        background: #fafafa;
        min-height: 80vh;
    }
    .catalog-sidebar {
        width: 300px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        padding: 32px 0 32px 0;
        height: fit-content;
        border-right: 1px solid #e5e5e5;
        display: flex;
        flex-direction: column;
        gap: 0;
        margin-top: 70px;
        margin-left: 20px;
    }

    .catalog-sidebar h2 {
        font-size: 1.25rem;
        margin: 0 32px 1.5rem 32px;
        color: #222;
        font-weight: 700;
        letter-spacing: 0.01em;
    }
    .catalog-sidebar .filter-group {
        margin-bottom: 0;
        padding: 0 32px 1.5rem 32px;
        border-bottom: 1px solid #ececec;
    }
    .catalog-sidebar .filter-group:last-child {
        border-bottom: none;
    }
    .catalog-sidebar .filter-group h3 {
        font-size: 1.05rem;
        margin-bottom: 0.7rem;
        color: #444;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .catalog-sidebar ul {
        list-style: none;
        padding: 0;
    }
    .catalog-sidebar li {
        margin-bottom: 0.5rem;
    }
    .catalog-sidebar label {
        font-size: 1rem;
        color: #333;
        cursor: pointer;
        z-index: 2;
        position: relative;
        background: #fff;
    }
    .catalog-sidebar input[type="checkbox"] {
        accent-color: #222;
        margin-right: 8px;
    }
    .catalog-products {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 24px;
        margin-bottom: 0;
        width: 100%;
        padding: 0 24px;
        max-width: 1400px;
        margin-left: auto;
        margin-right: auto;
    }

    @media (max-width: 1200px) {
        .catalog-products {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 900px) {
        .catalog-products {
            grid-template-columns: repeat(2, 1fr);
            padding: 0 16px;
        }
    }

    @media (max-width: 600px) {
        .catalog-products {
            grid-template-columns: 1fr;
            padding: 0 16px;
        }
    }
    .product-card {
        background: #fff;
        border-radius: 10px;
        border: 1px solid #e5e5e5;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-width: 0;
        box-shadow: none;
        transition: all 0.3s ease;
        width: 100%;
        max-width: 340px;
        margin: 0 auto;
        position: relative;
    }
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        border-color: #d48e0b;
    }
    .product-card img {
        width: 100%;
        aspect-ratio: 1/1;
        object-fit: cover;
        background: #ededed;
        display: block;
        transition: transform 0.3s ease;
    }
    .product-card:hover img {
        transform: scale(1.05);
    }
    .product-info {
        padding: 18px 18px 16px 18px;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        align-items: flex-start;
        background: #fff;
        transition: background-color 0.3s ease;
    }
    .product-card:hover .product-info {
        background-color: #f8f8f8;
    }
    .product-info h3 {
        font-size: 1.08rem;
        font-weight: 700;
        color: #222;
        margin-bottom: 0.1rem;
        letter-spacing: 0.01em;
        transition: color 0.3s ease;
    }
    .product-card:hover .product-info h3 {
        color: #d48e0b;
    }
    .product-price {
        font-size: 1.05rem;
        font-weight: 700;
        color: #111;
        margin-top: 0.2rem;
        letter-spacing: 0.01em;
        transition: color 0.3s ease;
    }
    .product-card:hover .product-price {
        color: #d48e0b;
    }
    footer {
        background-color: #333;
        color: white;
        padding: 1.5rem 2rem;
        text-align: center;
        margin-top: 30px;
    }
    .pagination-wrapper {
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 32px 0 0 0;
        margin-bottom: 20px;
    }
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 18px;
        font-family: inherit;
    }
    .pagination-arrow, .pagination-num {
        color: #888;
        background: none;
        border: none;
        font-size: 1.3rem;
        padding: 6px 14px;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.2s, color 0.2s;
    }
    .pagination-num.active {
        background: #f44325;
        color: #fff;
        font-weight: bold;
    }
    .pagination-arrow:hover, .pagination-num:hover {
        background: #eee;
        color: #222;
    }
    .pagination-ellipsis {
        color: #bbb;
        font-size: 1.3rem;
        padding: 0 8px;
    }
    @media (max-width: 900px) {
        .catalog-products {
            grid-template-columns: 1fr 1fr;
            padding: 0 8px;
        }
    }
    @media (max-width: 600px) {
        .catalog-products {
            grid-template-columns: 1fr;
            padding: 0 2vw;
        }
    }
    /* Add modal styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 1000;
        backdrop-filter: blur(5px);
    }

    .modal-content {
        position: relative;
        background-color: #fff;
        margin: 15vh auto;
        padding: 20px;
        width: 90%;
        max-width: 500px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    .close-modal {
        position: absolute;
        right: 20px;
        top: 15px;
        font-size: 24px;
        cursor: pointer;
        color: #666;
        transition: color 0.3s;
    }

    .close-modal:hover {
        color: #000;
    }

    .modal-product-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }

    .modal-product-image {
        width: 200px;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
    }

    .size-options {
        display: flex;
        gap: 10px;
        margin: 20px 0;
    }

    .size-option {
        padding: 10px 20px;
        border: 2px solid #ddd;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .size-option:hover {
        border-color: #d48e0b;
    }

    .size-option.selected {
        background-color: #d48e0b;
        color: white;
        border-color: #d48e0b;
    }

    .add-to-cart-btn {
        background-color: #d48e0b;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .add-to-cart-btn:hover {
        background-color: #b37609;
        transform: translateY(-2px);
    }

    .add-to-cart-btn:disabled {
        background-color: #ccc;
        cursor: not-allowed;
        transform: none;
    }

    /* Add quantity selector styles */
    .quantity-selector {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 10px 0;
    }

    .quantity-btn {
        width: 30px;
        height: 30px;
        border: none;
        background-color: #f0f0f0;
        border-radius: 5px;
        cursor: pointer;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.3s;
    }

    .quantity-btn:hover {
        background-color: #e0e0e0;
    }

    .quantity-input {
        width: 50px;
        height: 30px;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }

    .quantity-input::-webkit-inner-spin-button,
    .quantity-input::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    </style>
</head>
<body>
    <header>
        <div class="top-header">
            <div class="logo-container">
                <img src="images/datadrip.png" alt="DataDrip Logo">
            </div>
        </div>
        <div class="nav-auth">
            <nav>
                <ul>
                    <li>
                        <a href="<?php
                            if (isset($_SESSION['admin_username'])) {
                                echo 'admin_dashboard.php';
                            } else {
                                echo 'dashboard.php';
                            }
                        ?>">Home</a>
                    </li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="catalog.php">Catalog</a></li>
                        <li><a href="contact.php">Contact</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <span class="username" style="color: orange;">
                    <?php
                        if (isset($_SESSION['username'])) {
                            echo htmlspecialchars($_SESSION['username']);
                        } elseif (isset($_SESSION['admin_username'])) {
                            echo htmlspecialchars($_SESSION['admin_username']);
                        }
                    ?>
                </span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>
    <main class="catalog-main">
        <aside class="catalog-sidebar">
            <h2><a style="color: #ffffff;">zz.</a>Filters</h2>
            <div class="filter-group">
                <h3>Category</h3>
                <ul>
                    <li><input type="checkbox" id="tshirts" value="tshirts"> <label for="tshirts">T-Shirts</label></li>
                    <li><input type="checkbox" id="hoodies" value="hoodies"> <label for="hoodies">Hoodies</label></li>
                    <li><input type="checkbox" id="jackets" value="jackets"> <label for="jackets">Jackets</label></li>
                    <li><input type="checkbox" id="lowerwear" value="lowerwear"> <label for="lowerwear">Lower wear</label></li>
                    <li><input type="checkbox" id="bags" value="bags"> <label for="bags">Bags</label></li>
                </ul>
            </div>
            <div class="filter-group">
                <h3 style="margin-top: 10px;">Gender</h3>
                <ul>
                    <li><input type="checkbox" id="men"> <label for="men">Men</label></li>
                    <li><input type="checkbox" id="women"> <label for="women">Women</label></li>
                </ul>
            </div>
            <div class="filter-group">
                <h3 style="margin-top: 10px;">Search</h3>
                <div class="search-container">
                    <input type="text" id="searchInput" placeholder="Search..." class="search-input">
                </div>
            </div>
        </aside>
        <section class="catalog-products">
            <?php
            // Fetch all products from database
            $query = "SELECT p.*, c.name as category_name, g.name as gender_name 
                     FROM products p 
                     JOIN categories c ON p.category_id = c.id 
                     JOIN genders g ON p.gender_id = g.id 
                     ORDER BY 
                        c.name,
                        p.gender_id,
                        CAST(SUBSTRING_INDEX(p.name, ' ', -1) AS UNSIGNED),
                        p.name";
            $result = $conn->query($query);
            
            // Calculate total pages
            $total_products = $result->num_rows;
            $products_per_page = 20;
            $total_pages = ceil($total_products / $products_per_page);
            
            // Get current page from URL or default to 1
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $page = max(1, min($page, $total_pages));
            
            // Calculate offset
            $offset = ($page - 1) * $products_per_page;
            
            // Fetch products for current page
            $products = $result->fetch_all(MYSQLI_ASSOC);
            
            // Display products
            foreach ($products as $index => $product) {
                $page_number = ceil(($index + 1) / $products_per_page);
                $display_style = $page_number == $page ? '' : 'style="display:none;"';
                ?>
                <div class="product-card page-<?php echo $page_number; ?>" 
                     data-category="<?php echo strtolower(str_replace(' ', '', $product['category_name'])); ?>" 
                     data-gender="<?php echo strtolower($product['gender_name']); ?>" 
                     data-id="<?php echo $product['id']; ?>" 
                     <?php echo $display_style; ?>>
                    <img src="<?php echo $product['image_url']; ?>" 
                         alt="<?php echo $product['name']; ?>">
                    <div class="product-info">
                        <h3><?php echo $product['name']; ?></h3>
                        <p class="product-price">₱<?php echo $product['price']; ?></p>
<p class="product-quantity"><?php echo $product['inventory'] == 0 ? 'Out of Stock' : 'Stock: ' . $product['inventory']; ?></p>
                    </div>
                </div>
                <?php
            }
            ?>

            <!-- Add a container for dynamic product cards -->
            <div id="dynamic-products"></div>

        </section>
    </main>
    <div class="pagination-wrapper">
        <div class="pagination">
            <a href="#" class="pagination-arrow" data-page="prev">&#60;</a>
            <a href="#" class="pagination-num active" data-page="1">1</a>
            <a href="#" class="pagination-num" data-page="2">2</a>
            <a href="#" class="pagination-num" data-page="3">3</a>
            <a href="#" class="pagination-num" data-page="4">4</a>
            <a href="#" class="pagination-num" data-page="5">5</a>
            <span class="pagination-ellipsis">...</span>
            <a href="#" class="pagination-arrow" data-page="next">&#62;</a>
        </div>
    </div>
    <footer>
        <div class="footer-content">
            <div class="footer-links"></div>
            <div class="social-icons"></div>
        </div>
        <div class="copyright">
            &copy; 2025 DataDrip. All Rights Reserved.
        </div>
    </footer>
    <!-- Add modal HTML structure -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-product-info">
                <img src="" alt="" class="modal-product-image">
                <h2 class="modal-product-name"></h2>
                <p class="modal-product-price"></p>
                <div class="size-options">
                    <!-- Size options will be dynamically added here -->
                </div>
                <!-- Add quantity selector -->
                <div class="quantity-selector">
                    <button class="quantity-btn minus-btn">-</button>
                    <input type="number" class="quantity-input" value="1" min="1" max="10">
                    <button class="quantity-btn plus-btn">+</button>
                </div>
                <button class="add-to-cart-btn" disabled>Add to Cart</button>
            </div>
        </div>
    </div>

    <script>
    const products = document.querySelectorAll('.product-card');
    const paginationNums = document.querySelectorAll('.pagination-num');
    const prevBtn = document.querySelector('.pagination-arrow[data-page="prev"]');
    const nextBtn = document.querySelector('.pagination-arrow[data-page="next"]');
    const paginationWrapper = document.querySelector('.pagination-wrapper');
    const searchInput = document.getElementById('searchInput');
    let currentPage = 1;
    const totalPages = 5;
    let selectedSize = '';
    let activeProduct = null;

    function showPage(page) {
        currentPage = page;
        filterAndPaginate();
        paginationNums.forEach(num => {
            num.classList.toggle('active', Number(num.dataset.page) === page);
        });
    }

    function filterAndPaginate() {
        const searchTerm = searchInput.value.toLowerCase();
        const checkedCategories = Array.from(document.querySelectorAll('.filter-group input[type=checkbox][value]')).filter(cb => cb.checked).map(cb => cb.value);
        const checkedGenders = Array.from(document.querySelectorAll('.filter-group input[type=checkbox][id="men"], .filter-group input[type=checkbox][id="women"]')).filter(cb => cb.checked).map(cb => cb.id);
        
        products.forEach(card => {
            const productName = card.querySelector('h3').textContent.toLowerCase();
            const matchesSearch = productName.includes(searchTerm);
            const matchesCategory = checkedCategories.length === 0 || checkedCategories.includes(card.getAttribute('data-category'));
            const matchesGender = checkedGenders.length === 0 || checkedGenders.includes(card.getAttribute('data-gender'));
            
            if (matchesSearch && matchesCategory && matchesGender) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });

        // Show/hide pagination based on filters
        if (checkedCategories.length === 0 && checkedGenders.length === 0 && searchTerm === '') {
            // No filters: show only current page, show pagination
            products.forEach(card => {
                card.style.display = card.classList.contains('page-' + currentPage) ? '' : 'none';
            });
            paginationWrapper.style.display = '';
        } else {
            // Filter active: show all matching, hide pagination
            paginationWrapper.style.display = 'none';
        }
    }

    // Add search input event listener
    searchInput.addEventListener('input', filterAndPaginate);

    // Add event listeners for all checkboxes
    document.querySelectorAll('.filter-group input[type=checkbox]').forEach(cb => {
        cb.addEventListener('change', filterAndPaginate);
    });

    paginationNums.forEach(num => {
        num.addEventListener('click', e => {
            e.preventDefault();
            showPage(Number(num.dataset.page));
        });
    });
    prevBtn.addEventListener('click', e => {
        e.preventDefault();
        if (currentPage > 1) showPage(currentPage - 1);
    });
    nextBtn.addEventListener('click', e => {
        e.preventDefault();
        if (currentPage < totalPages) showPage(currentPage + 1);
    });
    // Show first page on load
    showPage(1);

    // Add modal functionality
    const modal = document.getElementById('productModal');
    const closeModal = document.querySelector('.close-modal');
    const addToCartBtn = document.querySelector('.add-to-cart-btn');

    function openModal(product) {
        // Store the active product
        activeProduct = product;
        
        const productId = product.dataset.id;
        const productName = product.querySelector('h3').textContent;
        const productPrice = product.querySelector('.product-price').textContent;
        const productImage = product.querySelector('img').src;
        const productCategory = product.dataset.category;

        modal.querySelector('.modal-product-name').textContent = productName;
        modal.querySelector('.modal-product-price').textContent = productPrice;
        modal.querySelector('.modal-product-image').src = productImage;

        // Reset quantity to 1 when opening modal
        quantityInput.value = 1;

        const sizeOptions = modal.querySelector('.size-options');
        sizeOptions.innerHTML = '';

        // Show size options for clothing items
        if (productCategory !== 'bags') {
            const sizes = ['S', 'M', 'L', 'XL'];
            sizes.forEach(size => {
                const sizeBtn = document.createElement('div');
                sizeBtn.className = 'size-option';
                sizeBtn.textContent = size;
                sizeBtn.onclick = () => {
                    document.querySelectorAll('.size-option').forEach(btn => btn.classList.remove('selected'));
                    sizeBtn.classList.add('selected');
                    selectedSize = size;
                    addToCartBtn.disabled = false;
                };
                sizeOptions.appendChild(sizeBtn);
            });
            addToCartBtn.disabled = true;
            sizeOptions.style.display = 'flex';
        } else {
            // For bags, no size selection needed
            sizeOptions.style.display = 'none';
            addToCartBtn.disabled = false;
        }

        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
    }

    // Add click event to all product cards
    products.forEach(product => {
        product.addEventListener('click', () => openModal(product));
    });

    // Close modal when clicking the close button or outside the modal
    closeModal.onclick = () => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        selectedSize = '';
        addToCartBtn.disabled = true;
        activeProduct = null; // Reset active product
    };

    window.onclick = (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            selectedSize = '';
            addToCartBtn.disabled = true;
            activeProduct = null; // Reset active product
        }
    };

    // Add quantity selector functionality
    const quantityInput = modal.querySelector('.quantity-input');
    const minusBtn = modal.querySelector('.minus-btn');
    const plusBtn = modal.querySelector('.plus-btn');

    minusBtn.addEventListener('click', () => {
        let currentValue = parseInt(quantityInput.value);
        if (currentValue > 1) {
            quantityInput.value = currentValue - 1;
        }
    });

    plusBtn.addEventListener('click', () => {
        let currentValue = parseInt(quantityInput.value);
        if (currentValue < 10) {
            quantityInput.value = currentValue + 1;
        }
    });

    quantityInput.addEventListener('change', () => {
        let value = parseInt(quantityInput.value);
        if (value < 1) {
            quantityInput.value = 1;
        } else if (value > 10) {
            quantityInput.value = 10;
        }
    });

    // Update add to cart functionality
    addToCartBtn.addEventListener('click', () => {
        if (!activeProduct) {
            console.error('No active product selected');
            alert('Error: No product selected');
            return;
        }

        const productId = activeProduct.dataset.id;
        const productName = modal.querySelector('.modal-product-name').textContent;
        const productPrice = modal.querySelector('.modal-product-price').textContent;
        const productCategory = activeProduct.dataset.category;
        const quantity = parseInt(quantityInput.value);
        const availableStockText = activeProduct.querySelector('.product-quantity').textContent;
        const availableStock = availableStockText === 'Out of Stock' ? 0 : parseInt(availableStockText.replace('Stock: ', ''));

        if (quantity > availableStock) {
            alert('Error: Selected quantity exceeds available stock. Only ' + availableStock + ' items are available.');
            return;
        }

        console.log('Adding to cart:', {
            productId,
            productName,
            productPrice,
            productCategory,
            quantity
        });

        // Create form data to send to server
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('name', productName);
        formData.append('price', productPrice.replace('₱', '').trim());
        formData.append('category', productCategory);
        formData.append('quantity', quantity);
        formData.append('size', productCategory === 'bags' ? 'one-size' : selectedSize);

        // Send POST request to add item to cart
        fetch('add_to_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Item added to cart successfully!');
                modal.style.display = 'none';
                document.body.style.overflow = '';
                activeProduct = null;
            } else {
                console.error('Server error:', data.error);
                alert(data.error || 'Error adding item to cart. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding item to cart. Please try again.');
        });
    });
    </script>
</body>
</html> 