<?php
// Language switcher component for supplier pages
?>
<div class="language-switcher">
    <div class="dropdown">
        <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-globe me-2"></i><span id="currentLanguage">English</span>
        </button>
        <ul class="dropdown-menu" aria-labelledby="languageDropdown">
            <li><a class="dropdown-item" href="#" onclick="changeLanguage('en', 'English')">
                <img src="https://flagcdn.com/16x12/us.png" alt="English" class="me-2">English
            </a></li>
            <li><a class="dropdown-item" href="#" onclick="changeLanguage('am', 'አማርኛ')">
                <img src="https://flagcdn.com/16x12/et.png" alt="Amharic" class="me-2">አማርኛ (Amharic)
            </a></li>
            <li><a class="dropdown-item" href="#" onclick="changeLanguage('ti', 'ትግርኛ')">
                <img src="https://flagcdn.com/16x12/et.png" alt="Tigrinya" class="me-2">ትግርኛ (Tigrinya)
            </a></li>
            <li><a class="dropdown-item" href="#" onclick="changeLanguage('om', 'Oromoo')">
                <img src="https://flagcdn.com/16x12/et.png" alt="Oromo" class="me-2">Oromoo (Oromo)
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-muted" href="#" onclick="debugGoogleTranslate()">
                <i class="fas fa-bug me-2"></i>Debug Translation
            </a></li>
        </ul>
    </div>
</div>

<style>
.language-switcher {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1050;
}

.language-switcher .dropdown-menu {
    min-width: 200px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    border: none;
    border-radius: 10px;
}

.language-switcher .dropdown-item {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.language-switcher .dropdown-item:hover {
    background-color: var(--primary-green);
    color: white;
    transform: translateX(5px);
}

.language-switcher img {
    width: 20px;
    height: 15px;
    border-radius: 2px;
}

.language-switcher .btn {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    font-weight: 500;
    padding: 8px 15px;
    border-radius: 25px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.language-switcher .btn:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.5);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

/* Google Translate Widget Styling */
.goog-te-banner-frame {
    display: none !important;
}

.goog-te-menu-value {
    padding: 3px !important;
}

.goog-te-gadget-simple {
    background-color: white;
    border-left: 1px solid #d5d5d5;
    border-top: 1px solid #9b9b9b;
    border-bottom: 1px solid #e8e8e8;
    border-right: 1px solid #d5d5d5;
    font-size: 10pt;
    display: inline-block;
    padding-top: 1px;
    padding-bottom: 2px;
    border-radius: 10px;
    cursor: pointer;
    zoom: 1;
}

body {
    top: 0px !important;
}

.goog-te-gadget-icon {
    display: none;
}

.goog-te-menu-value span {
    color: blue;
}

.goog-te-menu-value {
    color: #000;
}

.goog-te-combo {
    margin: 0px 0;
}

#google_translate_element {
    display: none;
}

/* Hide Google Translate top bar */
.goog-te-banner-frame.skiptranslate {
    display: none !important;
}

body {
    top: 0px !important;
}

/* Style for translated content */
.translatable {
    transition: all 0.3s ease;
}

/* Loading indicator for translations */
.translation-loading {
    opacity: 0.7;
    position: relative;
}

