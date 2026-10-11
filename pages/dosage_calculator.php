<?php
session_start();

// First include config to get database connection
require_once '../includes/config.php';

$page_title = "Smart Dosage Calculator - Agri Smart Hub";
require_once '../includes/header.php';

// Realistic crop recommendations based on Ethiopian agricultural practices
$crop_recommendations = [
    'teff' => [
        'name' => 'Teff',
        'scientific_name' => 'Eragrostis tef',
        'description' => 'A staple grain in Ethiopia, requires careful nutrient management and proper spacing for optimal yield.',
        'icon' => 'fa-seedling',
        'color' => '#1A5D1A',
        'season' => 'Main Season (June-August)',
        'recommendations' => [
            'seed' => [
                'name' => 'Teff Seed (DZ-Cr-387)',
                'dosage_per_ha' => 25, // 25kg per hectare (broadcast method)
                'unit' => 'kg',
                'description' => 'Improved teff variety with high yield potential',
                'price_per_unit' => 3200, // ETB per 25kg bag
                'application_method' => 'Broadcast or row planting',
                'planting_depth' => '1-2 cm'
            ],
            'nps_fertilizer' => [
                'name' => 'NPS Fertilizer (19-38-0)',
                'dosage_per_ha' => 100, // 100kg per hectare
                'unit' => 'kg',
                'description' => 'Nitrogen-Phosphorus-Sulfur blend for teff',
                'price_per_unit' => 4150, // ETB per 50kg bag
                'application_time' => 'At planting (basal application)',
                'method' => 'Broadcast before final plowing'
            ],
            'urea_fertilizer' => [
                'name' => 'Urea Fertilizer (46-0-0)',
                'dosage_per_ha' => 50, // 50kg per hectare
                'unit' => 'kg',
                'description' => 'Nitrogen supplement for vegetative growth',
                'price_per_unit' => 3950, // ETB per 50kg bag
                'application_time' => '30 days after planting',
                'method' => 'Top dressing'
            ],
            'herbicide' => [
                'name' => 'Selective Herbicide',
                'dosage_per_ha' => 2, // 2L per hectare
                'unit' => 'liter',
                'description' => 'Controls broadleaf weeds in teff fields',
                'price_per_unit' => 850, // ETB per liter
                'application_time' => '2-3 weeks after planting',
                'safety_notes' => 'Use protective gear'
            ]
        ]
    ],
    'maize' => [
        'name' => 'Maize',
        'scientific_name' => 'Zea mays',
        'description' => 'High-yielding cereal crop requiring balanced fertilization and proper spacing.',
        'icon' => 'fa-corn',
        'color' => '#FF8C00',
        'season' => 'Main Season (April-June)',
        'recommendations' => [
            'seed' => [
                'name' => 'Hybrid Maize BH-546',
                'dosage_per_ha' => 25, // 25kg per hectare
                'unit' => 'kg',
                'description' => 'High-yield hybrid maize for Ethiopian conditions',
                'price_per_unit' => 1850, // ETB per 10kg
                'spacing' => '75cm × 25cm',
                'planting_depth' => '3-5 cm'
            ],
            'dap_fertilizer' => [
                'name' => 'DAP Fertilizer (18-46-0)',
                'dosage_per_ha' => 100, // 100kg per hectare
                'unit' => 'kg',
                'description' => 'Phosphorus-rich fertilizer for root development',
                'price_per_unit' => 4250, // ETB per 50kg bag
                'application_time' => 'At planting',
                'method' => 'Band placement near seeds'
            ],
            'urea_fertilizer' => [
                'name' => 'Urea Fertilizer (46-0-0)',
                'dosage_per_ha' => 100, // 100kg per hectare
                'unit' => 'kg',
                'description' => 'Nitrogen supplement for vegetative growth',
                'price_per_unit' => 3950, // ETB per 50kg bag
                'application_time' => '30 days after planting',
                'method' => 'Top dressing'
            ],
            'pesticide' => [
                'name' => 'Stalk Borer Control',
                'dosage_per_ha' => 1.5, // 1.5kg per hectare
                'unit' => 'kg',
                'description' => 'Controls maize stalk borer infestation',
                'price_per_unit' => 1250, // ETB per kg
                'application_time' => 'At 3-4 leaf stage',
                'safety_notes' => 'Wear protective clothing'
            ]
        ]
    ],
    'wheat' => [
        'name' => 'Wheat',
        'scientific_name' => 'Triticum aestivum',
        'description' => 'Important cereal crop requiring nitrogen-rich fertilizers and proper timing.',
        'icon' => 'fa-wheat-awn',
        'color' => '#DAA520',
        'season' => 'Cool Season (October-December)',
        'recommendations' => [
            'seed' => [
                'name' => 'Improved Wheat Seed',
                'dosage_per_ha' => 120, // 120kg per hectare
                'unit' => 'kg',
                'description' => 'High-yielding wheat varieties for Ethiopian highlands',
                'price_per_unit' => 2200, // ETB per 25kg
                'spacing' => '20cm row spacing',
                'planting_depth' => '3-4 cm'
            ],
            'nps_fertilizer' => [
                'name' => 'NPS Fertilizer (19-38-0)',
                'dosage_per_ha' => 100, // 100kg per hectare
                'unit' => 'kg',
                'description' => 'Complete fertilizer for wheat establishment',
                'price_per_unit' => 4150, // ETB per 50kg bag
                'application_time' => 'At planting',
                'method' => 'Broadcast and incorporate'
            ],
            'urea_fertilizer' => [
                'name' => 'Urea Fertilizer (46-0-0)',
                'dosage_per_ha' => 150, // 150kg per hectare
                'unit' => 'kg',
                'description' => 'Nitrogen for tillering and grain filling',
                'price_per_unit' => 3950, // ETB per 50kg bag
                'application_time' => '45 days after planting',
                'method' => 'Split application recommended'
            ]
        ]
    ],
    'barley' => [
        'name' => 'Barley',
        'scientific_name' => 'Hordeum vulgare',
        'description' => 'Drought-tolerant crop suitable for high altitudes and marginal lands.',
        'icon' => 'fa-beer-mug-empty',
        'color' => '#8B4513',
        'season' => 'Cool Season (October-December)',
        'recommendations' => [
            'seed' => [
                'name' => 'Improved Barley Seed',
                'dosage_per_ha' => 100, // 100kg per hectare
                'unit' => 'kg',
                'description' => 'High-yielding barley varieties',
                'price_per_unit' => 1800, // ETB per 25kg
                'spacing' => '20cm row spacing',
                'planting_depth' => '3-4 cm'
            ],
            'nps_fertilizer' => [
                'name' => 'NPS Fertilizer (19-38-0)',
                'dosage_per_ha' => 80, // 80kg per hectare
                'unit' => 'kg',
                'description' => 'Essential nutrients for barley growth',
                'price_per_unit' => 4150, // ETB per 50kg bag
                'application_time' => 'At planting',
                'method' => 'Broadcast application'
            ],
            'urea_fertilizer' => [
                'name' => 'Urea Fertilizer (46-0-0)',
                'dosage_per_ha' => 60, // 60kg per hectare
                'unit' => 'kg',
                'description' => 'Nitrogen supplement for better yield',
                'price_per_unit' => 3950, // ETB per 50kg bag
                'application_time' => '30 days after planting',
                'method' => 'Top dressing'
            ]
        ]
    ],
    'sorghum' => [
        'name' => 'Sorghum',
        'scientific_name' => 'Sorghum bicolor',
        'description' => 'Drought-resistant cereal crop suitable for dryland farming.',
        'icon' => 'fa-wheat-alt',
        'color' => '#A0522D',
        'season' => 'Main Season (April-June)',
        'recommendations' => [
            'seed' => [
                'name' => 'Improved Sorghum Seed',
                'dosage_per_ha' => 10, // 10kg per hectare
                'unit' => 'kg',
                'description' => 'High-yielding sorghum varieties',
                'price_per_unit' => 1500, // ETB per 25kg
                'spacing' => '75cm × 20cm',
                'planting_depth' => '3-5 cm'
            ],
            'npk_fertilizer' => [
                'name' => 'NPK Fertilizer (15-15-15)',
                'dosage_per_ha' => 100, // 100kg per hectare
                'unit' => 'kg',
                'description' => 'Balanced fertilizer for sorghum',
                'price_per_unit' => 4450, // ETB per 50kg bag
                'application_time' => 'At planting',
                'method' => 'Band placement'
            ],
            'urea_fertilizer' => [
                'name' => 'Urea Fertilizer (46-0-0)',
                'dosage_per_ha' => 50, // 50kg per hectare
                'unit' => 'kg',
                'description' => 'Nitrogen for vegetative growth',
                'price_per_unit' => 3950, // ETB per 50kg bag
                'application_time' => '30 days after planting',
                'method' => 'Top dressing'
            ]
        ]
    ],
    'tomato' => [
        'name' => 'Tomato',
        'scientific_name' => 'Solanum lycopersicum',
        'description' => 'High-value vegetable crop requiring careful nutrient and pest management.',
        'icon' => 'fa-apple-alt',
        'color' => '#DC143C',
        'season' => 'Year-round (with irrigation)',
        'recommendations' => [
            'seedling' => [
                'name' => 'Tomato Seedlings',
                'dosage_per_ha' => 25000, // 25,000 seedlings per hectare
                'unit' => 'seedlings',
                'description' => 'Improved tomato varieties',
                'price_per_unit' => 2, // ETB per seedling
                'spacing' => '60cm × 40cm',
                'transplanting' => '4-6 weeks after sowing'
            ],
            'npk_fertilizer' => [
                'name' => 'NPK Fertilizer (15-15-15)',
                'dosage_per_ha' => 200, // 200kg per hectare
                'unit' => 'kg',
                'description' => 'Balanced fertilizer for tomatoes',
                'price_per_unit' => 4450, // ETB per 50kg bag
                'application_time' => 'At transplanting',
                'method' => 'Basal application'
            ],
            'fungicide' => [
                'name' => 'Tomato Fungicide',
                'dosage_per_ha' => 2, // 2kg per hectare
                'unit' => 'kg',
                'description' => 'Controls blight and other fungal diseases',
                'price_per_unit' => 1250, // ETB per kg
                'application_time' => 'Preventive spraying',
                'safety_notes' => '7-day pre-harvest interval'
            ],
            'insecticide' => [
                'name' => 'Tomato Insecticide',
                'dosage_per_ha' => 1.5, // 1.5L per hectare
                'unit' => 'liter',
                'description' => 'Controls aphids and whiteflies',
                'price_per_unit' => 1150, // ETB per liter
                'application_time' => 'As needed',
                'safety_notes' => 'Wear protective gear'
            ]
        ]
    ]
];

