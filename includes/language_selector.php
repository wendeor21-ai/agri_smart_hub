<?php
/**
 * Language Selector Component
 * Provides a dropdown for language selection
 */

function get_language_selector() {
    $languages = [
        'am' => ['name' => 'አማርኛ', 'flag' => '🇪🇹'],
        'en' => ['name' => 'English', 'flag' => '🇬🇧'],
        'ti' => ['name' => 'ትግርኛ', 'flag' => '🇪🇷'],
        'om' => ['name' => 'Afaan Oromo', 'flag' => '🇪🇹']
    ];
    
    return <<<HTML
    <div class="language-selector dropdown">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-globe me-2"></i>
            <span id="current-lang">English</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
            <li><a class="dropdown-item" href="#" onclick="changeLanguage('en'); return false;">🇬🇧 English</a></li>
            <li><a class="dropdown-item" href="#" onclick="changeLanguage('am'); return false;">🇪🇹 አማርኛ (Amharic)</a></li>
            <li><a class="dropdown-item" href="#" onclick="changeLanguage('ti'); return false;">🇪🇷 ትግርኛ (Tigrinya)</a></li>
            <li><a class="dropdown-item" href="#" onclick="changeLanguage('om'); return false;">🇪🇹 Afaan Oromo</a></li>
        </ul>
    </div>
    
    <script>
        function changeLanguage(lang) {
            // Store language preference
            localStorage.setItem('preferredLanguage', lang);
            
            // Trigger Google Translate
            if (window.google && window.google.translate) {
                var element = document.querySelector('.goog-te-combo');
                if (element) {
                    element.value = lang;
                    element.dispatchEvent(new Event('change'));
                }
            }
            
            // Update button text
            var langNames = {
                'en': 'English',
                'am': 'አማርኛ',
                'ti': 'ትግርኛ',
                'om': 'Afaan Oromo'
            };
            document.getElementById('current-lang').textContent = langNames[lang] || 'English';
        }
        
        // Load saved language preference on page load
        document.addEventListener('DOMContentLoaded', function() {
            var savedLang = localStorage.getItem('preferredLanguage') || 'en';
            if (savedLang !== 'en') {
                changeLanguage(savedLang);
            }
        });
    </script>
    
    <style>
        .language-selector {
            margin-left: 10px;
        }
        
        .language-selector .btn {
            font-size: 0.9rem;
            padding: 6px 12px;
        }
        
        .language-selector .dropdown-menu {
            min-width: 200px;
        }
        
        .language-selector .dropdown-item {
            padding: 10px 15px;
            font-size: 0.95rem;
        }
        
        .language-selector .dropdown-item:hover {
            background-color: #f0f0f0;
        }
    </style>
    HTML;
}
?>