.translation-loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Responsive design for mobile */
@media (max-width: 768px) {
    .language-switcher {
        top: 10px;
        right: 10px;
    }
    
    .language-switcher .btn {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    .language-switcher .dropdown-menu {
        min-width: 160px;
    }
}
</style>

<script type="text/javascript">
// Global variable to track Google Translate status
var googleTranslateReady = false;

function googleTranslateElementInit() {
    try {
        console.log('Initializing Google Translate...');
        
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            includedLanguages: 'en,am,ti,om',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false,
            multilanguagePage: true
        }, 'google_translate_element');
        
        googleTranslateReady = true;
        console.log('Google Translate initialized successfully');
        
        // Show success indicator
        setTimeout(function() {
            showTranslateStatus('Google Translate Ready!');
            setTimeout(hideTranslateStatus, 2000);
            
            // Apply saved language if exists
            var savedLang = localStorage.getItem('preferredLanguage');
            var savedLangName = localStorage.getItem('preferredLanguageName');
            
            if (savedLang && savedLangName && savedLang !== 'en') {
                console.log('Applying saved language:', savedLang, savedLangName);
                document.getElementById('currentLanguage').textContent = savedLangName;
                
                // Wait a bit more for Google Translate to be fully ready
                setTimeout(function() {
                    var selectField = document.querySelector("select.goog-te-combo");
                    if (selectField) {
                        selectField.value = savedLang;
                        selectField.dispatchEvent(new Event('change'));
                        console.log('Applied saved language:', savedLang);
                    }
                }, 1500);
            }
        }, 1000);
        
    } catch (error) {
        console.error('Google Translate initialization failed:', error);
        showTranslateError('Failed to initialize Google Translate: ' + error.message);
        googleTranslateReady = false;
    }
}

function changeLanguage(langCode, langName) {
    console.log('Changing language to:', langCode, langName);
    
    // Update the current language display immediately
    document.getElementById('currentLanguage').textContent = langName;
    
    // Store language preference
    localStorage.setItem('preferredLanguage', langCode);
    localStorage.setItem('preferredLanguageName', langName);
    
    if (!googleTranslateReady) {
        showTranslateError('Google Translate is not ready yet. Please wait a moment and try again.');
        
        // Try to initialize if not ready
        setTimeout(function() {
            if (window.google && window.google.translate) {
                googleTranslateElementInit();
            }
        }, 1000);
        return;
    }
    
    // Show loading state
    document.body.classList.add('translation-loading');
    showTranslateStatus('Translating to ' + langName + '...');
    
    // Multiple attempts to find and trigger Google Translate
    let attempts = 0;
    const maxAttempts = 5;
    
    function attemptTranslation() {
        attempts++;
        var selectField = document.querySelector("select.goog-te-combo");
        console.log('Translation attempt', attempts, 'Select field:', selectField);
        
        if (selectField) {
            try {
                selectField.value = langCode;
                selectField.dispatchEvent(new Event('change'));
                
                // Success
                console.log('Translation triggered successfully for:', langName);
                
                setTimeout(function() {
                    document.body.classList.remove('translation-loading');
                    hideTranslateStatus();
                    showTranslateStatus('Translated to ' + langName + '!');
                    setTimeout(hideTranslateStatus, 2000);
                }, 2000);
                
            } catch (error) {
                console.error('Error triggering translation:', error);
                showTranslateError('Translation failed: ' + error.message);
                document.body.classList.remove('translation-loading');
            }
        } else if (attempts < maxAttempts) {
            console.log('Google Translate not ready, retrying in 1 second...');
            setTimeout(attemptTranslation, 1000);
        } else {
            console.error('Failed to find Google Translate after', maxAttempts, 'attempts');
            showTranslateError('Translation widget not found. Please refresh the page and try again.');
            document.body.classList.remove('translation-loading');
        }
    }
    
    // Start translation attempts
    setTimeout(attemptTranslation, 300);
    
    // Update page direction for RTL languages if needed
    if (langCode === 'am' || langCode === 'ti') {
        document.documentElement.setAttribute('dir', 'ltr'); // These languages use LTR
    } else {
        document.documentElement.setAttribute('dir', 'ltr');
    }
}

function showTranslateStatus(message) {
    var statusDiv = document.getElementById('translateStatus');
    if (!statusDiv) {
        statusDiv = document.createElement('div');
        statusDiv.id = 'translateStatus';
        statusDiv.style.cssText = `
            position: fixed;
            top: 70px;
            right: 20px;
            background: rgba(39, 174, 96, 0.9);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            z-index: 1060;
            font-size: 14px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        `;
        document.body.appendChild(statusDiv);
    }
    statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>' + message;
    statusDiv.style.display = 'block';
}

function hideTranslateStatus() {
    var statusDiv = document.getElementById('translateStatus');
    if (statusDiv) {
        statusDiv.style.display = 'none';
    }
}