// Handle form submission
$results = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate'])) {
    $crop_type = sanitize($_POST['crop_type']);
    $field_size = (float)$_POST['field_size'];
    
    // Validate inputs
    if (empty($crop_type)) {
        $_SESSION['error'] = "Please select a crop type";
    } elseif ($field_size <= 0 || $field_size > 10000) {
        $_SESSION['error'] = "Please enter a valid field size (0.01 - 10,000 hectares)";
    } elseif (!isset($crop_recommendations[$crop_type])) {
        $_SESSION['error'] = "Selected crop not found in recommendations";
    } else {
        // Calculate requirements
        $crop_info = $crop_recommendations[$crop_type];
        $input_calculations = [];
        $total_cost = 0;
        
        foreach ($crop_info['recommendations'] as $input_type => $recommendation) {
            $total_needed = $recommendation['dosage_per_ha'] * $field_size;
            $bags_needed = ceil($total_needed / ($recommendation['unit'] == 'kg' ? 50 : 1));
            $total_cost_input = $bags_needed * $recommendation['price_per_unit'];
            $total_cost += $total_cost_input;
            
            $input_calculations[$input_type] = [
                'name' => $recommendation['name'],
                'dosage_per_ha' => $recommendation['dosage_per_ha'],
                'unit' => $recommendation['unit'],
                'total_needed' => $total_needed,
                'bags_needed' => $bags_needed,
                'description' => $recommendation['description'],
                'price_per_unit' => $recommendation['price_per_unit'],
                'total_cost' => $total_cost_input,
                'input_type' => $input_type,
                'application_method' => $recommendation['application_method'] ?? $recommendation['method'] ?? 'As recommended',
                'application_time' => $recommendation['application_time'] ?? 'As needed'
            ];
        }
        
        $results = [
            'crop_type' => $crop_type,
            'crop_name' => $crop_info['name'],
            'scientific_name' => $crop_info['scientific_name'],
            'field_size' => $field_size,
            'input_calculations' => $input_calculations,
            'total_cost' => $total_cost,
            'cost_per_hectare' => $total_cost / $field_size,
            'calculation_time' => date('Y-m-d H:i:s'),
            'crop_description' => $crop_info['description'],
            'season' => $crop_info['season'],
            'color' => $crop_info['color']
        ];
        
        // Save to session
        if (!isset($_SESSION['recent_calculations'])) {
            $_SESSION['recent_calculations'] = [];
        }
        array_unshift($_SESSION['recent_calculations'], $results);
        if (count($_SESSION['recent_calculations']) > 5) {
            array_pop($_SESSION['recent_calculations']);
        }
    }
}

// Get recent calculations from session
$recent_calculations = isset($_SESSION['recent_calculations']) ? $_SESSION['recent_calculations'] : [];
?>

