<?php
class KolosalAIAssistant {
    private $api_key;
    private $api_url;
    private $model;
    
    public function __construct() {
        $this->api_key = KOLOSAL_API_KEY;
        $this->api_url = KOLOSAL_API_URL;
        $this->model = KOLOSAL_MODEL;
    }
    
    public function generateProductDetails($image_description) {
        $prompt = "Anda adalah asisten e-commerce yang ahli. Berdasarkan deskripsi produk: \"$image_description\". 
        
        Buatlah detail produk yang MENARIK dan PERSUASIF dengan format JSON berikut:
        
        {
            \"product_name\": \"Nama produk maksimal 5 kata yang menarik\",
            \"description\": \"Deskripsi produk yang persuasif dalam 150-200 karakter\",
            \"tags\": \"5-7 tag keyword relevan dipisah koma\"
        }
        
        Pastikan:
        - Nama produk menarik dan mudah diingat
        - Deskripsi persuasif dan menjelaskan manfaat produk
        - Tag relevan dan populer di e-commerce
        - Gunakan bahasa Indonesia yang baik dan benar";
        
        $data = [
            "model" => $this->model,
            "messages" => [
                [
                    "role" => "user",
                    "content" => $prompt
                ]
            ],
            "max_tokens" => 500,
            "temperature" => 0.7
        ];
        
        $response = $this->makeApiCall($data);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
            
            // Extract JSON from response
            preg_match('/\{[^}]+\}/', $content, $matches);
            if (!empty($matches)) {
                $json_data = json_decode($matches[0], true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $json_data;
                }
            }
            
            // Fallback jika JSON tidak valid
            return $this->getFallbackResponse($image_description);
        }
        
        return $this->getFallbackResponse($image_description);
    }
    
    private function makeApiCall($data) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($http_code === 200) {
            return json_decode($response, true);
        } else {
            error_log("Kolosal.ai API Error: HTTP $http_code - $error");
            return null;
        }
    }
    
    private function getFallbackResponse($image_description) {
        // Fallback response jika API tidak bekerja
        $fallbacks = [
            [
                "product_name" => "Produk Premium Berkualitas Tinggi",
                "description" => "Produk unggulan dengan kualitas terbaik yang dirancang untuk memenuhi kebutuhan harian Anda. Nyaman digunakan dan tahan lama.",
                "tags" => "premium, berkualitas, original, terbaik, recommended"
            ],
            [
                "product_name" => "Item Fashion Trendi Modern",
                "description" => "Desain terkini yang mengikuti tren fashion terbaru. Cocok untuk berbagai kesempatan dengan gaya yang elegan dan stylish.",
                "tags" => "fashion, trendi, modern, stylish, elegan, terkini"
            ]
        ];
        
        return $fallbacks[array_rand($fallbacks)];
    }
    
    public function generateSeoData($product_name, $description) {
        $prompt = "Buatkan data SEO untuk produk e-commerce dengan detail:
        Nama Produk: $product_name
        Deskripsi: $description
        
        Hasilkan dalam format JSON:
        {
            \"seo_title\": \"Judul SEO maksimal 60 karakter\",
            \"seo_description\": \"Meta description maksimal 160 karakter\", 
            \"seo_keywords\": \"5-7 keyword SEO dipisah koma\"
        }";
        
        $data = [
            "model" => $this->model,
            "messages" => [
                [
                    "role" => "user",
                    "content" => $prompt
                ]
            ],
            "max_tokens" => 300,
            "temperature" => 0.5
        ];
        
        $response = $this->makeApiCall($data);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
            preg_match('/\{[^}]+\}/', $content, $matches);
            if (!empty($matches)) {
                $json_data = json_decode($matches[0], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $json_data;
                }
            }
        }
        
        // Fallback SEO data
        return [
            "seo_title" => substr($product_name . " - Beli Sekula di MyShopee", 0, 60),
            "seo_description" => substr($description, 0, 160),
            "seo_keywords" => "beli, online, shop, ecommerce, " . str_replace(' ', ', ', $product_name)
        ];
    }
}
?>