function showTranslateError(message) {
    var statusDiv = document.getElementById('translateStatus');
    if (!statusDiv) {
        statusDiv = document.createElement('div');
        statusDiv.id = 'translateStatus';
        statusDiv.style.cssText = `
            position: fixed;
            top: 70px;
            right: 20px;
            background: rgba(231, 76, 60, 0.9);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            z-index: 1060;
            font-size: 14px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        `;
        document.body.appendChild(statusDiv);
    }
    statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' + message;
    statusDiv.style.display = 'block';
    
    setTimeout(function() {
        statusDiv.style.display = 'none';
    }, 5000);
}

// Load saved language preference
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking for saved language preference');
    
    var savedLang = localStorage.getItem('preferredLanguage');
    var savedLangName = localStorage.getItem('preferredLanguageName');
    
    if (savedLang && savedLangName && savedLang !== 'en') {
        console.log('Found saved language:', savedLang, savedLangName);
        document.getElementById('currentLanguage').textContent = savedLangName;
        
        // Wait for Google Translate to load, then apply saved language
        var attempts = 0;
        var maxAttempts = 10;
        
        function tryApplySavedLanguage() {
            attempts++;
            var selectField = document.querySelector("select.goog-te-combo");
            
            if (selectField && googleTranslateReady) {
                console.log('Applying saved language:', savedLang);
                selectField.value = savedLang;
                selectField.dispatchEvent(new Event('change'));
            } else if (attempts < maxAttempts) {
                console.log('Waiting for Google Translate... Attempt:', attempts);
                setTimeout(tryApplySavedLanguage, 1000);
            } else {
                console.log('Failed to apply saved language after', maxAttempts, 'attempts');
            }
        }
        
        setTimeout(tryApplySavedLanguage, 2000);
    }
});

// Add smooth transitions for translated elements
document.addEventListener('DOMContentLoaded', function() {
    // Add translatable class to common elements
    var elementsToTranslate = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'p', 'span', 'button', 'label',
        '.stat-label', '.card-title', '.nav-link span',
        '.btn', '.alert', '.form-label'
    ];
    
    elementsToTranslate.forEach(function(selector) {
        var elements = document.querySelectorAll(selector);
        elements.forEach(function(element) {
            if (!element.classList.contains('translatable') && 
                !element.querySelector('i') && // Skip elements with icons
                element.textContent.trim().length > 0) {
                element.classList.add('translatable');
            }
        });
    });
});

// Handle translation completion
window.addEventListener('load', function() {
    console.log('Window loaded, setting up translation monitoring');
    
    // Monitor for Google Translate changes
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                // Translation completed, remove loading state
                document.body.classList.remove('translation-loading');
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Check if Google Translate loaded properly
    setTimeout(function() {
        if (!googleTranslateReady) {
            console.warn('Google Translate may not have loaded properly');
            showTranslateError('Translation service is loading. Please wait a moment.');
        }
    }, 5000);
});

// Debug function to test Google Translate
function debugGoogleTranslate() {
    console.log('=== Google Translate Debug Info ===');
    console.log('googleTranslateReady:', googleTranslateReady);
    console.log('Google Translate Element:', document.getElementById('google_translate_element'));
    console.log('Google Translate Select:', document.querySelector("select.goog-te-combo"));
    console.log('Saved Language:', localStorage.getItem('preferredLanguage'));
    console.log('Current Language Display:', document.getElementById('currentLanguage').textContent);
    
    // Try to find all Google Translate related elements
    var gtElements = document.querySelectorAll('[class*="goog-te"]');
    console.log('Google Translate Elements Found:', gtElements.length);
    gtElements.forEach(function(el, index) {
        console.log('GT Element', index, ':', el.className, el);
    });
}

// Make debug function available globally
window.debugGoogleTranslate = debugGoogleTranslate;
</script>

<!-- Google Translate Element (Hidden) -->
<div id="google_translate_element"></div>

<!-- Google Translate Script -->
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>