<div class="dosage-calculator-page">
    <!-- Hero Section -->
    <section class="calculator-hero">
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title">
                    <i class="fas fa-calculator"></i> Smart Dosage Calculator
                </h1>
                <p class="hero-subtitle">
                    Calculate precise fertilizer, seed, and pesticide requirements for optimal crop productivity
                </p>
                <div class="row justify-content-center mt-4">
                    <div class="col-md-2 col-4">
                        <div class="stat-card">
                            <i class="fas fa-seedling"></i>
                            <h3>6</h3>
                            <p>Crops</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-4">
                        <div class="stat-card">
                            <i class="fas fa-tractor"></i>
                            <h3>15+</h3>
                            <p>Inputs</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-4">
                        <div class="stat-card">
                            <i class="fas fa-chart-line"></i>
                            <h3>95%</h3>
                            <p>Accuracy</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-4 mt-3 mt-md-0">
                        <div class="stat-card">
                            <i class="fas fa-money-bill-wave"></i>
                            <h3>Save</h3>
                            <p>30% Costs</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-4 mt-3 mt-md-0">
                        <div class="stat-card">
                            <i class="fas fa-leaf"></i>
                            <h3>40%</h3>
                            <p>Yield Increase</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container main-container">
        <!-- Error/Success Messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="calculator-wrapper">
            <!-- Main Calculator Section -->
            <div class="calculator-main">
                <!-- Quick Start Section -->
                <div class="quick-start-section">
                    <div class="section-header">
                        <h2><i class="fas fa-bolt text-warning"></i> Quick Start Examples</h2>
                        <p>Try these common scenarios to get started quickly</p>
                    </div>
                    <div class="quick-examples-grid">
                        <div class="example-card" onclick="setExample('teff', 2)">
                            <div class="example-icon" style="background: #1A5D1A;">
                                <i class="fas fa-seedling"></i>
                            </div>
                            <div class="example-content">
                                <h4>Teff Cultivation</h4>
                                <p>2 hectares standard package</p>
                                <span class="example-badge">Most Popular</span>
                            </div>
                        </div>
                        <div class="example-card" onclick="setExample('maize', 5)">
                            <div class="example-icon" style="background: #FF8C00;">
                                <i class="fas fa-corn"></i>
                            </div>
                            <div class="example-content">
                                <h4>Maize Farming</h4>
                                <p>5 hectares complete package</p>
                                <span class="example-badge">High Yield</span>
                            </div>
                        </div>
                        <div class="example-card" onclick="setExample('tomato', 0.5)">
                            <div class="example-icon" style="background: #DC143C;">
                                <i class="fas fa-apple-alt"></i>
                            </div>
                            <div class="example-content">
                                <h4>Tomato Garden</h4>
                                <p>0.5 hectares intensive farming</p>
                                <span class="example-badge">High Value</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calculator Form -->
                <div class="calculator-form-section">
                    <div class="calculator-card">
                        <div class="calculator-header">
                            <h2><i class="fas fa-calculator text-primary"></i> Dosage Calculator</h2>
                            <p>Enter your farm details for precise recommendations</p>
                        </div>
                        
                        <form method="POST" action="" id="dosageCalculatorForm" class="calculator-form">
                            <!-- Crop Selection -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-seedling text-success"></i> Select Crop
                                    <span class="required">*</span>
                                </label>
                                <p class="form-help">Choose the crop you want to cultivate from our recommended list</p>
                                
                                <div class="crop-selection-grid">
                                    <?php foreach ($crop_recommendations as $crop_key => $crop_data): ?>
                                        <div class="crop-selection-card <?php echo (isset($_POST['crop_type']) && $_POST['crop_type'] == $crop_key) ? 'selected' : ''; ?>"
                                             onclick="selectCrop('<?php echo $crop_key; ?>')"
                                             style="border-color: <?php echo $crop_data['color']; ?>;">
                                            <div class="crop-icon" style="background: <?php echo $crop_data['color']; ?>">
                                                <i class="fas <?php echo $crop_data['icon']; ?>"></i>
                                            </div>
                                            <div class="crop-info">
                                                <h4><?php echo $crop_data['name']; ?></h4>
                                                <p class="scientific-name"><?php echo $crop_data['scientific_name']; ?></p>
                                                <p class="crop-desc"><?php echo substr($crop_data['description'], 0, 60); ?>...</p>
                                                <div class="crop-meta">
                                                    <span class="season-badge">
                                                        <i class="fas fa-calendar"></i> <?php echo $crop_data['season']; ?>
                                                    </span>
                                                    <span class="inputs-count">
                                                        <i class="fas fa-box"></i> <?php echo count($crop_data['recommendations']); ?> inputs
                                                    </span>
                                                </div>
                                            </div>
                                            <input type="radio" name="crop_type" value="<?php echo $crop_key; ?>"
                                                   <?php echo (isset($_POST['crop_type']) && $_POST['crop_type'] == $crop_key) ? 'checked' : ''; ?>
                                                   style="display: none;">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Fallback select for accessibility -->
                                <select name="crop_type_select" id="crop_type_select" class="form-select fallback-select" onchange="selectCropFromSelect(this.value)">
                                    <option value="">Select Crop Type</option>
                                    <?php foreach ($crop_recommendations as $crop_key => $crop_data): ?>
                                        <option value="<?php echo $crop_key; ?>" <?php echo (isset($_POST['crop_type']) && $_POST['crop_type'] == $crop_key) ? 'selected' : ''; ?>>
                                            <?php echo $crop_data['name']; ?> (<?php echo $crop_data['scientific_name']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Field Size -->
                            <div class="form-group">
                                <label class="form-label" for="field_size">
                                    <i class="fas fa-ruler-combined text-info"></i> Field Size
                                    <span class="required">*</span>
                                </label>
                                <p class="form-help">Enter the total area of your field in hectares (1 hectare = 10,000 m²)</p>
                                
                                <div class="field-size-wrapper">
                                    <div class="size-input-wrapper">
                                        <div class="input-group">
                                            <input type="number" 
                                                   id="field_size" 
                                                   name="field_size" 
                                                   step="0.01" 
                                                   min="0.01" 
                                                   max="10000" 
                                                   value="<?php echo isset($_POST['field_size']) ? $_POST['field_size'] : '1'; ?>" 
                                                   class="form-control size-input" 
                                                   placeholder="Enter hectares"
                                                   required>
                                            <span class="input-group-text">hectares</span>
                                        </div>
                                        <div class="size-equivalent">
                                            <small><i class="fas fa-info-circle"></i> Equivalent to: 
                                                <span id="sizeEquivalent"><?php echo number_format(isset($_POST['field_size']) ? $_POST['field_size'] * 10000 : 10000); ?> m²</span>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="size-presets">
                                        <p class="preset-label">Quick select:</p>
                                        <div class="preset-buttons">
                                            <button type="button" class="btn-preset" onclick="setFieldSize(0.25)">¼ ha</button>
                                            <button type="button" class="btn-preset" onclick="setFieldSize(0.5)">½ ha</button>
                                            <button type="button" class="btn-preset" onclick="setFieldSize(1)">1 ha</button>
                                            <button type="button" class="btn-preset" onclick="setFieldSize(2)">2 ha</button>
                                            <button type="button" class="btn-preset" onclick="setFieldSize(5)">5 ha</button>
                                            <button type="button" class="btn-preset" onclick="setFieldSize(10)">10 ha</button>
                                        </div>
                                    </div>
                                    
                                    <div class="size-slider-container">
                                        <label class="slider-label">Adjust size: <strong id="sizeValue"><?php echo isset($_POST['field_size']) ? $_POST['field_size'] : '1'; ?></strong> hectares</label>
                                        <input type="range" 
                                               id="field_size_slider" 
                                               class="form-range" 
                                               min="0.1" 
                                               max="50" 
                                               step="0.1" 
                                               value="<?php echo isset($_POST['field_size']) ? $_POST['field_size'] : '1'; ?>"
                                               oninput="updateSliderValue(this.value)">
                                        <div class="slider-ticks">
                                            <span>0.1 ha</span>
                                            <span>10 ha</span>
                                            <span>20 ha</span>
                                            <span>30 ha</span>
                                            <span>40 ha</span>
                                            <span>50 ha</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Crop Preview -->
                            <div class="crop-preview" id="cropPreview" style="<?php echo (isset($_POST['crop_type']) && isset($crop_recommendations[$_POST['crop_type']])) ? 'display: block;' : 'display: none;'; ?>">
                                <div class="preview-header">
                                    <h4><i class="fas fa-info-circle"></i> Selected Crop Information</h4>
                                </div>
                                <div class="preview-content" id="previewContent">
                                    <?php if (isset($_POST['crop_type']) && isset($crop_recommendations[$_POST['crop_type']])): 
                                        $selected_crop = $crop_recommendations[$_POST['crop_type']]; ?>
                                        <div class="crop-details-preview">
                                            <div class="crop-preview-icon" style="background: <?php echo $selected_crop['color']; ?>">
                                                <i class="fas <?php echo $selected_crop['icon']; ?>"></i>
                                            </div>
                                            <div class="crop-preview-info">
                                                <h3><?php echo $selected_crop['name']; ?></h3>
                                                <p class="scientific-name"><?php echo $selected_crop['scientific_name']; ?></p>
                                                <p class="crop-description"><?php echo $selected_crop['description']; ?></p>
                                                <div class="crop-details-grid">
                                                    <div class="detail-item">
                                                        <i class="fas fa-calendar-alt"></i>
                                                        <div>
                                                            <span class="detail-label">Season</span>
                                                            <span class="detail-value"><?php echo $selected_crop['season']; ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="detail-item">
                                                        <i class="fas fa-box"></i>
                                                        <div>
                                                            <span class="detail-label">Inputs Required</span>
                                                            <span class="detail-value"><?php echo count($selected_crop['recommendations']); ?> types</span>
                                                        </div>
                                                    </div>
                                                    <div class="detail-item">
                                                        <i class="fas fa-leaf"></i>
                                                        <div>
                                                            <span class="detail-label">Crop Type</span>
                                                            <span class="detail-value"><?php echo in_array($selected_crop['name'], ['Teff', 'Maize', 'Wheat', 'Barley', 'Sorghum']) ? 'Cereal' : 'Vegetable'; ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="submit" name="calculate" class="btn btn-calculate">
                                    <i class="fas fa-calculator"></i> Calculate Requirements
                                </button>
                                <button type="button" class="btn btn-reset" onclick="resetForm()">
                                    <i class="fas fa-redo"></i> Reset Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results Section -->
                <?php if ($results): ?>
                <div class="results-section" id="resultsSection">
                    <!-- Results Header -->
                    <div class="results-header" style="border-color: <?php echo $results['color']; ?>;">
                        <div class="results-title">
                            <div class="crop-badge" style="background: <?php echo $results['color']; ?>;">
                                <i class="fas <?php echo $crop_recommendations[$results['crop_type']]['icon']; ?>"></i>
                            </div>
                            <div>
                                <h3><i class="fas fa-chart-bar"></i> Calculation Results</h3>
                                <p class="results-subtitle">Requirements for <strong><?php echo number_format($results['field_size'], 2); ?> hectares</strong> of <strong><?php echo $results['crop_name']; ?></strong></p>
                            </div>
                        </div>
                        <div class="results-actions">
                            <button class="action-btn" onclick="printResults()" title="Print Report">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button class="action-btn" onclick="shareResults()" title="Share Results">
                                <i class="fas fa-share-alt"></i> Share
                            </button>
                            <button class="action-btn" onclick="saveCalculation()" title="Save Calculation">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </div>
                    </div>

                    <!-- Summary Stats -->
                    <div class="summary-stats">
                        <div class="stat-card">
                            <i class="fas fa-box-open"></i>
                            <div>
                                <h4><?php echo count($results['input_calculations']); ?></h4>
                                <p>Input Types</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-weight-hanging"></i>
                            <div>
                                <h4><?php 
                                    $total_weight = 0;
                                    foreach($results['input_calculations'] as $input) {
                                        if($input['unit'] == 'kg') $total_weight += $input['total_needed'];
                                    }
                                    echo number_format($total_weight);
                                ?> kg</h4>
                                <p>Total Weight</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-money-bill-wave"></i>
                            <div>
                                <h4>ETB <?php echo number_format($results['total_cost']); ?></h4>
                                <p>Estimated Cost</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-tractor"></i>
                            <div>
                                <h4>ETB <?php echo number_format($results['cost_per_hectare']); ?></h4>
                                <p>Cost per Hectare</p>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Requirements -->
                    <div class="requirements-section">
                        <div class="section-title">
                            <h4><i class="fas fa-clipboard-list"></i> Detailed Requirements</h4>
                            <p>Complete breakdown of inputs needed for your farm</p>
                        </div>
                        
                        <div class="requirements-table">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Input Type</th>
                                            <th>Product Name</th>
                                            <th>Rate/ha</th>
                                            <th>Total Required</th>
                                            <th>Bags Needed</th>
                                            <th>Unit Price</th>
                                            <th>Total Cost</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $subtotal = 0;
                                        foreach ($results['input_calculations'] as $input): 
                                            $subtotal += $input['total_cost'];
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="input-type-badge">
                                                    <?php
                                                    $icon_map = [
                                                        'seed' => 'fa-seedling',
                                                        'seedling' => 'fa-seedling',
                                                        'fertilizer' => 'fa-flask',
                                                        'nps_fertilizer' => 'fa-flask',
                                                        'dap_fertilizer' => 'fa-flask',
                                                        'urea_fertilizer' => 'fa-flask',
                                                        'npk_fertilizer' => 'fa-flask',
                                                        'herbicide' => 'fa-spray-can',
                                                        'pesticide' => 'fa-bug',
                                                        'insecticide' => 'fa-bug',
                                                        'fungicide' => 'fa-vial'
                                                    ];
                                                    $icon = $icon_map[$input['input_type']] ?? 'fa-box';
                                                    ?>
                                                    <i class="fas <?php echo $icon; ?> me-1"></i>
                                                    <?php echo ucfirst(str_replace('_', ' ', $input['input_type'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?php echo $input['name']; ?></strong>
                                                <small class="d-block text-muted"><?php echo $input['description']; ?></small>
                                            </td>
                                            <td>
                                                <span class="rate-value"><?php echo number_format($input['dosage_per_ha'], 2); ?> <?php echo $input['unit']; ?></span>
                                            </td>
                                            <td>
                                                <span class="total-value"><?php echo number_format($input['total_needed'], 2); ?> <?php echo $input['unit']; ?></span>
                                            </td>
                                            <td>
                                                <span class="bags-value"><?php echo $input['bags_needed']; ?> bags</span>
                                            </td>
                                            <td>
                                                <span class="price-value">ETB <?php echo number_format($input['price_per_unit']); ?></span>
                                            </td>
                                            <td>
                                                <span class="cost-value">ETB <?php echo number_format($input['total_cost']); ?></span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-action" onclick="addToCart('<?php echo $input['name']; ?>', <?php echo $input['bags_needed']; ?>)">
                                                        <i class="fas fa-cart-plus"></i>
                                                    </button>
                                                    <button class="btn-action" onclick="showInputDetails('<?php echo $input['name']; ?>')">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Total Estimated Cost:</strong></td>
                                            <td colspan="2"><strong class="total-cost">ETB <?php echo number_format($subtotal); ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Application Guide -->
                        <div class="application-guide">
                            <h5><i class="fas fa-calendar-check"></i> Application Schedule</h5>
                            <div class="timeline">
                                <?php 
                                $timeline_items = [];
                                foreach ($results['input_calculations'] as $input) {
                                    if(isset($input['application_time'])) {
                                        $timeline_items[] = [
                                            'input' => $input['name'],
                                            'time' => $input['application_time'],
                                            'method' => $input['application_method'],
                                            'type' => $input['input_type']
                                        ];
                                    }
                                }
                                
                                foreach($timeline_items as $item): 
                                    $type_color = [
                                        'seed' => '#1A5D1A',
                                        'fertilizer' => '#2E8B57',
                                        'pesticide' => '#DC143C',
                                        'herbicide' => '#FF8C00',
                                        'fungicide' => '#9370DB'
                                    ][$item['type']] ?? '#6C757D';
                                ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker" style="background: <?php echo $type_color; ?>"></div>
                                    <div class="timeline-content">
                                        <h6><?php echo $item['input']; ?></h6>
                                        <p class="timeline-time"><i class="fas fa-clock"></i> <?php echo $item['time']; ?></p>
                                        <p class="timeline-method"><?php echo $item['method']; ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Recommendations -->
                        <div class="recommendations-section">
                            <h5><i class="fas fa-lightbulb"></i> Expert Recommendations</h5>
                            <div class="recommendations-grid">
                                <div class="recommendation-card">
                                    <div class="rec-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div class="rec-content">
                                        <h6>Optimal Timing</h6>
                                        <p>Plant during the recommended season for best results. Monitor weather forecasts and soil moisture.</p>
                                    </div>
                                </div>
                                <div class="recommendation-card">
                                    <div class="rec-icon">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <div class="rec-content">
                                        <h6>Safety First</h6>
                                        <p>Always wear protective gear when handling chemicals. Store products away from children and animals.</p>
                                    </div>
                                </div>
                                <div class="recommendation-card">
                                    <div class="rec-icon">
                                        <i class="fas fa-leaf"></i>
                                    </div>
                                    <div class="rec-content">
                                        <h6>Sustainable Practices</h6>
                                        <p>Consider integrated pest management and organic alternatives where possible.</p>
                                    </div>
                                </div>
                                <div class="recommendation-card">
                                    <div class="rec-icon">
                                        <i class="fas fa-tint"></i>
                                    </div>
                                    <div class="rec-content">
                                        <h6>Water Management</h6>
                                        <p>Ensure adequate irrigation, especially during critical growth stages for optimal yield.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="calculator-sidebar">
                <!-- Recent Calculations -->
                <div class="sidebar-card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Recent Calculations</h3>
                        <?php if(!empty($recent_calculations)): ?>
                        <button class="btn-clear" onclick="clearHistory()">Clear All</button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_calculations)): ?>
                            <div class="empty-state">
                                <i class="fas fa-calculator"></i>
                                <p>No recent calculations</p>
                                <small>Your calculations will appear here</small>
                            </div>
                        <?php else: ?>
                            <div class="recent-list">
                                <?php foreach ($recent_calculations as $index => $calc): ?>
                                <div class="recent-item" onclick="loadCalculation(<?php echo $index; ?>)">
                                    <div class="recent-crop">
                                        <div class="crop-icon-sm" style="background: <?php echo $calc['color']; ?>;">
                                            <i class="fas <?php echo $crop_recommendations[$calc['crop_type']]['icon']; ?>"></i>
                                        </div>
                                        <div class="recent-info">
                                            <h6><?php echo $calc['crop_name']; ?></h6>
                                            <p><?php echo number_format($calc['field_size'], 2); ?> ha • ETB <?php echo number_format($calc['total_cost']); ?></p>
                                        </div>
                                    </div>
                                    <div class="recent-date">
                                        <?php echo date('M d', strtotime($calc['calculation_time'])); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Tools -->
                <div class="sidebar-card">
                    <div class="card-header">
                        <h3><i class="fas fa-tools"></i> Quick Tools</h3>
                    </div>
                    <div class="card-body">
                        <div class="tools-grid">
                            <a href="unit-converter.php" class="tool-item">
                                <div class="tool-icon">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <div class="tool-info">
                                    <h6>Unit Converter</h6>
                                    <p>Convert farming units</p>
                                </div>
                            </a>
                            <a href="profit-calculator.php" class="tool-item">
                                <div class="tool-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="tool-info">
                                    <h6>Profit Calculator</h6>
                                    <p>Calculate profits</p>
                                </div>
                            </a>
                            <a href="area-calculator.php" class="tool-item">
                                <div class="tool-icon">
                                    <i class="fas fa-crop-alt"></i>
                                </div>
                                <div class="tool-info">
                                    <h6>Area Calculator</h6>
                                    <p>Measure land area</p>
                                </div>
                            </a>
                            <a href="calendar.php" class="tool-item">
                                <div class="tool-icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="tool-info">
                                    <h6>Planting Calendar</h6>
                                    <p>View schedule</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Farming Tips -->
                <div class="sidebar-card tips-card">
                    <div class="card-header">
                        <h3><i class="fas fa-lightbulb"></i> Farming Tips</h3>
                    </div>
                    <div class="card-body">
                        <div class="tips-list">
                            <div class="tip-item">
                                <i class="fas fa-tint text-primary"></i>
                                <div>
                                    <h6>Water Conservation</h6>
                                    <p>Use drip irrigation to save 40-60% water while increasing yields.</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <i class="fas fa-seedling text-success"></i>
                                <div>
                                    <h6>Soil Health</h6>
                                    <p>Rotate crops and add organic matter to maintain soil fertility.</p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <i class="fas fa-bug text-danger"></i>
                                <div>
                                    <h6>Pest Control</h6>
                                    <p>Monitor crops weekly for early pest detection and control.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Input Details Modal -->
<div class="modal fade" id="inputDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="inputDetailsContent">
                <!-- Content loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="modalAddToCartBtn">
                    <i class="fas fa-cart-plus"></i> Add to Cart
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Save Calculation Modal -->
<div class="modal fade" id="saveCalculationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-save"></i> Save Calculation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="saveCalculationForm">
                    <div class="mb-3">
                        <label for="calculationName" class="form-label">Calculation Name</label>
                        <input type="text" class="form-control" id="calculationName" 
                               value="<?php echo isset($results) ? $results['crop_name'] . ' - ' . $results['field_size'] . ' ha' : ''; ?>"
                               required>
                    </div>
                    <div class="mb-3">
                        <label for="calculationNotes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="calculationNotes" rows="3" 
                                  placeholder="Add any notes about this calculation..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="saveCalculationToAccount()">
                    <i class="fas fa-save"></i> Save to Account
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Dosage Calculator Styles */
:root {
    --primary-green: #1A5D1A;
    --secondary-green: #2E8B57;
    --accent-orange: #FF8C00;
    --accent-red: #DC143C;
    --light-bg: #F8F9FA;
    --card-shadow: 0 4px 20px rgba(0,0,0,0.08);
    --border-radius: 12px;
}

.dosage-calculator-page {
    background: linear-gradient(135deg, #f5f7fa 0%, #e8f5e9 100%);
    min-height: 100vh;
}

/* Hero Section */
.calculator-hero {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);
    color: white;
    padding: 80px 0 60px;
    margin-bottom: 40px;
    position: relative;
    overflow: hidden;
}

.calculator-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.05" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
    background-size: cover;
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-title {
    font-size: 2.8rem;
    font-weight: 700;
    margin-bottom: 15px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.hero-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 40px;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

.stat-card {
    text-align: center;
    padding: 20px 10px;
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.stat-card:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-5px);
}

.stat-card i {
    font-size: 2.5rem;
    margin-bottom: 15px;
    opacity: 0.9;
}

.stat-card h3 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 5px;
}

.stat-card p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9rem;
}

