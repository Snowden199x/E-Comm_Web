<?php

/*
 | Category-specific product detail fields for the Seller Add Product form.
 | Keys are category slugs: Str::slug(str_replace('&', 'and', $category->name)).
 | Field types: text, textarea, number, date, select, chips, yesno.
 | Only universally-applicable fields are `required`. Everything else may stay blank.
 | `suggest` => datalist hints on a free-text field. `variation_hints` => quick-add variation names.
 | When the backend moves this to category_attributes, serve the same shape as JSON.
 */
$skin = ['All skin types', 'Normal', 'Dry', 'Oily', 'Combination', 'Sensitive'];
$apparel = [
    ['key' => 'clothing_type', 'label' => 'Clothing Type', 'type' => 'text', 'required' => true, 'suggest' => ['Dress', 'Top', 'Blouse', 'Shirt', 'Pants', 'Shorts', 'Skirt', 'Jacket', 'Sleepwear', 'Shoes', 'Bag', 'Accessory']],
    ['key' => 'material', 'label' => 'Material', 'type' => 'text', 'placeholder' => 'e.g. Cotton, polyester'],
    ['key' => 'style', 'label' => 'Style', 'type' => 'select', 'options' => ['Casual', 'Formal', 'Streetwear', 'Vintage', 'Sporty', 'Elegant', 'Minimalist', 'Other']],
    ['key' => 'fit', 'label' => 'Fit', 'type' => 'select', 'options' => ['Regular', 'Slim', 'Relaxed', 'Loose', 'Oversized']],
    ['key' => 'pattern', 'label' => 'Pattern', 'type' => 'select', 'options' => ['Solid', 'Striped', 'Floral', 'Plaid', 'Graphic', 'Polka Dot', 'Other']],
    ['key' => 'occasion', 'label' => 'Occasion', 'type' => 'select', 'options' => ['Casual', 'Work', 'Party', 'Formal', 'Sports', 'Beach', 'Sleep']],
];

