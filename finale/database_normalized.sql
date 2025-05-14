-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS user_auth;

-- Use the database
USE user_auth;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    shipping_address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (username),
    INDEX (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create categories table

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create genders table
CREATE TABLE IF NOT EXISTS genders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name ENUM('men', 'women', 'unisex') NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create order status table
CREATE TABLE IF NOT EXISTS order_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create shipping methods table
CREATE TABLE IF NOT EXISTS shipping_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category_id INT NOT NULL,
    gender_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(512) NOT NULL,
    description TEXT,
    inventory INT NOT NULL DEFAULT 50,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (gender_id) REFERENCES genders(id) ON DELETE RESTRICT,
    INDEX (category_id),
    INDEX (gender_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create product_additions table to track product additions and updates
CREATE TABLE IF NOT EXISTS product_additions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    admin_id INT NOT NULL,
    action ENUM('add', 'update', 'delete') NOT NULL,
    old_price DECIMAL(10,2),
    new_price DECIMAL(10,2),
    old_inventory INT,
    new_inventory INT,
    old_description TEXT,
    new_description TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE RESTRICT,
    INDEX (product_id),
    INDEX (admin_id),
    INDEX (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Create cart_items table
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX (user_id),
    INDEX (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    order_shipping_name VARCHAR(100) NOT NULL,
    order_shipping_contact VARCHAR(20) NOT NULL,
    order_shipping_address TEXT NOT NULL,
    shipping_method_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status_id INT NOT NULL DEFAULT 1, -- Default is 'pending'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(id) ON DELETE RESTRICT,
    FOREIGN KEY (status_id) REFERENCES order_status(id) ON DELETE RESTRICT,
    INDEX (user_id),
    INDEX (status_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX (order_id),
    INDEX (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(255),
    email VARCHAR(255),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- Insert initial data for lookup tables
INSERT INTO categories (name) VALUES 
('tshirts'), ('hoodies'), ('jackets'), ('lowerwear'), ('bags');

INSERT INTO genders (name) VALUES 
('men'), ('women'), ('unisex');

INSERT INTO order_status (status) VALUES 
('pending'), ('processing'), ('shipped'), ('delivered'), ('cancelled');

INSERT INTO shipping_methods (name, description) VALUES 
('lalamove', 'Sameday delivery service'),
('jnt', 'Cheapest delivery service'),
('flash', 'Fast and Cheap  ');

-- Insert sample products using lookup table IDs
INSERT INTO products (name, category_id, gender_id, price, image_url, description, inventory) VALUES
('T-Shirt 1', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'men'), 1000.00, 'https://i5.walmartimages.com/asr/f230ac7a-246d-4114-9686-6958d8d32bd6.5f124ae1aa6ef9e784500cfc662e67e7.jpeg?odnHeight=768&odnWidth=768&odnBg=FFFFFF', 'Sample T-Shirt 1', 100),
('T-Shirt 2', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'men'), 1000.00, 'https://martinvalen.com/29323-mv_large_default/men-s-oversize-collar-screen-printed-black-heavy-t-shirt.jpg', 'Sample T-Shirt 2', 100),
('T-Shirt 3', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'men'), 1000.00, 'https://cdn-images.farfetch-contents.com/16/30/95/80/16309580_45155731_600.jpg', 'Sample T-Shirt 3', 100),
('T-Shirt 4', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'men'), 1000.00, 'https://yellowimages.com/cdn-cgi/imagedelivery/F5KOmplEz0rStV2qDKhYag/26a9ca15-92c0-4d3f-1d8b-827bfca19a00/omcover', 'Sample T-Shirt 4', 100),
('T-Shirt 5', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'men'), 1000.00, 'https://teveo.com/cdn/shop/files/Acid_Men_Tshirt_Schwarz_1_533x.png?v=1720434270', 'Sample T-Shirt 5', 100),
('T-Shirt 6', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'men'), 1000.00, 'https://pronk.in/cdn/shop/files/299_d802ead6-afd3-4ee3-86f2-110e76150914.jpg?v=1739425105', 'Sample T-Shirt 6', 100),
('T-Shirt 7', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'men'), 1000.00, 'https://www.lezhougarment.com/wp-content/uploads/2024/02/Oversized-heavy-stone-wash-wax-printing-men-t-shirt-10.jpg', 'Sample T-Shirt 7', 100),
('T-Shirt 8', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'men'), 1000.00, 'https://pronk.in/cdn/shop/files/340_5bddb955-5144-405f-a557-b14153f2e4df.jpg?v=1740636446&width=1080', 'Sample T-Shirt 8', 100),
('T-Shirt 9', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'men'), 1000.00, 'https://thalasiknitfab.com/cdn/shop/files/ANIMEOVERSIZEDTSHIRT_6e28c0e6-b2a8-4932-a59b-4cc93ec85245_490x.progressive.png.jpg?v=1734612522', 'Sample T-Shirt 9', 100),
('T-Shirt 10', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'men'), 1000.00, 'https://thebearhouse.com/cdn/shop/files/TSH-PARADISE-GR_1.jpg?v=1746177385', 'Sample T-Shirt 10', 100),
('T-Shirt 11', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'women'), 1000.00, 'https://i5.walmartimages.com/seo/Love-Yourself-Oversized-Graphic-Tee-for-Women-Women-s-Baggy-Short-Sleeve-T-Shirts-Summer-Fashion-Round-Neck-Top_4f407d7c-a56b-457e-a853-a91d0af96448.56199ffacbc2e5b667627dbd7659c367.jpeg?odnHeight=768&odnWidth=768&odnBg=FFFFFF', 'Sample T-Shirt 11', 100),
('T-Shirt 12', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'women'), 1000.00, 'https://m.media-amazon.com/images/I/41FkNJyeKjL._AC_.jpg', 'Sample T-Shirt 12', 100),
('T-Shirt 13', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'women'), 1000.00, 'https://i.etsystatic.com/23779612/r/il/f54136/6439242004/il_570xN.6439242004_ltst.jpg', 'Sample T-Shirt 13', 100),
('T-Shirt 14', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'women'), 1000.00, 'https://m.media-amazon.com/images/I/81MRN7ygtmL._AC_UY1100_.jpg', 'Sample T-Shirt 14', 100),
('T-Shirt 15', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'women'), 1000.00, 'https://media.zid.store/thumbs/076be695-f763-4359-af7f-9a4d7b3d42a7/f9faca8c-704f-4dea-8638-ff508202e944-thumbnail-1000x1000-70.jpeg', 'Sample T-Shirt 15', 100),
('T-Shirt 16', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'women'), 1000.00, 'https://thalasiknitfab.com/cdn/shop/files/THALASI-JUJUTSU-KAISEN-OVERSIZED-ANIME-T-SHIRT-FOR-WOMEN_2_490x.progressive.jpg?v=1713166314', 'Sample T-Shirt 16', 100),
('T-Shirt 17', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'women'), 1000.00, 'https://theyayacafe.com/wp-content/uploads/2023/05/5175RwfKeHL-5-jpg.webp', 'Sample T-Shirt 17', 100),
('T-Shirt 18', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'women'), 1000.00, 'https://images.napali.app/global/roxy-products/all/default/xlarge/urjzt03708_roxy,m_wbb0_frt1.jpg', 'Sample T-Shirt 18', 100),
('T-Shirt 19', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'women'), 1000.00, 'https://pronk.in/cdn/shop/files/6_99a7dfd0-a7ec-4f41-ac88-462ee73a9f7a.jpg?v=1744350002', 'Sample T-Shirt 19', 100),
('T-Shirt 20', (SELECT id FROM categories WHERE name = 'tshirts'), (SELECT id FROM genders WHERE name = 'women'), 1000.00, 'https://i5.walmartimages.com/seo/Vintage-Graphic-Tees-for-Women-Oversized-Tshirts-Aesthetic-Trendy-T-Shirt-Baggy-Cute-Halloween-Gothic-Shirts-for-Teen-Girl_e3e1a17d-67f1-4171-8da8-ae83bb058646.86aa0741bcfb08ecfd29f43eb9c47e7a.jpeg?odnHeight=768&odnWidth=768&odnBg=FFFFFF', 'Sample T-Shirt 20', 100),
('Hoodie 1', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'men'), 1200.00, 'https://lh3.googleusercontent.com/proxy/cvodKqPRehRpKkNvap2M9laoZX9hYj-uUQSizAOnHL-7XQ47kD7Qt46APnYbWPpPl0KwYx4fJgHVsQUkaA3TssvRaavYtt2ZccypnoV8KvZdJRY9Q-5CBotNHqdrrzAGhJwaP73IZpz3GOr6mZM2nI3gng', 'Sample Hoodie 1', 100),
('Hoodie 2', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'men'), 1200.00, 'https://herschel.ph/cdn/shop/files/1000475855_04.jpg?v=1737340280&width=1080', 'Sample Hoodie 2', 100),
('Hoodie 3', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'men'), 1200.00, 'https://img01.ztat.net/article/spp-media-p1/c3f8c16db68840aa99a97faff97fab0d/9552f532eb6e4953a118f2777194f89a.jpg?imwidth=1800', 'Sample Hoodie 3', 100),
('Hoodie 4', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'men'), 1200.00, 'https://cdn.media.amplience.net/i/frasersdev/53700622_o_a3?fmt=auto&upscale=false&w=767&h=767&sm=scaleFit&$h-ttl$', 'Sample Hoodie 4', 100),
('Hoodie 5', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'men'), 1200.00, 'https://i.ebayimg.com/images/g/WD4AAOSwfNZlfDep/s-l1200.jpg', 'Sample Hoodie 5', 100),
('Hoodie 6', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'men'), 1200.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTjoOuyXjw2GgFptnnSSoutVBAg-tnv34tDAg&s', 'Sample Hoodie 6', 100),
('Hoodie 7', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'men'), 1200.00, 'https://img.freepik.com/free-photo/autumn-person-with-beautiful-hat_23-2149137843.jpg', 'Sample Hoodie 7', 100),
('Hoodie 8', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'men'), 1200.00, 'https://n.nordstrommedia.com/it/16b48efd-732a-4f69-b69b-b83d89e275ca.jpeg?h=368&w=240&dpr=2', 'Sample Hoodie 8', 100),
('Hoodie 9', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'men'), 1200.00, 'https://i5.walmartimages.com/asr/b233c384-6cb6-4f0b-9e5c-436d8f22d24c.cb01caefbb619c6383d40e11d1be1bf3.jpeg?odnHeight=768&odnWidth=768&odnBg=FFFFFF', 'Sample Hoodie 9', 100),
('Hoodie 10', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'men'), 1200.00, 'https://cdn.shopify.com/s/files/1/0516/4538/2842/files/HOODIE-LA-GREY_4229_b7cbcc50-179c-415a-a920-6cba94f4bab6_600x600.jpg?v=1730860639', 'Sample Hoodie 10', 100),
('Hoodie 11', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'women'), 1200.00, 'https://n.nordstrommedia.com/it/8a11c3d6-1459-4f6b-90f0-cc4cb3795f1b.jpeg?h=368&w=240&dpr=2', 'Sample Hoodie 11', 100),
('Hoodie 12', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'women'), 1200.00, 'https://martinvalen.com/36491-mv_large_default/grieto-mv-crack-print-gray-oversized-designer-hoodie.jpg', 'Sample Hoodie 12', 100),
('Hoodie 13', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'women'), 1200.00, 'https://ae01.alicdn.com/kf/Hd033340976074a62a55465e410b21de9G/Gothic-Oversized-Hoodies-Female-Zip-Up-Long-Sleeve-Women-Devil-Horn-Hooded-Sweatshirt-2021-Spring-Autumn.jpg', 'Sample Hoodie 13', 100),
('Hoodie 14', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'women'), 1200.00, 'https://i.ebayimg.com/images/g/iEgAAOSwrDBm-9A3/s-l1200.jpg', 'Sample Hoodie 14', 100),
('Hoodie 15', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'women'), 1200.00, 'https://m.media-amazon.com/images/I/717XCi4RlpL._AC_UY1000_.jpg', 'Sample Hoodie 15', 100),
('Hoodie 16', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'women'), 1200.00, 'https://n.nordstrommedia.com/it/cfde6fca-4177-4415-9403-2d41316c1b2c.jpeg?h=368&w=240&dpr=2', 'Sample Hoodie 16', 100),
('Hoodie 17', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'women'), 1200.00, 'https://n.nordstrommedia.com/it/8271c160-91e5-499a-acd3-b0289e51ae91.jpeg?h=368&w=240&dpr=2', 'Sample Hoodie 17', 100),
('Hoodie 18', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'women'), 1200.00, 'https://cdn-images.farfetch-contents.com/19/79/12/87/19791287_44702120_600.jpg', 'Sample Hoodie 18', 100),
('Hoodie 19', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'women'), 1200.00, 'https://m.media-amazon.com/images/I/61zTHZB8q+L._AC_UY1000_.jpg', 'Sample Hoodie 19', 100),
('Hoodie 20', (SELECT id FROM categories WHERE name = 'hoodies'), (SELECT id FROM genders WHERE name = 'women'), 1200.00, 'https://img01.ztat.net/article/spp-media-p1/fab5b2015d4543f0994ead4e69b075e3/17f2f919699441c4b6aa630067f5b176.jpg?imwidth=780', 'Sample Hoodie 20', 100),
('Jacket 1', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'men'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+1', 'Sample Jacket 1', 100),
('Jacket 2', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'men'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+2', 'Sample Jacket 2', 100),
('Jacket 3', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'men'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+3', 'Sample Jacket 3', 100),
('Jacket 4', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'men'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+4', 'Sample Jacket 4', 100),
('Jacket 5', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'men'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+5', 'Sample Jacket 5', 100),
('Jacket 6', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'men'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+6', 'Sample Jacket 6', 100),
('Jacket 7', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'men'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+7', 'Sample Jacket 7', 100),
('Jacket 8', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'men'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+8', 'Sample Jacket 8', 100),
('Jacket 9', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'men'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+9', 'Sample Jacket 9', 100),
('Jacket 10', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'men'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+10', 'Sample Jacket 10', 100),
('Jacket 11', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'women'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+11', 'Sample Jacket 11', 100),
('Jacket 12', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'women'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+12', 'Sample Jacket 12', 100),
('Jacket 13', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'women'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+13', 'Sample Jacket 13', 100),
('Jacket 14', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'women'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+14', 'Sample Jacket 14', 100),
('Jacket 15', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'women'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+15', 'Sample Jacket 15', 100),
('Jacket 16', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'women'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+16', 'Sample Jacket 16', 100),
('Jacket 17', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'women'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+17', 'Sample Jacket 17', 100),
('Jacket 18', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'women'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+18', 'Sample Jacket 18', 100),
('Jacket 19', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'women'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+19', 'Sample Jacket 19', 100),
('Jacket 20', (SELECT id FROM categories WHERE name = 'jackets'), (SELECT id FROM genders WHERE name = 'women'), 1500.00, 'https://via.placeholder.com/400x400?text=Jacket+20', 'Sample Jacket 20', 100),
('Lower Wear 1', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'men'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+1', 'Lower Wear 1', 100),
('Lower Wear 2', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'men'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+2', 'Lower Wear 2', 100),
('Lower Wear 3', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'men'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+3', 'Lower Wear 3', 100),
('Lower Wear 4', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'men'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+4', 'Lower Wear 4', 100),
('Lower Wear 5', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'men'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+5', 'Lower Wear 5', 100),
('Lower Wear 6', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'men'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+6', 'Lower Wear 6', 100),
('Lower Wear 7', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'men'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+7', 'Lower Wear 7', 100),
('Lower Wear 8', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'men'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+8', 'Lower Wear 8', 100),
('Lower Wear 9', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'men'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+9', 'Lower Wear 9', 100),
('Lower Wear 10', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'men'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+10', 'Lower Wear 10', 100),
('Lower Wear 11', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'women'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+11', 'Lower Wear 11', 100),
('Lower Wear 12', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'women'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+12', 'Lower Wear 12', 100),
('Lower Wear 13', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'women'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+13', 'Lower Wear 13', 100),
('Lower Wear 14', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'women'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+14', 'Lower Wear 14', 100),
('Lower Wear 15', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'women'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+15', 'Lower Wear 15', 100),
('Lower Wear 16', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'women'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+16', 'Lower Wear 16', 100),
('Lower Wear 17', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'women'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+17', 'Lower Wear 17', 100),
('Lower Wear 18', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'women'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+18', 'Lower Wear 18', 100),
('Lower Wear 19', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'women'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+19', 'Lower Wear 19', 100),
('Lower Wear 20', (SELECT id FROM categories WHERE name = 'lowerwear'), (SELECT id FROM genders WHERE name = 'women'), 1300.00, 'https://via.placeholder.com/400x400?text=Lower+Wear+20', 'Lower Wear 20', 100),
('Bag 1', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'men'), 900.00, 'https://m.media-amazon.com/images/I/81aOsgTUQHL._AC_UY900_.jpg', 'Bag 1', 100),
('Bag 2', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'men'), 900.00, 'https://www.offwrld-techwear.com/cdn/shop/files/preview_images/cd131748081244b784fed4469ee1d44c.thumbnail.0000000000.jpg?v=1690043177&width=1946', 'Bag 2', 100),
('Bag 3', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'men'), 900.00, 'https://i.ebayimg.com/images/g/~ysAAOSwzNJhXUC6/s-l1600.jpg', 'Bag 3', 100),
('Bag 4', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'men'), 900.00, 'https://i.ebayimg.com/images/g/tsUAAOSwgfZl3W~w/s-l1200.jpg', 'Bag 4', 100),
('Bag 5', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'men'), 900.00, 'https://image-cdn.hypb.st/https%3A%2F%2Fhypebeast.com%2Fimage%2F2023%2F04%2Faeliza-a5-messenger-bag-official-drop-imagery-6.jpg?q=75&w=800&cbr=1&fit=max', 'Bag 5', 100),
('Bag 6', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'men'), 900.00, 'https://blinkleather.com/cdn/shop/products/H0122462f3700427484a7dee35f2f9880d_900x.jpg?v=1604053071', 'Bag 6', 100),
('Bag 7', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'men'), 900.00, 'https://www.prada.com/content/dam/pradabkg_products/2/2VZ/2VZ106/9Z2F0201/2VZ106_9Z2_F0201_V_OOO_MDL.jpg', 'Bag 7', 100),
('Bag 8', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'men'), 900.00, 'https://i.etsystatic.com/8536790/r/il/bb762d/3427559652/il_570xN.3427559652_5ucz.jpg', 'Bag 8', 100),
('Bag 9', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'men'), 900.00, 'https://www.apetogentleman.com/wp-content/uploads/2024/10/11LuxuryBagBrands70.jpg', 'Bag 9', 100),
('Bag 10', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'men'), 900.00, 'https://ae01.alicdn.com/kf/S079f7e1b1e8d48178f129fe68f89ab8eP.jpg_640x640q90.jpg', 'Bag 10', 100),
('Bag 11', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'women'), 900.00, 'https://dynamic.zacdn.com/Vr1c5u6A4TybGdoCESC5FRxCF8U=/filters:quality(70):format(webp)/https://static-id.zacdn.com/p/gentlewoman-5369-1345834-1.jpg', 'Bag 11', 100),
('Bag 12', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'women'), 900.00, 'https://img.joomcdn.net/34a76eb4b061fc0f307492073bb7a33d714daa10_original.jpeg', 'Bag 12', 100),
('Bag 13', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'women'), 900.00, 'https://i5.walmartimages.com/asr/c7546085-14fb-4e01-a515-868e077f73ef.7398169323474896427b5299d54c0e7e.jpeg', 'Bag 13', 100),
('Bag 14', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'women'), 900.00, 'https://cdn.shopify.com/s/files/1/0066/0360/4086/products/d19a1620df3c49429c5556ffb621d74a.jpg?v=1689755231', 'Bag 14', 100),
('Bag 15', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'women'), 900.00, 'https://www.apetogentleman.com/wp-content/uploads/2024/10/11LuxuryBagBrands70.jpg', 'Bag 15', 100),
('Bag 16', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'women'), 900.00, 'https://ae01.alicdn.com/kf/S97f983c942964115897c5a009ff24927I.jpg_960x960.jpg', 'Bag 16', 100),
('Bag 17', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'women'), 900.00, 'https://image.made-in-china.com/202f0j00hFscVbUlbTkg/Ladies-Soft-Leather-Hobos-Messenger-Bags-Women-Shopper-Bag-Female-Hobo-Handbag-Large-Capacity-Shoulder-Big-Style-Tote-Bag.webp', 'Bag 17', 100),
('Bag 18', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'women'), 900.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ5C2fHM9vD_Zx8aqnLNiwKFfMbg0HWT3yaP4PeWlcqPo_g32xtp1JqVVhKMLoJscT6MBE&usqp=CAU', 'Bag 18', 100),
('Bag 19', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'women'), 900.00, 'https://ae01.alicdn.com/kf/Sc22f2331c0864c4f87317615c0c4834ce.jpg', 'Bag 19', 100),
('Bag 20', (SELECT id FROM categories WHERE name = 'bags'), (SELECT id FROM genders WHERE name = 'women'), 900.00, 'https://i.ebayimg.com/images/g/2XQAAOSwn5Vj7dek/s-l1200.jpg', 'Bag 20', 100);


drop database user_auth;