/* Main Layout */
.main-container {
    padding-bottom: 60px;
}

.calculator-wrapper {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 30px;
}

@media (max-width: 992px) {
    .calculator-wrapper {
        grid-template-columns: 1fr;
    }
}

/* Quick Start Section */
.quick-start-section {
    margin-bottom: 30px;
}

.section-header {
    margin-bottom: 25px;
}

.section-header h2 {
    font-size: 1.8rem;
    color: var(--dark-color);
    margin-bottom: 10px;
}

.section-header p {
    color: var(--medium-gray);
    margin-bottom: 0;
}

.quick-examples-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.example-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    box-shadow: var(--card-shadow);
}

.example-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    border-color: var(--primary-green);
}

.example-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.8rem;
    margin-bottom: 20px;
}

.example-content h4 {
    font-size: 1.3rem;
    margin-bottom: 10px;
    color: var(--dark-color);
}

.example-content p {
    color: var(--medium-gray);
    margin-bottom: 15px;
}

.example-badge {
    display: inline-block;
    background: var(--light-color);
    color: var(--primary-green);
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

/* Calculator Form */
.calculator-form-section {
    margin-bottom: 40px;
}

.calculator-card {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--card-shadow);
}

.calculator-header {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
    padding: 25px 30px;
}

.calculator-header h2 {
    font-size: 1.8rem;
    margin-bottom: 10px;
}

