<?php
/**
 * Google Translate Integration
 * Supports: Amharic, English, Tigrinya, Oromo
 */

function get_translate_widget() {
    return <<<HTML
    <!-- Google Translate Widget -->
    <div id="google_translate_element" style="margin: 10px 0;"></div>
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: 'am,en,ti,om',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    
    <style>
        #google_translate_element {
            display: inline-block;
        }
        
        .goog-te-gadget {
            font-family: Arial, sans-serif;
        }
        
        .goog-te-gadget-simple {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px 12px;
        }
        
        .goog-te-gadget-simple .goog-te-gadget-simple {
            background-color: transparent;
            border: none;
            padding: 0;
        }
        
        .goog-te-combo {
            padding: 6px 8px;
            border-radius: 3px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        
        /* Hide Google Translate branding */
        .goog-te-gadget-simple .goog-te-gadget-simple {
            display: none;
        }
    </style>
    HTML;
}

/**
 * Get language name in native language
 */
function get_language_name($code) {
    $languages = [
        'am' => 'አማርኛ',      // Amharic
        'en' => 'English',      // English
        'ti' => 'ትግርኛ',       // Tigrinya
        'om' => 'Afaan Oromo'   // Oromo
    ];
    
    return $languages[$code] ?? $code;
}

/**
 * Get all supported languages
 */
function get_supported_languages() {
    return [
        'am' => ['name' => 'አማርኛ', 'native' => 'Amharic'],
        'en' => ['name' => 'English', 'native' => 'English'],
        'ti' => ['name' => 'ትግርኛ', 'native' => 'Tigrinya'],
        'om' => ['name' => 'Afaan Oromo', 'native' => 'Oromo']
    ];
}
?>
