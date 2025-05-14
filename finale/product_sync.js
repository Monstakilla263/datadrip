// Listen for product deletion events
window.addEventListener('productDeleted', function(e) {
    const productId = e.detail;
    
    // Remove product from catalog if it exists
    const productCard = document.querySelector(`[data-product-id="${productId}"]`);
    if (productCard) {
        productCard.remove();
    }
});

// Listen for product addition events
window.addEventListener('productAdded', function(e) {
    const productData = e.detail;
    
    // Create new product card
    const productCard = document.createElement('div');
    productCard.className = 'product-card';
    productCard.setAttribute('data-category', productData.category);
    productCard.setAttribute('data-gender', productData.gender);
    productCard.setAttribute('data-id', productData.id);
    productCard.style.display = 'none';

    // Create product card content
    productCard.innerHTML = `
        <img src="${productData.image_url}" alt="${productData.name}">
        <div class="product-info">
            <h3>${productData.name}</h3>
            <p class="product-price">₱${productData.price}</p>
        </div>
    `;

    // Add to dynamic products container
    const dynamicProducts = document.getElementById('dynamic-products');
    if (dynamicProducts) {
        dynamicProducts.appendChild(productCard);
        
        // Show the card with animation
        setTimeout(() => {
            productCard.style.display = 'block';
            productCard.style.opacity = '1';
            productCard.style.transform = 'scale(1)';
        }, 100);

        // Apply existing filters
        applyFilters();
    }
});

// Function to trigger product addition event
function triggerProductAddition(productData) {
    // Instead of triggering an event, directly refresh the catalog
    window.location.reload();
}

// Apply existing filters to new products
function applyFilters() {
    const categoryFilters = document.querySelectorAll('.filter-group input[type="checkbox"][id^="category"]');
    const genderFilters = document.querySelectorAll('.filter-group input[type="checkbox"][id^="gender"]');
    const searchInput = document.getElementById('searchInput');

    // Get current filter states
    const activeCategories = Array.from(categoryFilters)
        .filter(filter => filter.checked)
        .map(filter => filter.value);
    
    const activeGenders = Array.from(genderFilters)
        .filter(filter => filter.checked)
        .map(filter => filter.value);
    
    const searchTerm = searchInput.value.toLowerCase();

    // Apply filters to all product cards
    document.querySelectorAll('.product-card').forEach(card => {
        const category = card.getAttribute('data-category');
        const gender = card.getAttribute('data-gender');
        const title = card.querySelector('h3').textContent.toLowerCase();
        
        const matchesCategory = activeCategories.length === 0 || activeCategories.includes(category);
        const matchesGender = activeGenders.length === 0 || activeGenders.includes(gender);
        const matchesSearch = title.includes(searchTerm);
        
        card.style.display = matchesCategory && matchesGender && matchesSearch ? 'block' : 'none';
    });
}

// Function to trigger product deletion event
function triggerProductDeletion(productId) {
    const event = new CustomEvent('productDeleted', {
        detail: productId
    });
    window.dispatchEvent(event);
}

// Function to trigger product addition event
function triggerProductAddition(productData) {
    const event = new CustomEvent('productAdded', {
        detail: productData
    });
    window.dispatchEvent(event);
}