.calculator-header p {
    opacity: 0.9;
    margin-bottom: 0;
}

.calculator-form {
    padding: 30px;
}

.form-group {
    margin-bottom: 30px;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    color: var(--dark-color);
    font-size: 1.1rem;
    margin-bottom: 8px;
}

.form-label .required {
    color: #dc3545;
}

.form-help {
    color: var(--medium-gray);
    font-size: 0.95rem;
    margin-bottom: 15px;
}

/* Crop Selection */
.crop-selection-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.crop-selection-card {
    border: 2px solid var(--light-gray);
    border-radius: 10px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.crop-selection-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.crop-selection-card.selected {
    border-color: var(--primary-green);
    background: #f0f9f0;
    box-shadow: 0 0 0 3px rgba(26, 93, 26, 0.1);
}

.crop-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    margin-bottom: 15px;
}

.crop-info h4 {
    font-size: 1.2rem;
    margin-bottom: 5px;
    color: var(--dark-color);
}

.scientific-name {
    color: var(--medium-gray);
    font-size: 0.9rem;
    font-style: italic;
    margin-bottom: 10px;
}

.crop-desc {
    color: var(--medium-gray);
    font-size: 0.9rem;
    margin-bottom: 15px;
    line-height: 1.5;
}

.crop-meta {
    display: flex;
    gap: 15px;
}

.season-badge, .inputs-count {
    display: flex;
    align-items: center;
    gap: 5px;
    color: var(--medium-gray);
    font-size: 0.85rem;
}

.fallback-select {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--light-gray);
    border-radius: 8px;
    font-size: 1rem;
}

/* Field Size */
.field-size-wrapper {
    background: var(--light-bg);
    border-radius: 10px;
    padding: 25px;
}