return [
    'pet-supplies' => ['variation_hints' => ['Size', 'Flavor', 'Color'], 'fields' => [
        ['key' => 'pet_type', 'label' => 'Pet Type', 'type' => 'select', 'required' => true, 'options' => ['Dog', 'Cat', 'Bird', 'Fish', 'Small Pet', 'Other']],
        ['key' => 'product_type', 'label' => 'Product Type', 'type' => 'text', 'required' => true, 'suggest' => ['Food', 'Treats', 'Shampoo', 'Toy', 'Collar & Leash', 'Cage / Carrier', 'Bed', 'Bowl']],
        ['key' => 'material', 'label' => 'Material', 'type' => 'text'],
        ['key' => 'size', 'label' => 'Size', 'type' => 'text', 'placeholder' => 'e.g. Small, 30 cm'],
        ['key' => 'flavor', 'label' => 'Flavor', 'type' => 'text'],
        ['key' => 'life_stage', 'label' => 'Life Stage', 'type' => 'select', 'options' => ['All life stages', 'Puppy / Kitten', 'Adult', 'Senior']],
        ['key' => 'net_weight', 'label' => 'Net Weight', 'type' => 'text', 'placeholder' => 'e.g. 1.5 kg'],
        ['key' => 'expiration_date', 'label' => 'Expiration Date', 'type' => 'date', 'help' => 'Only for food, treats and similar items.'],
    ]],
    'electronics-and-gadgets' => ['variation_hints' => ['Color', 'Storage', 'Model'], 'fields' => [
        ['key' => 'model', 'label' => 'Model', 'type' => 'text'],
        ['key' => 'warranty', 'label' => 'Warranty', 'type' => 'select', 'options' => ['No warranty', '7 days', '1 month', '3 months', '6 months', '1 year', '2 years', '3 years or more']],
        ['key' => 'power_source', 'label' => 'Power Source', 'type' => 'select', 'options' => ['Rechargeable battery', 'Replaceable battery', 'USB powered', 'AC / Plug-in', 'Solar', 'Not applicable']],
        ['key' => 'connectivity', 'label' => 'Connectivity', 'type' => 'chips', 'options' => ['Bluetooth', 'Wi-Fi', 'USB', 'Wired', '3.5mm Audio', 'NFC', 'None']],
        ['key' => 'voltage_power', 'label' => 'Voltage / Power', 'type' => 'text', 'placeholder' => 'e.g. 220V, 18W'],
        ['key' => 'compatibility', 'label' => 'Compatibility', 'type' => 'textarea', 'placeholder' => 'Devices or systems this works with'],
    ]],
    'womens-apparel' => ['variation_hints' => ['Color', 'Size'], 'fields' => $apparel],
    'mens-apparel' => ['variation_hints' => ['Color', 'Size'], 'fields' => $apparel],
    'kids-and-baby' => ['variation_hints' => ['Color', 'Size', 'Age'], 'fields' => [
        ['key' => 'product_type', 'label' => 'Product Type', 'type' => 'text', 'required' => true, 'suggest' => ['Clothing', 'Toy', 'Feeding', 'Diapering', 'Stroller / Gear', 'Nursery', 'Book']],
        ['key' => 'age_range', 'label' => 'Age Range', 'type' => 'select', 'required' => true, 'options' => ['0–6 months', '6–12 months', '1–2 years', '3–5 years', '6–8 years', '9–12 years', 'All ages']],
        ['key' => 'material', 'label' => 'Material', 'type' => 'text'],
        ['key' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => ['Boys', 'Girls', 'Unisex']],
        ['key' => 'safety_information', 'label' => 'Safety Information', 'type' => 'textarea', 'placeholder' => 'Warnings, certifications, choking-hazard notes'],
    ]],
    'home-and-garden' => ['variation_hints' => ['Color', 'Size'], 'fields' => [
        ['key' => 'product_type', 'label' => 'Product Type', 'type' => 'text', 'required' => true],
        ['key' => 'material', 'label' => 'Material', 'type' => 'text'],
        ['key' => 'dimensions', 'label' => 'Dimensions', 'type' => 'text', 'placeholder' => 'e.g. 120 x 60 x 75 cm'],
        ['key' => 'indoor_outdoor', 'label' => 'Indoor / Outdoor', 'type' => 'select', 'options' => ['Indoor', 'Outdoor', 'Both']],
        ['key' => 'assembly_required', 'label' => 'Assembly Required', 'type' => 'yesno'],
    ]],
    'sports-and-outdoors' => ['variation_hints' => ['Color', 'Size'], 'fields' => [
        ['key' => 'sport_activity', 'label' => 'Sport / Activity', 'type' => 'text', 'suggest' => ['Running', 'Cycling', 'Camping', 'Hiking', 'Swimming', 'Basketball', 'Badminton', 'Fitness']],
        ['key' => 'product_type', 'label' => 'Product Type', 'type' => 'text', 'required' => true],
        ['key' => 'material', 'label' => 'Material', 'type' => 'text'],
        ['key' => 'size', 'label' => 'Size', 'type' => 'text'],
        ['key' => 'skill_level', 'label' => 'Skill Level', 'type' => 'select', 'options' => ['All levels', 'Beginner', 'Intermediate', 'Advanced']],
    ]],
    'health-and-beauty' => ['variation_hints' => ['Size', 'Scent', 'Shade'], 'fields' => [
        ['key' => 'product_type', 'label' => 'Product Type', 'type' => 'text', 'required' => true, 'suggest' => ['Cleanser', 'Moisturizer', 'Serum', 'Shampoo', 'Supplement', 'Sunscreen', 'Personal care device']],
        ['key' => 'formulation', 'label' => 'Formulation', 'type' => 'select', 'options' => ['Cream', 'Gel', 'Lotion', 'Serum', 'Liquid', 'Powder', 'Capsule / Tablet', 'Spray', 'Other']],
        ['key' => 'skin_hair_type', 'label' => 'Skin / Hair Type', 'type' => 'select', 'options' => array_merge($skin, ['Dry hair', 'Oily hair', 'Damaged hair'])],
        ['key' => 'ingredients', 'label' => 'Ingredients', 'type' => 'textarea'],
        ['key' => 'net_content', 'label' => 'Net Content', 'type' => 'text', 'placeholder' => 'e.g. 100 ml'],
        ['key' => 'expiration_date', 'label' => 'Expiration Date', 'type' => 'date'],
    ]],
    'makeup-and-cosmetics' => ['variation_hints' => ['Shade', 'Size'], 'fields' => [
        ['key' => 'makeup_type', 'label' => 'Makeup Type', 'type' => 'select', 'required' => true, 'options' => ['Foundation', 'Concealer', 'Powder', 'Blush', 'Lipstick', 'Lip Gloss / Tint', 'Eyeshadow', 'Eyeliner', 'Mascara', 'Brow', 'Setting Spray', 'Brushes & Tools', 'Other']],
        ['key' => 'shade_color', 'label' => 'Shade / Color', 'type' => 'text'],
        ['key' => 'finish', 'label' => 'Finish', 'type' => 'select', 'options' => ['Matte', 'Dewy', 'Satin', 'Glossy', 'Shimmer', 'Natural']],
        ['key' => 'skin_type', 'label' => 'Skin Type', 'type' => 'select', 'options' => $skin],
        ['key' => 'ingredients', 'label' => 'Ingredients', 'type' => 'textarea'],
        ['key' => 'net_content', 'label' => 'Net Content', 'type' => 'text', 'placeholder' => 'e.g. 30 ml'],
        ['key' => 'expiration_date', 'label' => 'Expiration Date', 'type' => 'date'],
    ]],
    'books-and-media' => ['variation_hints' => ['Format', 'Edition'], 'fields' => [
        ['key' => 'media_type', 'label' => 'Media Type', 'type' => 'select', 'required' => true, 'options' => ['Book', 'Magazine', 'Music (CD / Vinyl)', 'Movie (DVD / Blu-ray)', 'Video Game', 'Other']],
        ['key' => 'author_creator', 'label' => 'Author / Creator', 'type' => 'text'],
        ['key' => 'publisher', 'label' => 'Publisher', 'type' => 'text'],
        ['key' => 'language', 'label' => 'Language', 'type' => 'text', 'placeholder' => 'e.g. English'],
        ['key' => 'publication_year', 'label' => 'Publication Year', 'type' => 'number', 'min' => 1400, 'max' => 2100],
        ['key' => 'isbn', 'label' => 'ISBN', 'type' => 'text', 'placeholder' => '10 or 13 digits'],
        ['key' => 'format', 'label' => 'Format', 'type' => 'select', 'options' => ['Hardcover', 'Paperback', 'Magazine', 'CD', 'Vinyl', 'DVD', 'Blu-ray', 'Game disc / cartridge', 'Other']],
    ]],
    'food-and-gourmet' => ['variation_hints' => ['Flavor', 'Package Size'], 'fields' => [
        ['key' => 'food_type', 'label' => 'Food Type', 'type' => 'select', 'required' => true, 'options' => ['Snacks', 'Candy & Sweets', 'Beverages', 'Coffee & Tea', 'Baking Ingredients', 'Canned / Packaged', 'Sauces & Condiments', 'Fresh / Frozen', 'Organic / Health Food', 'Other']],
        ['key' => 'flavor', 'label' => 'Flavor', 'type' => 'text'],
        ['key' => 'ingredients', 'label' => 'Ingredients', 'type' => 'textarea'],
        ['key' => 'net_weight_volume', 'label' => 'Net Weight / Volume', 'type' => 'text', 'placeholder' => 'e.g. 250 g'],
        ['key' => 'allergen_information', 'label' => 'Allergen Information', 'type' => 'text', 'placeholder' => 'e.g. Contains peanuts, milk'],
        ['key' => 'expiration_date', 'label' => 'Expiration Date', 'type' => 'date', 'required' => true],
        ['key' => 'storage_instructions', 'label' => 'Storage Instructions', 'type' => 'textarea'],
    ]],
    'automotive-and-motorcycle' => ['variation_hints' => ['Color', 'Size', 'Fitment'], 'fields' => [
        ['key' => 'product_type', 'label' => 'Product Type', 'type' => 'text', 'required' => true, 'suggest' => ['Helmet', 'Tire', 'Oil / Fluid', 'Brake part', 'Light', 'Tool', 'Cover', 'Accessory']],
        ['key' => 'vehicle_type', 'label' => 'Vehicle Type', 'type' => 'select', 'options' => ['Car', 'Motorcycle', 'Truck / Van', 'Universal']],
        ['key' => 'compatibility', 'label' => 'Compatibility', 'type' => 'textarea', 'placeholder' => 'Makes, models and years this fits'],
        ['key' => 'material', 'label' => 'Material', 'type' => 'text'],
        ['key' => 'part_model_number', 'label' => 'Part / Model Number', 'type' => 'text'],
        ['key' => 'installation_type', 'label' => 'Installation Type', 'type' => 'select', 'options' => ['Plug and play', 'DIY', 'Professional installation', 'Not applicable']],
    ]],
    'furniture-and-office-equipment' => ['variation_hints' => ['Color', 'Size'], 'fields' => [
        ['key' => 'product_type', 'label' => 'Product Type', 'type' => 'text', 'required' => true, 'suggest' => ['Desk', 'Chair', 'Cabinet', 'Shelf', 'Table', 'Lighting']],
        ['key' => 'material', 'label' => 'Material', 'type' => 'text'],
        ['key' => 'dimensions', 'label' => 'Dimensions', 'type' => 'text', 'placeholder' => 'e.g. 120 x 60 x 75 cm'],
        ['key' => 'weight_capacity', 'label' => 'Weight Capacity', 'type' => 'text', 'placeholder' => 'e.g. 120 kg'],
        ['key' => 'assembly_required', 'label' => 'Assembly Required', 'type' => 'yesno'],
    ]],
    'jewelry-and-watches' => ['variation_hints' => ['Color', 'Size'], 'fields' => [
        ['key' => 'product_type', 'label' => 'Product Type', 'type' => 'select', 'required' => true, 'options' => ['Necklace', 'Ring', 'Earrings', 'Bracelet', 'Watch', 'Anklet', 'Jewelry set', 'Other']],
        ['key' => 'material', 'label' => 'Material', 'type' => 'text', 'placeholder' => 'e.g. Stainless steel, 18k gold'],
        ['key' => 'color', 'label' => 'Color', 'type' => 'text'],
        ['key' => 'size', 'label' => 'Size', 'type' => 'text'],
        ['key' => 'gemstone', 'label' => 'Gemstone', 'type' => 'text'],
        ['key' => 'watch_movement', 'label' => 'Watch Movement', 'type' => 'select', 'options' => ['Quartz', 'Automatic', 'Mechanical', 'Digital', 'Solar', 'Not applicable']],
        ['key' => 'water_resistance', 'label' => 'Water Resistance', 'type' => 'select', 'options' => ['None', 'Splash resistant', '30 m', '50 m', '100 m', '200 m or more']],
    ]],
    'office-and-school-supplies' => ['variation_hints' => ['Color', 'Pack Size'], 'fields' => [
        ['key' => 'product_type', 'label' => 'Product Type', 'type' => 'text', 'required' => true, 'suggest' => ['Notebook', 'Pen', 'Paper', 'Bag', 'Folder', 'Art material', 'Printer supply']],
        ['key' => 'material', 'label' => 'Material', 'type' => 'text'],
        ['key' => 'size', 'label' => 'Size', 'type' => 'text', 'placeholder' => 'e.g. A4'],
        ['key' => 'color', 'label' => 'Color', 'type' => 'text'],
        ['key' => 'quantity_per_pack', 'label' => 'Quantity per Pack', 'type' => 'number', 'min' => 1],
    ]],
];