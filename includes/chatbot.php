<?php
// Chatbot functionality
class AgriChatbot {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function processQuestion($question, $language = 'en') {
        $question = strtolower(trim($question));
        
        // Check for Amharic or English keywords
        $responses = $this->getPredefinedResponses();
        
        foreach ($responses as $keywords => $response) {
            $keywordArray = explode('|', $keywords);
            foreach ($keywordArray as $keyword) {
                if (strpos($question, $keyword) !== false) {
                    return $this->formatResponse($response, $language);
                }
            }
        }
        
        // If no match found, try to find similar articles
        return $this->searchArticles($question);
    }
    
    private function getPredefinedResponses() {
        return [
            // English keywords
            'maize|corn|zea' => [
                'en' => '🌽 **Maize Farming Advice:**\n\n**Best Varieties:** BH-540 (120-130 days), BH-546 (140-150 days)\n**Planting:** 75cm x 25cm spacing during rainy season\n**Fertilizer:** 100kg DAP at planting, 100kg Urea at 30 days\n**Yield:** 6-10 tons/ha with good management',
                'am' => '🌽 **የአበባ ማሳደግ ምክር:**\n\n**ጥሩ ዘሮች:** BH-540 (120-130 ቀናት), BH-546 (140-150 ቀናት)\n**መትከል:** 75ሴሜ x 25ሴሜ በዝናብ ወቅት\n**ማዳበሪያ:** 100ኪግ DAP በመትከል, 100ኪግ ዩሪያ ከ30 ቀን\n**ምርት:** 6-10 ቶን/ሄክታር በጥሩ አስተዳደር'
            ],
            
            'teff|ጤፍ' => [
                'en' => '🌾 **Teff Farming Guide:**\n\n**Varieties:** DZ-Cr-387 (White), DZ-Cr-37 (Brown)\n**Seed Rate:** 25-30 kg/ha\n**Fertilizer:** 100kg NPS + 50kg Urea\n**Harvest:** When stems turn yellow',
                'am' => '🌾 **የጤፍ ማሳደግ መመሪያ:**\n\n**ዘሮች:** DZ-Cr-387 (ነጭ), DZ-Cr-37 (ቡናማ)\n**የዘር መጠን:** 25-30 ኪግ/ሄክታር\n**ማዳበሪያ:** 100ኪግ NPS + 50ኪግ ዩሪያ\n**ማጨድ:** ግንዶች ቢጫ ሲሆኑ'
            ],
            
            // Add more responses...
        ];
    }
    
    private function searchArticles($question) {
        $stmt = $this->pdo->prepare("
            SELECT title, excerpt 
            FROM advisory 
            WHERE MATCH(title, content, tags) AGAINST(? IN NATURAL LANGUAGE MODE)
            LIMIT 3
        ");
        $stmt->execute([$question]);
        $results = $stmt->fetchAll();
        
        if (count($results) > 0) {
            $response = "I found these articles that might help:\n\n";
            foreach ($results as $result) {
                $response .= "📚 **" . $result['title'] . "**\n";
                $response .= substr($result['excerpt'], 0, 100) . "...\n\n";
            }
            $response .= "Type 'read [article name]' for more details.";
            return $response;
        }
        
        return "I'm still learning about Ethiopian agriculture. For detailed advice, please:\n\n1. Call our hotline: 011-123-4567\n2. Visit nearest agricultural office\n3. Check our detailed guides above\n\nWhat specific crop are you interested in?";
    }
    
    private function formatResponse($response, $language) {
        return $response[$language] ?? $response['en'];
    }
}
?>