.size-input-wrapper {
    margin-bottom: 25px;
}

.size-input {
    font-size: 1.2rem;
    font-weight: 600;
    padding: 15px;
    border: 2px solid var(--primary-green);
}

.size-input:focus {
    box-shadow: 0 0 0 3px rgba(26, 93, 26, 0.25);
    border-color: var(--primary-green);
}

.size-equivalent {
    margin-top: 10px;
    color: var(--medium-gray);
}

.size-presets {
    margin-bottom: 25px;
}

.preset-label {
    color: var(--medium-gray);
    font-weight: 500;
    margin-bottom: 10px;
}

.preset-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.btn-preset {
    background: white;
    border: 2px solid var(--light-gray);
    color: var(--dark-color);
    padding: 8px 15px;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-preset:hover {
    background: var(--primary-green);
    border-color: var(--primary-green);
    color: white;
    transform: translateY(-2px);
}

.size-slider-container {
    padding-top: 20px;
    border-top: 1px solid var(--light-gray);
}

.slider-label {
    display: block;
    margin-bottom: 15px;
    color: var(--dark-color);
    font-weight: 500;
}

#field_size_slider {
    width: 100%;
    height: 8px;
    -webkit-appearance: none;
    background: #ddd;
    border-radius: 4px;
    outline: none;
}

#field_size_slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 25px;
    height: 25px;
    background: var(--primary-green);
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(26, 93, 26, 0.4);
    border: 3px solid white;
}

.slider-ticks {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
    color: var(--medium-gray);
    font-size: 0.8rem;
}

/* Crop Preview */
.crop-preview {
    background: linear-gradient(135deg, #f0f9f0, #e8f5e9);
    border-radius: 10px;
    padding: 25px;
    margin: 25px 0;
    border-left: 4px solid var(--primary-green);
}

.preview-header {
    margin-bottom: 20px;
}

.preview-header h4 {
    color: var(--primary-green);
    font-size: 1.3rem;
}

.crop-details-preview {
    display: flex;
    gap: 25px;
    align-items: center;
}

.crop-preview-icon {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2.5rem;
    flex-shrink: 0;
}

.crop-preview-info h3 {
    font-size: 1.8rem;
    margin-bottom: 5px;
    color: var(--dark-color);
}

.scientific-name {
    color: var(--medium-gray);
    font-style: italic;
    margin-bottom: 15px;
}

.crop-description {
    color: var(--dark-color);
    line-height: 1.6;
    margin-bottom: 20px;
}

.crop-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 15px;
}

.detail-item i {
    font-size: 1.5rem;
    color: var(--primary-green);
    width: 40px;
}

.detail-label {
    display: block;
    color: var(--medium-gray);
    font-size: 0.9rem;
}

.detail-value {
    display: block;
    color: var(--dark-color);
    font-weight: 600;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 15px;
    padding-top: 25px;
    border-top: 1px solid var(--light-gray);
}

.btn-calculate {
    flex: 1;
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
    border: none;
    padding: 16px 30px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.btn-calculate:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(26, 93, 26, 0.3);
}

.btn-reset {
    background: white;
    color: var(--dark-color);
    border: 2px solid var(--light-gray);
    padding: 16px 30px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.btn-reset:hover {
    background: var(--light-gray);
}

/* Results Section */
.results-section {
    animation: slideIn 0.5s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.results-header {
    background: white;
    border-radius: var(--border-radius);
    padding: 25px 30px;
    margin-bottom: 30px;
    border-left: 5px solid var(--primary-green);
    box-shadow: var(--card-shadow);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.results-title {
    display: flex;
    align-items: center;
    gap: 20px;
}

.crop-badge {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.8rem;
}

.results-title h3 {
    font-size: 1.8rem;
    margin-bottom: 5px;
    color: var(--dark-color);
}

.results-subtitle {
    color: var(--medium-gray);
    margin-bottom: 0;
}

.results-actions {
    display: flex;
    gap: 10px;
}

.action-btn {
    background: white;
    border: 2px solid var(--light-gray);
    color: var(--dark-color);
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.action-btn:hover {
    background: var(--light-bg);
    transform: translateY(-2px);
}

.action-btn:nth-child(1):hover {
    border-color: #007bff;
    color: #007bff;
}

.action-btn:nth-child(2):hover {
    border-color: var(--primary-green);
    color: var(--primary-green);
}

.action-btn:nth-child(3):hover {
    border-color: var(--accent-orange);
    color: var(--accent-orange);
}

/* Summary Stats */
.summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-stats .stat-card {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: var(--card-shadow);
    display: flex;
    align-items: center;
    gap: 20px;
    text-align: left;
}

.summary-stats .stat-card i {
    font-size: 2.5rem;
    margin-bottom: 0;
    color: var(--primary-green);
}

.summary-stats .stat-card h4 {
    font-size: 1.8rem;
    margin-bottom: 5px;
    color: var(--dark-color);
}

.summary-stats .stat-card p {
    color: var(--medium-gray);
    margin-bottom: 0;
}

/* Requirements Table */
.requirements-section {
    background: white;
    border-radius: var(--border-radius);
    padding: 30px;
    box-shadow: var(--card-shadow);
    margin-bottom: 30px;
}

.section-title {
    margin-bottom: 30px;
}

.section-title h4 {
    font-size: 1.5rem;
    color: var(--dark-color);
    margin-bottom: 10px;
}

.section-title p {
    color: var(--medium-gray);
    margin-bottom: 0;
}

.requirements-table table {
    margin-bottom: 0;
}

.requirements-table th {
    background: var(--light-bg);
    font-weight: 600;
    color: var(--dark-color);
    border-bottom: 2px solid var(--light-gray);
    padding: 15px;
}

.requirements-table td {
    padding: 15px;
    vertical-align: middle;
    border-color: var(--light-gray);
}

.input-type-badge {
    display: inline-flex;
    align-items: center;
    background: var(--light-bg);
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--dark-color);
}

.rate-value, .total-value, .bags-value, .price-value, .cost-value {
    font-weight: 600;
    color: var(--dark-color);
}

.cost-value {
    color: var(--primary-green);
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-action {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid var(--light-gray);
    background: white;
    color: var(--medium-gray);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.btn-action:hover {
    background: var(--light-bg);
    color: var(--dark-color);
    transform: translateY(-2px);
}

.btn-action:first-child:hover {
    border-color: var(--primary-green);
    color: var(--primary-green);
}

.btn-action:last-child:hover {
    border-color: #007bff;
    color: #007bff;
}

.total-cost {
    font-size: 1.3rem;
    color: var(--primary-green);
}

/* Application Guide */
.application-guide {
    background: var(--light-bg);
    border-radius: 10px;
    padding: 25px;
    margin: 30px 0;
}

.application-guide h5 {
    font-size: 1.3rem;
    color: var(--dark-color);
    margin-bottom: 25px;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--light-gray);
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 5px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.timeline-content {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.timeline-content h6 {
    font-size: 1.1rem;
    color: var(--dark-color);
    margin-bottom: 10px;
}

.timeline-time {
    color: var(--medium-gray);
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.timeline-method {
    color: var(--dark-color);
    font-size: 0.95rem;
    margin-bottom: 0;
}

/* Recommendations */
.recommendations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.recommendation-card {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: var(--card-shadow);
    transition: all 0.3s ease;
}

.recommendation-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.rec-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--light-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-green);
    font-size: 1.3rem;
    margin-bottom: 20px;
}

.rec-content h6 {
    font-size: 1.1rem;
    color: var(--dark-color);
    margin-bottom: 10px;
}

.rec-content p {
    color: var(--medium-gray);
    font-size: 0.95rem;
    line-height: 1.5;
    margin-bottom: 0;
}

/* Sidebar */
.calculator-sidebar {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.sidebar-card {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--card-shadow);
}

.card-header {
    background: var(--light-bg);
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--light-gray);
}

.card-header h3 {
    font-size: 1.3rem;
    margin-bottom: 0;
    color: var(--dark-color);
}

.btn-clear {
    background: none;
    border: none;
    color: var(--medium-gray);
    font-size: 0.9rem;
    padding: 5px 10px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.btn-clear:hover {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.card-body {
    padding: 20px;
}

/* Recent Calculations */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--medium-gray);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-state p {
    margin-bottom: 5px;
    font-weight: 500;
}

.empty-state small {
    font-size: 0.85rem;
}

.recent-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.recent-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: var(--light-bg);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.recent-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.recent-crop {
    display: flex;
    align-items: center;
    gap: 15px;
}

.crop-icon-sm {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
}

.recent-info h6 {
    font-size: 1rem;
    margin-bottom: 5px;
    color: var(--dark-color);
}

.recent-info p {
    font-size: 0.85rem;
    color: var(--medium-gray);
    margin-bottom: 0;
}

.recent-date {
    font-size: 0.85rem;
    color: var(--medium-gray);
}

/* Quick Tools */
.tools-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.tool-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 20px 15px;
    background: var(--light-bg);
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
}

.tool-item:hover {
    background: #e8f5e9;
    transform: translateY(-3px);
    color: var(--primary-green);
}

.tool-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--primary-green);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    margin-bottom: 15px;
}

.tool-info h6 {
    font-size: 0.95rem;
    margin-bottom: 5px;
    color: var(--dark-color);
}

.tool-info p {
    font-size: 0.8rem;
    color: var(--medium-gray);
    margin-bottom: 0;
}

/* Tips Card */
.tips-card {
    background: linear-gradient(135deg, #f0f9f0, #e8f5e9);
    border: none;
}

.tips-card .card-header {
    background: transparent;
}

.tips-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.tip-item {
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.tip-item i {
    font-size: 1.3rem;
    margin-top: 3px;
    flex-shrink: 0;
}

.tip-item h6 {
    font-size: 1rem;
    color: var(--dark-color);
    margin-bottom: 5px;
}

.tip-item p {
    font-size: 0.9rem;
    color: var(--medium-gray);
    line-height: 1.5;
    margin-bottom: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2.2rem;
    }
    
    .hero-subtitle {
        font-size: 1rem;
    }
    
    .stat-card {
        padding: 15px 5px;
    }
    
    .crop-selection-grid {
        grid-template-columns: 1fr;
    }
    
    .summary-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .tools-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .results-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .results-actions {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 576px) {
    .hero-title {
        font-size: 1.8rem;
    }
    
    .calculator-hero {
        padding: 60px 0 40px;
    }
    
    .calculator-form {
        padding: 20px;
    }
    
    .crop-details-preview {
        flex-direction: column;
        text-align: center;
    }
    
    .summary-stats {
        grid-template-columns: 1fr;
    }
    
    .preset-buttons {
        flex-direction: column;
    }
    
    .btn-preset {
        text-align: center;
    }
    
    .requirements-table {
        font-size: 0.9rem;
    }
    
    .requirements-table th,
    .requirements-table td {
        padding: 10px;
    }
}
</style>

<script>
// Crop data for JavaScript
const cropData = <?php echo json_encode($crop_recommendations); ?>;

// Input details data
const inputDetails = {
    'Teff Seed (DZ-Cr-387)': {
        description: 'Improved teff variety with high yield potential, drought tolerance, and good milling quality.',
        benefits: ['High yield (2-3 tons/ha)', 'Drought tolerant', 'Good for teff injera', 'Early maturing (90-120 days)'],
        application: 'Broadcast 25kg/ha or row plant at 20cm spacing',
        precautions: 'Use certified seeds, treat with fungicide before planting'
    },
    'NPS Fertilizer (19-38-0)': {
        description: 'Nitrogen-Phosphorus-Sulfur fertilizer blend essential for teff establishment and growth.',
        benefits: ['Promotes root development', 'Improves tillering', 'Enhances drought resistance', 'Balanced nutrition'],
        application: 'Apply 100kg/ha at planting, broadcast and incorporate into soil',
        precautions: 'Store in dry place, avoid mixing with seeds directly'
    },
    'Urea Fertilizer (46-0-0)': {
        description: 'High-nitrogen fertilizer for vegetative growth and protein synthesis.',
        benefits: ['Promotes leaf growth', 'Increases protein content', 'Quickly available to plants', 'Cost-effective nitrogen source'],
        application: 'Apply 50kg/ha 30 days after planting as top dressing',
        precautions: 'Apply when soil is moist, incorporate to reduce volatilization'
    },
    'Selective Herbicide': {
        description: 'Controls broadleaf weeds in teff fields without harming the crop.',
        benefits: ['Selective weed control', 'Reduces labor costs', 'Improves crop yield', 'Easy to apply'],
        application: 'Apply 2L/ha mixed with 200L water, spray 2-3 weeks after planting',
        precautions: 'Use protective gear, avoid drift to other crops, follow pre-harvest interval'
    },
    'Hybrid Maize BH-546': {
        description: 'High-yielding hybrid maize variety suitable for Ethiopian conditions.',
        benefits: ['Yield: 8-10 tons/ha', 'Matures in 120-130 days', 'Disease resistant', 'Good for high altitudes'],
        application: 'Plant at 75cm × 25cm spacing, 3-5cm depth',
        precautions: 'Use proper crop rotation, monitor for stalk borer'
    },
    'DAP Fertilizer (18-46-0)': {
        description: 'Di-ammonium phosphate fertilizer rich in phosphorus for root development.',
        benefits: ['Strong root system', 'Early crop establishment', 'Improved flowering', 'Better drought tolerance'],
        application: 'Apply 100kg/ha at planting in bands 5cm from seeds',
        precautions: 'Do not place directly with seeds, store in dry place'
    }
};

// JavaScript functions
function selectCrop(cropKey) {
    // Update radio button
    document.querySelectorAll('input[name="crop_type"]').forEach(radio => {
        radio.checked = radio.value === cropKey;
    });
    
    // Update fallback select
    document.getElementById('crop_type_select').value = cropKey;
    
    // Update UI selection
    document.querySelectorAll('.crop-selection-card').forEach(card => {
        card.classList.remove('selected');
        if (card.querySelector('input').value === cropKey) {
            card.classList.add('selected');
        }
    });
    
    // Show crop preview
    showCropPreview(cropKey);
}

function selectCropFromSelect(value) {
    if (value) {
        selectCrop(value);
    }
}

function setExample(crop, size) {
    selectCrop(crop);
    setFieldSize(size);
    document.getElementById('field_size_slider').value = size;
    document.getElementById('sizeValue').textContent = size;
    updateSizeEquivalent(size);
    showCropPreview(crop);
    
    // Show confirmation
    showNotification(`Example loaded: ${cropData[crop].name} - ${size} hectares`, 'success');
}

function setFieldSize(size) {
    document.getElementById('field_size').value = size;
    updateSizeEquivalent(size);
}

function updateSliderValue(value) {
    document.getElementById('field_size').value = value;
    document.getElementById('sizeValue').textContent = value;
    updateSizeEquivalent(value);
}

function updateSizeEquivalent(hectares) {
    const squareMeters = hectares * 10000;
    document.getElementById('sizeEquivalent').textContent = squareMeters.toLocaleString() + ' m²';
}

function showCropPreview(cropKey) {
    const preview = document.getElementById('cropPreview');
    const content = document.getElementById('previewContent');
    
    const crop = cropData[cropKey];
    
    if (!crop) {
        preview.style.display = 'none';
        return;
    }
    
    content.innerHTML = `
        <div class="crop-details-preview">
            <div class="crop-preview-icon" style="background: ${crop.color}">
                <i class="fas ${crop.icon}"></i>
            </div>
            <div class="crop-preview-info">
                <h3>${crop.name}</h3>
                <p class="scientific-name">${crop.scientific_name}</p>
                <p class="crop-description">${crop.description}</p>
                <div class="crop-details-grid">
                    <div class="detail-item">
                        <i class="fas fa-calendar-alt"></i>
                        <div>
                            <span class="detail-label">Season</span>
                            <span class="detail-value">${crop.season}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-box"></i>
                        <div>
                            <span class="detail-label">Inputs Required</span>
                            <span class="detail-value">${Object.keys(crop.recommendations).length} types</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-leaf"></i>
                        <div>
                            <span class="detail-label">Crop Type</span>
                            <span class="detail-value">${['Teff', 'Maize', 'Wheat', 'Barley', 'Sorghum'].includes(crop.name) ? 'Cereal' : 'Vegetable'}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    preview.style.display = 'block';
}

function resetForm() {
    if (confirm('Reset all inputs? This will clear your current calculation.')) {
        document.getElementById('dosageCalculatorForm').reset();
        document.querySelectorAll('.crop-selection-card').forEach(card => {
            card.classList.remove('selected');
        });
        document.getElementById('cropPreview').style.display = 'none';
        document.getElementById('field_size_slider').value = 1;
        document.getElementById('sizeValue').textContent = '1';
        updateSizeEquivalent(1);
        
        // Clear results if visible
        const results = document.getElementById('resultsSection');
        if (results) {
            results.style.display = 'none';
        }
        
        showNotification('Form reset successfully', 'info');
    }
}

function addToCart(productName, quantity) {
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
        return;
    <?php endif; ?>
    
    // Show loading
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
        showNotification(`${productName} (${quantity} bags) added to cart`, 'success');
        button.innerHTML = originalText;
        button.disabled = false;
    }, 1000);
}

function showInputDetails(productName) {
    const details = inputDetails[productName] || {
        description: 'Detailed information for this product will be available soon.',
        benefits: ['Benefit information coming soon'],
        application: 'Apply as recommended on the product label',
        precautions: 'Follow safety guidelines and manufacturer instructions'
    };
    
    const modalContent = document.getElementById('inputDetailsContent');
    modalContent.innerHTML = `
        <h5>${productName}</h5>
        <p class="text-muted mb-4">${details.description}</p>
        
        <h6><i class="fas fa-check-circle text-success me-2"></i>Benefits</h6>
        <ul class="mb-4">
            ${details.benefits.map(benefit => `<li>${benefit}</li>`).join('')}
        </ul>
        
        <h6><i class="fas fa-tasks text-primary me-2"></i>Application Method</h6>
        <p class="mb-4">${details.application}</p>
        
        <h6><i class="fas fa-exclamation-triangle text-warning me-2"></i>Precautions</h6>
        <p class="mb-0">${details.precautions}</p>
    `;
    
    // Update add to cart button
    const addToCartBtn = document.getElementById('modalAddToCartBtn');
    addToCartBtn.onclick = () => {
        // Find the quantity from the results table
        const tableRows = document.querySelectorAll('.requirements-table tbody tr');
        let quantity = 1;
        tableRows.forEach(row => {
            const nameCell = row.cells[1].querySelector('strong');
            if (nameCell && nameCell.textContent === productName) {
                const bagsCell = row.cells[4].querySelector('.bags-value');
                if (bagsCell) {
                    quantity = parseInt(bagsCell.textContent);
                }
            }
        });
        
        addToCart(productName, quantity);
        const modal = bootstrap.Modal.getInstance(document.getElementById('inputDetailsModal'));
        modal.hide();
    };
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('inputDetailsModal'));
    modal.show();
}

function printResults() {
    window.print();
}

function shareResults() {
    <?php if ($results): ?>
        const text = `🌱 Agri Smart Hub - Dosage Calculation Results
=======================
Crop: <?php echo $results['crop_name']; ?> (<?php echo $results['scientific_name']; ?>)
Field Size: <?php echo number_format($results['field_size'], 2); ?> hectares
Season: <?php echo $results['season']; ?>

📊 Requirements:
<?php foreach ($results['input_calculations'] as $input): ?>
• ${<?php echo json_encode($input['name']); ?>}: ${<?php echo $input['bags_needed']; ?>} bags (${<?php echo number_format($input['total_needed'], 2); ?>} ${<?php echo $input['unit']; ?>})
<?php endforeach; ?>

💰 Total Estimated Cost: ETB ${<?php echo number_format($results['total_cost']); ?>}
📅 Calculated on: ${<?php echo date('F j, Y'); ?>}

Generated by Agri Smart Hub Precision Calculator`;

        if (navigator.share) {
            navigator.share({
                title: 'Dosage Calculation Results',
                text: text,
                url: window.location.href
            });
        } else {
            navigator.clipboard.writeText(text)
                .then(() => showNotification('Results copied to clipboard!', 'success'))
                .catch(() => {
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    showNotification('Results copied to clipboard!', 'success');
                });
        }
    <?php endif; ?>
}

function saveCalculation() {
    const modal = new bootstrap.Modal(document.getElementById('saveCalculationModal'));
    modal.show();
}

function saveCalculationToAccount() {
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
        return;
    <?php endif; ?>
    
    const calculationName = document.getElementById('calculationName').value;
    const notes = document.getElementById('calculationNotes').value;
    
    if (!calculationName.trim()) {
        showNotification('Please enter a calculation name', 'error');
        return;
    }
    
    // Show loading
    const button = document.querySelector('#saveCalculationModal .btn-success');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    button.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
        showNotification(`Calculation "${calculationName}" saved to your account`, 'success');
        button.innerHTML = originalText;
        button.disabled = false;
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('saveCalculationModal'));
        modal.hide();
    }, 1500);
}

function loadCalculation(index) {
    <?php if (!empty($recent_calculations)): ?>
        const calc = <?php echo json_encode($recent_calculations); ?>[index];
        if (calc) {
            selectCrop(calc.crop_type);
            setFieldSize(calc.field_size);
            document.getElementById('field_size_slider').value = calc.field_size;
            document.getElementById('sizeValue').textContent = calc.field_size;
            updateSizeEquivalent(calc.field_size);
            showCropPreview(calc.crop_type);
            
            // Submit the form to recalculate
            setTimeout(() => {
                document.getElementById('dosageCalculatorForm').submit();
            }, 300);
        }
    <?php endif; ?>
}

function clearHistory() {
    if (confirm('Clear all recent calculations?')) {
        // This would typically be an AJAX call to clear server-side session
        window.location.href = '?clear_history=true';
    }
}

function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existing = document.querySelectorAll('.notification-toast');
    existing.forEach(n => n.remove());
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `notification-toast notification-${type}`;
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    
    notification.innerHTML = `
        <div class="toast-content">
            <i class="fas ${icons[type] || 'fa-info-circle'}" style="color: ${colors[type] || '#17a2b8'}"></i>
            <span>${message}</span>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-radius: 8px;
        padding: 15px 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 15px;
        z-index: 9999;
        animation: slideInRight 0.3s ease;
        max-width: 350px;
        border-left: 4px solid ${colors[type] || '#17a2b8'};
    `;
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .toast-content {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
    }
    
    .toast-close {
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        font-size: 1.2rem;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.3s;
    }
    
    .toast-close:hover {
        background: #f8f9fa;
    }
`;
document.head.appendChild(style);

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Update size equivalent on load
    const fieldSize = document.getElementById('field_size').value;
    updateSizeEquivalent(fieldSize);
    
    // Set initial crop selection if form was submitted
    <?php if (isset($_POST['crop_type']) && $_POST['crop_type']): ?>
        setTimeout(() => {
            selectCrop('<?php echo $_POST['crop_type']; ?>');
        }, 100);
    <?php endif; ?>
    
    // Handle clear history parameter
    <?php if (isset($_GET['clear_history']) && $_GET['clear_history'] === 'true'): ?>
        <?php unset($_SESSION['recent_calculations']); ?>
        showNotification('Recent calculations cleared', 'success');
        // Remove the parameter from URL without reloading
        const url = new URL(window.location);
        url.searchParams.delete('clear_history');
        window.history.replaceState({}, '', url);
    <?php endif; ?>
});
</script>

<?php 
require_once '../includes/footer.php'; 
?>